<?php

use Livewire\Livewire;
use Workbench\App\Livewire\CityShowEditor;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

it('can resolve nested JSON data rules', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create([
        'country_id' => $country->id,
        'is_capital' => false,
        'geo' => [
            'latitude' => 1.23,
            'longitude' => 4.56,
        ],
    ]);

    $test = Livewire::test(CityShowEditor::class, ['city' => $city])
        ->assertSet('form.name', $city->name)
        ->assertSet('form.geo.latitude', 1.23)
        ->set('form.geo.latitude', 10.5)
        ->assertHasNoErrors();

    // Verify all keys are resolved
    $rules = $test->instance()->rules();
    expect($rules)->toHaveKeys([
        'name',
        'background',
        'population',
        'is_capital',
        'country_id',
        'geo.latitude',
        'geo.longitude',
        'country.name',
        'country.code',
    ]);

    $city->refresh();
    expect($city->geo['latitude'])->toBe(10.5);

    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->set('form.geo.latitude', 'invalid')
        ->assertHasErrors(['form.geo.latitude']);
});
