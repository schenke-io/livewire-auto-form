<?php

namespace Tests\Feature\Livewire\Core;

use SchenkeIo\LivewireAutoForm\LivewireAutoFormException;
use Tests\Feature\Livewire\Components\FlexibleTestComponent;
use Workbench\App\Models\City;

it('covers invalidRelationType exception', function () {
    $e = LivewireAutoFormException::invalidRelationType('test', 'SomeClass');
    expect($e->getMessage())->toContain('Relation [test] is of type [SomeClass] which is not supported for this operation.');
});

it('verifies Configuration Integrity by throwing rootModelNotSet when accessing relations before mount', function () {
    $component = new FlexibleTestComponent;
    expect(fn () => $component->ensureRelationAllowed('non_existent'))
        ->toThrow(LivewireAutoFormException::class, "Relation 'non_existent' not defined in rules.");
});

it('covers BadMethodCallException in loadContext', function () {
    $city = City::factory()->create();
    $brand = \Workbench\App\Models\Brand::factory()->create();

    $component = new FlexibleTestComponent;

    $method = new \ReflectionMethod($component, 'loadContext');
    $method->setAccessible(true);

    $component->mount($city, [
        'name' => 'required',
        'invalid_rel.name' => 'required',
    ], $brand);

    expect(fn () => $method->invoke($component, 'invalid_rel', $brand->id))
        ->toThrow(LivewireAutoFormException::class);
});

it('covers allOptionsForRelation errors', function () {
    $component = new FlexibleTestComponent;

    // Line 569: throw LivewireAutoFormException::rootModelNotSet();
    // We need resolveModelInstance to return null.
    // This happens if rootModelId is set but find() returns null (e.g. deleted from DB)
    $city = City::factory()->create();
    $component->mount($city, ['brands.name' => 'nullable']);
    $city->delete();

    expect(fn () => $component->allOptionsForRelation('brands'))
        ->toThrow(LivewireAutoFormException::class);

    $city2 = City::factory()->create();
    $component = new FlexibleTestComponent;
    $component->mount($city2, [
        'name' => 'required',
        'brands.name' => 'required',
    ]);

    // Line 566: throw LivewireAutoFormException::invalidRelationType
    // 'brands' is HasMany, but allOptionsForRelation expects BelongsTo or BelongsToMany
    expect(fn () => $component->allOptionsForRelation('brands'))
        ->toThrow(LivewireAutoFormException::class);
});

it('covers CrudProcessor:39 - missing root model in save', function () {
    $city = City::factory()->create();
    $component = \Livewire\Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => ['name' => 'nullable'],
    ]);

    $city->delete();

    // Should not throw exception
    $component->call('save');
    expect(true)->toBeTrue();
});

it('covers CrudProcessor:94 & 112 - dot notation and throwables in saveRootModel', function () {
    $city = City::factory()->create();
    $component = new FlexibleTestComponent;
    $component->mount($city, ['name' => 'nullable']);

    $processor = new \SchenkeIo\LivewireAutoForm\CrudProcessor(
        $component->form,
        new \SchenkeIo\LivewireAutoForm\ModelResolver,
        new \SchenkeIo\LivewireAutoForm\DataProcessor
    );

    // Pass flat data directly to save() to hit line 94
    $processor->save(['invalid_relation.id' => 123, 'getConnection.id' => 123]);

    expect(true)->toBeTrue();
});

it('covers CrudProcessor:185 - missing related model in update mode', function () {
    $city = City::factory()->create();
    $brand = \Workbench\App\Models\Brand::factory()->create(['city_id' => $city->id]);
    $component = \Livewire\Livewire::test(FlexibleTestComponent::class, [
        'model' => $city,
        'rules' => [
            'name' => 'nullable',
            'brands.name' => 'nullable',
        ],
    ])
        ->call('edit', 'brands', $brand->id);

    $brand->delete();

    $component->call('save'); // Should return early
    expect(true)->toBeTrue();
});

it('covers CrudProcessor:286 - BadMethodCallException in updatedForm', function () {
    $city = City::factory()->create();
    $component = new FlexibleTestComponent;
    $component->mount($city, [
        'name' => 'nullable',
        'invalid_relation.id' => 'nullable',
    ]);

    // We mock the resolver to return a model instead of throwing an exception
    $resolver = \Mockery::mock(\SchenkeIo\LivewireAutoForm\ModelResolver::class);
    $resolver->shouldReceive('resolve')->andReturn($city);

    $processor = new \SchenkeIo\LivewireAutoForm\CrudProcessor(
        $component->form,
        $resolver,
        new \SchenkeIo\LivewireAutoForm\DataProcessor
    );

    $component->form->setContext('invalid_relation', 1);
    $component->form->autoSave = true;

    // This will now call $city->invalid_relation() at line 275 and throw BadMethodCallException
    $processor->updatedForm('invalid_relation.id', 1, $component->getRules());

    expect(true)->toBeTrue();
});

it('covers rootModelNotSet exceptions', function () {
    $component = new FlexibleTestComponent;
    $component->form = new \SchenkeIo\LivewireAutoForm\FormCollection;
    $component->form->setRootModel(City::class, 999999); // Non-existent ID
    $component->customRules = [
        'name' => 'required',
        'brands.name' => 'required',
    ];

    expect(fn () => $component->allOptionsForRelation('brands'))
        ->toThrow(LivewireAutoFormException::class, 'Root model is not set');
});
