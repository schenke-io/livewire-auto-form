<?php

namespace Tests\Unit\Helpers;

use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;
use Tests\TestCase;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

class ModelResolverTest extends TestCase
{
    private ModelResolver $resolver;

    private FormCollection $state;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ModelResolver;
        $this->state = new FormCollection;
    }

    public function test_resolve_throws_if_root_model_class_missing()
    {
        // Line 32
        $this->expectException(LivewireAutoFormException::class);
        $this->expectExceptionMessage('[SchenkeIo\LivewireAutoForm\Helpers\ModelResolver]');
        $this->state->rootModelClass = '';
        $this->resolver->resolve($this->state, '', null);
    }

    public function test_resolve_with_empty_context_and_no_id()
    {
        $this->state->rootModelClass = City::class;
        $model = $this->resolver->resolve($this->state, '', null);
        $this->assertInstanceOf(City::class, $model);
        $this->assertFalse($model->exists);
    }

    public function test_resolve_with_id_returns_null_if_not_found()
    {
        $this->state->rootModelClass = City::class;
        $model = $this->resolver->resolve($this->state, '', 999999);
        $this->assertNull($model);
    }

    public function test_resolve_breaks_on_null_relation_parent()
    {
        // Line 77
        $this->state->rootModelClass = City::class;
        $city = new City; // not saved, no country

        // Resolve 'country.cities' - country is null because it's not saved and has no ID
        $model = $this->resolver->resolve($this->state, 'country.cities', null, true, $city);
        $this->assertNull($model);
    }

    public function test_resolve_throws_on_non_existent_relation()
    {
        // Lines 106-108
        $this->state->rootModelClass = City::class;
        $city = City::factory()->create();
        $this->state->rootModelId = $city->id;

        $this->expectException(LivewireAutoFormException::class);
        $this->expectExceptionMessage('[SchenkeIo\LivewireAutoForm\Helpers\ModelResolver]');
        $this->resolver->resolve($this->state, 'nonExistentRelation', null);
    }

    public function test_resolve_applies_state_to_root()
    {
        $this->state->rootModelClass = City::class;
        $this->state->put('name', 'New Name');

        $model = $this->resolver->resolve($this->state, '', null);
        $this->assertEquals('New Name', $model->name);
    }

    public function test_resolve_relation_with_id()
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $this->state->rootModelClass = City::class;
        $this->state->rootModelId = $city->id;

        $model = $this->resolver->resolve($this->state, 'country', $country->id);
        $this->assertInstanceOf(Country::class, $model);
        $this->assertEquals($country->id, $model->id);
    }
}
