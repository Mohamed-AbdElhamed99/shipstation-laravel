<?php

declare(strict_types=1);

namespace Mohamed\ShipStation\Tests;

use Mohamed\ShipStation\ShipStationServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ShipStationServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('shipstation.api_key', 'TEST_api_key_xxx');
        $app['config']->set('shipstation.environment', 'sandbox');
        $app['config']->set('shipstation.retries', 0); // disable retries in tests
        $app['config']->set('shipstation.webhooks.secret', 'test_webhook_secret');
    }
}
