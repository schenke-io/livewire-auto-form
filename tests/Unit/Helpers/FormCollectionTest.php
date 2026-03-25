<?php

use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use Workbench\App\Models\Country;

it('manages root model class and id', function () {
    $state = new FormCollection([]);

    expect($state->getRootModelClass())->toBeNull();
    expect($state->getRootModelId())->toBeNull();

    $state->setRootModel(Country::class, 42);

    expect($state->getRootModelClass())->toBe(Country::class);
    expect($state->getRootModelId())->toBe(42);
});

it('manages active context and id with fallback to empty string', function () {
    $state = new FormCollection([]);

    // Default fallback
    expect($state->getActiveContext())->toBe('');
    expect($state->getActiveId())->toBeNull();

    $state->setContext('cities', 7);
    expect($state->getActiveContext())->toBe('cities');
    expect($state->getActiveId())->toBe(7);

    // Change only id
    $state->setActiveId(8);
    expect($state->getActiveContext())->toBe('cities');
    expect($state->getActiveId())->toBe(8);
});

it('manages auto-save flag', function () {
    $state = new FormCollection([]);

    expect($state->isAutoSave())->toBeFalse();
    $state->setAutoSave(true);
    expect($state->isAutoSave())->toBeTrue();
});

it('manages nullables list', function () {
    $state = new FormCollection([]);

    expect($state->getNullables())->toBeArray()->toBe([]);
    $state->setNullables(['name', 'status']);
    expect($state->getNullables())->toBe(['name', 'status']);
});

it('prevents setting __system key via setNested', function () {
    $state = new FormCollection([]);
    $key = FormCollection::SYSTEM_KEY.'.something';

    expect(fn () => $state->setNested($key, 'foo'))
        ->toThrow(LivewireAutoFormException::class, "The key '$key' is reserved for internal use.");
});

it('prevents setting __system key via offsetSet with dot notation', function () {
    $state = (new FormCollection([]));
    $key = FormCollection::SYSTEM_KEY.'.something';

    expect(fn () => $state[$key] = 'foo')
        ->toThrow(LivewireAutoFormException::class, "The key '$key' is reserved for internal use.");
});

it('allows nullables to be accessible via magic set', function () {
    $form = new FormCollection;
    $form->nullables = ['field1'];
    expect($form->nullables)->toBe(['field1']);
    expect($form->meta['nullables'])->toBe(['field1']);
    expect($form->has('nullables'))->toBeFalse();
});

it('allows nullables to be readable via magic get', function () {
    $form = new FormCollection;
    $form->setNullables(['field2']);
    expect($form->nullables)->toBe(['field2']);
});

it('allows jsonColumns to be accessible via magic set/get/isset', function () {
    $form = new FormCollection;
    expect(isset($form->jsonColumns))->toBeFalse();
    $form->jsonColumns = ['field1'];
    expect($form->jsonColumns)->toBe(['field1']);
    expect(isset($form->jsonColumns))->toBeTrue();
    expect($form->isJsonColumn('field1'))->toBeTrue();
});

it('converts to and from Livewire format', function () {
    $form = new FormCollection(['name' => 'John']);
    $form->setContext('test', 123);
    $form->setNullables(['email']);

    $livewireData = $form->toLivewire();

    expect($livewireData)->toHaveKey(FormCollection::SYSTEM_KEY);
    expect($livewireData['name'])->toBe('John');
    expect($livewireData[FormCollection::SYSTEM_KEY]['activeContext'])->toBe('test');
    expect($livewireData[FormCollection::SYSTEM_KEY]['activeId'])->toBe(123);
    expect($livewireData[FormCollection::SYSTEM_KEY]['nullables'])->toBe(['email']);

    $restoredForm = FormCollection::fromLivewire($livewireData);

    expect($restoredForm)->toBeInstanceOf(FormCollection::class);
    expect($restoredForm->get('name'))->toBe('John');
    expect($restoredForm->activeContext)->toBe('test');
    expect($restoredForm->activeId)->toBe(123);
    expect($restoredForm->nullables)->toBe(['email']);
    expect($restoredForm->has(FormCollection::SYSTEM_KEY))->toBeFalse();
});

it('throws exception when using system key in magic set', function () {
    $form = new FormCollection;
    expect(fn () => $form->{FormCollection::SYSTEM_KEY} = 'foo')
        ->toThrow(LivewireAutoFormException::class);
});

it('throws exception when using system key in put()', function () {
    $form = new FormCollection;
    expect(fn () => $form->put(FormCollection::SYSTEM_KEY, 'foo'))
        ->toThrow(LivewireAutoFormException::class);
});

it('throws exception when using system key in offsetSet', function () {
    $form = new FormCollection;
    expect(fn () => $form[FormCollection::SYSTEM_KEY] = 'foo')
        ->toThrow(LivewireAutoFormException::class);
});

