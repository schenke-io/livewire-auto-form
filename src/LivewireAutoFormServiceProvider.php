<?php

namespace SchenkeIo\LivewireAutoForm;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service provider for the LivewireAutoForm package.
 *
 * This class handles the registration and booting of package resources
 * using the Spatie Laravel Package Tools. It serves as the main entry point
 * for the package, facilitating the integration of AutoForm and AutoWizardForm
 * into Laravel applications.
 *
 * Responsibilities:
 * - Identifying the package name for Laravel's service container.
 * - (Optional) Handling any custom registration or boot logic as needed.
 * - Integration with Laravel's package discovery mechanism.
 *
 * Architecture Note:
 * This provider follows the standard Spatie pattern for modern Laravel packages,
 * ensuring clean and efficient loading of package assets and configuration.
 */
class LivewireAutoFormServiceProvider extends PackageServiceProvider
{

    /**
     * Boots package services and resources.
     *
     * @codeCoverageIgnore
     */
    public function boot(): void
    {
        parent::boot();

        /*
         * Register the resources/boost directory for guideline loading.
         */
        $this->loadViewsFrom(__DIR__.'/../resources/boost', 'livewire-auto-form-boost');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/boost' => resource_path('boost/livewire-auto-form'),
            ], 'livewire-auto-form-boost');
        }
    }

    /**
     * Configures the package's identification and resources.
     *
     * @param  Package  $package  The package configuration object.
     *
     * @codeCoverageIgnore
     */
    public function configurePackage(Package $package): void
    {
        $package->name('livewire-auto-form');
    }
}
