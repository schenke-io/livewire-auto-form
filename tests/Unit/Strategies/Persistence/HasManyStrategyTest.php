<?php

namespace Tests\Unit\Strategies\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Strategies\Persistence\HasManyStrategy;

it('saves hasMany by creating new record when id is null', function () {
    $strategy = new HasManyStrategy;
    $relation = Mockery::mock(HasMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);

    $data = ['name' => 'New Item'];
    $relation->shouldReceive('create')->with($data)->once();

    $strategy->save($relation, $root, 'items', null, $data, $state);
});

it('saves hasMany by updating existing record when id is provided', function () {
    $strategy = new HasManyStrategy;
    $relation = Mockery::mock(HasMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $model = Mockery::mock(Model::class);

    $data = ['name' => 'Updated Item'];
    $relation->shouldReceive('find')->with(5)->andReturn($model);
    $model->shouldReceive('update')->with($data)->once();

    $strategy->save($relation, $root, 'items', 5, $data, $state);
});

it('does not handle updateField in hasMany', function () {
    $strategy = new HasManyStrategy;
    $relation = Mockery::mock(HasMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $processor = Mockery::mock(DataProcessor::class);

    $result = $strategy->updateField($relation, $root, 'items', 5, 'name', 'New', $state, $processor, []);
    expect($result)->toBeFalse();
});

it('deletes hasMany record', function () {
    $strategy = new HasManyStrategy;
    $relation = Mockery::mock(HasMany::class);
    $root = Mockery::mock(Model::class);
    $model = Mockery::mock(Model::class);

    $relation->shouldReceive('find')->with(5)->andReturn($model);
    $model->shouldReceive('delete')->once();

    $strategy->delete($relation, $root, 'items', 5);
});
