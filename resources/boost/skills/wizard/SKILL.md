---
name: livewire-auto-form-wizard
description: Create multi-step form workflows with per-step validation.
---

# Livewire Auto Form Wizard

## When to use this skill
Use this skill when you have a large form that is best presented as a sequence of steps (e.g., a registration process, a complex multi-part application, or a setup wizard). It allows for better user experience by breaking down the data entry process into manageable units.

## Features
- **Sequential Navigation**: Built-in support for moving forward (`next()`) and backward (`previous()`) through steps.
- **Per-Step Validation**: Only fields assigned to the current step are validated before the user can proceed to the next step.
- **Unified Save**: Data is only persisted to the database upon completion of the final step, ensuring data integrity.
- **Structure Enforcement**: Automatically verifies that every field defined in your `rules()` is assigned to a specific step.

## Implementation Example

### Wizard Component
Extend the `AutoWizardForm` class and define the `$structure` of your steps.

```php
use SchenkeIo\LivewireAutoForm\AutoWizardForm;
use App\Models\Application;

class ApplicationWizard extends AutoWizardForm
{
    /**
     * Define the steps (view names) and their associated fields.
     */
    public array $structure = [
        'identity' => ['first_name', 'last_name'],
        'contact'  => ['email', 'phone'],
        'review'   => []
    ];

    /**
     * Prefix for the step Blade views.
     */
    public string $stepViewPrefix = 'livewire.application.steps.';

    public function mount(Application $application)
    {
        $this->setModel($application);
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => 'required|email',
            'phone'      => 'required|string',
        ];
    }
}
```

### Main Blade View
Use `getSteps()` to iterate through and include your step partials.

```html
<form wire:submit.prevent="submit">
    @foreach($this->getSteps() as $index => $step)
        <div x-show="{{ $this->isStepActive($index) ? 'true' : 'false' }}">
            @include($this->stepViewPrefix . $step, [
                'isActive' => $this->isStepActive($index)
            ])
        </div>
    @endforeach

    <div class="navigation-controls">
        @if($currentStepIndex > 0)
            <button type="button" wire:click="previous">Back</button>
        @endif

        <button type="submit">
            {{ $this->isLastStep() ? 'Finish and Submit' : 'Continue' }}
        </button>
    </div>
</form>
```

## Important Constraints
- **`autoSave` Prohibited**: Wizards are inherently batch-oriented. Enabling `autoSave` in a wizard context will trigger an exception.
- **Field Completeness**: All fields defined in the `rules()` method must be assigned to exactly one step in the `$structure` array.
- **Step Validation**: The `next()` method (called automatically by `submit()` if not on the last step) performs validation only for the current step's fields.
