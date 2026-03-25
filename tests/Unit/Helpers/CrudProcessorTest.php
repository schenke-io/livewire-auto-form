<?php

namespace Tests\Unit\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Mockery;
use SchenkeIo\LivewireAutoForm\Helpers\CrudProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;
use SchenkeIo\LivewireAutoForm\Strategies\Persistence\PersistenceStrategy;
use Tests\TestCase;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

class ModelWithJson extends Model
{
    protected $table = 'model_with_json';

    protected $guarded = [];

    protected $casts = ['settings' => 'array'];
}

class ModelWithExplicitJson extends Model
{
    protected $table = 'model_with_explicit_json';

    protected $guarded = [];
}

uses(TestCase::class);
uses(Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->state = Mockery::mock(FormCollection::class);
    $this->state->shouldReceive('getJsonColumns')->andReturn([]);
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
    $this->processor->shouldReceive('translatePath')->andReturnArg(0);
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
    $this->processor->shouldReceive('translatePath')->andReturnArg(0);
    $this->state->shouldReceive('getNullables')->andReturn([]);
    $this->state->shouldReceive('isAutoSave')->andReturn(false);

    $result = $this->crudProcessor->updatedForm('name', 'value', []);

    expect($result)->toHaveKey('cleanValue', 'clean');
    expect($result)->toHaveKey('saved', false);
});

it('updatedForm() returns saved=true and persists when Auto-Save is on', function () {
    $this->processor->shouldReceive('sanitizeValue')->andReturn('clean');
    $this->processor->shouldReceive('translatePath')->andReturnArg(0);
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

    $strategy = Mockery::mock(PersistenceStrategy::class);
    $this->crudProcessor->shouldReceive('getStrategy')->andReturn($strategy);

    $strategy->shouldReceive('delete')->with($relationObj, $rootModel, 'tags', 789)->once();

    $this->crudProcessor->delete('tags', 789);
});

it('resolveRelation() throws LivewireAutoFormException when relation does not exist', function () {
    $rootModel = Mockery::mock(Model::class);
    $rootModel->shouldReceive('isRelation')->with('invalid_rel')->andReturn(false);

    $reflection = new \ReflectionClass(CrudProcessor::class);
    $method = $reflection->getMethod('resolveRelation');
    $method->setAccessible(true);

    $this->expectException(LivewireAutoFormException::class);
    $this->expectExceptionMessage('Relation [invalid_rel] does not exist on ['.get_class($rootModel).'].');

    $method->invoke($this->crudProcessor, $rootModel, 'invalid_rel');
});

it('saveRootModel ignores exceptions when probing relation keys (covers catch at line 132)', function () {
    $rootModel = Mockery::mock(Model::class);

    // First pass: ensure the dotted key is not treated as a direct relation form block
    $rootModel->shouldReceive('isRelation')->with('broken.id')->andReturn(false);

    // Fallback discovery: treat the first segment as a relation name
    $rootModel->shouldReceive('isRelation')->with('broken')->andReturn(true);

    // Saving with empty root data is still allowed
    $rootModel->shouldReceive('forceFill')->with([])->andReturnSelf();
    $rootModel->shouldReceive('save')->once();
    $rootModel->shouldReceive('refresh')->once();

    $reflection = new \ReflectionClass(CrudProcessor::class);
    $method = $reflection->getMethod('saveRootModel');
    $method->setAccessible(true);

    // Provide state nullables (not used here but part of sanitizeValue signature if reached)
    $this->state->shouldReceive('getNullables')->andReturn([]);

    // Calling an undefined relation method on the mock (broken()) will throw a BadMethodCallException
    // which must be swallowed by the try/catch in saveRootModel.
    $method->invoke($this->crudProcessor, $rootModel, ['broken.id' => 123]);

    expect(true)->toBeTrue(); // If we arrive here, the exception was caught and ignored
});

