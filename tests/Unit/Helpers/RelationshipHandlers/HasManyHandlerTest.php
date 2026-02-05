<?php

namespace Tests\Unit\Helpers\RelationshipHandlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers\HasManyHandler;

it('saves hasMany by creating new record when id is null', function () {
    $handler = new HasManyHandler;
    $relation = Mockery::mock(HasMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);

    $data = ['name' => 'New Item'];
    $relation->shouldReceive('create')->with($data)->once();

    $handler->save($relation, $root, 'items', null, $data, $state);
});

it('saves hasMany by updating existing record when id is provided', function () {
    $handler = new HasManyHandler;
    $relation = Mockery::mock(HasMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $model = Mockery::mock(Model::class);

    $data = ['name' => 'Updated Item'];
    $relation->shouldReceive('find')->with(5)->andReturn($model);
    $model->shouldReceive('update')->with($data)->once();

    $handler->save($relation, $root, 'items', 5, $data, $state);
});

it('does not handle updateField in hasMany', function () {
    $handler = new HasManyHandler;
    $relation = Mockery::mock(HasMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $processor = Mockery::mock(DataProcessor::class);

    $result = $handler->updateField($relation, $root, 'items', 5, 'name', 'New', $state, $processor, []);
    expect($result)->toBeFalse();
});

it('deletes hasMany record', function () {
    $handler = new HasManyHandler;
    $relation = Mockery::mock(HasMany::class);
    $root = Mockery::mock(Model::class);
    $model = Mockery::mock(Model::class);

    $relation->shouldReceive('find')->with(5)->andReturn($model);
    $model->shouldReceive('delete')->once();

    $handler->delete($relation, $root, 'items', 5);
});
