<?php

namespace Tests\Support;

use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use RuntimeException;

/** Explicitly owned, newly created disposable target; never accepts an env database name. */
final class DisposableMysql
{
    public const DATABASE = 'rs_v11_disposable_20261028_01a17f5c';
    public const OWNER = 'room-sense-v11-01a17f5c-20261028';

    public static function credentials(): array
    {
        return \Dotenv\Dotenv::createArrayBacked(dirname(__DIR__, 2))->load();
    }

    public static function configure($app): void
    {
        $env = self::credentials();
        $app->afterBootstrapping(LoadConfiguration::class, function ($app) use ($env) {
            $app['config']->set('database.default', 'mysql');
            $app['config']->set('database.connections.mysql.url', null);
            foreach (['host' => 'DB_HOST', 'port' => 'DB_PORT', 'username' => 'DB_USERNAME', 'password' => 'DB_PASSWORD'] as $key => $variable) {
                $app['config']->set('database.connections.mysql.'.$key, $env[$variable] ?? '');
            }
            $app['config']->set('database.connections.mysql.database', self::DATABASE);
            // Explicit per-connection session setting; global server/app timezone remains unchanged.
            $app['config']->set('database.connections.mysql.timezone', '+00:00');
            $app['config']->set('mail.default', 'array');
            $app['config']->set('broadcasting.default', 'null');
            $app['config']->set('session.driver', 'database');
            $app['config']->set('session.connection', 'mysql');
            $app['config']->set('cache.default', 'array');
            $app['config']->set('queue.default', 'sync');
            $app['config']->set('app.url', 'http://127.0.0.1:8093');
        });
    }

    public static function verify($app): void
    {
        if (!$app->environment('testing')) {
            throw new RuntimeException('Disposable MySQL requires testing environment.');
        }
        $db = $app['db']->connection();
        if ($db->getDriverName() !== 'mysql' || $db->selectOne('SELECT DATABASE() AS db')->db !== self::DATABASE) {
            throw new RuntimeException('Disposable database identity mismatch.');
        }
        if ($db->table('_rs_test_ownership')->value('owner') !== self::OWNER) {
            throw new RuntimeException('Disposable ownership marker mismatch.');
        }
    }
}
