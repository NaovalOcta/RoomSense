<?php

namespace Tests;

/** Default database tests retain the pre-bootstrap in-memory identity guard. */
abstract class TestCase extends IsolatedMemoryTestCase
{
    protected function isolatedSessionDriver(): string
    {
        // Existing authentication regression tests exercise persisted device sessions.
        return 'database';
    }
}
