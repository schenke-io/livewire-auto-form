<?php

namespace Tests\Feature\Livewire;

use Livewire\Livewire;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\Brand;
use Workbench\App\Models\City;

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
    $city = City::factory()->create();
    $brand = Brand::factory()->create(['city_id' => $city->id, 'name' => 'Original Brand']);

    $component = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'required',
            'brands.name' => 'required',
        ],
    ])
        ->call('edit', 'brands', $brand->id)
        ->set('form.brands.name', 'Updated Brand');

    $activeModel = $component->instance()->getActiveModel();

    expect($activeModel)->toBeInstanceOf(Brand::class)
        ->and($activeModel->id)->toBe($brand->id)
        ->and($activeModel->name)->toBe('Updated Brand');

    // Root model should still be accessible
    expect($component->instance()->getModel()->id)->toBe($city->id);
});

it('correctly identifies when a record is being edited via isEdited()', function () {
    $city = City::factory()->create();
    $brand = Brand::factory()->create(['city_id' => $city->id]);

    $test = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'required',
            'brands.name' => 'required',
        ],
    ]);

    // Test root model
    expect($test->instance()->isEdited('', $city->id))->toBeTrue();
    expect($test->instance()->isEdited('', 999))->toBeFalse();
    expect($test->instance()->isEdited('brands', $brand->id))->toBeFalse();

    // Test relation model
    $test->call('edit', 'brands', $brand->id);
    expect($test->instance()->isEdited('brands', $brand->id))->toBeTrue();
    expect($test->instance()->isEdited('', $city->id))->toBeFalse();
    expect($test->instance()->isEdited('brands', 999))->toBeFalse();

    // Test after cancel
    $test->call('cancel');
    expect($test->instance()->isEdited('', $city->id))->toBeTrue();
    expect($test->instance()->isEdited('brands', $brand->id))->toBeFalse();
});
