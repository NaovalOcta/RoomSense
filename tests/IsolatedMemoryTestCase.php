<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/** Feature tests using a fresh, private SQLite connection for each test. */
abstract class IsolatedMemoryTestCase extends BaseTestCase
{
    protected function isolatedSessionDriver(): string
    {
        return 'array';
    }

    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $this->traitsUsedByTest = class_uses_recursive(static::class);
        // Override connection and side effects before providers can boot.
        $app->afterBootstrapping(LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'sqlite');
            $app['config']->set('database.connections.sqlite.url', null);
            $app['config']->set('database.connections.sqlite.database', ':memory:');
            $app['config']->set('mail.default', 'array');
            $app['config']->set('broadcasting.default', 'null');
            $app['config']->set('session.driver', $this->isolatedSessionDriver());
            $app['config']->set('session.connection', 'sqlite');
            $app['config']->set('queue.default', 'sync');
        });
        $app->make(Kernel::class)->bootstrap();
        if (! $app->environment('testing')) {
            throw new RuntimeException('In-memory tests require the testing environment.');
        }

        $connection = $app['db']->connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('Expected a private SQLite in-memory database.');
        }

        // Migrate only the new in-memory connection; never use migrate:fresh.
        Artisan::call('migrate', ['--force' => true]);

        return $app;
    }
}
