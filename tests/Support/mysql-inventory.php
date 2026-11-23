<?php
// Read-only inventory. Never bootstrap HTTP, migrate, seed, or print credentials.
require __DIR__.'/../../vendor/autoload.php';
$env = Dotenv\Dotenv::createArrayBacked(dirname(__DIR__, 2))->load();
$pdo = new PDO('mysql:host='.($env['DB_HOST'] ?? '127.0.0.1').';port='.($env['DB_PORT'] ?? '3306').';charset=utf8mb4', $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
$result = ['host' => $env['DB_HOST'] ?? '127.0.0.1', 'port' => $env['DB_PORT'] ?? '3306', 'configured_database' => $env['DB_DATABASE'] ?? null, 'runtime' => $pdo->query('SELECT VERSION() AS version, @@hostname AS server, @@session.time_zone AS session_timezone, @@global.time_zone AS global_timezone')->fetch(PDO::FETCH_ASSOC), 'databases' => []];
foreach (['room-sense', 'room_sense_testing'] as $database) {
    $tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$database' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
    $state = [];
    foreach ($tables as $table) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $table)) { throw new RuntimeException('Unexpected table identifier'); }
        $rows = $pdo->query("SELECT * FROM `$database`.`$table`")->fetchAll(PDO::FETCH_ASSOC);
        $encoded = array_map(fn ($row) => json_encode($row, JSON_THROW_ON_ERROR), $rows);
        sort($encoded);
        $state[$table] = ['count' => count($rows), 'sha256' => hash('sha256', implode("\n", $encoded))];
    }
    $result['databases'][$database] = $state;
}
echo json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
