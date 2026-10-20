<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.default');
        $database = $app['config']->get('database.connections.'.$connection.'.database');
        if (! $app->environment('testing') || ! (($connection === 'sqlite' && $database === ':memory:')
            || (is_string($database) && str_ends_with($database, '_test')))) {
            throw new \RuntimeException('Tests require SQLite :memory: or an explicitly disposable database ending in _test.');
        }
        return $app;
    }
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32))]);
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    }
}
