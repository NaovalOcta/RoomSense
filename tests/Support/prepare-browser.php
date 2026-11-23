<?php
require __DIR__.'/../../vendor/autoload.php';
use Tests\Support\DisposableMysql;
putenv('APP_ENV=testing'); $_ENV['APP_ENV'] = 'testing'; $_SERVER['APP_ENV'] = 'testing';
$app = require __DIR__.'/../../bootstrap/app.php';
DisposableMysql::configure($app);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
DisposableMysql::verify($app);
if ($app['db']->table('users')->exists() || $app['db']->table('rooms')->exists()) { throw new RuntimeException('Browser fixture target must be empty.'); }
$password = bin2hex(random_bytes(16));
$user = App\Models\User::factory()->create(['name' => 'Browser Student', 'email' => 'v11.student@webmail.umm.ac.id', 'password' => Illuminate\Support\Facades\Hash::make($password)]);
$admin = App\Models\User::factory()->create(['name' => 'Browser Admin', 'email' => 'v11.admin@roomsense.test', 'password' => Illuminate\Support\Facades\Hash::make($password), 'is_admin' => true]);
$room = App\Models\Room::create(['name' => 'WIB Browser Room 😀', 'building' => 'Timur', 'capacity' => 50, 'is_active' => true]);
App\Models\Room::create(['name' => 'Small Browser Room', 'building' => 'Barat', 'capacity' => 10, 'is_active' => true]);
App\Models\Room::create(['name' => 'Inactive Browser Room', 'building' => 'Timur', 'capacity' => 100, 'is_active' => false]);
$path = storage_path('framework/testing/v11-browser-credentials.json');
@mkdir(dirname($path), 0777, true);
file_put_contents($path, json_encode(['student' => $user->email, 'admin' => $admin->email, 'password' => $password, 'room' => $room->id, 'date' => now('Asia/Jakarta')->addDay()->format('Y-m-d')]));
echo 'Synthetic browser fixtures prepared in '.DisposableMysql::DATABASE.'; credentials stored privately, not printed.'.PHP_EOL;
