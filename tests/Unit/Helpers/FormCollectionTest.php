<?php

use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

it('manages root model class and id', function () {
    $state = new FormCollection([]);

    expect($state->getRootModelClass())->toBeNull();
    expect($state->getRootModelId())->toBeNull();

    $state->setRootModel(\Workbench\App\Models\Country::class, 42);

    expect($state->getRootModelClass())->toBe(\Workbench\App\Models\Country::class);
    expect($state->getRootModelId())->toBe(42);
});

it('manages active context and id with fallback to empty string', function () {
    $state = new FormCollection([]);

    // Default fallback
    expect($state->getActiveContext())->toBe('');
    expect($state->getActiveId())->toBeNull();

    $state->setContext('cities', 7);
    expect($state->getActiveContext())->toBe('cities');
    expect($state->getActiveId())->toBe(7);

    // Change only id
    $state->setActiveId(8);
    expect($state->getActiveContext())->toBe('cities');
    expect($state->getActiveId())->toBe(8);
});

it('manages auto-save flag', function () {
    $state = new FormCollection([]);

    expect($state->isAutoSave())->toBeFalse();
    $state->setAutoSave(true);
    expect($state->isAutoSave())->toBeTrue();
});

it('manages nullables list', function () {
    $state = new FormCollection([]);

    expect($state->getNullables())->toBeArray()->toBe([]);
    $state->setNullables(['name', 'status']);
    expect($state->getNullables())->toBe(['name', 'status']);
});
