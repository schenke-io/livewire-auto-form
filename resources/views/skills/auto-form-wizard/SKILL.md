---
name: auto-form-wizard
description: Create multi-step form workflows with per-step validation.
---

# Livewire Auto Form — Wizard

## When to use
Use this skill when a large form is best split into a sequence of steps (e.g., a registration flow, a multi-part application, or a setup wizard). Each step is a separate Blade partial. The user can only advance after all fields on the current step pass validation. Data is only persisted on the final step.

## Key concepts

| Property / Method | Description |
|-------------------|-------------|
| `$structure` | Maps step view names to the list of fields belonging to that step |
| `$stepViewPrefix` | Blade view namespace prefix prepended to each step key |
| `$currentStepIndex` | Zero-based index of the active step (public, Livewire-tracked) |
| `submit()` | Bind to `wire:submit`. Calls `next()` on intermediate steps or `save()` on the last step |
| `previous()` | Navigate back without validating the current step |
| `isStepActive(int $index)` | Returns `true` if the given index is the current step |
| `isLastStep()` | Returns `true` when on the final step |
| `getSteps()` | Returns the ordered list of step view name keys |
| `validateStructure()` | Throws if any `ruleKeys()` key is missing from a step, or any step field has no rule |

## Component

```php
use SchenkeIo\LivewireAutoForm\AutoWizardForm;
use App\Models\Application;

class ApplicationWizard extends AutoWizardForm
{
    public string $stepViewPrefix = 'livewire.application.steps.';

    public array $structure = [
        'identity' => ['first_name', 'last_name'],
        'contact'  => ['email', 'phone'],
        'review'   => [],   // summary step — no fields to validate before advancing
    ];

    public function mount(Application $application): void
    {
        $this->setModel($application);
        $this->validateStructure(); // always call after setModel(); throws early on misconfiguration
    }

    public function ruleKeys(): array
    {
        return ['first_name', 'last_name', 'email', 'phone'];
    }
}
```

> **Important**: Always call `$this->validateStructure()` in `mount()`. It verifies that every `ruleKeys()` key is assigned to a step and every step field has a matching rule. Without this call, misconfiguration is only caught at the final `save()`.

## Main Blade view

```blade
<form wire:submit="submit">
    @foreach($this->getSteps() as $index => $step)
        <div @style(['display:none' => !$this->isStepActive($index)])>
            @include($this->stepViewPrefix . $step, ['isActive' => $this->isStepActive($index)])
        </div>
    @endforeach

    <div>
        @if($currentStepIndex > 0)
            <button type="button" wire:click="previous">Back</button>
        @endif

        <button type="submit">
            {{ $this->isLastStep() ? 'Submit' : 'Continue' }}
        </button>
    </div>
</form>
```

## Step partial (e.g., `livewire/application/steps/identity.blade.php`)

Each step partial receives `$isActive` (bool). Use it to toggle between an editable form and a read-only summary for completed steps.

```blade
@if($isActive)
    <input type="text" wire:model.blur="form.first_name" placeholder="First name">
    @error('form.first_name') <span>{{ $message }}</span> @enderror

    <input type="text" wire:model.blur="form.last_name" placeholder="Last name">
    @error('form.last_name') <span>{{ $message }}</span> @enderror
@else
    <p>{{ $form->first_name }} {{ $form->last_name }}</p>
@endif
```

## Constraints

| Constraint | Detail |
|------------|--------|
| `autoSave` prohibited | Enabling auto-save in a wizard throws `LivewireAutoFormException` |
| Field completeness | Every `ruleKeys()` key must appear in exactly one step |
| Step completeness | Every step field must have a corresponding rule |
| Persistence timing | Data is written to the database only when `save()` is called on the final step |