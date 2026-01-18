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

    $model = $component->instance()->getModel();

    expect($model)->toBeInstanceOf(City::class)
        ->and($model->id)->toBe($city->id)
        ->and($model->name)->toBe('Updated Name');

    // Check that it's NOT yet saved to database
    expect($city->refresh()->name)->toBe('Original Name');
});

it('returns null for getActiveModel() when no context is active', function () {
    $city = City::factory()->create();

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['name' => 'required'],
    ]);

    expect($component->instance()->getActiveModel())->toBeNull();
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

    $activeModel = $component->instance()->getActiveModel();

    expect($activeModel)->toBeInstanceOf(City::class)
        ->and($activeModel->id)->toBe($city->id)
        ->and($activeModel->name)->toBe('Updated City');

    // Root model should still be accessible
    expect($component->instance()->getModel()->id)->toBe($country->id);
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

    // Test root model
    expect($test->instance()->isEdited('', $country->id))->toBeTrue();
    expect($test->instance()->isEdited('', 999))->toBeFalse();
    expect($test->instance()->isEdited('cities', $city->id))->toBeFalse();

    // Test relation model
    $test->call('edit', 'cities', $city->id);
    expect($test->instance()->isEdited('cities', $city->id))->toBeTrue();
    expect($test->instance()->isEdited('', $country->id))->toBeFalse();
    expect($test->instance()->isEdited('cities', 999))->toBeFalse();

    // Test after cancel
    $test->call('cancel');
    expect($test->instance()->isEdited('', $country->id))->toBeTrue();
    expect($test->instance()->isEdited('cities', $city->id))->toBeFalse();
});
