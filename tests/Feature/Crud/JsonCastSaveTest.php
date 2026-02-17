<?php

namespace Tests\Feature\Crud;

use Illuminate\Database\Eloquent\Model;
use Mockery;
use SchenkeIo\LivewireAutoForm\Helpers\CrudProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;
use Workbench\App\Models\User;

uses(Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration::class);

it('saveRootModel does not call JSON field as relation', function () {
    // We use a real model to ensure database interaction works as expected
    $user = User::factory()->create([
        'name' => 'Original Name',
    ]);

    $state = new FormCollection;
    $state->setRootModel(User::class, $user->id);

    $processor = new CrudProcessor($state, new ModelResolver, new DataProcessor);

    // Create a partial mock of the user to catch any attempt to call 'address' as a method.
    // In our Workbench User, 'address' is a fillable field but not a relationship.
    $mockUser = Mockery::mock($user)->makePartial();

    // Explicitly define that we do NOT expect the 'address' method to be called
    $mockUser->shouldNotReceive('address');

    $allData = [
        'address.street' => 'Some street', // This should be treated as a non-relation dot-notation
        'name' => 'New Name',               // This is a direct attribute
    ];

    // Trigger saveRootModel via reflection
    $method = new \ReflectionMethod(CrudProcessor::class, 'saveRootModel');
    $method->setAccessible(true);

    // This should NOT call $mockUser->address() because $mockUser->isRelation('address') is false.
    $method->invoke($processor, $mockUser, $allData);

    // Verify that the direct attribute was updated.
    // Note: since we used forceFill on the root model inside saveRootModel,
    // and we passed $mockUser (the mock), the changes should be reflected on it or persisted.

    $user->refresh();
    expect($user->name)->toBe('New Name');
});
