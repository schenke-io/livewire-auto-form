<?php

namespace SchenkeIo\LivewireAutoForm;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LivewireAutoFormServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('livewire-auto-form')
            ->hasViews('livewire-auto-form-boost');
    }
}
