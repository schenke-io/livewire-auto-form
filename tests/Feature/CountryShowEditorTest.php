<?php

use Livewire\Livewire;
use Workbench\App\Livewire\CountryShowEditor;
use Workbench\App\Models\Country;

it('can resolve inherited rules for CountryShowEditor', function () {
    $country = Country::factory()->create();

    $test = Livewire::test(CountryShowEditor::class, ['country' => $country]);

    $test->assertSet('form.name', $country->name)
        ->assertHasNoErrors();

    // Check if nested keys are present in rules
    /** @var CountryShowEditor $instance */
    $instance = $test->instance();
    $rules = $instance->rules();

    expect($rules)->toHaveKeys([
        'name',
        'code',
        'cities.name',
        'cities.population',
        'borders.id',
        'borders.name',
        'borders.pivot.border_length_km',
    ]);
});

it('throws no exception when resolving rules for CountryShowEditor', function () {
    $country = Country::factory()->create();

    // The fact that this test starts without exception means rules() was called successfully
    Livewire::test(CountryShowEditor::class, ['country' => $country])
        ->assertOk();
});