it('clears data while preserving state', function () {
    $form = new FormCollection(['name' => 'John']);
    $form->setContext('test', 123);

    $form->clearData();

    expect($form->all())->toBeEmpty();
    expect($form->activeContext)->toBe('test');
    expect($form->activeId)->toBe(123);
});

it('maps non-meta magic set to items', function () {
    $form = new FormCollection;
    $form->some_key = 'some_value';
    expect($form->get('some_key'))->toBe('some_value');
});

it('supports magic getters for meta properties', function () {
    $form = new FormCollection;
    $form->setContext('ctx', 789);
    $form->rootModelClass = 'MyModel';
    $form->rootModelId = 101;

    expect($form->activeContext)->toBe('ctx');
    expect($form->activeId)->toBe(789);
    expect($form->rootModelClass)->toBe('MyModel');
    expect($form->rootModelId)->toBe(101);
    expect($form->non_existent)->toBeNull();
});

it('supports magic setters for meta properties', function () {
    $form = new FormCollection;
    $form->activeContext = 'new_ctx';
    $form->activeId = 111;
    $form->rootModelClass = 'OtherModel';
    $form->rootModelId = 222;
    $form->autoSave = true;

    expect($form->activeContext)->toBe('new_ctx');
    expect($form->activeId)->toBe(111);
    expect($form->rootModelClass)->toBe('OtherModel');
    expect($form->rootModelId)->toBe(222);
    expect($form->autoSave)->toBeTrue();
});

it('supports offsetUnset', function () {
    $form = new FormCollection(['name' => 'John']);
    unset($form['name']);
    expect($form->has('name'))->toBeFalse();
});

it('supports explicit getters and setters', function () {
    $form = new FormCollection;
    $form->setContext('ctx', 1);
    $form->setRootModel('Model', 2);
    $form->setNullables(['a']);
    $form->setAutoSave(true);

    expect($form->getActiveContext())->toBe('ctx');
    expect($form->getActiveId())->toBe(1);
    expect($form->getRootModelClass())->toBe('Model');
    expect($form->getRootModelId())->toBe(2);
    expect($form->getNullables())->toBe(['a']);
    expect($form->isAutoSave())->toBeTrue();
    expect($form->isRoot())->toBeFalse();

    $form->setContext('', null);
    expect($form->isRoot())->toBeTrue();
});

it('is countable and iterable', function () {
    $form = new FormCollection(['a' => 1, 'b' => 2]);
    expect($form)->toHaveCount(2);
    $items = iterator_to_array($form);
    expect($items)->toBe(['a' => 1, 'b' => 2]);
});

it('handles setNested with non-array base', function () {
    $form = new FormCollection;
    $form->put('relation', 'string');
    $form->setNested('relation.field', 'value');
    expect($form->get('relation'))->toBe(['field' => 'value']);
});

it('handles setNested with simple key', function () {
    $form = new FormCollection;
    $form->setNested('simple', 'value');
    expect($form->get('simple'))->toBe('value');
});

it('handles magic set with dot notation', function () {
    $form = new FormCollection;
    $form->{'relation.field'} = 'value';
    expect($form->get('relation'))->toBe(['field' => 'value']);
});

it('provides basic collection operations', function () {
    $form = new FormCollection(['a' => 1]);
    expect($form->toArray())->toBe(['a' => 1]);

    $form->setActiveId(555);
    expect($form->activeId)->toBe(555);

    $form->forget('a');
    expect($form->has('a'))->toBeFalse();

    $form->put('b', 2);
    $form->forget(['b']);
    expect($form->has('b'))->toBeFalse();

    $form->setContext('test', 123);
    expect(isset($form->activeContext))->toBeTrue();
    expect(isset($form->non_existent_meta))->toBeFalse();

    $form->setContext('', null);
    expect(isset($form->activeContext))->toBeFalse();
    expect(isset($form->activeId))->toBeFalse();

    $form->some_item = 'value';
    expect(isset($form->some_item))->toBeTrue();
    expect(isset($form->other_item))->toBeFalse();
});

it('supports offsetExists', function () {
    $form = new FormCollection(['a' => 1]);
    expect(isset($form['a']))->toBeTrue();
    expect(isset($form['b']))->toBeFalse();
});

it('supports offsetGet', function () {
    $form = new FormCollection(['a' => 1]);
    expect($form['a'])->toBe(1);
});

it('supports isset for nullables and autosave', function () {
    $form = new FormCollection;

    expect(isset($form->nullables))->toBeFalse();
    $form->nullables = ['field1'];
    expect(isset($form->nullables))->toBeTrue();

    expect(isset($form->autoSave))->toBeTrue();
});
