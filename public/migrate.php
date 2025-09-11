<?php
/* migrate.php – Configuració inicial: esquema i seed */

require_once __DIR__ . '/db.php';
error_reporting(E_ALL); ini_set('display_errors','1');
header('Content-Type: text/html; charset=utf-8');

/* ---------- Helpers bàsics ---------- */
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

/* Polyfills per compatibilitat (per si al host no hi ha aquestes funcions) */
if (!function_exists('str_starts_with')) {
  function str_starts_with($haystack, $needle){
    if ($needle === '') return true;
    return strncmp($haystack, $needle, strlen($needle)) === 0;
  }
}
if (!function_exists('str_contains')) {
  function str_contains($haystack, $needle){
    return $needle === '' || strpos($haystack, $needle) !== false;
  }
}

/** Retalla una sentència per deixar-la curta en els logs */
function snippet(string $sql): string {
  $s = preg_replace('/\s+/', ' ', trim($sql));
  if (function_exists('mb_substr')) {
    return mb_substr($s, 0, 140) . (mb_strlen($s) > 140 ? '…' : '');
  }
  return substr($s, 0, 140) . (strlen($s) > 140 ? '…' : '');
}

/** Detecta versió major de MySQL (8, 5, …) */
function mysql_major_version(PDO $pdo): int {
  $v = $pdo->query("SELECT VERSION() AS v")->fetchColumn();
  if (!$v) return 8;
  if (preg_match('/^(\d+)\./', $v, $m)) return (int)$m[1];
  return 8;
}

/** Executa un fitxer .sql (comentaris suportats, múltiples sentències simples) */
function run_sql_file(PDO $pdo, string $path): array {
  $out = [];
  if (!is_file($path)) {
    return ["[ERROR] No existeix el fitxer: $path"];
  }
  $sql = file_get_contents($path);
  if ($sql === false) {
    return ["[ERROR] No puc llegir: $path"];
  }

  $lines = preg_split("/\r?\n/", $sql);
  $buffer = '';

  foreach ($lines as $rawLine) {
    $line = trim($rawLine);

    // Omet comentaris i línies buides
    if ($line === '' || str_starts_with($line, '--') || str_starts_with($line, '#')) {
      continue;
    }
    // Omet directives DELIMITER (no les fem servir)
    if (preg_match('/^\s*DELIMITER\s/i', $line)) continue;

    $buffer .= $rawLine . "\n";

    // Executa quan la línia acaba amb ;
    if (preg_match('/;\s*$/', $line)) {
      $stmt = trim($buffer);
      $buffer = '';
      if ($stmt !== '') {
        try {
          $pdo->exec($stmt);
          $out[] = "[OK] " . snippet($stmt);
        } catch (Throwable $e) {
          $out[] = "[ERROR] " . $e->getMessage() . " :: " . snippet($stmt);
        }
      }
    }
  }

  // Resta pendent sense ; final
  $stmt = trim($buffer);
  if ($stmt !== '') {
    try {
      $pdo->exec($stmt);
      $out[] = "[OK] " . snippet($stmt);
    } catch (Throwable $e) {
      $out[] = "[ERROR] " . $e->getMessage() . " :: " . snippet($stmt);
    }
  }

  return $out;
}

/* ---------- Inicialització ---------- */
$pdo   = db();
$major = mysql_major_version($pdo);

// Fitxers per defecte
$defaultSchema = ($major >= 8)
  ? __DIR__ . '/../sql/schema.mysql80.sql'
  : __DIR__ . '/../sql/schema.mysql56.sql';
$defaultSeed   = __DIR__ . '/../sql/seed_tasks.sql';

$action   = $_POST['action']   ?? '';
$schema   = $_POST['schema']   ?? $defaultSchema;
$seedfile = $_POST['seedfile'] ?? $defaultSeed;

$logs = [];
$okSummary = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($action === 'schema' || $action === 'both') {
    $logs[] = "==> Executant ESQUEMA: " . h($schema);
    $logs   = array_merge($logs, run_sql_file($pdo, $schema));
  }
  if ($action === 'seed' || $action === 'both') {
    $logs[] = "==> Executant SEED: " . h($seedfile);
    $logs   = array_merge($logs, run_sql_file($pdo, $seedfile));
  }

  // Resum de taules
  try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $okSummary = "Taules actuals: " . h(implode(', ', $tables));
  } catch (Throwable $e) {
    $logs[] = "[WARN] No s'ha pogut llistar taules: " . $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Migrate · Instal·lació inicial</title>
  <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css" />
  <style>
    .muted{color:#666}
    .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;}
    pre{white-space: pre-wrap}
    .ok{color:#0a8a3a}
    .err{color:#b00020}
  </style>
</head>
<body>
<main class="container">
  <h1>Configuració inicial (migrate)</h1>
  <p class="muted">MySQL detectat: <strong><?=h($major)?></strong> · Esquema per defecte: <code class="mono"><?=h($defaultSchema)?></code></p>

  <form method="post" class="grid">
    <label>Acció
      <select name="action" required>
        <option value="">— Tria què vols fer —</option>
        <option value="schema">Crear/actualitzar taules (ESQUEMA)</option>
        <option value="seed">Inserir dades inicials (SEED)</option>
        <option value="both">Fer les dues coses (ESQUEMA + SEED)</option>
      </select>
    </label>

    <label>Fitxer d’esquema (.sql)
      <input type="text" name="schema" value="<?=h($schema)?>" />
      <small class="muted">Recomanat: <code class="mono"><?=h($defaultSchema)?></code>. Pots forçar un altre fitxer (p. ex. <code class="mono">../sql/schema.mysql56.sql</code>).</small>
    </label>

    <label>Fitxer de dades inicials (seed) (.sql)
      <input type="text" name="seedfile" value="<?=h($seedfile)?>" />
      <small class="muted">Per defecte: <code class="mono"><?=h($defaultSeed)?></code></small>
    </label>

    <button type="submit">Executa</button>
  </form>

  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
    <article style="margin-top:1rem">
      <h3>Resultat</h3>
      <?php if ($okSummary): ?><p class="ok"><?= $okSummary ?></p><?php endif; ?>
      <pre class="mono"><?php
        foreach ($logs as $line) {
          $cls = (strpos($line, '[ERROR]') !== false) ? 'err' : ((strpos($line,'[OK]')!==false) ? 'ok' : '');
          echo ($cls ? '['.$cls.'] ' : '') . h($line) . "\n";
        }
      ?></pre>
    </article>
  <?php endif; ?>

  <hr>
  <details>
    <summary>Ajuda / Notes</summary>
    <ul>
      <li><strong>ESQUEMA</strong>: crea/recrea les taules segons el fitxer triat.</li>
      <li><strong>SEED</strong>: insereix/actualitza les tasques per defecte per a cada família.</li>
      <li>Parser simple: sentències separades amb <code>;</code>, comentaris <code>--</code>/<code>#</code>, sense <code>DELIMITER</code>.</li>
      <li>Per una instal·lació nova, fes “<em>ESQUEMA + SEED</em>”.</li>
    </ul>
  </details>
</main>
</body>
</html>
