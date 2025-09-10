<?php
require_once __DIR__ . '/config.php';


function db(): PDO {
static $pdo = null;
if ($pdo) return $pdo;
$dsn = "mysql:host={$GLOBALS['servername']};dbname={$GLOBALS['dbname']};charset=utf8mb4";
$options = [
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES => false,
];
$pdo = new PDO($dsn, $GLOBALS['username'], $GLOBALS['password'], $options);
return $pdo;
}


function json_response($data, int $status = 200) {
http_response_code($status);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
exit;
}

?>