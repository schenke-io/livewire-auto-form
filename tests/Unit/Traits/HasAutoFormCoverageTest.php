<?php

namespace Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;
use Tests\TestCase;

/**
 * HasAutoFormCoverageTest focuses on achieving 100% code coverage for the HasAutoForm trait.
 * It specifically targets edge cases and branches that are not covered by standard usage tests,
 * such as rule array handling, ID field defaults, and missing model scenarios.
 */
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

    private function getTraitInstance(?Model $model = null, ?string $modelClass = null): object
    {
        return new class($model, $modelClass)
        {
            use HasAutoForm;

            public function __construct(private ?Model $model, private ?string $modelClass)
            {
                $this->form = new FormCollection;
                if ($modelClass) {
                    $this->form->rootModelClass = $modelClass;
                } elseif ($model) {
                    $this->form->rootModelClass = get_class($model);
                }
            }

            public array $testRuleKeys = [];

            public function ruleKeys(): array
            {
                return $this->testRuleKeys;
            }

            public function getModel(): ?Model
            {
                return $this->model;
            }

            public function callScanInheritedRules(array $rules = []): array
            {
                return $this->scanInheritedRules($rules);
            }
        };
    }

    public function test_get_rules_from_model_uses_method()
    {
        $model = new class extends Model
        {
            /** @return array<string, mixed> */
            /** @return array<string, mixed> */
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
        $instance = $this->getTraitInstance($model);
        $rules = $this->callGetRulesFromModel($instance, $model);
        $this->assertEquals([], $rules);
    }

    public function test_scan_inherited_rules_returns_rules_when_no_model_found()
    {
        // rootModelClass is set, ruleKeys is not empty, but getModel() returns null
        $instance = $this->getTraitInstance(null, 'SomeModelClass');
        $instance->testRuleKeys = ['name'];
        $rules = ['name' => 'required'];
        $result = $instance->callScanInheritedRules($rules);
        $this->assertEquals($rules, $result);
    }

    public function test_scan_inherited_rules_handles_id_key()
    {
        $model = new class extends Model {};
        $instance = $this->getTraitInstance($model);
        $instance->testRuleKeys = ['id'];
        $result = $instance->callScanInheritedRules();
        $this->assertEquals(['id' => 'nullable'], $result);
    }

    public function test_ensure_sometimes_rule_handles_array()
    {
        $model = new class extends Model
        {
            public function rules(): array
            {
                return ['tags' => ['required', 'array']];
            }
        };
        $instance = $this->getTraitInstance($model);
        $instance->testRuleKeys = ['tags'];
        $result = $instance->callScanInheritedRules();
        $this->assertEquals(['tags' => ['sometimes', 'required', 'array']], $result);
    }

    public function test_ensure_sometimes_rule_does_not_duplicate_sometimes_in_array()
    {
        $model = new class extends Model
        {
            public function rules(): array
            {
                return ['tags' => ['sometimes', 'required', 'array']];
            }
        };
        $instance = $this->getTraitInstance($model);
        $instance->testRuleKeys = ['tags'];
        $result = $instance->callScanInheritedRules();
        $this->assertEquals(['tags' => ['sometimes', 'required', 'array']], $result);
    }
}
