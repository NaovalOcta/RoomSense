<?php
// Creation only: refuse to reuse, reset or drop ANY existing database.
require __DIR__.'/../../vendor/autoload.php';
use Tests\Support\DisposableMysql;
$env = DisposableMysql::credentials();
$pdo = new PDO('mysql:host='.$env['DB_HOST'].';port='.$env['DB_PORT'].';charset=utf8mb4', $env['DB_USERNAME'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
$name = DisposableMysql::DATABASE;
$exists = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
$exists->execute([$name]);
if ((int) $exists->fetchColumn() !== 0) { throw new RuntimeException('Target already exists; refusing any change.'); }
$pdo->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$name`");
$pdo->exec('CREATE TABLE _rs_test_ownership (owner VARCHAR(100) PRIMARY KEY)');
$pdo->prepare('INSERT INTO _rs_test_ownership (owner) VALUES (?)')->execute([DisposableMysql::OWNER]);
putenv('APP_ENV=testing'); $_ENV['APP_ENV'] = 'testing'; $_SERVER['APP_ENV'] = 'testing';
$app = require __DIR__.'/../../bootstrap/app.php';
DisposableMysql::configure($app);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
DisposableMysql::verify($app);
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
echo json_encode(['created_database' => $name, 'owner' => DisposableMysql::OWNER, 'migrations' => $app['db']->table('migrations')->count(), 'timezone' => $app['db']->selectOne('SELECT @@session.time_zone AS tz')->tz], JSON_PRETTY_PRINT).PHP_EOL;
