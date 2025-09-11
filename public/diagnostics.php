<?php
session_start();
if (empty($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { http_response_code(403); echo "Només admins"; exit; }
?>
<!doctype html>
<html lang="ca"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnòstic</title>
<link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css">
</head><body class="container">
<h1>Diagnòstic</h1>
<article>
  <h3>Entorn</h3>
  <ul>
    <li>Usuari: <code><?=htmlspecialchars($_SESSION['user']['email'])?></code> (<?=htmlspecialchars($_SESSION['user']['role'])?>), família ID: <?= (int)$_SESSION['user']['family_id'] ?></li>
    <li>PHP: <?=phpversion()?></li>
    <li>GD present: <?= function_exists('imagecreatefromstring')?'sí':'no' ?></li>
    <li>upload_max_filesize: <?= ini_get('upload_max_filesize') ?></li>
    <li>post_max_size: <?= ini_get('post_max_size') ?></li>
    <li>Carpeta uploads/members: 
      <?php
      $dir = __DIR__.'/uploads/members';
      echo is_dir($dir)?'existeix':'NO existeix';
      echo ' · '.(is_writable($dir)?'escrivible':'NO escrivible');
      ?>
    </li>
  </ul>
</article>

<article>
  <h3>Contingut de /uploads/members</h3>
  <pre><?php
    $d = @scandir($dir);
    if ($d===false) echo "(no accessible)";
    else foreach ($d as $f) if ($f!=='.' && $f!=='..') echo htmlspecialchars($f)."\n";
  ?></pre>
</article>

<article>
  <h3>Últimes línies del log</h3>
  <pre><?php
    $log = __DIR__.'/logs/app.log';
    if (file_exists($log)) {
      $lines = @file($log);
      if ($lines!==false) {
        $tail = array_slice($lines, -50);
        echo htmlspecialchars(implode('', $tail));
      } else echo "(no es pot llegir)";
    } else echo "(no hi ha log)";
  ?></pre>
</article>
</body></html>
