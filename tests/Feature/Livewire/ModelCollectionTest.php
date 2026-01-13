<?php

namespace Tests\Feature\Livewire;

use Livewire\Livewire;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

it('can switch between different root models using edit', function () {
    $country = Country::factory()->create();
    $city1 = City::factory()->create(['name' => 'City 1', 'country_id' => $country->id]);
    $city2 = City::factory()->create(['name' => 'City 2', 'country_id' => $country->id]);

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city1,
        'rules' => ['name' => 'required', 'country_id' => 'required'],
    ])
        ->assertSet('form.name', 'City 1')
        ->call('edit', '', $city2->id)
        ->assertSet('form.name', 'City 2')
        ->assertSet('form.rootModelId', $city2->id);
});

it('can prepare for a new root model using add', function () {
    $country = Country::factory()->create();
    $city1 = City::factory()->create(['name' => 'City 1', 'country_id' => $country->id]);

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city1,
        'rules' => ['name' => 'required', 'country_id' => 'required'],
    ])
        ->assertSet('form.name', 'City 1')
        ->call('add', '')
        ->assertSet('form.name', null)
        ->assertSet('form.rootModelId', null);
});

it('can save a new root model after add', function () {
    $country = Country::factory()->create();
    $city1 = City::factory()->create(['name' => 'City 1', 'country_id' => $country->id]);

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city1,
        'rules' => ['name' => 'required', 'country_id' => 'required'],
    ])
        ->call('add', '')
        ->set('form.name', 'City 3')
        ->set('form.country_id', $country->id)
        ->call('save');

    $this->assertDatabaseHas('cities', ['name' => 'City 3', 'country_id' => $country->id]);
});

it('can delete a root model using delete with empty relation', function () {
    $country = Country::factory()->create();
    $city1 = City::factory()->create(['name' => 'City 1', 'country_id' => $country->id]);
    $city2 = City::factory()->create(['name' => 'City 2', 'country_id' => $country->id]);

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city1,
        'rules' => ['name' => 'required', 'country_id' => 'required'],
    ])
        ->call('delete', '', $city2->id);

    $this->assertDatabaseMissing('cities', ['id' => $city2->id]);
    $this->assertDatabaseHas('cities', ['id' => $city1->id]);
});

it('resets context to root if deleted model was the active root', function () {
    $country = Country::factory()->create();
    $city1 = City::factory()->create(['name' => 'City 1', 'country_id' => $country->id]);

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city1,
        'rules' => ['name' => 'required', 'country_id' => 'required'],
    ])
        ->call('delete', '', $city1->id)
        ->assertSet('form.name', null)
        ->assertSet('form.rootModelId', null);
});
