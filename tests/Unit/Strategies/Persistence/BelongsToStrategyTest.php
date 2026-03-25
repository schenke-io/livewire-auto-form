<?php

namespace Tests\Unit\Strategies\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mockery;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Strategies\Persistence\BelongsToStrategy;

it('saves belongsTo by updating existing related model', function () {
    $strategy = new BelongsToStrategy;
    $relation = Mockery::mock(BelongsTo::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $related = Mockery::mock(Model::class);
    $relatedClass = Mockery::mock(Model::class);

    $data = ['id' => 1, 'name' => 'Test'];

    $relation->shouldReceive('getRelated')->andReturn($relatedClass);
    $relatedClass->shouldReceive('getKeyName')->andReturn('id');

    $relatedClass->shouldReceive('find')->with(1)->andReturn($related);
    $related->shouldReceive('update')->with($data)->once();

    $strategy->save($relation, $root, 'address', 1, $data, $state);
});

it('saves belongsTo by switching to a different existing model', function () {
    $strategy = new BelongsToStrategy;
    $relation = Mockery::mock(BelongsTo::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $newRelated = Mockery::mock(Model::class);
    $relatedClass = Mockery::mock(Model::class);

    $data = ['id' => 2, 'name' => 'New'];

    $relation->shouldReceive('getRelated')->andReturn($relatedClass);
    $relatedClass->shouldReceive('getKeyName')->andReturn('id');

    // Switch logic
    $relatedClass->shouldReceive('find')->with(2)->andReturn($newRelated);
    $state->shouldReceive('setActiveId')->with(2)->once();
    $relation->shouldReceive('getForeignKeyName')->andReturn('address_id');
    $root->shouldReceive('setAttribute')->with('address_id', 2)->once();
    $root->shouldReceive('save')->once();
    $newRelated->shouldReceive('update')->with($data)->once();

    $strategy->save($relation, $root, 'address', 1, $data, $state);
});

it('updates field in belongsTo when ID changes', function () {
    $strategy = new BelongsToStrategy;
    $relation = Mockery::mock(BelongsTo::class);
    $root = Mockery::mock(Model::class);
    $state = Mockery::mock(FormCollection::class);
    $processor = Mockery::mock(DataProcessor::class);
    $relatedClass = Mockery::mock(Model::class);
    $related = Mockery::mock(Model::class);

    $relation->shouldReceive('getRelated')->andReturn($relatedClass);
    $relatedClass->shouldReceive('getKeyName')->andReturn('id');
    $relation->shouldReceive('getForeignKeyName')->andReturn('address_id');

    $root->shouldReceive('forceFill')->with(['address_id' => 3])->andReturn($root)->once();
    $root->shouldReceive('save')->once();
    $state->shouldReceive('setActiveId')->with(3)->once();

    $relatedClass->shouldReceive('find')->with(3)->andReturn($related);
    $processor->shouldReceive('extractFilteredData')->andReturn(['id' => 3, 'name' => 'X'])->once();
    $state->shouldReceive('put')->with('address', ['id' => 3, 'name' => 'X'])->once();

    $result = $strategy->updateField($relation, $root, 'address', 1, 'id', 3, $state, $processor, []);
    expect($result)->toBeTrue();
});

it('deletes belongsTo by setting foreign key to null', function () {
    $strategy = new BelongsToStrategy;
    $relation = Mockery::mock(BelongsTo::class);
    $root = Mockery::mock(Model::class);

    $relation->shouldReceive('getForeignKeyName')->andReturn('address_id');
    $root->shouldReceive('update')->with(['address_id' => null])->once();

    $strategy->delete($relation, $root, 'address', 1);
});
