<?php
require __DIR__.'/../../vendor/autoload.php';
putenv('APP_ENV=testing'); $_ENV['APP_ENV'] = 'testing'; $_SERVER['APP_ENV'] = 'testing';
$app = require __DIR__.'/../../bootstrap/app.php';
Tests\Support\DisposableMysql::configure($app);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Tests\Support\DisposableMysql::verify($app);
$bookings = $app['db']->table('bookings')->select('id','start_time','end_time','status')->get();
echo json_encode(['database'=>Tests\Support\DisposableMysql::DATABASE,'timezone'=>$app['db']->selectOne('SELECT @@session.time_zone AS tz')->tz,'bookings'=>$bookings], JSON_PRETTY_PRINT).PHP_EOL;
