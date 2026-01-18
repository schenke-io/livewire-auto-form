<?php

use Workbench\App\Models\City;
use Workbench\App\Models\Country;

it('confirms basic country fields can be edited and saved', function () {
    $country = Country::factory()->create(['name' => 'Old Name', 'code' => 'AA']);

    $this->visit("/countries/{$country->id}")
        ->type("[dusk='name']", 'New Country Name')
        ->type("[dusk='code']", 'BB')
        ->press('Save')
        ->waitForText('Saved successfully');

    $country->refresh();
    expect($country->name)->toBe('New Country Name')
        ->and($country->code)->toBe('BB');
});

it('confirms editing a city from within the country editor persists correctly', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id, 'name' => 'Old City Name']);

    $this->visit("/countries/{$country->id}")
        ->click("[dusk='edit-city-{$city->id}']")
        ->waitForText('Edit City')
        ->type("[dusk='city-field-name']", 'New City Name')
        ->press('Save')
        ->waitForText('Saved successfully');

    expect($city->refresh()->name)->toBe('New City Name');
});

it('verifies adding a new city', function () {
    $country = Country::factory()->create();

    $this->visit("/countries/{$country->id}")
        ->click("[dusk='add-city']")
        ->waitForText('New City')
        ->type("[dusk='new-city-field-name']", 'Brand New City')
        ->type("[dusk='new-city-field-population']", '50000')
        ->press('Save')
        ->waitForText('Saved successfully');

    expect(City::where('name', 'Brand New City')->where('country_id', $country->id)->exists())->toBeTrue();
});

it('verifies deleting a city', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id]);

    $this->visit("/countries/{$country->id}")
        ->click("[dusk='delete-city-{$city->id}']")
        ->wait(0.5);

    expect(City::find($city->id))->toBeNull();
});

it('tests borders management in country editor', function () {
    $country = Country::factory()->create();
    $otherCountry = Country::factory()->create(['name' => 'Neighbor']);

    // Add border
    $browser = $this->visit("/countries/{$country->id}");

    $browser->press('Add Border')
        ->waitForText('New Border Country')
        ->select("[dusk='new-border-field-id']", $otherCountry->id)
        ->type("[dusk='new-border-field-pivot-border_length_km']", '100')
        ->click("[dusk='save-new-border']")
        ->waitForText('Saved successfully')
        ->wait(2);

    expect($country->refresh()->borders)->toHaveCount(1);
    expect($country->borders->first()->pivot->border_length_km)->toBe(100);

    // Update pivot field
    $browser->click("[dusk='edit-border-{$otherCountry->id}']")
        ->waitForText('Edit Border Country')
        ->type("[dusk='border-field-pivot-border_length_km']", '200')
        ->wait(0.5)
        ->click("[dusk='save-border']")
        ->waitForText('Saved successfully');

    $this->assertDatabaseHas('country_borders', [
        'country_id' => $country->id,
        'neighbor_id' => $otherCountry->id,
        'border_length_km' => 200,
    ]);

    // Remove border
    $browser->click("[dusk='delete-border-{$otherCountry->id}']")
        ->wait(0.5);

    expect($country->refresh()->borders)->toHaveCount(0);
});

it('verifies goto buttons and hidden id fields in country editor', function () {
    $country = Country::factory()->create();
    $city = City::factory()->create(['country_id' => $country->id, 'name' => 'City 1']);
    $neighbor = Country::factory()->create(['name' => 'Neighbor']);
    $country->borders()->attach($neighbor, ['border_length_km' => 100]);

    $this->visit("/countries/{$country->id}")
        // Check goto buttons
        ->assertPresent("[dusk='goto-city-{$city->id}']")
        ->assertAttribute("[dusk='goto-city-{$city->id}']", 'href', route('cities.show', $city->id))
        ->assertPresent("[dusk='goto-border-{$neighbor->id}']")
        ->assertAttribute("[dusk='goto-border-{$neighbor->id}']", 'href', route('countries.show', $neighbor->id))
        // Check hidden ID in city edit
        ->click("[dusk='edit-city-{$city->id}']")
        ->waitForText('Edit City')
        ->assertMissing("[dusk='city-field-id']")
        // Check hidden ID in new city
        ->click("[dusk='add-city']")
        ->waitForText('New City')
        ->assertMissing("[dusk='new-city-field-id']");
});