it('resolveRelation breaks on non-Model intermediate results and throws (covers break at line 384)', function () {
    $rootModel = Mockery::mock(Model::class);

    // First segment is a valid relation
    $rootModel->shouldReceive('isRelation')->with('cities')->andReturn(true);

    // Accessing dynamic property returns a Collection (not a Model), triggering the break path
    $rootModel->shouldReceive('getAttribute')->with('cities')->andReturn(collect());

    $reflection = new \ReflectionClass(CrudProcessor::class);
    $method = $reflection->getMethod('resolveRelation');
    $method->setAccessible(true);

    $this->expectException(LivewireAutoFormException::class);

    $method->invoke($this->crudProcessor, $rootModel, 'cities.nonexistent');
});

it('getRelationList() selects correct columns', function () {
    $country = Country::create(['name' => 'Testland', 'code' => 'TL']);
    City::create(['name' => 'City 1', 'country_id' => $country->id, 'population' => 1000]);
    City::create(['name' => 'City 2', 'country_id' => $country->id, 'population' => 2000]);

    $state = new FormCollection([]);
    $state->setRootModel(Country::class, $country->id);

    $resolver = new ModelResolver;
    $processor = new DataProcessor;
    $crudProcessor = new CrudProcessor($state, $resolver, $processor);

    $rules = [
        'cities.name' => 'required',
        'cities.population' => 'integer',
    ];

    $list = $crudProcessor->getRelationList('cities', $rules);

    expect($list)->toHaveCount(2);
    expect($list[0]->getAttributes())->toHaveKey('id');
    expect($list[0]->getAttributes())->toHaveKey('name');
    expect($list[0]->getAttributes())->toHaveKey('population');
    expect($list[0]->getAttributes())->not->toHaveKey('is_capital');
});

it('getRelationList() returns empty when no root class', function () {
    $state = new FormCollection([]);
    $resolver = new ModelResolver;
    $processor = new DataProcessor;
    $crudProcessor = new CrudProcessor($state, $resolver, $processor);

    $list = $crudProcessor->getRelationList('cities', []);
    expect($list)->toBeEmpty();
});

it('crud_processor_saves_explicit_json_column_without_casts', function () {
    Schema::create('model_with_explicit_json', function (Blueprint $table) {
        $table->id();
        $table->json('meta')->nullable();
        $table->timestamps();
    });

    $model = ModelWithExplicitJson::create(['meta' => json_encode(['old' => 'data'])]);
    $state = new FormCollection;
    $state->setRootModel(get_class($model), $model->id);
    $state->setJsonColumns(['meta']);
    $state->setAutoSave(true);

    $processor = new CrudProcessor($state, new ModelResolver, new DataProcessor);

    // Simulate updated form for 'meta.color'
    $rules = [
        'meta' => 'json_column',
        'meta.color' => 'required',
    ];

    $result = $processor->updatedForm('meta.color', 'red', $rules);

    expect($result)->toHaveKey('saved', true);
    expect($result)->toHaveKey('cleanValue', 'red');

    $model->refresh();
    $meta = json_decode($model->meta, true);
    expect($meta)->toBeArray();
    expect($meta['color'])->toBe('red');
    expect($meta['old'])->toBe('data');
});

it('crud_processor_saves_multiple_root_fields_including_json', function () {
    Schema::create('model_with_json', function (Blueprint $table) {
        $table->id();
        $table->json('settings')->nullable();
        $table->timestamps();
    });

    $model = ModelWithJson::create(['settings' => ['theme' => 'light']]);
    $state = new FormCollection;
    $state->setRootModel(get_class($model), $model->id);
    $state->setJsonColumns(['settings']);

    $processor = new CrudProcessor($state, new ModelResolver, new DataProcessor);

    $allData = [
        'settings.theme' => 'dark',
        'settings.font' => 'sans',
    ];

    $processor->save($allData);

    $model->refresh();
    expect($model->settings['theme'])->toBe('dark');
    expect($model->settings['font'])->toBe('sans');
});
