<?php

namespace Tests;

use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use SchenkeIo\LivewireAutoForm\LivewireAutoFormServiceProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /** {@inheritDoc} */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            LivewireAutoFormServiceProvider::class,
            WorkbenchServiceProvider::class,
        ];
    }

    /**
     * Ensure package/workbench migrations are loaded for in-memory sqlite during tests.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(\dirname(__DIR__).'/workbench/database/migrations');
    }
}
