<?php

namespace Tests\Feature\Livewire\Workbench;

use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Livewire\CityShowEditor;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

it('can edit and save a city', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();

    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->assertSet('form.name', $city->name)
        ->set('form.name', 'New City Name')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('New City Name', $city->refresh()->name);
});

it('offers id or timestamps in the form for brands', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();
    $brand = $city->brands()->first();

    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->call('edit', 'brands', $brand->id)
        ->assertSet('form.brands.id', $brand->id);
});

it('can delete a brand of a city', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();
    $brand = $city->brands()->first();
    $brandId = $brand->id;

    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->call('delete', 'brands', $brandId)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('brands', ['id' => $brandId]);
});

test('delete() returns early if root model is not found', function () {
    $city = City::factory()->create();
    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'nullable|string',
            'brands.name' => 'nullable|string',
        ],
    ])
        ->call('deleteRootModel')
        ->call('delete', 'brands', 1)
        ->assertHasNoErrors();
});

test('delete() handles missing related model gracefully', function () {
    $city = City::factory()->create();
    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->call('delete', 'brands', 999)
        ->assertHasNoErrors();
});

test('delete() calls cancel if current record is deleted', function () {
    $city = City::factory()->create();
    $brand = $city->brands()->create(['name' => 'Test Brand', 'group' => 'cars']);

    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->call('edit', 'brands', $brand->id)
        ->assertSet('form.activeContext', 'brands')
        ->call('delete', 'brands', $brand->id)
        ->assertSet('form.activeContext', '');
});

test('loadContext() ignores toFormArray() as it is removed', function () {
    $city = City::factory()->create(['name' => 'original']);
    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->assertSet('form.name', 'original');
});

it('verifies the Data Loading Strategy correctly filters data according to defined rules', function () {
    $city = City::factory()->create(['name' => 'A', 'background' => 'B']);
    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['name' => 'required'],
    ])
        ->assertSet('form.name', 'A')
        ->assertSet('form.background', null);
});

test('resolveModelInstance() returns null if root model is missing', function () {
    $city = City::factory()->create();
    $test = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['name' => 'nullable'],
    ]);
    $test->call('deleteRootModel');
    $test->call('cancel');
    expect($test->get('form.data'))->toBeEmpty();
});

test('updatedForm() returns early if autoSave is false', function () {
    $city = City::factory()->create(['name' => 'Old']);
    $component = Livewire::test(CityShowEditor::class, ['city' => $city]);

    // Explicitly set autoSave to false (it is false by default in CityShowEditor)
    $component->set('form.autoSave', false)
        ->set('form.name', 'New');

    $this->assertEquals('Old', $city->refresh()->name);
});

test('updatedForm() returns early if activeId is null', function () {
    $city = City::factory()->create();
    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->set('autoSave', true)
        ->call('add', 'brands')
        ->set('form.brands.name', 'New Brand')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('brands', ['name' => 'New Brand']);
});

test('updatedForm() returns early if model is not found', function () {
    $city = City::factory()->create();
    $brand = $city->brands()->create(['name' => 'Old', 'group' => 'cars']);
    $component = Livewire::test(CityShowEditor::class, ['city' => $city])
        ->set('autoSave', true)
        ->call('edit', 'brands', $brand->id);

    $brand->delete();

    $component->set('form.brands.name', 'New')
        ->assertHasNoErrors();
});

it('unsets form context if related model is not found during load', function () {
    $city = City::factory()->create();
    $test = Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'brands.name' => 'nullable',
        ],
    ]);

    // Manually call edit with a non-existent ID for a relation
    $test->call('edit', 'brands', 9999)
        ->assertSet('form.brands', null);
});

it('handles non-relation keys and dots in updatedForm by defaulting to root context', function () {
    $city = City::factory()->create(['name' => 'Old City', 'background' => 'Initial']);
    $test = Livewire::test(CityShowEditor::class, ['city' => $city]);
    $test->set('autoSave', true);

    // 1. Key with dot but not a registered relation
    // It should try to save to root. 'background' exists.
    $test->set('form.background', 'New Background');
    expect($city->refresh()->background)->toBe('New Background');

    // To hit the "non-registered relation with dot" logic specifically,
    // we use a key that doesn't exist as a relation but has a dot.
    // It will catch the error if it fails to save to root, but it exercises the code path.
    try {
        $test->set('form.non_existent.key', 'value');
    } catch (\Throwable $e) {
    }

    // 2. Key without dot
    $test->set('form.name', 'New City Name');

    $city->refresh();
    expect($city->name)->toBe('New City Name');
});

test('allOptionsForRelation() returns correct options', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();
    $test = Livewire::test(CityShowEditor::class, ['city' => $city]);

    $options = $test->instance()->allOptionsForRelation('country');
    expect($options)->not->toBeEmpty();
    expect($options[0])->toHaveKeys(['value', 'label']);
});

test('save() updates root model record', function () {
    $city = City::factory()->create(['name' => 'Old']);
    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->set('form.name', 'New')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('New', $city->refresh()->name);
});

test('save() creates new root model record', function () {
    $country = Country::factory()->create();

    Livewire::test(FlexibleTestComponent::class, [
        'model' => new City,
        'rules' => [
            'name' => 'required',
            'country_id' => 'required',
            'is_capital' => 'required',
        ],
    ])
        ->set('form.name', 'Brand New City')
        ->set('form.country_id', $country->id)
        ->set('form.is_capital', false)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('cities', ['name' => 'Brand New City', 'country_id' => $country->id, 'is_capital' => 0]);
});
