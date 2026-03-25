<?php

use Workbench\App\Models\City;
use Workbench\App\Models\Country;

it('confirms changes to a city field are persisted with auto-save enabled', function () {
    $country = Country::factory()->create(['name' => 'Test Country']);
    $city = City::factory()->create([
        'name' => 'Old Name',
        'country_id' => $country->id,
        'population' => 1000,
    ]);

    visit("/cities/{$city->id}")
        ->type("[dusk='name']", 'New City Name')
        ->waitForText('Saved successfully');

    expect($city->refresh()->name)->toBe('New City Name');
});

it('confirms country reassignment via select persists correctly', function () {
    $country1 = Country::factory()->create(['name' => 'Country 1']);
    $country2 = Country::factory()->create(['name' => 'Country 2']);
    $city = City::factory()->create(['country_id' => $country1->id]);

    visit("/cities/{$city->id}")
        ->select("[dusk='country_id']", (string) $country2->id)
        ->wait(0.5);

    expect($city->refresh()->country_id)->toBe($country2->id);
});

it('verifies that the id field is not visible when editing country details from within the city editor', function () {
    $country = Country::factory()->create(['name' => 'Original Country']);
    $city = City::factory()->create(['country_id' => $country->id]);

    visit("/cities/{$city->id}")
        ->click("[dusk='edit-country-details']")
        ->waitForText('Edit Country Details')
        ->type("[dusk='country-field-name']", 'Updated Country Name')
        ->click("[dusk='save-country']")
        ->waitForText('Saved successfully');

    expect($country->refresh()->name)->toBe('Updated Country Name');
});

it('verifies the goto button for country exists and has correct href', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    visit("/cities/{$city->id}")
        ->assertPresent("[dusk='goto-country']")
        ->assertAttribute("[dusk='goto-country']", 'href', route('countries.show', $country->id));
});
