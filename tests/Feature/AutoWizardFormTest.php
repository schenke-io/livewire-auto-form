<?php

namespace Tests\Feature;

use Livewire\Livewire;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use Tests\Feature\Livewire\Components\TestWizard;
use Workbench\App\Models\City;

beforeEach(function () {
    Livewire::component('test-wizard', TestWizard::class);
});

it('validates structure on mount', function () {
    $city = City::factory()->create();

    // Valid structure
    Livewire::test(TestWizard::class, [
        'model' => $city,
        'rules' => ['name' => 'required'],
        'structure' => ['step1' => ['name']],
    ]);

    // Missing field in structure
    try {
        Livewire::test(TestWizard::class, [
            'model' => $city,
            'rules' => ['name' => 'required', 'population' => 'required'],
            'structure' => ['step1' => ['name']],
        ]);
        $this->fail('Exception not thrown');
    } catch (\Throwable $e) {
        $actual = $e instanceof \Illuminate\View\ViewException ? $e->getPrevious() : $e;
        expect($actual)->toBeInstanceOf(LivewireAutoFormException::class);
        expect($actual->getMessage())->toContain('[Tests\Feature\Livewire\Components\TestWizard]');
        expect($actual->getMessage())->toContain('population');
    }
});

it('can navigate through steps', function () {
    $city = City::factory()->create();

    $component = Livewire::test(TestWizard::class, [
        'model' => $city,
        'rules' => ['name' => 'required', 'population' => 'required'],
        'structure' => [
            'step1' => ['name'],
            'step2' => ['population'],
        ],
    ]);

    expect($component->get('currentStepIndex'))->toBe(0);
    expect($component->instance()->isStepActive(0))->toBeTrue();
    expect($component->instance()->isLastStep())->toBeFalse();

    $component->instance()->next();
    expect($component->get('currentStepIndex'))->toBe(1);
    expect($component->instance()->isStepActive(1))->toBeTrue();
    expect($component->instance()->isLastStep())->toBeTrue();

    $component->instance()->previous();
    expect($component->get('currentStepIndex'))->toBe(0);
});

it('validates current step fields on next', function () {
    $city = City::factory()->create();

    $component = Livewire::test(TestWizard::class, [
        'model' => $city,
        'rules' => ['name' => 'required', 'population' => 'required'],
        'structure' => [
            'step1' => ['name'],
            'step2' => ['population'],
        ],
    ]);

    $component->set('form.name', '');
    $component->call('next');

    $component->assertHasErrors(['form.name' => 'required']);
    expect($component->get('currentStepIndex'))->toBe(0);

    $component->set('form.name', 'Valid Name');
    $component->call('next');
    $component->assertHasNoErrors();
    expect($component->get('currentStepIndex'))->toBe(1);
});

it('submits correctly', function () {
    $city = City::factory()->create();

    $component = Livewire::test(TestWizard::class, [
        'model' => $city,
        'rules' => ['name' => 'required', 'population' => 'required'],
        'structure' => [
            'step1' => ['name'],
            'step2' => ['population'],
        ],
    ]);

    // In step 0, submit calls next
    $component->call('submit');
    expect($component->get('currentStepIndex'))->toBe(1);

    // In step 1 (last step), submit calls save
    $component->set('form.name', 'New Name');
    $component->call('submit');

    $city->refresh();
    expect($city->name)->toBe('New Name');
});

it('throws exception if autoSave is enabled', function () {
    $city = City::factory()->create();

    $component = Livewire::test(TestWizard::class, [
        'model' => $city,
        'rules' => ['name' => 'required'],
        'structure' => ['step1' => ['name']],
    ]);

    expect(fn () => $component->set('form.autoSave', true))
        ->toThrow(LivewireAutoFormException::class);
});

it('throws exception if field in structure is not in rules', function () {
    $city = City::factory()->create();

    try {
        Livewire::test(TestWizard::class, [
            'model' => $city,
            'rules' => ['name' => 'required'],
            'structure' => ['step1' => ['name', 'invalid_field']],
        ]);
        $this->fail('Exception not thrown');
    } catch (\Throwable $e) {
        $actual = $e instanceof \Illuminate\View\ViewException ? $e->getPrevious() : $e;
        expect($actual)->toBeInstanceOf(LivewireAutoFormException::class);
    }
});

it('returns empty array if step index is invalid in getStepFields', function () {
    $city = City::factory()->create();

    $component = Livewire::test(TestWizard::class, [
        'model' => $city,
        'rules' => ['name' => 'required'],
        'structure' => ['step1' => ['name']],
    ]);

    expect($component->instance()->getStepFields(999))->toBe([]);
});
