<?php
// migrate.php — aplicador d'schema/seed via web
// ⚠️ Després d’usar-lo, ELIMINA aquest fitxer per seguretat.

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/db.php'; // carrega config.php i funció db()

function run_sql_file(PDO $pdo, string $path): array {
  $out = ['file' => $path, 'ok' => true, 'messages' => []];

  if (!file_exists($path)) {
    $out['ok'] = false;
    $out['messages'][] = "No s'ha trobat l'arxiu: $path";
    return $out;
  }

  $sql = file_get_contents($path);
  if ($sql === false) {
    $out['ok'] = false;
    $out['messages'][] = "No s'ha pogut llegir: $path";
    return $out;
  }

  // Divideix per ; a final de línia — prou per a aquests scripts
  $statements = array_filter(array_map('trim', preg_split('/;\s*$/m', $sql)));

  try {
    $pdo->beginTransaction();
    foreach ($statements as $stmt) {
      if ($stmt === '' || strpos($stmt, '--') === 0) continue;
      $pdo->exec($stmt);
    }
    $pdo->commit();
    $out['messages'][] = "Executat correctament.";
  } catch (Throwable $e) {
    $pdo->rollBack();
    $out['ok'] = false;
    $out['messages'][] = "ERROR: " . $e->getMessage();
  }
  return $out;
}

$do = $_POST['do'] ?? ''; // 'schema', 'seed', 'both'
$results = [];
$okPing = true;
$pingMsg = 'Connexió OK';

try {
  $pdo = db();
  // prova simple
  $pdo->query('SELECT 1');
} catch (Throwable $e) {
  $okPing = false;
  $pingMsg = 'ERROR connexió BD: ' . $e->getMessage();
}

$baseDir = dirname(__DIR__); // si estàs a public/, el sql/ és un nivell amunt
// Si els SQL són a ./sql respecte migrate.php, canvia a __DIR__ . '/sql'
$schemaPath = is_dir(__DIR__ . '/../sql') ? (__DIR__ . '/../sql/schema.sql') : (__DIR__ . '/sql/schema.sql');
$seedPath   = is_dir(__DIR__ . '/../sql') ? (__DIR__ . '/../sql/seed_tasks.sql') : (__DIR__ . '/sql/seed_tasks.sql');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $okPing) {
  if ($do === 'schema' || $do === 'both') {
    $results[] = run_sql_file($pdo, $schemaPath);
  }
  if ($do === 'seed' || $do === 'both') {
    $results[] = run_sql_file($pdo, $seedPath);
  }
}
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Instal·lador / Migracions</title>
  <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css" />
  <style>
    .ok { background:#e8fff0; border:1px solid #b3ffd1; color:#1f8a4a; padding:.5rem .75rem; border-radius:8px; }
    .err{ background:#ffe8e8; border:1px solid #ffb3b3; color:#8a1f1f; padding:.5rem .75rem; border-radius:8px; }
    pre{ white-space:pre-wrap; }
  </style>
</head>
<body class="container">
  <h1>Instal·lador / Migracions</h1>

  <article>
    <h3>Connexió a la base de dades</h3>
    <p class="<?= $okPing ? 'ok':'err' ?>">
      <?= htmlspecialchars($pingMsg, ENT_QUOTES, 'UTF-8') ?>
    </p>
    <details>
      <summary>Credencials (de config.php)</summary>
      <pre><?php
        // Mostra només host i nom de BD per no exposar la contrasenya
        require __DIR__ . '/config.php';
        echo "Host: " . htmlspecialchars($servername) . "\n";
        echo "BD:   " . htmlspecialchars($dbname) . "\n";
        echo "Usuari: " . htmlspecialchars($username) . "\n";
        echo "(La contrasenya no es mostra)";
      ?></pre>
    </details>
  </article>

  <article>
    <h3>Executar scripts SQL</h3>
    <form method="post">
      <fieldset>
        <label>
          <input type="radio" name="do" value="schema" required>
          Només <strong>schema.sql</strong> (crea taules)
        </label>
        <label>
          <input type="radio" name="do" value="seed">
          Només <strong>seed_tasks.sql</strong> (omple tasques)
        </label>
        <label>
          <input type="radio" name="do" value="both">
          <strong>Ambdós</strong> (schema + seed)
        </label>
      </fieldset>
      <button type="submit">Executa</button>
    </form>

    <?php if (!empty($results)): ?>
      <h4>Resultat</h4>
      <?php foreach ($results as $r): ?>
        <div class="<?= $r['ok'] ? 'ok':'err' ?>">
          <strong><?= htmlspecialchars($r['file']) ?></strong><br>
          <?php foreach ($r['messages'] as $m): ?>
            <?= htmlspecialchars($m) ?><br>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </article>

  <article>
    <h3>On ha de ser la carpeta <code>sql/</code>?</h3>
    <p>
      Per defecte, aquest instal·lador busca els fitxers en:
      <code><?= htmlspecialchars($schemaPath) ?></code> i
      <code><?= htmlspecialchars($seedPath) ?></code>.
    </p>
    <p>Si tens una altra estructura, mou la carpeta <code>sql/</code> perquè coincideixi, o edita <code>migrate.php</code> i ajusta les rutes.</p>
  </article>

  <article>
    <h3>Seguretat</h3>
    <ul>
      <li><strong>Elimina</strong> aquest fitxer <code>migrate.php</code> després d’usar-lo.</li>
      <li>Assegura’t que <code>config.php</code> no es puja mai a un repo públic amb les teves claus reals.</li>
    </ul>
  </article>

  <footer class="muted">Un cop creat tot, ves a <code>index.php</code> i comprova que l’app funciona.</footer>
</body>
</html>
