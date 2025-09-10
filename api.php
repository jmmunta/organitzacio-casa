<?php
// api.php — API JSON per a Organització Casa (PHP + PDO + MySQL)
require_once __DIR__ . '/db.php';

// Errors: log al servidor, JSON net a client
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

try {
  $action = $_GET['action'] ?? $_POST['action'] ?? '';

  switch ($action) {
    case 'bootstrap':
      bootstrap();
      break;

    case 'list_all':
      list_all();
      break;

    case 'add_member':
      require_method('POST');
      add_member();
      break;

    case 'delete_member':
      require_method('POST');
      delete_member();
      break;

    case 'add_task':
      require_method('POST');
      add_task();
      break;

    case 'delete_task':
      require_method('POST');
      delete_task();
      break;

    case 'add_entry':
      require_method('POST');
      add_entry();
      break;

    case 'delete_entry':
      require_method('POST');
      delete_entry();
      break;

    default:
      json_error('Unknown action', 400);
  }
} catch (Throwable $e) {
  json_error($e->getMessage(), 500);
}

// ------ Helpers ------

function require_method(string $m) {
  if (strtoupper($_SERVER['REQUEST_METHOD']) !== $m) {
    json_error("Method not allowed, use $m", 405);
  }
}
function json_ok($data = []) {
  http_response_code(200);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}
function json_error(string $msg, int $code = 400) {
  http_response_code($code);
  echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

// ------ Accions ------

function bootstrap() {
  $pdo = db();

  // Membres per defecte
  $countM = (int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
  if ($countM === 0) {
    $members = [
      ['Chebe','Pare'],
      ['Lídia','Mare'],
      ['Gael','9 anys'],
      ['Gala','7 anys'],
      ['Aran','4 anys'],
      ['Gat','Supervisor de migdiades'],
    ];
    $stmt = $pdo->prepare("INSERT INTO members(name, role) VALUES (?, ?)");
    foreach ($members as $m) { $stmt->execute($m); }
  }

  // Tasques per defecte (amb icona)
  $countT = (int)$pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
  if ($countT === 0) {
    // Nom, punts base, icona
    $tasks = [
      ['Treure la brossa', 10, '🗑️'],
      ['Parar/Desparar taula', 8, '🍽️'],
      ['Fregar plats', 12, '🧽'],
      ['Endreçar habitació', 9, '🧺'],
      ['Donar menjar al gat', 6, '🐱'],
      ['Fer la llista/compra', 14, '🛒'],
    ];
    // Si la teva taula ja té la columna `icon`:
    $stmt = $pdo->prepare("INSERT INTO tasks(name, base_points, icon) VALUES (?, ?, ?)");
    foreach ($tasks as $t) { $stmt->execute($t); }
  }

  json_ok(['ok' => true]);
}

function list_all() {
  $pdo = db();
  $members = $pdo->query("SELECT id, name, role FROM members ORDER BY id ASC")->fetchAll();
  // Incloem icon
  $tasks   = $pdo->query("SELECT id, name, base_points, icon FROM tasks ORDER BY id ASC")->fetchAll();
  $entries = $pdo->query("SELECT id, member_id, task_id, date_iso, quality, notes FROM entries ORDER BY date_iso DESC")->fetchAll();
  json_ok(['members' => $members, 'tasks' => $tasks, 'entries' => $entries]);
}

function add_member() {
  $name = trim($_POST['name'] ?? '');
  $role = trim($_POST['role'] ?? '');
  if ($name === '') json_error('Name required', 422);

  $pdo = db();
  $stmt = $pdo->prepare("INSERT INTO members(name, role) VALUES (?, ?)");
  $stmt->execute([$name, $role]);
  $id = (int)$pdo->lastInsertId();

  json_ok(['id' => $id, 'name' => $name, 'role' => $role]);
}

function delete_member() {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) json_error('Invalid id', 422);

  $pdo = db();
  $pdo->prepare("DELETE FROM entries WHERE member_id = ?")->execute([$id]); // per si no hi ha CASCADE
  $pdo->prepare("DELETE FROM members WHERE id = ?")->execute([$id]);

  json_ok(['ok' => true]);
}

function add_task() {
  $name = trim($_POST['name'] ?? '');
  $base = (int)($_POST['base_points'] ?? 0);
  $icon = trim($_POST['icon'] ?? ''); // nou
  if ($name === '' || $base <= 0) json_error('Invalid task data', 422);

  $pdo = db();
  // Si no vols icona, passa NULL
  $stmt = $pdo->prepare("INSERT INTO tasks(name, base_points, icon) VALUES (?, ?, ?)");
  $stmt->execute([$name, $base, ($icon !== '' ? $icon : null)]);
  $id = (int)$pdo->lastInsertId();

  json_ok(['id' => $id, 'name' => $name, 'base_points' => $base, 'icon' => $icon]);
}

function delete_task() {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) json_error('Invalid id', 422);

  $pdo = db();
  $pdo->prepare("DELETE FROM entries WHERE task_id = ?")->execute([$id]); // per si no hi ha CASCADE
  $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$id]);

  json_ok(['ok' => true]);
}

function add_entry() {
  $member_id = (int)($_POST['member_id'] ?? 0);
  $task_id   = (int)($_POST['task_id'] ?? 0);
  $date_iso  = trim($_POST['date_iso'] ?? '');
  $quality   = (int)($_POST['quality'] ?? 3);
  $notes     = ($_POST['notes'] ?? null);

  if ($member_id <= 0 || $task_id <= 0) json_error('member_id and task_id are required', 422);
  if ($date_iso === '') json_error('date_iso is required', 422);
  if ($quality < 1 || $quality > 5) $quality = 3;

  if (!preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $date_iso)) {
    json_error('date_iso must be "YYYY-MM-DD HH:MM:SS"', 422);
  }

  $pdo = db();
  if ((int)$pdo->query("SELECT COUNT(*) FROM members WHERE id = ".(int)$member_id)->fetchColumn() === 0)
    json_error('Member not found', 404);
  if ((int)$pdo->query("SELECT COUNT(*) FROM tasks WHERE id = ".intVal($task_id))->fetchColumn() === 0)
    json_error('Task not found', 404);

  $stmt = $pdo->prepare("INSERT INTO entries(member_id, task_id, date_iso, quality, notes) VALUES (?,?,?,?,?)");
  $stmt->execute([$member_id, $task_id, $date_iso, $quality, $notes]);
  $id = (int)$pdo->lastInsertId();

  json_ok([
    'id' => $id,
    'member_id' => $member_id,
    'task_id' => $task_id,
    'date_iso' => $date_iso,
    'quality' => $quality,
    'notes' => $notes
  ]);
}

function delete_entry() {
  $id = (int)($_POST['id'] ?? 0);
  if ($id <= 0) json_error('Invalid id', 422);

  $pdo = db();
  $pdo->prepare("DELETE FROM entries WHERE id = ?")->execute([$id]);

  json_ok(['ok' => true]);
}
