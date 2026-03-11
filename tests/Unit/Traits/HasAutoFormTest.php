<?php

namespace Tests\Unit\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use SchenkeIo\LivewireAutoForm\Helpers\CrudProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;
use Tests\TestCase;
use Workbench\App\Models\City;

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
}
