<?php

namespace Tests\Feature\Livewire\Models;

use Database\Seeders\DatabaseSeeder;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\Brand;
use Workbench\App\Models\City;
use Workbench\App\Models\River;

it('covers resolveModelInstance with null id for relation', function () {
    $city = City::factory()->create();
    $component = new FlexibleTestComponent;
    $component->mount($city, ['brands.name' => 'required']);

    $method = new \ReflectionMethod($component, 'resolveModelInstance');
    $method->setAccessible(true);

    $instance = $method->invoke($component, 'brands', null);
    expect($instance)->toBeInstanceOf(Brand::class)
        ->and($instance->exists)->toBeFalse();
});

it('covers preserving relation data on reloadModel', function () {
    $city = City::factory()->create();
    $brand = Brand::factory()->create(['city_id' => $city->id]);

    $component = \Livewire\Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'required',
            'brands.name' => 'required',
        ],
    ])
        ->call('edit', 'brands', $brand->id)
        ->set('form.brands.name', 'New Name');

    // Manually call reloadModel
    $component->instance()->reloadModel($city);

    // Verify name is still 'New Name' in form state
    expect($component->instance()->form->get('brands')['name'])->toBe('New Name');
});

it('links rivers only to cities and verifies Berlin and Düsseldorf associations with bridge_count', function () {
    $this->seed(DatabaseSeeder::class);

    $berlin = City::where('name', 'Berlin')->firstOrFail();
    $duesseldorf = City::where('name', 'Düsseldorf')->firstOrFail();

    $spree = River::where('name', 'Spree')->firstOrFail();
    $havel = River::where('name', 'Havel')->firstOrFail();
    $rhein = River::where('name', 'Rhine')->firstOrFail();
    $duessel = River::where('name', 'Düssel')->firstOrFail();

    // Assert river-city associations exist (passthrough via cities)
    expect($berlin->rivers->pluck('name'))->toContain('Spree', 'Havel');
    expect($duesseldorf->rivers->pluck('name'))->toContain('Rhine', 'Düssel');

    // Assert that pivot has bridge_count column available
    // We default to 0 unless otherwise set; ensure the column exists and is an int
    $pivotKeys = array_keys($berlin->rivers->first()->pivot->getAttributes());
    expect($pivotKeys)->toContain('bridge_count');

    // Also verify that accessing the pivot value returns an int
    $bridgeCount = $berlin->rivers->first()->pivot->bridge_count;
    expect(is_int($bridgeCount) || ctype_digit((string) $bridgeCount))->toBeTrue();
});

it('can be instantiated and handles mount with model', function () {
    $brand = Brand::factory()->create(['name' => 'Tesla']);
    $component = new FlexibleTestComponent;
    $component->mount($brand, ['name' => 'required|string']);

    expect($component->form->rootModelClass)->toBe(Brand::class)
        ->and($component->form['name'])->toBe('Tesla');
});

it('requires rules() to be implemented', function () {
    $component = new FlexibleTestComponent;
    $component->customRules = ['name' => 'required|string'];
    expect($component->rules())->toBe(['name' => 'required|string']);
});
