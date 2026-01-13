<?php

namespace Tests\Feature\Livewire\Relationships;

use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Enums\BrandGroup;
use Workbench\App\Livewire\BrandShowEditor;
use Workbench\App\Models\Brand;
use Workbench\App\Models\City;

it('covers updatedForm with BelongsTo foreign key change', function () {
    $city1 = City::factory()->create();
    $city2 = City::factory()->create();
    $brand = Brand::factory()->create(['city_id' => $city1->id]);

    $test = Livewire::test(FlexibleTestComponent::class, [
        'model' => $brand,
        'rules' => [
            'name' => 'required',
            'city.id' => 'required',
            'city.name' => 'required',
        ],
    ])
        ->set('autoSave', true)
        ->call('edit', 'city', $city1->id)
        // Line 415-420: Changing the foreign key of a BelongsTo relationship
        ->set('form.city.id', $city2->id);

    $brand->refresh();
    expect($brand->city_id)->toBe($city2->id);
});

it('can change the city of a brand and edit its name', function () {
    $this->seed(DatabaseSeeder::class);
    $brand = Brand::first();
    $oldCity = $brand->city;
    $newCity = City::where('id', '!=', $oldCity->id)->first();

    $component = Livewire::test(BrandShowEditor::class, ['brand' => $brand])
        ->call('edit', 'city', $oldCity->id)
        ->assertSet('form.city.name', $oldCity->name);

    // Use the selector on the root model (city_id)
    $component->set('form.city_id', $newCity->id)
        ->assertSet('form.city_id', $newCity->id);

    // Now enter edit mode for the NEW city
    $component->call('edit', 'city', $newCity->id)
        ->set('form.city.name', 'Brand New Name')
        ->call('save');

    $brand->refresh();
    expect($brand->city_id)->toBe($newCity->id);
    expect($newCity->refresh()->name)->toBe('Brand New Name');
});

it('can delete a belongsTo relation (nullify foreign key)', function () {
    $city = City::factory()->create();
    $brand = Brand::factory()->create(['city_id' => $city->id]);

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $brand,
        'rules' => [
            'name' => 'required',
            'city.name' => 'nullable',
        ],
    ])
        ->call('delete', 'city', $city->id);

    $brand->refresh();
    expect($brand->city_id)->toBeNull();
});

it('can change BelongsTo ID within its own edit context', function () {
    $city1 = City::factory()->create(['name' => 'City 1']);
    $city2 = City::factory()->create(['name' => 'City 2']);
    $brand = Brand::factory()->create(['city_id' => $city1->id, 'name' => 'Brand Name', 'group' => BrandGroup::Cars]);

    $test = Livewire::test(FlexibleTestComponent::class, [
        'model' => $brand,
        'rules' => [
            'name' => 'nullable',
            'group' => 'nullable',
            'city_id' => 'nullable',
        ],
    ])
        ->set('autoSave', false) // Disable autoSave for this test
        ->set('form.name', 'Brand Name')
        ->set('form.group', BrandGroup::Cars->value)
        ->set('form.city_id', $city2->id)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'city_id' => $city2->id,
    ]);
});

it('covers BelongsTo foreign key update in save related model', function () {
    $city1 = City::factory()->create();
    $city2 = City::factory()->create();
    $brand = Brand::factory()->create(['city_id' => $city1->id]);

    $component = new FlexibleTestComponent;
    $component->mount($brand, [
        'name' => 'required',
        'city.id' => 'required',
    ]);

    $processor = new \SchenkeIo\LivewireAutoForm\CrudProcessor(
        $component->form,
        new \SchenkeIo\LivewireAutoForm\ModelResolver,
        new \SchenkeIo\LivewireAutoForm\DataProcessor
    );

    // We use a dot in the key to trigger the BelongsTo logic
    $allData = [
        'city.id' => $city2->id,
    ];

    $method = new \ReflectionMethod($processor, 'saveRelatedModel');
    $method->setAccessible(true);
    // Use an existing ID for update mode to hit line 188-212
    $method->invoke($processor, $brand, 'city', $city1->id, $allData);

    $brand->refresh();
    expect($brand->city_id)->toBe($city2->id);
});
