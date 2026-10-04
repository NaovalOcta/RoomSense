<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();

        // This runs before Laravel boots RefreshDatabase or other testing traits.
        if (! $app->environment('testing')) {
            throw new RuntimeException('Database tests require the testing environment.');
        }

        if ($app['config']->get('database.default') !== 'mysql') {
            throw new RuntimeException('Database tests require the mysql connection.');
        }

        try {
            $database = $app['db']->connection()->selectOne('SELECT DATABASE() AS db')->db;
        } catch (\Throwable) {
            throw new RuntimeException('Cannot verify the testing database; tests stopped.');
        }

        if ($database !== 'room_sense_testing') {
            throw new RuntimeException('Database tests require room_sense_testing; tests stopped.');
        }

        return $app;
    }
}
