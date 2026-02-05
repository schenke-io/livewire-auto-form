<?php

namespace Tests\Feature;

use SchenkeIo\LivewireAutoForm\LivewireAutoFormServiceProvider;

it('can load service provider', function () {
    expect($this->app->providerIsLoaded(LivewireAutoFormServiceProvider::class))->toBeTrue();
});

it('registers boost views', function () {
    expect(view()->exists('livewire-auto-form-boost::guidelines.core'))->toBeTrue();
});
