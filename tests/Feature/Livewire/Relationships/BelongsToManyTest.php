<?php

namespace Tests\Feature\Livewire\Relationships;

use Database\Seeders\DatabaseSeeder;
use Livewire\Livewire;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\City;
use Workbench\App\Models\River;

test('loadContext() correctly extracts pivot data into the $form buffer', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();
    $river = $city->rivers()->first();

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'required',
            'rivers.name' => 'required',
            'rivers.pivot.bridge_count' => 'integer',
        ],
    ])
        ->call('edit', 'rivers', $river->id)
        ->assertSet('form.name', $city->name)
        ->assertSet('form.rivers.name', $river->name)
        ->assertSet('form.rivers.pivot.bridge_count', $river->pivot->bridge_count);
});

test('updatedForm() performs atomic update for pivot fields', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();
    $river = $city->rivers()->first();

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'required',
            'rivers.name' => 'required',
            'rivers.pivot.bridge_count' => 'integer',
        ],
    ])
        ->set('autoSave', true)
        ->call('edit', 'rivers', $river->id)
        ->set('form.rivers.pivot.bridge_count', 99)
        ->assertHasNoErrors();

    $this->assertEquals(99, $city->rivers()->find($river->id)->pivot->bridge_count);
});

test('save() updates related model and pivot data', function () {
    $this->seed(DatabaseSeeder::class);
    $city = City::first();
    $river = $city->rivers()->first();

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'required',
            'rivers.name' => 'required',
            'rivers.pivot.bridge_count' => 'integer',
        ],
    ])
        ->call('edit', 'rivers', $river->id)
        ->set('form.rivers.name', 'Updated River Name')
        ->set('form.rivers.pivot.bridge_count', 123)
        ->call('save')
        ->assertHasNoErrors();

    $river->refresh();
    $this->assertEquals('Updated River Name', $river->name);
    $this->assertEquals(123, $city->rivers()->find($river->id)->pivot->bridge_count);
});

test('save() creates new BelongsToMany record with pivot data', function () {
    $city = City::factory()->create();

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'required',
            'rivers.name' => 'required',
            'rivers.pivot.bridge_count' => 'integer',
        ],
    ])
        ->call('add', 'rivers')
        ->set('form.rivers.name', 'Brand New River')
        ->set('form.rivers.pivot.bridge_count', 456)
        ->call('save')
        ->assertHasNoErrors();

    $newRiver = River::where('name', 'Brand New River')->first();
    $this->assertNotNull($newRiver);
    $this->assertTrue($city->rivers()->where('river_id', $newRiver->id)->exists());
    $this->assertEquals(456, $city->rivers()->find($newRiver->id)->pivot->bridge_count);
});

it('can delete a BelongsToMany relation (detach)', function () {
    $city = City::factory()->create();
    $river = River::factory()->create();
    $city->rivers()->attach($river->id, ['bridge_count' => 1]);

    Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'required',
            'rivers.name' => 'required',
            'rivers.pivot.bridge_count' => 'integer',
        ],
    ])
        ->call('delete', 'rivers', $river->id);

    expect($city->rivers()->where('river_id', $river->id)->exists())->toBeFalse();
    // River itself should still exist
    expect(River::find($river->id))->not->toBeNull();
});
