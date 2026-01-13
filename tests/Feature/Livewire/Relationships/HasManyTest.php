<?php

namespace Tests\Feature\Livewire\Relationships;

use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Workbench\App\Livewire\CityShowEditor;
use Workbench\App\Models\City;

it('can add a brand to a city (HasMany)', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();
    $initialCount = $city->brands()->count();

    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->call('add', 'brands')
        ->set('form.brands.name', 'Brand 123')
        ->set('form.brands.group', \Workbench\App\Enums\BrandGroup::Digital->value)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals($initialCount + 1, $city->brands()->count());
    $this->assertTrue($city->brands()->where('name', 'Brand 123')->exists());
});

it('can edit and save a brand of a city (HasMany)', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();
    $brand = $city->brands()->first();

    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->call('edit', 'brands', $brand->id)
        ->assertSet('form.brands.name', $brand->name)
        ->set('form.brands.name', 'Updated Brand Name')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertEquals('Updated Brand Name', $brand->refresh()->name);
});

it('can delete a brand of a city (HasMany)', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();
    $brand = $city->brands()->first();
    $brandId = $brand->id;

    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->call('delete', 'brands', $brandId)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('brands', ['id' => $brandId]);
});
