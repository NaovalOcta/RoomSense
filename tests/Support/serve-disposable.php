<?php
// Local-only isolated router. No migrations or configuration writes.
if (PHP_SAPI !== 'cli-server') { throw new RuntimeException('Use PHP loopback development server only.'); }
$public = realpath(__DIR__.'/../../public');
$file = realpath($public.parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($file && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)) { return false; }
require __DIR__.'/../../vendor/autoload.php';
putenv('APP_ENV=testing'); $_ENV['APP_ENV'] = 'testing'; $_SERVER['APP_ENV'] = 'testing';
$app = require __DIR__.'/../../bootstrap/app.php';
Tests\Support\DisposableMysql::configure($app);
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();
Tests\Support\DisposableMysql::verify($app);
$app->handleRequest(Illuminate\Http\Request::capture());
