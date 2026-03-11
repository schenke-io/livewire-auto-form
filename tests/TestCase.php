<?php

namespace Tests;

use Flux\FluxServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use SchenkeIo\LivewireAutoForm\LivewireAutoFormServiceProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

/**
 * @method \Pest\Browser\Api\PendingAwaitablePage|\Pest\Browser\Api\ArrayablePendingAwaitablePage visit(string|array $url, array $options = [])
 * @method void assertDatabaseHas(string $table, array $data, ?string $connection = null)
 */
abstract class TestCase extends BaseTestCase
{
    /** {@inheritDoc} */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
            LivewireAutoFormServiceProvider::class,
            WorkbenchServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $isBrowser = str_contains(get_class($this), 'Browser');
        $databasePath = $isBrowser ? '/tmp/livewire-auto-form-test.sqlite' : ':memory:';

        if ($isBrowser && ! file_exists($databasePath)) {
            touch($databasePath);
        }

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => $databasePath,
            'prefix' => '',
            'foreign_key_constraints' => $isBrowser ? false : true,
        ]);
    }

    /**
     * Ensure package/workbench migrations are loaded.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(realpath(__DIR__.'/../workbench/database/migrations'));
    }
}
