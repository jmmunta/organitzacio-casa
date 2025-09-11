<?php
// api.php — API JSON
require_once __DIR__ . '/db.php';
error_reporting(E_ALL); ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
session_start();
if (!defined('APP_DEBUG')) define('APP_DEBUG', true);

/* ---------- Logger senzill ---------- */
function app_log($msg){
  if (!defined('APP_DEBUG') || !APP_DEBUG) return;
  $logDir = __DIR__ . '/logs';
  if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
  if (!is_writable($logDir)) { error_log('app_log: dir NO escrivible: '.$logDir); return; }
  $file = $logDir.'/app.log';
  @file_put_contents($file, '['.date('Y-m-d H:i:s').'] '.$msg."\n", FILE_APPEND | LOCK_EX);
}

/* Traça de la request (CORREGIT l’accés a $_SESSION) */
app_log(
  'REQ action=' . ($_GET['action'] ?? $_POST['action'] ?? '(null)') .
  ' user=' . (isset($_SESSION['user']) ? ($_SESSION['user']['email'].'/'.$_SESSION['user']['role']) : '(anon)')
);

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';

  switch ($action) {
    /* --- AUTH --- */
    case 'register': register(); break;
    case 'login':    login();    break;
    case 'logout':   logout();   break;
    case 'me':       me();       break;

    case 'ping_log':
      app_log('PING des de ping_log (prova de logger)');
      json_ok(['ok'=>true]);
      break;

    /* --- APP --- */
    case 'bootstrap': require_login(); json_ok(['ok'=>true]); break;
    case 'list_all':  require_login(); list_all(); break;

    case 'add_member':    require_admin_or_memberSelf(); add_member(); break;
    case 'delete_member': require_admin(); delete_member(); break;

    case 'add_task':      require_admin(); add_task(); break;
    case 'delete_task':   require_admin(); delete_task(); break;

    case 'add_entry':     require_login(); add_entry_guarded(); break;
    case 'delete_entry':  require_admin_or_ownerEntry(); delete_entry_guarded(); break;

    case 'upload_member_photo': require_login(); upload_member_photo(); break;

    case 'upload_task_icon': require_admin(); upload_task_icon(); break;

    default: json_error('Unknown action',400);
  }
} catch (Throwable $e) {
  app_log('API ERROR: '.$e->getMessage().' / action='.($action??''));
  json_error($e->getMessage(),500);
}

