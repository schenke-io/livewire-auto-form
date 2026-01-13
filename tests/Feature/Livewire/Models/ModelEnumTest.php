<?php

namespace Tests\Feature\Livewire\Models;

use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Tests\Feature\Livewire\Components\Models\ModelWithException;
use Tests\Feature\Livewire\Components\Models\ModelWithInvalidEnumCast;
use Tests\Feature\Livewire\Components\Models\ModelWithPureEnum;
use Workbench\App\Enums\BrandGroup;
use Workbench\App\Models\Brand;
use Workbench\App\Models\City;

it('covers enumOptionsFor with relation', function () {
    $city = City::factory()->create();

    $component = new FlexibleTestComponent;
    $component->mount($city, [
        'brands.name' => 'required',
        'brands.group' => 'required',
    ]);

    // Line 653-655
    $options = $component->enumOptionsFor('group', 'brands');
    expect($options)->not->toBeEmpty();
});

test('enumOptionsFor() returns empty array if rootModelClass is empty', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->setRootModel('', null);

    expect($component->enumOptionsFor('group'))->toBe([]);
});

test('enumOptionsFor() throws exception for non-casted attribute', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->setRootModel(Brand::class, null);

    $component->enumOptionsFor('name');
})->throws(\SchenkeIo\LivewireAutoForm\LivewireAutoFormException::class);

test('enumOptionsFor() throws exception for non-enum cast', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->setRootModel(City::class, null);

    $component->enumOptionsFor('population');
})->throws(\SchenkeIo\LivewireAutoForm\LivewireAutoFormException::class);

test('enumOptionsFor() returns correct options for backed enum', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->setRootModel(Brand::class, null);

    $options = $component->enumOptionsFor('group');

    expect($options)->not->toBeEmpty()
        ->and($options[0])->toHaveKeys(['value', 'label'])
        ->and($options[0]['value'])->toBe(BrandGroup::Cars->value)
        ->and($options[0]['label'])->toBe('Cars');
});

test('enumOptionsFor() returns correct options for pure enum', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->setRootModel(ModelWithPureEnum::class, null);

    $options = $component->enumOptionsFor('pure');

    expect($options)->not->toBeEmpty()
        ->and($options[0])->toHaveKeys(['value', 'label'])
        ->and($options[0]['value'])->toBe('Alpha')
        ->and($options[0]['label'])->toBe('Alpha');
});

test('enumOptionsFor() returns empty array for non-existent enum class', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->setRootModel(ModelWithInvalidEnumCast::class, null);

    expect($component->enumOptionsFor('invalid'))->toBe([]);
});

test('enumOptionsFor() handles exceptions and returns empty array', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->setRootModel(ModelWithException::class, null);

    expect($component->enumOptionsFor('group'))->toBe([]);
});
