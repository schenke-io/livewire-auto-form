<?php

namespace Tests\Unit\Helpers;

use Illuminate\Database\Eloquent\Model;
use Mockery;
use SchenkeIo\LivewireAutoForm\Helpers\CrudProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;
use SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers\RelationshipHandler;

uses(Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration::class);

beforeEach(function () {
    $this->state = Mockery::mock(FormCollection::class);
    $this->resolver = Mockery::mock(ModelResolver::class);
    $this->processor = Mockery::mock(DataProcessor::class);
    $this->crudProcessor = new CrudProcessor($this->state, $this->resolver, $this->processor);
});

afterEach(function () {
    Mockery::close();
});

it('save() calls saveRootModel and optionally saveRelatedModel', function () {
    $this->state->shouldReceive('getActiveContext')->andReturn('relation');
    $this->state->shouldReceive('getActiveId')->andReturn(123);
    $this->state->shouldReceive('getRootModelId')->andReturn(456);
    $this->state->shouldReceive('getNullables')->andReturn([]);

    $rootModel = Mockery::mock(Model::class);
    $rootModel->shouldReceive('isRelation')->andReturn(false);
    $rootModel->shouldReceive('isFillable')->andReturn(true);

    $this->resolver->shouldReceive('resolve')
        ->with($this->state, '', 456)
        ->andReturn($rootModel);

    // saveRootModel logic check
    $this->processor->shouldReceive('sanitizeValue')->andReturn('sanitized');
    $rootModel->shouldReceive('forceFill')->with(Mockery::on(function ($arg) {
        return isset($arg['name']) && $arg['name'] === 'sanitized';
    }))->andReturnSelf();
    $rootModel->shouldReceive('save')->once();
    $rootModel->shouldReceive('refresh')->once();

    // saveRelatedModel logic check
    $this->resolver->shouldReceive('resolve')
        ->with($this->state, 'relation', 123)
        ->andReturn(null); // Simple case for now

    $this->crudProcessor->save(['name' => 'test']);
});

it('updatedForm() returns saved=false when Auto-Save is off', function () {
    $this->processor->shouldReceive('sanitizeValue')->andReturn('clean');
    $this->state->shouldReceive('getNullables')->andReturn([]);
    $this->state->shouldReceive('isAutoSave')->andReturn(false);

    $result = $this->crudProcessor->updatedForm('name', 'value', []);

    expect($result)->toHaveKey('cleanValue', 'clean');
    expect($result)->toHaveKey('saved', false);
});

it('updatedForm() returns saved=true and persists when Auto-Save is on', function () {
    $this->processor->shouldReceive('sanitizeValue')->andReturn('clean');
    $this->state->shouldReceive('getNullables')->andReturn([]);
    $this->state->shouldReceive('isAutoSave')->andReturn(true);
    $this->state->shouldReceive('getRootModelId')->andReturn(456);
    $this->state->shouldReceive('getActiveContext')->andReturn('');
    $this->state->shouldReceive('getActiveId')->andReturn(456);

    $rootModel = Mockery::mock(Model::class);
    $rootModel->exists = true;

    $this->resolver->shouldReceive('resolve')
        ->with($this->state, '', 456)
        ->andReturn($rootModel);

    $this->resolver->shouldReceive('resolve')
        ->with($this->state, '', 456)
        ->andReturn($rootModel);

    $rootModel->shouldReceive('forceFill')->with(['name' => 'clean'])->andReturnSelf();
    $rootModel->shouldReceive('save')->once();
    $rootModel->shouldReceive('refresh')->once();

    $result = $this->crudProcessor->updatedForm('name', 'value', []);

    expect($result)->toHaveKey('cleanValue', 'clean');
    expect($result)->toHaveKey('saved', true);
});

it('delete() deletes root model when relation is empty', function () {
    $this->state->shouldReceive('getRootModelClass')->andReturn('App\Models\User');

    $model = Mockery::mock(Model::class);
    $model->shouldReceive('delete')->once();

    // Mock app container behavior for find()
    $mockApp = Mockery::mock('stdClass');
    $mockApp->shouldReceive('find')->with(123)->andReturn($model);
    app()->instance('App\Models\User', $mockApp);

    $this->crudProcessor->delete('', 123);
});

it('delete() delegates to handler when relation is provided', function () {
    $this->state->shouldReceive('getRootModelId')->andReturn(456);
    $rootModel = Mockery::mock(Model::class);
    $rootModel->exists = true;

    $this->resolver->shouldReceive('resolve')
        ->with($this->state, '', 456)
        ->andReturn($rootModel);

    // Mock resolveRelation - this is protected/internal in CrudProcessor but called in delete
    // Actually, resolveRelation is public!
    $relationObj = Mockery::mock('stdClass');

    // We need to partial mock CrudProcessor to mock resolveRelation if it was protected,
    // but it is public, so we can't easily mock it without a partial mock.
    // Let's use a real model if possible or just mock the call.

    // Re-instantiate with partial mock
    $this->crudProcessor = Mockery::mock(CrudProcessor::class, [$this->state, $this->resolver, $this->processor])->makePartial();
    $this->crudProcessor->shouldAllowMockingProtectedMethods();

    $this->crudProcessor->shouldReceive('resolveRelation')->andReturn($relationObj);

    $handler = Mockery::mock(RelationshipHandler::class);
    $this->crudProcessor->shouldReceive('getHandler')->andReturn($handler);

    $handler->shouldReceive('delete')->with($relationObj, $rootModel, 'tags', 789)->once();

    $this->crudProcessor->delete('tags', 789);
});
