<?php

namespace Tests\Feature\Livewire\Core;

use SchenkeIo\LivewireAutoForm\Helpers\ContextManager;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;

it('clears form when root model not found in loadContext', function () {
    $state = new FormCollection;
    $state->put('name', 'Old Name');

    $resolver = \Mockery::mock(ModelResolver::class);
    $resolver->shouldReceive('resolve')->andReturn(null);

    $manager = new ContextManager($state, $resolver, new DataProcessor);

    $manager->loadContext('', 999, []);

    expect($state->all())->toBeEmpty();
});

it('forgets relation form when related model not found in loadContext', function () {
    $state = new FormCollection;
    $state->put('cities', ['name' => 'Old City']);

    $resolver = \Mockery::mock(ModelResolver::class);
    $resolver->shouldReceive('resolve')->andReturn(null);

    $manager = new ContextManager($state, $resolver, new DataProcessor);

    $manager->loadContext('cities', 999, []);

    expect($state->has('cities'))->toBeFalse();
});

it('preserves existing relation data when reloading root context', function () {
    $state = new FormCollection;
    $state->put('cities', ['name' => 'Existing City']);

    $city = \Workbench\App\Models\City::factory()->create();
    $resolver = \Mockery::mock(ModelResolver::class);
    $resolver->shouldReceive('resolve')->andReturn($city);

    // rules contain 'cities.name'
    $rules = ['cities.name' => 'required', 'name' => 'required'];

    $manager = new ContextManager($state, $resolver, new DataProcessor);
    $manager->loadContext('', $city->id, $rules, true);

    expect($state->get('cities'))->toBe(['name' => 'Existing City']);
});
