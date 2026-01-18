<?php

namespace Tests\Feature\Livewire\Components;

use SchenkeIo\LivewireAutoForm\AutoForm;

class FlexibleTestComponent extends AutoForm
{
    public array $customRules = [];

    /**
     * @var mixed Used to override resolveModelInstance
     */
    public $mockModel = null;

    public function mount($model = null, array $rules = [], $mockModel = null): void
    {
        $this->customRules = $rules;
        $this->mockModel = $mockModel;
        if ($model && $model instanceof \Illuminate\Database\Eloquent\Model) {
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

    public function validate($rules = null, $messages = [], $attributes = []): array
    {
        if ($this->useRealValidation) {
            return parent::validate($rules, $messages, $attributes);
        }

        // For testing purposes, we often want to bypass real validation
        return ['form' => $this->form->all()];
    }

    public function validateOnly($field, $rules = null, $messages = [], $attributes = [], $dataOverrides = []): array
    {
        if ($this->useRealValidation) {
            return parent::validateOnly($field, $rules, $messages, $attributes, $dataOverrides);
        }

        // No-op for testing
        return [$field => data_get($this, $field)];
    }

    public function deleteRootModel()
    {
        $this->getModel()?->delete();
    }

    public function render()
    {
        return '<div></div>';
    }
}
