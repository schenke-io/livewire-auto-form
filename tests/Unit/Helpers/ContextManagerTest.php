<?php

namespace Tests\Unit\Helpers;

use Illuminate\Database\Eloquent\Model;
use Mockery;
use SchenkeIo\LivewireAutoForm\Helpers\ContextManager;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;

it('loads root context and clears data if model not found', function () {
    $state = Mockery::mock(FormCollection::class);
    $resolver = Mockery::mock(ModelResolver::class);
    $processor = Mockery::mock(DataProcessor::class);

    $manager = new ContextManager($state, $resolver, $processor);

    $state->shouldReceive('setContext')->with('', null)->once();
    $resolver->shouldReceive('resolve')->andReturn(null)->once();
    $state->shouldReceive('clearData')->once();

    $manager->loadContext('', null, []);
});

it('loads relation context and forgets data if model not found', function () {
    $state = Mockery::mock(FormCollection::class);
    $resolver = Mockery::mock(ModelResolver::class);
    $processor = Mockery::mock(DataProcessor::class);

    $manager = new ContextManager($state, $resolver, $processor);

    $state->shouldReceive('setContext')->with('address', 1)->once();
    $resolver->shouldReceive('resolve')->andReturn(null)->once();
    $state->shouldReceive('forget')->with(['address'])->once();

    $manager->loadContext('address', 1, []);
});

it('loads root context and merges data while preserving relations', function () {
    $state = Mockery::mock(FormCollection::class);
    $resolver = Mockery::mock(ModelResolver::class);
    $processor = Mockery::mock(DataProcessor::class);
    $model = Mockery::mock(Model::class);

    $manager = new ContextManager($state, $resolver, $processor);

    $rules = ['name' => 'required', 'address' => 'required'];
    $data = ['name' => 'John'];

    $state->shouldReceive('setContext')->with('', null)->once();
    $resolver->shouldReceive('resolve')->andReturn($model)->once();
    $processor->shouldReceive('extractFilteredData')->andReturn($data)->once();

    // Preservation logic
    $processor->shouldReceive('findRelations')->with($rules)->andReturn(['address'])->once();
    $state->shouldReceive('has')->with('address')->andReturn(true)->once();
    $state->shouldReceive('get')->with('address')->andReturn(['city' => 'NY'])->once();

    $state->shouldReceive('all')->andReturn(['name' => 'Old', 'address' => ['city' => 'NY']])->once();
    $state->shouldReceive('forget')->with(['name', 'address'])->once();

    $state->shouldReceive('put')->with('name', 'John')->once();
    $state->shouldReceive('put')->with('address', ['city' => 'NY'])->once();

    $manager->loadContext('', null, $rules, true);
});

it('loads relation context and sets nested data', function () {
    $state = Mockery::mock(FormCollection::class);
    $resolver = Mockery::mock(ModelResolver::class);
    $processor = Mockery::mock(DataProcessor::class);
    $model = Mockery::mock(Model::class);

    $manager = new ContextManager($state, $resolver, $processor);

    $data = ['city' => 'NY'];

    $state->shouldReceive('setContext')->with('address', 1)->once();
    $resolver->shouldReceive('resolve')->andReturn($model)->once();
    $processor->shouldReceive('extractFilteredData')->andReturn($data)->once();

    $state->shouldReceive('setNested')->with('address', $data)->once();

    $manager->loadContext('address', 1, []);
});
