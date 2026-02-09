<?php

namespace Tests\Feature\Livewire\Core;

use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\City;

it('throws an exception when __system is used in rules', function () {
    $city = City::factory()->make();
    $component = new FlexibleTestComponent;
    $component->bootHandlesFormState();
    $component->customRules = ['__system' => 'required'];

    expect(fn () => $component->setModel($city))
        ->toThrow(LivewireAutoFormException::class, "The key '__system' is reserved for internal use.");
});
