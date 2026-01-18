<?php

namespace Workbench\App\Livewire\Forms;

use SchenkeIo\LivewireAutoForm\AutoWizardForm;
use Workbench\App\Models\User;

/**
 * UserWizardForm Component
 *
 * This component implements a multi-step form (Wizard) for user management.
 * It uses the AutoWizardForm to manage four sequential steps:
 * 1. Account: Basic user credentials and settings.
 * 2. Address: Physical location and shipping information.
 * 3. Contact: Email, phone and communication preferences.
 * 4. Options: Additional user-specific settings and opt-ins.
 *
 * It demonstrates explicit field structure definition and
 * per-step validation before proceeding to the next step.
 */
class UserWizardForm extends AutoWizardForm
{
    public string $stepViewPrefix = 'livewire.user-wizard-steps.';

    public array $structure = [
        'account' => ['name', 'email'],
        'address' => ['address', 'zip_code', 'city'],
        'contact' => ['phone'],
        'options' => ['marketing_opt_in'],
    ];

    public function mount(): void
    {
        $this->setModel(new User);
        parent::mount();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'address' => 'required|string|max:255',
            'zip_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'marketing_opt_in' => 'boolean',
        ];
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Support\Htmlable
    {
        return view('livewire.user-wizard')
            ->layout('layouts.app');
    }
}
