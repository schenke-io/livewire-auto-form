<?php

namespace Tests\Feature\Livewire\Components;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\AutoForm;

class FlexibleTestComponent extends AutoForm
{
    /** @var array<string, mixed> */
    public array $customRules = [];

    /**
     * @var mixed Used to override resolveModelInstance
     */
    public $mockModel = null;

    public function mount(?object $model = null, array $rules = [], ?object $mockModel = null): void
    {
        $this->customRules = $rules;
        $this->mockModel = $mockModel;
        if ($model && $model instanceof Model) {
            $this->setModel($model);
        }
    }

    public function rules(): array
    {
        return $this->customRules ?: ['name' => 'nullable'];
    }

    public function ensureRelationAllowed(string $relation): void
    {
        $reflection = new \ReflectionMethod(parent::class, 'ensureRelationAllowed');
        $reflection->setAccessible(true);
        $reflection->invoke($this, $relation);
    }

    public function getRules(): array
    {
        $rules = $this->rules();
        $prefixedRules = [];
        foreach ($rules as $key => $rule) {
            $prefixedRules['form.'.$key] = $rule;
        }

        return $prefixedRules;
    }

    public bool $useRealValidation = false;

    /**
     * @param  ?array<string, mixed>  $rules
     * @param  array<string, mixed>  $messages
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function validate($rules = null, $messages = [], $attributes = []): array
    {
        if ($this->useRealValidation) {
            return parent::validate($rules, $messages, $attributes);
        }

        // For testing purposes, we often want to bypass real validation
        return ['form' => $this->form->all()];
    }

    /**
     * @param  string  $field
     * @param  ?array<string, mixed>  $rules
     * @param  array<string, mixed>  $messages
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $dataOverrides
     * @return array<string, mixed>
     */
    public function validateOnly($field, $rules = null, $messages = [], $attributes = [], $dataOverrides = []): array
    {
        if ($this->useRealValidation) {
            return parent::validateOnly($field, $rules, $messages, $attributes, $dataOverrides);
        }

        // No-op for testing
        return [$field => data_get($this, $field)];
    }

    public function deleteRootModel(): void
    {
        $this->getModel()?->delete();
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
