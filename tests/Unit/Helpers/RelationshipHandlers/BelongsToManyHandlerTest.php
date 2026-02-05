<?php

namespace Tests\Unit\Helpers\RelationshipHandlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Mockery;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers\BelongsToManyHandler;

it('saves belongsToMany by attaching existing record with pivot data', function () {
    $handler = new BelongsToManyHandler;
    $relation = Mockery::mock(BelongsToMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $relatedClass = Mockery::mock(Model::class);

    $data = ['id' => 10, 'pivot' => ['role' => 'admin']];
    $relation->shouldReceive('getRelated')->andReturn($relatedClass);
    $relatedClass->shouldReceive('getKeyName')->andReturn('id');

    $relation->shouldReceive('attach')->with(10, ['role' => 'admin'])->once();

    $handler->save($relation, $root, 'roles', null, $data, $state);
});

it('saves belongsToMany by creating new record with pivot data', function () {
    $handler = new BelongsToManyHandler;
    $relation = Mockery::mock(BelongsToMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $relatedClass = Mockery::mock(Model::class);

    $data = ['name' => 'New Role', 'pivot' => ['role' => 'editor']];
    $relation->shouldReceive('getRelated')->andReturn($relatedClass);
    $relatedClass->shouldReceive('getKeyName')->andReturn('id');

    $relation->shouldReceive('create')->with(['name' => 'New Role'], ['role' => 'editor'])->once();

    $handler->save($relation, $root, 'roles', null, $data, $state);
});

it('saves belongsToMany by updating existing pivot and model', function () {
    $handler = new BelongsToManyHandler;
    $relation = Mockery::mock(BelongsToMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $relatedClass = Mockery::mock(Model::class);
    $model = Mockery::mock(Model::class);

    $data = ['name' => 'Updated Role', 'pivot' => ['role' => 'viewer']];
    $relation->shouldReceive('getRelated')->andReturn($relatedClass);
    $relatedClass->shouldReceive('getKeyName')->andReturn('id');

    $relation->shouldReceive('find')->with(10)->andReturn($model);
    $model->shouldReceive('update')->with(['name' => 'Updated Role'])->once();
    $relation->shouldReceive('updateExistingPivot')->with(10, ['role' => 'viewer'])->once();

    $handler->save($relation, $root, 'roles', 10, $data, $state);
});

it('updates pivot field in belongsToMany', function () {
    $handler = new BelongsToManyHandler;
    $relation = Mockery::mock(BelongsToMany::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $processor = Mockery::mock(DataProcessor::class);

    $relation->shouldReceive('updateExistingPivot')->with(10, ['role' => 'manager'])->once();

    $result = $handler->updateField($relation, $root, 'roles', 10, 'pivot.role', 'manager', $state, $processor, []);
    expect($result)->toBeTrue();
});

it('detaches belongsToMany record', function () {
    $handler = new BelongsToManyHandler;
    $relation = Mockery::mock(BelongsToMany::class);
    $root = Mockery::mock(Model::class);

    $relation->shouldReceive('detach')->with(10)->once();

    $handler->delete($relation, $root, 'roles', 10);
});
