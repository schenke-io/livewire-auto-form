<?php

namespace Tests\Feature\Livewire\Core;

use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\City;

it('throws an exception when __system is used in rules', function () {
    $city = City::factory()->create();
    $component = new FlexibleTestComponent;

    expect(fn () => $component->mount($city, [
        '__system' => 'nullable',
    ]))->toThrow(LivewireAutoFormException::class, "[Tests\Feature\Livewire\Components\FlexibleTestComponent] The key '__system' is reserved for internal use.");
});
