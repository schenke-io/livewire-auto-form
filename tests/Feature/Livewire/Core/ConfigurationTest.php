<?php

namespace Tests\Feature\Livewire\Core;

use Livewire\Livewire;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Livewire\CityShowEditor;
use Workbench\App\Models\City;

it('covers save with empty form values', function () {
    $city = City::factory()->create();
    $component = new FlexibleTestComponent;
    $component->mount($city, ['name' => 'nullable']);
    $component->form->setContext('non_existent', null);

    // Should return early
    expect($component->save())->toBeNull();
});

test('getSaveModeSuffix() returns (live) when autoSave is true', function () {
    $city = City::factory()->create();
    $test = Livewire::test(CityShowEditor::class, ['city' => $city]);
    $test->set('autoSave', true);
    expect($test->instance()->getSaveModeSuffix())->toBe(' (live)');
});

test('getSaveModeSuffix() returns (save) when autoSave is false', function () {
    $city = City::factory()->create();
    $test = Livewire::test(CityShowEditor::class, ['city' => $city]);
    $test->set('autoSave', false);
    expect($test->instance()->getSaveModeSuffix())->toBe(' (save)');
});

test('ensureRelationAllowed() allows relation defined in rules', function () {
    $city = City::factory()->create();
    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->call('ensureRelationAllowed', 'brands');
    expect(true)->toBeTrue();
});

test('ensureRelationAllowed() throws exception for relation not in rules', function () {
    $city = City::factory()->create();
    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->call('ensureRelationAllowed', 'nonExistentRelation');
})->throws(\SchenkeIo\LivewireAutoForm\LivewireAutoFormException::class, "Relation 'nonExistentRelation' not defined in rules.");

test('updated hook throws exception for Rules Discrepancy if field key is not in rules', function () {
    $city = City::factory()->create();
    Livewire::test(CityShowEditor::class, ['city' => $city])
        ->set('form.key_not_in_rules', 'some_value');
})->throws(\SchenkeIo\LivewireAutoForm\LivewireAutoFormException::class, "Field key 'key_not_in_rules' not defined in rules.");

it('covers FormCollection helpers', function () {
    $form = new \SchenkeIo\LivewireAutoForm\FormCollection;

    // isRoot (96)
    expect($form->isRoot())->toBeTrue();
    $form->setContext('brands', null);
    expect($form->isRoot())->toBeFalse();

    // __get (116)
    $form->setContext('test', null);
    expect($form->__get('activeContext'))->toBe('test');
    expect($form->activeContext)->toBe('test');

    // setNested non-array (167)
    $form->put('a', 'not-an-array');
    $form->setNested('a.b', 1);
    expect($form->get('a'))->toBe(['b' => 1]);

    // __set protected (165)
    $form->activeContext = 'new-context';
    expect($form->get('activeContext'))->toBe('new-context')
        ->and($form->activeContext)->toBe('test'); // should still be 'test' from previous setContext
});

it('covers LivewireAutoFormComponent:260 - updated with empty/form key', function () {
    $city = City::factory()->create();
    $component = new FlexibleTestComponent;
    $component->mount($city, ['name' => 'required']);

    // These should return early and do nothing
    $component->updated('form.', []);
    $component->updated('form.form', []);
    expect(true)->toBeTrue();
});
