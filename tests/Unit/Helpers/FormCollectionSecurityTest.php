<?php

use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;

it('prevents setting __system key via setNested', function () {
    $state = new FormCollection([]);
    $key = FormCollection::SYSTEM_KEY.'.something';

    expect(fn () => $state->setNested($key, 'foo'))
        ->toThrow(LivewireAutoFormException::class, "The key '$key' is reserved for internal use.");
});

it('prevents setting __system key via offsetSet with dot notation', function () {
    $state = new FormCollection([]);
    $key = FormCollection::SYSTEM_KEY.'.something';

    expect(fn () => $state[$key] = 'foo')
        ->toThrow(LivewireAutoFormException::class, "The key '$key' is reserved for internal use.");
});
