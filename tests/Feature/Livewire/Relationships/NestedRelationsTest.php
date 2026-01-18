<?php

namespace Tests\Feature\Livewire\Relationships;

use Livewire\Livewire;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

it('can edit a nested relation (city -> country -> other city)', function () {
    $country = Country::factory()->create(['name' => 'Main Country']);
    $city1 = City::factory()->create(['name' => 'City 1', 'country_id' => $country->id]);
    $city2 = City::factory()->create(['name' => 'City 2', 'country_id' => $country->id]);

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city1,
        'rules' => [
            'name' => 'required',
            'country.cities.name' => 'required',
        ],
    ])
        ->assertSet('form.name', 'City 1')
        ->call('edit', 'country.cities', $city2->id)
        ->assertSet('form.country.cities.name', 'City 2')
        ->assertSet('form.activeContext', 'country.cities')
        ->assertSet('form.activeId', (string) $city2->id);
});

it('can save a nested relation', function () {
    $country = Country::factory()->create(['name' => 'Main Country']);
    $city1 = City::factory()->create(['name' => 'City 1', 'country_id' => $country->id]);
    $city2 = City::factory()->create(['name' => 'City 2', 'country_id' => $country->id]);

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city1,
        'rules' => [
            'name' => 'required',
            'country.cities.name' => 'required',
        ],
    ])
        ->call('edit', 'country.cities', $city2->id)
        ->set('form.country.cities.name', 'Updated City 2')
        ->call('save');

    expect($city2->fresh()->name)->toBe('Updated City 2');
});
