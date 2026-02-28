<?php

namespace Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;
use Tests\TestCase;

class HasAutoFormScanRulesTest extends TestCase
{
    private function getTestComponent(Model $model)
    {
        return new class($model) implements \ArrayAccess
        {
            use HasAutoForm;

            private array $testRuleKeys = [];

            public function __construct(private Model $model)
            {
                $this->form = new FormCollection;
                $this->form->rootModelClass = get_class($model);
            }

            public function ruleKeys(): array
            {
                return $this->testRuleKeys;
            }

            public function setTestRuleKeys(array $keys): void
            {
                $this->testRuleKeys = $keys;
            }

            public function getActiveModel(): ?Model
            {
                return $this->model;
            }

            // Public wrapper for testing
            public function callScanInheritedRules(array $rules = []): array
            {
                return $this->scanInheritedRules($rules);
            }

            public function resolveModelInstance(string $context, int|string|null $id): ?Model
            {
                // Simple mock for testing nested relations
                if ($context === 'relation') {
                    return new class extends Model
                    {
                        /** @return array<string, mixed> */
                        public function rules(): array
                        {
                            return ['field' => 'required|string'];
                        }
                    };
                }

                return null;
            }
        };
    }

    public function test_scan_inherited_rules_resolves_root_fields()
    {
        $model = new class extends Model
        {
            /** @return array<string, mixed> */
            public function rules(): array
            {
                return ['name' => 'required'];
            }
        };

        $component = $this->getTestComponent($model);
        $component->setTestRuleKeys(['name']);
        $rules = $component->callScanInheritedRules();

        $this->assertEquals(['name' => 'sometimes|required'], $rules);
    }

    public function test_scan_inherited_rules_resolves_relation_fields()
    {
        $model = new class extends Model
        {
            /** @return array<string, mixed> */
            public function rules(): array
            {
                return [];
            }
        };

        $component = $this->getTestComponent($model);
        $component->setTestRuleKeys(['relation.field']);
        $rules = $component->callScanInheritedRules();

        $this->assertEquals(['relation.field' => 'sometimes|required|string'], $rules);
    }

    public function test_scan_inherited_rules_respects_overrides()
    {
        $model = new class extends Model
        {
            /** @return array<string, mixed> */
            public function rules(): array
            {
                return ['name' => 'required'];
            }
        };

        $component = $this->getTestComponent($model);
        $component->setTestRuleKeys(['name']);
        $rules = $component->callScanInheritedRules(['name' => 'nullable']);

        $this->assertEquals(['name' => 'nullable'], $rules);
    }

    public function test_scan_inherited_rules_throws_on_unknown_root_key()
    {
        $model = new class extends Model
        {
            /** @return array<string, mixed> */
            public function rules(): array
            {
                return [];
            }
        };

        $component = $this->getTestComponent($model);
        $component->setTestRuleKeys(['unknown']);

        $this->expectException(LivewireAutoFormException::class);
        $this->expectExceptionMessage("Rule key 'unknown' could not be resolved");
        $component->callScanInheritedRules();
    }

    public function test_scan_inherited_rules_throws_on_unknown_relation()
    {
        $model = new class extends Model
        {
            /** @return array<string, mixed> */
            public function rules(): array
            {
                return [];
            }
        };

        $component = $this->getTestComponent($model);
        $component->setTestRuleKeys(['unknown.field']);

        $this->expectException(LivewireAutoFormException::class);
        $this->expectExceptionMessage("Rule key 'unknown.field' could not be resolved");
        $component->callScanInheritedRules();
    }

    public function test_scan_inherited_rules_throws_on_unknown_relation_field()
    {
        $model = new class extends Model
        {
            /** @return array<string, mixed> */
            public function rules(): array
            {
                return [];
            }
        };

        $component = $this->getTestComponent($model);
        $component->setTestRuleKeys(['relation.unknown']);

        $this->expectException(LivewireAutoFormException::class);
        $this->expectExceptionMessage("Rule key 'relation.unknown' could not be resolved");
        $component->callScanInheritedRules();
    }

    public function test_scan_inherited_rules_returns_provided_rules_if_no_active_model()
    {
        $component = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
            }

            public function getActiveModel(): ?Model
            {
                return null;
            }

            public function callScanInheritedRules(array $rules = []): array
            {
                return $this->scanInheritedRules($rules);
            }
        };

        $rules = $component->callScanInheritedRules(['existing' => 'rule']);
        $this->assertEquals(['existing' => 'rule'], $rules);
    }
}
