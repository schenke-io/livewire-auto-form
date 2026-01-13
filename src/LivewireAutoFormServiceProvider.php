<?php

namespace SchenkeIo\LivewireAutoForm;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LivewireAutoFormServiceProvider extends PackageServiceProvider
{
    /**
     * @codeCoverageIgnore
     */
    public function register(): void {}

    /**
     * @codeCoverageIgnore
     */
    public function boot(): void {}

    /**
     * @codeCoverageIgnore
     */
    public function configurePackage(Package $package): void
    {
        $package->name('livewire-auto-form');
    }
}
