<?php

namespace Tests\Feature;

use Livewire\Livewire;
use Workbench\App\Data\EmployeeDetailsData;
use Workbench\App\Livewire\EmployeeEditor;
use Workbench\App\Models\Employee;

/**
 * Test the assembly and initial loading of a complex object (Spatie Data) into the AutoForm.
 */
it('loads complex object from model into auto-form', function () {
    // 1. Assembly: Create a model with a complex object attribute
    $employee = Employee::create([
        'name' => 'John Doe',
        'details' => new EmployeeDetailsData(position: 'Developer', salary: 50000),
    ]);

    // 2. Use in AutoForm & Hydration: Verify the Livewire component correctly hydrates the nested state
    Livewire::test(EmployeeEditor::class, ['employee' => $employee])
        ->assertSet('form.name', 'John Doe')
        ->assertSet('form.details.position', 'Developer')
        ->assertSet('form.details.salary', 50000);
});

/**
 * Test the hydration/dehydration and persistence of changed complex object properties.
 */
it('saves changed complex object from auto-form back to model', function () {
    $employee = Employee::create([
        'name' => 'John Doe',
        'details' => new EmployeeDetailsData(position: 'Developer', salary: 50000),
    ]);

    // 1. Hydration: Change values via Livewire
    Livewire::test(EmployeeEditor::class, ['employee' => $employee])
        ->set('form.details.position', 'Senior Developer')
        ->set('form.details.salary', 70000)
        // 2. Return correctly: Save back to the model
        ->call('save')
        ->assertHasNoErrors();

    $employee->refresh();
    // Verify the object is correctly reconstructed and persisted
    expect($employee->details)->toBeInstanceOf(EmployeeDetailsData::class)
        ->and($employee->details->position)->toBe('Senior Developer')
        ->and($employee->details->salary)->toBe(70000);
});

/**
 * Test validation of nested properties within a complex object.
 */
it('validates complex object properties', function () {
    $employee = Employee::create([
        'name' => 'John Doe',
        'details' => new EmployeeDetailsData(position: 'Developer', salary: 50000),
    ]);

    Livewire::test(EmployeeEditor::class, ['employee' => $employee])
        ->set('form.details.position', '')
        ->call('save')
        ->assertHasErrors(['form.details.position' => 'required']);
});
