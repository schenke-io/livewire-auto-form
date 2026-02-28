<?php

namespace Tests\Feature\Helpers;

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

    public function test_resolve_throws_if_root_model_class_missing(): void
    {
        // Line 32
        $this->expectException(LivewireAutoFormException::class);
        $this->expectExceptionMessage('[SchenkeIo\LivewireAutoForm\Helpers\ModelResolver]');
        $this->state->rootModelClass = '';
        $this->resolver->resolve($this->state, '', null);
    }

    public function test_resolve_with_empty_context_and_no_id(): void
    {
        $this->state->rootModelClass = City::class;
        $model = $this->resolver->resolve($this->state, '', null);
        $this->assertInstanceOf(City::class, $model);
        $this->assertFalse($model->exists);
    }

    public function test_resolve_with_id_returns_null_if_not_found(): void
    {
        $this->state->rootModelClass = City::class;
        $model = $this->resolver->resolve($this->state, '', 999999);
        $this->assertNull($model);
    }

    public function test_resolve_breaks_on_null_relation_parent(): void
    {
        // Line 77
        $this->state->rootModelClass = City::class;
        $city = new City; // not saved, no country

        // Resolve 'country.cities' - country is null because it's not saved and has no ID
        $model = $this->resolver->resolve($this->state, 'country.cities', null, true, $city);
        $this->assertNull($model);
    }

    public function test_resolve_returns_null_on_non_existent_relation(): void
    {
        // Lines 106-108
        $this->state->rootModelClass = City::class;
        $city = City::factory()->create();
        $this->state->rootModelId = $city->id;

        $model = $this->resolver->resolve($this->state, 'nonExistentRelation', null);
        $this->assertNull($model);
    }

    public function test_resolve_applies_state_to_root(): void
    {
        $this->state->rootModelClass = City::class;
        $this->state->put('name', 'New Name');

        $model = $this->resolver->resolve($this->state, '', null);
        $this->assertNotNull($model);
        /** @var City $model */
        $this->assertEquals('New Name', $model->name);
    }

    public function test_resolve_nested_relationship_traversal(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $this->state->rootModelClass = Country::class;
        $this->state->rootModelId = $country->id;

        // country.cities.id -> resolves to a city
        $model = $this->resolver->resolve($this->state, 'cities', $city->id);
        $this->assertNotNull($model);
        $this->assertInstanceOf(City::class, $model);
        $this->assertEquals($city->id, $model->id);
    }

    public function test_resolve_nested_relationship_new_instance(): void
    {
        $country = Country::factory()->create();

        $this->state->rootModelClass = Country::class;
        $this->state->rootModelId = $country->id;

        // country.cities with no ID -> resolves to new City instance
        $model = $this->resolver->resolve($this->state, 'cities', null);
        $this->assertInstanceOf(City::class, $model);
        $this->assertFalse($model->exists);
    }

    public function test_resolve_applies_state_to_related_model(): void
    {
        $country = Country::factory()->create();
        $city = City::factory()->create(['country_id' => $country->id]);

        $this->state->rootModelClass = Country::class;
        $this->state->rootModelId = $country->id;
        $this->state->put('cities', [$city->id => ['name' => 'Updated City Name']]);

        $model = $this->resolver->resolve($this->state, 'cities', $city->id);
        $this->assertNotNull($model);
        /** @var City $model */
        $this->assertEquals('Updated City Name', $model->name);
    }
}
