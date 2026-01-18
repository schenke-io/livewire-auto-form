<?php

namespace Tests\Feature\Livewire\Components;

use SchenkeIo\LivewireAutoForm\AutoWizardForm;

class TestWizard extends AutoWizardForm
{
    public array $customRules = [];

    public function mount($model = null, array $rules = [], array $structure = [], string $stepViewPrefix = ''): void
    {
        $this->customRules = $rules;
        $this->structure = $structure;
        $this->stepViewPrefix = $stepViewPrefix;
        if ($model) {
            $this->setModel($model);
        }
        parent::mount();
    }

    public function rules(): array
    {
        return $this->customRules;
    }

    public function render()
    {
        return '<div></div>';
    }
}
