<?php

namespace Tests\Feature\Livewire\Relationships;

use Livewire\Livewire;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Livewire\BrandShowEditor;
use Workbench\App\Models\Brand;
use Workbench\App\Models\City;

it('keeps main model editable when editing a relation', function () {
    $city = City::factory()->create(['name' => 'Original City']);
    $brand = Brand::factory()->create(['name' => 'Original Brand', 'city_id' => $city->id]);

    Livewire::test(BrandShowEditor::class, ['brand' => $brand])
        ->assertSet('form.name', 'Original Brand')
        ->call('edit', 'city', $city->id)
        ->assertSet('form.name', 'Original Brand')
        ->assertSet('form.city.name', 'Original City');
});

it('covers flat data in saveRelatedModel', function () {
    $city = City::factory()->create();
    $component = new FlexibleTestComponent;
    $component->mount($city, [
        'name' => 'nullable',
        'brands.name' => 'nullable',
        'brands.group' => 'nullable',
    ]);

    $processor = new \SchenkeIo\LivewireAutoForm\CrudProcessor(
        $component->form,
        new \SchenkeIo\LivewireAutoForm\ModelResolver,
        new \SchenkeIo\LivewireAutoForm\DataProcessor
    );

    $allData = [
        'name' => $city->name,
        'brands.name' => 'Brand X',
        'brands.group' => \Workbench\App\Enums\BrandGroup::Cars->value,
    ];

    $method = new \ReflectionMethod($processor, 'saveRelatedModel');
    $method->setAccessible(true);
    $method->invoke($processor, $city, 'brands', null, $allData);

    expect(Brand::where('name', 'Brand X')->exists())->toBeTrue();
});
