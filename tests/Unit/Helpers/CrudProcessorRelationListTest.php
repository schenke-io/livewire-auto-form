<?php

namespace Tests\Unit\Helpers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use SchenkeIo\LivewireAutoForm\Helpers\CrudProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;
use Tests\TestCase;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

class CrudProcessorRelationListTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_relation_list_selects_correct_columns()
    {
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

        $this->assertCount(2, $list);
        $this->assertArrayHasKey('id', $list[0]->getAttributes());
        $this->assertArrayHasKey('name', $list[0]->getAttributes());
        $this->assertArrayHasKey('population', $list[0]->getAttributes());
        $this->assertArrayNotHasKey('is_capital', $list[0]->getAttributes());
    }

    public function test_get_relation_list_returns_empty_when_no_root_class()
    {
        $state = new FormCollection([]);
        $resolver = new ModelResolver;
        $processor = new DataProcessor;
        $crudProcessor = new CrudProcessor($state, $resolver, $processor);

        $list = $crudProcessor->getRelationList('cities', []);
        $this->assertTrue($list->isEmpty());
    }
}
