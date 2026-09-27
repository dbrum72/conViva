<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = parent::createApplication();
        $connection = $app['config']->get('database.connections.mysql');
        if (! $app->environment('testing') || $app['config']->get('database.default') !== 'mysql'
            || $connection['database'] !== 'conviva_db' || ! empty($connection['url'])) {
            throw new \RuntimeException('Testes recusados: use exclusivamente MySQL conviva_db, sem URL alternativa.');
        }
        $actual = $app['db']->connection()->selectOne('SELECT DATABASE() AS name');
        if ($actual->name !== 'conviva_db') {
            throw new \RuntimeException('O banco efetivo não é conviva_db.');
        }

        return $app;
    }
}
