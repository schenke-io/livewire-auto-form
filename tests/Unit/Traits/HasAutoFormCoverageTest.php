<?php

namespace Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;
use Tests\TestCase;

class HasAutoFormCoverageTest extends TestCase
{
    /**
     * Helper to call the private method getRulesFromModel
     */
    private function callGetRulesFromModel($traitInstance, Model $model): array
    {
        $reflection = new \ReflectionClass($traitInstance);
        $method = $reflection->getMethod('getRulesFromModel');
        $method->setAccessible(true);

        return $method->invoke($traitInstance, $model);
    }

    private function getTraitInstance()
    {
        return new class
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
            }
        };
    }

    public function test_get_rules_from_model_uses_method()
    {
        $model = new class extends Model
        {
            public function rules(): array
            {
                return ['name' => 'required'];
            }
        };
        $instance = $this->getTraitInstance();
        $rules = $this->callGetRulesFromModel($instance, $model);
        $this->assertEquals(['name' => 'required'], $rules);
    }

    public function test_get_rules_from_model_uses_property()
    {
        $model = new class extends Model
        {
            public array $rules = ['email' => 'required|email'];
        };
        $instance = $this->getTraitInstance();
        $rules = $this->callGetRulesFromModel($instance, $model);
        $this->assertEquals(['email' => 'required|email'], $rules);
    }

    public function test_get_rules_from_model_returns_empty_array_by_default()
    {
        $model = new class extends Model {};
        $instance = $this->getTraitInstance();
        $rules = $this->callGetRulesFromModel($instance, $model);
        $this->assertEquals([], $rules);
    }
}
