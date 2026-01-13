<?php

namespace Tests\Feature\Livewire\Components;

use SchenkeIo\LivewireAutoForm\LivewireAutoFormComponent;

class FlexibleTestComponent extends LivewireAutoFormComponent
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
            parent::mount($model);
        }
    }

    public function rules(): array
    {
        return $this->customRules ?: ['name' => 'nullable'];
    }

    public function resolveModelInstance(string $context, int|string|null $id): ?\Illuminate\Database\Eloquent\Model
    {
        if ($this->mockModel && $context !== '' && $this->mockModel instanceof \Illuminate\Database\Eloquent\Model) {
            return $this->mockModel;
        }

        return parent::resolveModelInstance($context, $id);
    }

    public function validate($rules = null, $messages = [], $attributes = [])
    {
        // For testing purposes, we often want to bypass real validation
        return ['form' => $this->form->all()];
    }

    public function validateOnly($field, $rules = null, $messages = [], $attributes = [], $dataOverrides = [])
    {
        // No-op for testing
        return [$field => data_get($this, $field)];
    }

    public function deleteRootModel()
    {
        $this->resolveModelInstance('', $this->form->rootModelId)?->delete();
    }

    public function render()
    {
        return '<div></div>';
    }
}
