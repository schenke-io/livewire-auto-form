<?php

namespace Tests\Feature\Livewire\Workbench;

use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Workbench\App\Livewire\BrandShowEditor;
use Workbench\App\Models\Brand;

it('does not offer city_id when editing city of a brand', function () {
    $this->seed(DatabaseSeeder::class);
    $brand = Brand::first();
    $city = $brand->city;

    Livewire::test(BrandShowEditor::class, ['brand' => $brand])
        ->call('edit', 'city', $city->id)
        ->assertSet('form.name', $brand->name)
        ->assertSet('form.city.name', $city->name);
});

it('does not offer country_id when editing city of a brand', function () {
    $this->seed(DatabaseSeeder::class);
    $brand = Brand::first();
    $city = $brand->city;

    Livewire::test(BrandShowEditor::class, ['brand' => $brand])
        ->call('edit', 'city', $city->id)
        ->assertSet('form.city.country_id', null);
});

it('resets context to root after saving an edited city', function () {
    $this->seed(DatabaseSeeder::class);
    $brand = Brand::first();
    $city = $brand->city;

    Livewire::test(BrandShowEditor::class, ['brand' => $brand])
        ->call('edit', 'city', $city->id)
        ->assertSet('form.activeContext', 'city')
        ->set('form.city.name', 'Updated City Name')
        ->call('save')
        ->assertSet('form.activeContext', '');
});

test('getRelationList() returns empty collection if root model is missing', function () {
    $brand = Brand::factory()->create();
    $component = Livewire::test(BrandShowEditor::class, ['brand' => $brand]);
    $brand->delete();
    expect($component->instance()->getRelationList('city'))->toBeEmpty();
});

test('getRelationList() correctly handles simple and dotted column names', function () {
    $this->seed(DatabaseSeeder::class);
    $brand = Brand::first();
    $test = Livewire::test(BrandShowEditor::class, ['brand' => $brand]);
    $list = $test->instance()->getRelationList('city');
    expect($list)->not->toBeEmpty();
});

test('ensureRelationAllowed() throws exception for unregistered relation', function () {
    $brand = Brand::factory()->create();
    Livewire::test(BrandShowEditor::class, ['brand' => $brand])
        ->call('edit', 'unregistered', 1);
})->throws(\SchenkeIo\LivewireAutoForm\LivewireAutoFormException::class, "Relation 'unregistered' not defined in rules.");

it('renders the edit button group and show link for city', function () {
    $this->seed(DatabaseSeeder::class);
    $brand = Brand::first();
    $city = $brand->city;

    Livewire::test(BrandShowEditor::class, ['brand' => $brand])
        ->assertSee('Edit')
        ->assertSee(route('cities.show', $city->id));
});
