<?php

namespace Tests\Feature\Livewire;

use Livewire\Livewire;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

it('can get the root model via getModel()', function () {
    $city = City::factory()->create(['name' => 'Original Name']);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['name' => 'required'],
    ]);

    $component->set('form.name', 'Updated Name');

    /** @var FlexibleTestComponent $instance */
    $instance = $component->instance();
    /** @var City $model */
    $model = $instance->getModel();

    expect($model)->toBeInstanceOf(City::class)
        ->and($model->id)->toBe($city->id)
        ->and($model->name)->toBe('Updated Name');

    // Check that it's NOT yet saved to database
    expect($city->refresh()->name)->toBe('Original Name');
});

it('can get the active model via getActiveModel() when in a relation context', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id, 'name' => 'Original City']);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $country,
        'rules' => [
            'name' => 'required',
            'cities.name' => 'required',
        ],
    ])
        ->call('edit', 'cities', $city->id)
        ->set('form.cities.name', 'Updated City');

    /** @var FlexibleTestComponent $instance */
    $instance = $component->instance();
    /** @var City $activeModel */
    $activeModel = $instance->getActiveModel();

    expect($activeModel)->toBeInstanceOf(City::class)
        ->and($activeModel->id)->toBe($city->id)
        ->and($activeModel->name)->toBe('Updated City');

    // Root model should still be accessible
    /** @var FlexibleTestComponent $instance */
    $instance = $component->instance();
    /** @var Country $rootModel */
    $rootModel = $instance->getModel();
    expect($rootModel->id)->toBe($country->id);
});

it('correctly identifies when a record is being edited via isEdited()', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $test = Livewire::test(FlexibleTestComponent::class, [
        'model' => $country,
        'rules' => [
            'name' => 'required',
            'cities.name' => 'required',
        ],
    ]);

    /** @var FlexibleTestComponent $instance */
    $instance = $test->instance();

    // Test root model
    expect($instance->isEdited('', $country->id))->toBeTrue();
    expect($instance->isEdited('', 999))->toBeFalse();
    expect($instance->isEdited('cities', $city->id))->toBeFalse();

    // Test relation model
    $test->call('edit', 'cities', $city->id);
    /** @var FlexibleTestComponent $instance */
    $instance = $test->instance();
    expect($instance->isEdited('cities', $city->id))->toBeTrue();
    expect($instance->isEdited('', $country->id))->toBeFalse();
    expect($instance->isEdited('cities', 999))->toBeFalse();

    // Test after cancel
    $test->call('cancel');
    /** @var FlexibleTestComponent $instance */
    $instance = $test->instance();
    expect($instance->isEdited('', $country->id))->toBeTrue();
    expect($instance->isEdited('cities', $city->id))->toBeFalse();
});
