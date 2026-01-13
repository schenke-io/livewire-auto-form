<?php

namespace Tests\Feature\Livewire\Models;

use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\City;

it('verifies reloadModel with a different model instance updates the root model', function () {
    $city1 = City::factory()->create(['name' => 'City 1']);
    $city2 = City::factory()->create(['name' => 'City 2']);

    $component = \Livewire\Livewire::test(FlexibleTestComponent::class, [
        'model' => $city1,
        'rules' => ['name' => 'required'],
    ]);

    expect($component->instance()->form->rootModelId)->toBe($city1->id);
    expect($component->instance()->form['name'])->toBe('City 1');

    // Reload with city2
    $component->call('reloadModel', $city2);

    // This should fail currently if my analysis is correct
    expect($component->instance()->form->rootModelId)->toBe($city2->id);
    expect($component->instance()->form['name'])->toBe('City 2');
});
