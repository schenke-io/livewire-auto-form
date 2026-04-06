<?php

namespace Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use SchenkeIo\LivewireAutoForm\Data\PathInfo;
use SchenkeIo\LivewireAutoForm\Helpers\CrudProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;
use Tests\TestCase;
use Workbench\App\Models\City;
use Workbench\App\Models\Country;

class HasAutoFormTest extends TestCase
{
    private object $testClass;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testClass = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            public function resetErrorBag($key = null): void {}

            public function loadContext($context, $id, $applyState = true, $model = null): void {}
        };
    }

    public function test_set_model_throws_if_null()
    {
        // Line 51
        $this->expectException(LivewireAutoFormException::class);
        $this->expectExceptionMessage('anonymous');
        $this->testClass->setModel(null);
    }

    public function test_default_rules()
    {
        // Line 75
        $this->assertEquals([], $this->testClass->rules());
    }

    public function test_get_inherited_rules_returns_component_rules_when_model_has_no_rules()
    {
        $model = new class extends Model
        {
            protected $guarded = [];

            /** @return array<string, mixed> */
            public function rules(): array
            {
                return [];
            }
        };

        $component = new class($model) implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct($m)
            {
                $this->form = new FormCollection;
                $this->model = $m;
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            /** @return list<string> */
            public function ruleKeys(): array
            {
                return ['name'];
            }

            /** @return array<string, mixed> */
            public function rules(): array
            {
                return $this->scanInheritedRules(['name' => 'required']);
            }

            public function getActiveModel(): ?Model
            {
                return $this->model;
            }
        };

        $rules = $component->rules();
        $this->assertSame(['name' => 'required'], $rules);
    }

    public function test_get_inherited_rules_merges_and_filters()
    {
        $model = new class extends Model
        {
            protected $guarded = [];

            /** @return array<string, mixed> */
            public function rules(): array
            {
                return [
                    'name' => 'required|string',
                    'email' => 'email',
                    'ignored' => 'sometimes',
                ];
            }
        };

        $component = new class($model) implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct($m)
            {
                $this->form = new FormCollection;
                $this->model = $m;
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            /** @return list<string> */
            public function ruleKeys(): array
            {
                return [
                    'name',
                    'email',
                ];
            }

            /** @return array<string, mixed> */
            public function rules(): array
            {
                return $this->scanInheritedRules([
                    'name' => 'sometimes',
                    'email' => 'nullable',
                ]);
            }

            public function getActiveModel(): ?Model
            {
                return $this->model;
            }
        };

        $rules = $component->rules();
        // Contains model rules for fields used plus component overrides with precedence
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayNotHasKey('ignored', $rules);
        // Component overrides should take precedence
        $this->assertEquals('sometimes', $rules['name']);
        $this->assertEquals('nullable', $rules['email']);
    }

    public function test_validate_throws_validation_exception()
    {
        // Lines 111-113
        $this->testClass = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection(['name' => '']);
            }

            /** @return array<string, mixed> */
            public function rules(): array
            {
                return ['name' => 'required'];
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            public function resetErrorBag($key = null) {}
        };

        $this->expectException(ValidationException::class);
        $this->testClass->validate();
    }

    public function test_validate_only_handles_prefixed_field()
    {
        // Lines 135-139
        $this->testClass = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection(['name' => 'John']);
            }

            /** @return array<string, mixed> */
            public function rules(): array
            {
                return ['name' => 'required'];
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            public function resetErrorBag($key = null) {}
        };

        // Test with unprefixed field
        $result = $this->testClass->validateOnly('name');
        $this->assertEquals('John', $result['name']);

        // Test with prefixed field
        $result = $this->testClass->validateOnly('form.name');
        $this->assertEquals('John', $result['name']);
    }

    public function test_validate_only_throws_validation_exception()
    {
        // Line 141-142
        $this->testClass = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection(['name' => '']);
            }

            /** @return array<string, mixed> */
            public function rules(): array
            {
                return ['name' => 'required'];
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            public function resetErrorBag($key = null) {}
        };

        $this->expectException(ValidationException::class);
        $this->testClass->validateOnly('name');
    }

    public function test_save_calls_crud_processor()
    {
        $mockProcessor = $this->createMock(CrudProcessor::class);
        $mockProcessor->expects($this->once())->method('save');

        $this->testClass = new class($mockProcessor) implements \ArrayAccess
        {
            use HasAutoForm;

            public $processor;

            public function __construct($p)
            {
                $this->form = new FormCollection(['name' => 'John']);
                $this->processor = $p;
            }

            /** @return array<string, mixed> */
            public function rules(): array
            {
                return ['name' => 'required'];
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            public function resetErrorBag($key = null) {}

            protected function getCrudProcessor(): CrudProcessor
            {
                return $this->processor;
            }

            public function dispatch($event, ...$params) {}
        };

        $this->testClass->save();
    }

    public function test_cancel_reloads_context()
    {
        $this->testClass = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public $contextLoaded = false;

            public function __construct()
            {
                $this->form = new FormCollection;
                $this->form->rootModelId = 123;
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            protected function loadContext(string $context, $id, bool $preserve = true, $model = null): void
            {
                if ($context === '' && $id === 123) {
                    $this->contextLoaded = true;
                }
            }
        };

        $this->testClass->cancel();
        $this->assertTrue($this->testClass->contextLoaded);
    }

    public function test_trait_updated_auto_save_toggle()
    {
        $this->testClass->updated('form.autoSave', true);
        $this->assertTrue($this->testClass->form->autoSave);
    }

    public function test_trait_updated_ignores_meta_keys()
    {
        $this->testClass->updated('form.activeId', 456);
        $this->assertNull($this->testClass->form->get('activeId')); // should not be in items
    }

    public function test_trait_updated_throws_on_invalid_key()
    {
        $this->expectException(LivewireAutoFormException::class);
        $this->expectExceptionMessage('anonymous');
        $this->testClass->updated('form.invalid_key', 'val');
    }

    public function test_get_property_name()
    {
        $this->assertEquals('form', $this->testClass->getPropertyName());
    }

    public function test_trait_updated_dispatches_event_when_saved()
    {
        $mockProcessor = $this->createMock(CrudProcessor::class);
        $mockProcessor->expects($this->once())
            ->method('updatedForm')
            ->willReturn(['saved' => true, 'cleanValue' => 'John', 'context' => 'root', 'id' => 1]);

        $this->testClass = new class($mockProcessor) implements \ArrayAccess
        {
            use HasAutoForm;

            public $processor;

            public $dispatchedEvent = null;

            public $dispatchedParams = [];

            public function __construct($p)
            {
                $this->form = new FormCollection;
                $this->processor = $p;
            }

            /** @return array<string, mixed> */
            public function rules(): array
            {
                return ['name' => 'required'];
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            protected function getCrudProcessor(): CrudProcessor
            {
                return $this->processor;
            }

            public function dispatch($event, ...$params)
            {
                $this->dispatchedEvent = $event;
                $this->dispatchedParams = $params;
            }

            public function validateOnly($field, $rules = null, $messages = [], $attributes = [], $dataOverrides = []): array
            {
                return [];
            }
        };

        $this->testClass->updated('form.name', 'John');
        $this->assertEquals('field-updated', $this->testClass->dispatchedEvent);
        $this->assertEquals('name', $this->testClass->dispatchedParams['changed']);
    }

    public function test_resolve_model_instance()
    {
        $this->testClass->form->rootModelClass = City::class;
        $result = $this->testClass->resolveModelInstance('', null);
        $this->assertNotNull($result);
        $this->assertInstanceOf(City::class, $result);
    }

    private function getScanRulesComponent(Model $model)
    {
        return new class($model) implements \ArrayAccess
        {
            use HasAutoForm;

            private array $testRuleKeys = [];

            public function __construct(private Model $model)
            {
                $this->form = new FormCollection;
                $this->form->setRootModel(get_class($model), null);
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

            public function callScanInheritedRules(array $rules = []): array
            {
                return $this->scanInheritedRules($rules);
            }

            public function resolveModelInstance(string $context, int|string|null $id): ?Model
            {
                if ($context === 'relation') {
                    return new class extends Model
                    {
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
            public function rules(): array
            {
                return ['name' => 'required'];
            }
        };

        $component = $this->getScanRulesComponent($model);
        $component->setTestRuleKeys(['name']);
        $rules = $component->callScanInheritedRules();

        $this->assertEquals(['name' => 'sometimes|required'], $rules);
    }

    public function test_scan_inherited_rules_resolves_relation_fields()
    {
        $model = new class extends Model
        {
            public function isRelation($name): bool
            {
                return $name === 'relation';
            }

            public function rules(): array
            {
                return [];
            }
        };

        $component = $this->getScanRulesComponent($model);
        $component->setTestRuleKeys(['relation.field']);
        $rules = $component->callScanInheritedRules();

        $this->assertEquals(['relation.field' => 'sometimes|required|string'], $rules);
    }

    public function test_scan_inherited_rules_respects_overrides()
    {
        $model = new class extends Model
        {
            public function rules(): array
            {
                return ['name' => 'required'];
            }
        };

        $component = $this->getScanRulesComponent($model);
        $component->setTestRuleKeys(['name']);
        $rules = $component->callScanInheritedRules(['name' => 'nullable']);

        $this->assertEquals(['name' => 'nullable'], $rules);
    }

    public function test_scan_inherited_rules_defaults_on_unknown_root_key()
    {
        $model = new class extends Model
        {
            public function rules(): array
            {
                return [];
            }
        };

        $component = $this->getScanRulesComponent($model);
        $component->setTestRuleKeys(['unknown']);

        $rules = $component->callScanInheritedRules();
        $this->assertEquals(['unknown' => 'nullable'], $rules);
    }

    public function test_scan_inherited_rules_defaults_on_unknown_relation()
    {
        $model = new class extends Model
        {
            public function rules(): array
            {
                return [];
            }
        };

        $component = $this->getScanRulesComponent($model);
        $component->setTestRuleKeys(['unknown.field']);

        $rules = $component->callScanInheritedRules();
        $this->assertEquals(['unknown.field' => 'nullable'], $rules);
    }

    public function test_scan_inherited_rules_defaults_on_unknown_relation_field()
    {
        $model = new class extends Model
        {
            public function rules(): array
            {
                return [];
            }
        };

        $component = $this->getScanRulesComponent($model);
        $component->setTestRuleKeys(['relation.unknown']);

        $rules = $component->callScanInheritedRules();
        $this->assertEquals(['relation.unknown' => 'nullable'], $rules);
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

    private function callGetRulesFromModel($traitInstance, Model $model): array
    {
        $reflection = new \ReflectionClass($traitInstance);
        $method = $reflection->getMethod('getRulesFromModel');
        $method->setAccessible(true);

        return $method->invoke($traitInstance, $model);
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
        $instance = $this->getScanRulesComponent($model);
        $rules = $this->callGetRulesFromModel($instance, $model);
        $this->assertEquals(['name' => 'required'], $rules);
    }

    public function test_get_rules_from_model_uses_property()
    {
        $model = new class extends Model
        {
            public array $rules = ['email' => 'required|email'];
        };
        $instance = $this->getScanRulesComponent($model);
        $rules = $this->callGetRulesFromModel($instance, $model);
        $this->assertEquals(['email' => 'required|email'], $rules);
    }

    public function test_get_rules_from_model_returns_empty_array_by_default()
    {
        $model = new class extends Model {};
        $instance = $this->getScanRulesComponent($model);
        $rules = $this->callGetRulesFromModel($instance, $model);
        $this->assertEquals([], $rules);
    }

    public function test_scan_inherited_rules_returns_rules_when_no_model_found()
    {
        $instance = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public array $testRuleKeys = [];

            public function __construct()
            {
                $this->form = new FormCollection;
                $this->form->rootModelClass = Country::class;
            }

            public function ruleKeys(): array
            {
                return $this->testRuleKeys;
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

        $instance->testRuleKeys = ['name'];
        $rules = ['name' => 'required'];
        $result = $instance->callScanInheritedRules($rules);
        $this->assertEquals($rules, $result);
    }

    public function test_scan_inherited_rules_handles_id_key()
    {
        $model = new class extends Model {};
        $instance = $this->getScanRulesComponent($model);
        $instance->setTestRuleKeys(['id']);
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
        $instance = $this->getScanRulesComponent($model);
        $instance->setTestRuleKeys(['tags']);
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
        $instance = $this->getScanRulesComponent($model);
        $instance->setTestRuleKeys(['tags']);
        $result = $instance->callScanInheritedRules();
        $this->assertEquals(['tags' => ['sometimes', 'required', 'array']], $result);
    }

    public function test_sync_to_draft_state_flattens_form_data()
    {
        $component = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
            }

            public function getPropertyName(): string
            {
                return 'form';
            }
        };

        $component->form->setNested('name', 'John');
        $component->form->setNested('address.city', 'New York');

        $method = new \ReflectionMethod($component, 'syncToDraftState');
        $method->setAccessible(true);
        $method->invoke($component);

        $this->assertEquals([
            'name' => 'John',
            'address.city' => 'New York',
        ], $component->draftState);
    }

    public function test_updated_hook_does_not_sync_draft_state_to_form_immediately()
    {
        $component = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
            }

            public function getPropertyName(): string
            {
                return 'form';
            }
        };

        $component->draftState['name'] = 'Jane';
        $component->updated('draftState.name', 'Jane');

        $this->assertNotEquals('Jane', $component->form->get('name'));
    }

    public function test_validate_syncs_draft_state_to_form()
    {
        $component = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
                $this->draftState = ['name' => 'Alice', 'address.city' => 'Paris'];
            }

            public function rules(): array
            {
                return ['name' => 'required', 'address.city' => 'required'];
            }

            public function getPropertyName(): string
            {
                return 'form';
            }

            public function resetErrorBag($key = null): void {}
        };

        $component->validate();

        $this->assertEquals('Alice', $component->form->get('name'));
        $this->assertEquals('Paris', data_get($component->form->all(), 'address.city'));
    }

    public function test_offset_set_syncs_to_draft_state()
    {
        $component = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
            }

            public function getPropertyName(): string
            {
                return 'form';
            }
        };

        $component['name'] = 'Bob';
        $this->assertEquals('Bob', $component->draftState['name']);
    }

    public function test_rules_caching()
    {
        $component = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public int $scanned = 0;

            protected function scanInheritedRules(array $rules = []): array
            {
                $this->scanned++;

                return ['a' => 'b'];
            }
        };
        $this->assertEquals(['a' => 'b'], $component->rules());
        $this->assertEquals(['a' => 'b'], $component->rules());
        $this->assertEquals(1, $component->scanned);
    }

    public function test_boot_has_auto_form_handles_throwable()
    {
        $component = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
                $this->form->rootModelClass = 'SomeClass';
            }

            public function getModel(): ?Model
            {
                return new class extends Model
                {
                    public function isRelation($name): bool
                    {
                        return true;
                    }

                    public function rules(): array
                    {
                        return ['field' => 'string'];
                    }
                };
            }

            public function ruleKeys(): array
            {
                return ['field'];
            }

            public function getPathResolver(): PathResolver
            {
                return new class extends PathResolver
                {
                    public function resolve(Model $model, string $path): PathInfo
                    {
                        throw new \Exception('Test Exception');
                    }
                };
            }
        };
        $component->bootHasAutoForm();
        $this->assertTrue(true); // Should not throw
    }

    public function test_sync_from_draft_state_with_key()
    {
        $component = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
            }

            public function callSyncFromDraftState($key, $value)
            {
                $this->syncFromDraftState($key, $value);
            }
        };
        $component->callSyncFromDraftState('key', 'value');
        $this->assertEquals('value', $component->form->get('key'));
    }

    public function test_scan_inherited_rules_returns_rules_when_no_root_model()
    {
        $component = new class implements \ArrayAccess
        {
            use HasAutoForm;

            public function __construct()
            {
                $this->form = new FormCollection;
                $this->form->rootModelClass = 'SomeClass';
            }

            public function getModel(): ?Model
            {
                return null;
            }

            public function ruleKeys(): array
            {
                return ['key'];
            }

            public function callScanInheritedRules($rules)
            {
                return $this->scanInheritedRules($rules);
            }
        };
        $this->assertEquals(['over' => 'rule'], $component->callScanInheritedRules(['over' => 'rule']));
    }
}