/* ---------- Helpers ---------- */
function json_ok($data=[]) {
  http_response_code(200);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function json_error($msg,$code=400){
  app_log("json_error [$code]: $msg");
  http_response_code($code);
  echo json_encode(['error'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}
function user(){ return $_SESSION['user'] ?? null; }
function require_login(){ if(!user()) json_error('Not authenticated',401); }
function require_admin(){ if(!user() || user()['role']!=='admin') json_error('Admin only',403); }

/* El membre “self” és el que tingui user_id igual a l’usuari logat (si n’hi ha) */
function require_admin_or_memberSelf(){
  if(user() && user()['role']==='admin') return;
  // validem a cada acció concreta
}

/* Per esborrar entrades: admin o propietari de l’entrada */
function require_admin_or_ownerEntry(){
  if(user() && user()['role']==='admin') return;
  // validació dins delete_entry_guarded()
}

/* ---------- AUTH ---------- */
function register(){
  $pdo = db();
  $family_name = trim($_POST['family_name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if($family_name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<6)
    json_error('Dades de registre invàlides',422);

  $pdo->prepare("INSERT INTO families(name) VALUES (?)")->execute([$family_name]);
  $family_id = (int)$pdo->lastInsertId();

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users(family_id,email,password_hash,role) VALUES (?,?,?,'admin')");
  $stmt->execute([$family_id,$email,$hash]);
  $user_id = (int)$pdo->lastInsertId();

  seed_default_tasks($pdo, $family_id);

  $_SESSION['user'] = ['id'=>$user_id, 'family_id'=>$family_id, 'email'=>$email, 'role'=>'admin'];
  header('Location: index.php'); exit;
}

function login(){
  $pdo = db();
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $stmt = $pdo->prepare("SELECT id,family_id,email,password_hash,role FROM users WHERE email=?");
  $stmt->execute([$email]);
  $u = $stmt->fetch();
  if(!$u || !password_verify($password, $u['password_hash'])) {
    header('Location: login.php?m='.urlencode('Credencials incorrectes')); exit;
  }
  $_SESSION['user'] = ['id'=>(int)$u['id'], 'family_id'=>(int)$u['family_id'], 'email'=>$u['email'], 'role'=>$u['role']];
  header('Location: index.php'); exit;
}

function logout(){ session_destroy(); header('Location: login.php?m='.urlencode('Sessió tancada')); exit; }
function me(){ require_login(); json_ok(['user'=>user()]); }

function seed_default_tasks(PDO $pdo, int $family_id){
  $tasks = [
    ['Parar taula',8,'🍽️'],['Desparar taula',8,'🍽️'],['Fregar plats',12,'🧽'],
    ['Endreçar cuina',10,'🧼'],['Treure la brossa',10,'🗑️'],['Fer la compra',14,'🛒']
  ];
  $stmt = $pdo->prepare("INSERT INTO tasks(name,base_points,icon,family_id) VALUES (?,?,?,?)");
  foreach($tasks as $t){ $stmt->execute([$t[0],$t[1],$t[2],$family_id]); }
}

/* ---------- App: tot filtrat per family_id ---------- */

function list_all(){
  $pdo = db(); $fam = user()['family_id'];
  $members = $pdo->prepare("SELECT id,name,role,photo,user_id FROM members WHERE family_id=? ORDER BY id ASC");
  $members->execute([$fam]); $members=$members->fetchAll();
  $tasks = $pdo->prepare("SELECT id,name,base_points,icon,icon_img,category FROM tasks WHERE family_id=? ORDER BY id ASC");
  $tasks->execute([$fam]); $tasks=$tasks->fetchAll();
  $entries = $pdo->prepare("SELECT id,member_id,task_id,date_iso,quality,notes FROM entries WHERE family_id=? ORDER BY date_iso DESC");
  $entries->execute([$fam]); $entries=$entries->fetchAll();
  json_ok(compact('members','tasks','entries'));
}

function add_member(){
  $pdo = db(); $fam=user()['family_id'];
  $name=trim($_POST['name']??''); $role=trim($_POST['role']??'');
  if($name==='') json_error('Name required',422);
  $stmt=$pdo->prepare("INSERT INTO members(family_id,name,role) VALUES (?,?,?)");
  $stmt->execute([$fam,$name,$role]);
  json_ok(['id'=>(int)$pdo->lastInsertId()]);
}

function delete_member(){
  require_admin();
  $pdo = db(); $fam=user()['family_id']; $id=(int)($_POST['id']??0);
  $own = $pdo->prepare("SELECT id FROM members WHERE id=? AND family_id=?"); $own->execute([$id,$fam]);
  if(!$own->fetch()) json_error('Not found',404);
  $pdo->prepare("DELETE FROM entries WHERE member_id=? AND family_id=?")->execute([$id,$fam]);
  $pdo->prepare("DELETE FROM members WHERE id=? AND family_id=?")->execute([$id,$fam]);
  json_ok(['ok'=>true]);
}

function add_task(){
  require_admin();
  $pdo = db(); $fam=user()['family_id'];

  // accepta tant x-www-form-urlencoded com multipart
  $name = trim($_POST['name'] ?? '');
  $base = (int)($_POST['base_points'] ?? 0);
  $category = trim($_POST['category'] ?? '');
  $emoji = trim($_POST['icon'] ?? ''); // opcional (herència)

  if($name==='' || $base<=0) json_error('Invalid task data', 422);

  // primer inserim sense imatge
  $stmt = $pdo->prepare("INSERT INTO tasks(family_id,name,base_points,icon,category) VALUES (?,?,?,?,?)");
  $stmt->execute([$fam,$name,$base,($emoji!==''?$emoji:null), ($category!==''?$category:null)]);
  $task_id = (int)$pdo->lastInsertId();

  // si ve un fitxer 'icon_img' dins del mateix POST, el processem
  if (!empty($_FILES['icon_img']) && is_uploaded_file($_FILES['icon_img']['tmp_name'])) {
    $path = save_task_icon_file($_FILES['icon_img'], $task_id);
    $pdo->prepare("UPDATE tasks SET icon_img=? WHERE id=? AND family_id=?")->execute([$path, $task_id, $fam]);
  }

  json_ok(['id'=>$task_id]);
}
function upload_task_icon(){
  $pdo = db(); $fam=user()['family_id'];
  $task_id = (int)($_POST['task_id'] ?? 0);
  if($task_id<=0) json_error('task_id requerit', 422);

  $own = $pdo->prepare("SELECT id FROM tasks WHERE id=? AND family_id=?");
  $own->execute([$task_id,$fam]);
  if(!$own->fetch()) json_error('Tasca no trovada', 404);

  if (empty($_FILES['icon_img']) || !is_uploaded_file($_FILES['icon_img']['tmp_name'])) {
    json_error('No s\'ha rebut cap fitxer', 422);
  }
  $path = save_task_icon_file($_FILES['icon_img'], $task_id);
  $pdo->prepare("UPDATE tasks SET icon_img=? WHERE id=? AND family_id=?")->execute([$path, $task_id, $fam]);
  json_ok(['icon_img'=>$path]);
}

function delete_task(){
  require_admin();
  $pdo=db(); $fam=user()['family_id']; $id=(int)($_POST['id']??0);
  $own = $pdo->prepare("SELECT id FROM tasks WHERE id=? AND family_id=?"); $own->execute([$id,$fam]);
  if(!$own->fetch()) json_error('Not found',404);
  $pdo->prepare("DELETE FROM entries WHERE task_id=? AND family_id=?")->execute([$id,$fam]);
  $pdo->prepare("DELETE FROM tasks WHERE id=? AND family_id=?")->execute([$id,$fam]);
  json_ok(['ok'=>true]);
}

function add_entry_guarded(){
  $pdo=db(); $fam=user()['family_id']; $uid=user()['id']; $role=user()['role'];
  $member_id=(int)($_POST['member_id']??0);
  $task_id=(int)($_POST['task_id']??0);
  $date_iso=trim($_POST['date_iso']??'');
  $quality=(int)($_POST['quality']??3);
  $notes=($_POST['notes']??null);
  if($member_id<=0||$task_id<=0||$date_iso==='') json_error('Bad entry',422);

  $okM = $pdo->prepare("SELECT id,user_id FROM members WHERE id=? AND family_id=?"); $okM->execute([$member_id,$fam]); $m=$okM->fetch();
  $okT = $pdo->prepare("SELECT id FROM tasks WHERE id=? AND family_id=?"); $okT->execute([$task_id,$fam]); $t=$okT->fetch();
  if(!$m||!$t) json_error('Member or task not found',404);

  if($role!=='admin'){
    $selfMember = $pdo->prepare("SELECT id FROM members WHERE user_id=? AND family_id=?");
    $selfMember->execute([$uid,$fam]); $sm=$selfMember->fetch();
    if(!$sm || (int)$sm['id'] !== (int)$member_id) json_error('Forbidden',403);
  }

  $stmt=$pdo->prepare("INSERT INTO entries(member_id,task_id,date_iso,quality,notes,family_id) VALUES (?,?,?,?,?,?)");
  $stmt->execute([$member_id,$task_id,$date_iso,$quality,$notes,$fam]);
  json_ok(['id'=>(int)$pdo->lastInsertId()]);
}

function delete_entry_guarded(){
  $pdo=db(); $fam=user()['family_id']; $uid=user()['id']; $role=user()['role'];
  $id=(int)($_POST['id']??0);
  $e = $pdo->prepare("SELECT id,member_id FROM entries WHERE id=? AND family_id=?");
  $e->execute([$id,$fam]); $row=$e->fetch(); if(!$row) json_error('Not found',404);

  if($role!=='admin'){
    $sm = $pdo->prepare("SELECT id FROM members WHERE user_id=? AND family_id=?");
    $sm->execute([$uid,$fam]); $mine=$sm->fetch();
    if(!$mine || (int)$mine['id'] !== (int)$row['member_id']) json_error('Forbidden',403);
  }
  $pdo->prepare("DELETE FROM entries WHERE id=? AND family_id=?")->execute([$id,$fam]);
  json_ok(['ok'=>true]);
}

/* --- Upload foto: admin o el mateix membre --- */
function upload_member_photo(){
  // permisos: admin o el membre vinculat a aquest user
  $u = user(); if(!$u) json_error('Not authenticated',401);
  $pdo = db();

  $member_id = (int)($_POST['member_id'] ?? 0);
  if ($member_id<=0) json_error('member_id invàlid', 422);

  if ($u['role'] !== 'admin') {
    $q = $pdo->prepare("SELECT id FROM members WHERE user_id=? AND family_id=?");
    $q->execute([$u['id'], $u['family_id']]);
    $sm = $q->fetch();
    if (!$sm || (int)$sm['id'] !== $member_id) json_error('No teniu permís per pujar la foto d\'aquest membre', 403);
  }

  if (empty($_FILES['photo']) || !is_uploaded_file($_FILES['photo']['tmp_name'])) {
    json_error('No s\'ha rebut cap fitxer', 422);
  }

  $fam = $u['family_id'];
  $own = $pdo->prepare("SELECT id FROM members WHERE id=? AND family_id=?");
  $own->execute([$member_id,$fam]);
  if(!$own->fetch()) json_error('Membre inexistent o no pertany a la família', 404);

  $uploadDir = __DIR__ . '/uploads/members';
  if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
  if (!is_writable($uploadDir)) json_error('La carpeta d\'uploads no és escrivible', 500);

  $tmp  = $_FILES['photo']['tmp_name'];
  $mime = @mime_content_type($tmp) ?: ($_FILES['photo']['type'] ?? 'application/octet-stream');
  $ext = '.jpg';
  if (stripos($mime,'png')!==false) $ext='.png';
  elseif (stripos($mime,'webp')!==false) $ext='.webp';
  elseif (stripos($mime,'jpeg')!==false) $ext='.jpg';

  $filename   = 'm_' . $member_id . $ext;
  $targetPath = $uploadDir . '/' . $filename;

  $hasGD = function_exists('imagecreatefromstring');
  try {
    if ($hasGD) {
      $raw = @file_get_contents($tmp);
      $src = @imagecreatefromstring($raw);
      if ($src === false) throw new Exception('Fitxer d\'imatge no vàlid');
      $w=imagesx($src); $h=imagesy($src);
      $max=640; $scale = max($w,$h)>$max ? ($max/max($w,$h)) : 1;
      $nw=(int)round($w*$scale); $nh=(int)round($h*$scale);
      $dst=imagecreatetruecolor($nw,$nh);
      imagecopyresampled($dst,$src,0,0,0,0,$nw,$nh,$w,$h);
      if ($ext==='.png' && function_exists('imagepng')) imagepng($dst, $targetPath);
      elseif ($ext==='.webp' && function_exists('imagewebp')) imagewebp($dst, $targetPath, 85);
      else { $targetPath = $uploadDir.'/m_'.$member_id.'.jpg'; imagejpeg($dst, $targetPath, 85); $ext='.jpg'; $filename='m_'.$member_id.$ext; }
      imagedestroy($src); imagedestroy($dst);
    } else {
      if (!move_uploaded_file($tmp, $targetPath)) throw new Exception('No s\'ha pogut desar el fitxer (move_uploaded_file)');
    }
  } catch (Throwable $e) { json_error('Error processant imatge: '.$e->getMessage(), 500); }

  $publicUrl = 'uploads/members/' . $filename;
  $pdo->prepare("UPDATE members SET photo=? WHERE id=? AND family_id=?")->execute([$publicUrl,$member_id,$fam]);
  json_ok(['photo'=>$publicUrl]);
}
function save_task_icon_file(array $file, int $task_id): string {
  $dir = __DIR__ . '/uploads/tasks';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  if (!is_writable($dir)) json_error('Carpeta d\'uploads no escrivible', 500);

  $tmp = $file['tmp_name'];
  $mime = @mime_content_type($tmp) ?: ($file['type'] ?? 'application/octet-stream');
  $ext = '.jpg';
  if (stripos($mime,'png')!==false) $ext='.png';
  elseif (stripos($mime,'webp')!==false) $ext='.webp';
  elseif (stripos($mime,'jpeg')!==false) $ext='.jpg';

  $filename = 't_'.$task_id.$ext;
  $target = $dir.'/'.$filename;

  // Redueix a 256x256 (si hi ha GD). Si no hi ha GD, només mou.
  $hasGD = function_exists('imagecreatefromstring');
  if ($hasGD) {
    $raw = @file_get_contents($tmp);
    $src = @imagecreatefromstring($raw);
    if ($src === false) json_error('Imatge d\'icona no vàlida', 422);
    $w=imagesx($src); $h=imagesy($src);
    $size = 256;
    $scale = min($size/$w, $size/$h, 1);
    $nw = max(1,(int)round($w*$scale));
    $nh = max(1,(int)round($h*$scale));
    $dst = imagecreatetruecolor($size,$size);
    // fons blanc
    $white = imagecolorallocate($dst, 255,255,255);
    imagefilledrectangle($dst, 0,0, $size,$size, $white);
    // centrat
    $ox = (int)(($size-$nw)/2); $oy=(int)(($size-$nh)/2);
    imagecopyresampled($dst,$src,$ox,$oy,0,0,$nw,$nh,$w,$h);

    if ($ext==='.png' && function_exists('imagepng')) imagepng($dst,$target);
    elseif ($ext==='.webp' && function_exists('imagewebp')) imagewebp($dst,$target,90);
    else imagejpeg($dst,$target,90);
    imagedestroy($src); imagedestroy($dst);
  } else {
    if (!move_uploaded_file($tmp, $target)) json_error('No s\'ha pogut desar la imatge', 500);
  }

  return 'uploads/tasks/'.$filename;
}
