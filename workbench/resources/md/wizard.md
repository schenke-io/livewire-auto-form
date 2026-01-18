# Multi-Step Wizards

The `AutoWizardForm` extends the core `AutoForm` functionality to support complex, multi-step workflows with ease. It handles step navigation, per-step validation, and a unified submission flow.

### Magic Features

- **Unified `submit()` Flow**: A single `submit()` method handles both navigating to the next step (with validation) and final persistence when the last step is reached.
- **Progress Integrity**: Before final saving, the wizard performs an integrity check to ensure that all fields defined in your `rules()` were actually present in at least one of the steps. If any field is missing, a `LivewireAutoFormException` is thrown.

### Configuration

To create a wizard, extend `AutoWizardForm` and configure the following:

- **`$structure`**: A map of Blade view names to field names (e.g., `['step-one' => ['field1', 'field2'], 'step-two' => ['field3']]`).
- **`$stepViewPrefix`**: A prefix for the views defined in `$structure` (e.g., `livewire.user-wizard-steps.`).
- **`rules()`**: Define your validation rules.
- **`mount()`**: Initialize the model with `setModel($model)`.

### API Reference

| Method | Description |
| --- | --- |
| `submit()` | Handles both transitions and final saving. Calls `next()` or `save()`. |
| `next()` | Validates current step's fields and moves forward. |
| `previous()` | Moves to the previous step. |
| `isLastStep()` | Returns `true` if on the final step. |
| `getSteps()` | Returns the list of defined step views. |
| `isStepActive(int $index)` | Checks if a step is currently active. |

### Full Example

**1. The Livewire Component:**

```php
namespace App\Livewire;

use App\Models\User;
use SchenkeIo\LivewireAutoForm\AutoWizardForm;

class UserWizard extends AutoWizardForm
{
    public array $structure = [
        'profile' => ['name', 'email'],
        'address' => ['city'],
        'preferences' => ['marketing_opt_in']
    ];
    
    public string $stepViewPrefix = 'livewire.user-wizard-steps.';

    public function mount(User $user)
    {
        $this->setModel($user);
        parent::mount();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'city' => 'required',
            'marketing_opt_in' => 'boolean',
        ];
    }
}
```

**2. The Main Blade View (`user-wizard.blade.php`):**

```html
<form wire:submit.prevent="submit">
    @foreach($this->getSteps() as $index => $step)
        @include('livewire.user-wizard-steps.' . $step, [
            'isActive' => $this->isStepActive($index)
        ])
    @endforeach

    <div class="actions">
        @if($currentStepIndex > 0)
            <button type="button" wire:click="previous">Previous</button>
        @endif

        <button type="submit">
            {{ $this->isLastStep() ? 'Finish' : 'Next' }}
        </button>
    </div>
</form>
```

**3. Individual Step View (`profile.blade.php`):**

```html
<div class="{{ $isActive ? 'block' : 'hidden' }}">
    <input type="text" wire:model="form.name">
    <input type="email" wire:model="form.email">
</div>
```
