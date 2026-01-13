<?php

namespace Tests\Feature\Livewire\Models;

use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\City;

it('verifies Data Loading Strategy by filtering root model attributes in loadContext', function () {
    $city = City::factory()->create(['name' => 'Old Name']);
    $component = new FlexibleTestComponent;
    $component->mount($city, ['name' => 'required']);
    expect($component->form->has('name'))->toBeTrue();
});

test('sanitizeValue() converts empty string to null for nullable keys', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->setNullables(['field1']);

    $method = new \ReflectionMethod(FlexibleTestComponent::class, 'sanitizeValue');
    $method->setAccessible(true);

    expect($method->invoke($component, 'field1', ''))->toBeNull()
        ->and($method->invoke($component, 'field2', ''))->toBe('');
});

test('sanitizeValue() trims strings', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;

    $method = new \ReflectionMethod(FlexibleTestComponent::class, 'sanitizeValue');
    $method->setAccessible(true);

    expect($method->invoke($component, 'field1', '  hello  '))->toBe('hello');
});

test('sanitizeValue() returns value as is for non-strings', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;

    $method = new \ReflectionMethod(FlexibleTestComponent::class, 'sanitizeValue');
    $method->setAccessible(true);

    expect($method->invoke($component, 'field1', 123))->toBe(123)
        ->and($method->invoke($component, 'field1', true))->toBeTrue()
        ->and($method->invoke($component, 'field1', null))->toBeNull();
});

test('updatedForm() reflects sanitized value back to UI if it changed', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->autoSave = false;

    // Test trimming
    $component->form['name'] = '  trim me  ';
    $component->updatedForm('  trim me  ', 'name');
    expect($component->form['name'])->toBe('trim me');

    // Test nullable conversion
    $component->form->setNullables(['email']);
    $component->form['email'] = '';
    $component->updatedForm('', 'email');
    expect($component->form['email'])->toBeNull();
});
