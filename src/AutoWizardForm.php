<?php

namespace SchenkeIo\LivewireAutoForm;

use Livewire\Component;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;

/**
 * AutoWizardForm extends the base form logic to support multi-step workflows.
 *
 * @implements \ArrayAccess<string, mixed>
 */
abstract class AutoWizardForm extends Component implements \ArrayAccess
{
    use HasAutoForm;

    /**
     * @throws LivewireAutoFormException
     */
    public function mount(): void
    {
        $this->validateStructure();
    }

    /**
     * The prefix for the blade views defined in $structure.
     *
     * For example, if your steps are in `resources/views/livewire/wizard/`,
     * the prefix would be `livewire.wizard.`.
     */
    public string $stepViewPrefix = '';

    /**
     * Map of blade view names to field names.
     *
     * This defines the sequence of steps and the fields that belong to each.
     *
     * Example: ['step-one' => ['name', 'email'], 'step-two' => ['address']]
     *
     * @var array<string, array<int, string>>
     */
    public array $structure = [];

    /**
     * The zero-based index of the current active step.
     */
    public int $currentStepIndex = 0;

    /**
     * Provides the list of steps to the component.
     *
     * @return array<int, string> The list of step view names.
     */
    public function getSteps(): array
    {
        return array_keys($this->structure);
    }

    /**
     * Returns true if the provided step index is the one currently being edited.
     *
     * @param  int  $index  The zero-based index of the step to check.
     * @return bool True if the step is active, false otherwise.
     */
    public function isStepActive(int $index): bool
    {
        return $this->currentStepIndex === $index;
    }

    /**
     * Returns true if currently on the last step of the wizard.
     *
     * @return bool True if on the last step, false otherwise.
     */
    public function isLastStep(): bool
    {
        return $this->currentStepIndex === count($this->getSteps()) - 1;
    }

    /**
     * Handles navigation and submission in the wizard.
     *
     * This method is intended to be called by `wire:submit` on the wizard's form element.
     * It intelligently decides whether to proceed to the next step or perform the final save.
     */
    public function submit(): void
    {
        if ($this->isLastStep()) {
            $this->save();
        } else {
            $this->next();
        }
    }

    /**
     * Navigates to the next step after validating current form form.
     *
     * This method ensures that the user cannot proceed to the next step
     * until all fields required by the current step are valid. It uses
     * filtered rules based on the current step's fields.
     *
     * Tip: If your Eloquent models expose `rules()`, you can leverage
     * `scanInheritedRules()` in your component's `rules()` implementation
     * to avoid duplicating validation logic across steps.
     */
    public function next(): void
    {
        if ($this->currentStepIndex < count($this->getSteps()) - 1) {
            $stepFields = $this->getStepFields($this->currentStepIndex);

            if ($stepFields) {
                $rules = $this->rules();
                $filteredRules = array_intersect_key($rules, array_flip($stepFields));
                $this->validate($filteredRules);
            }

            $this->currentStepIndex++;
        }
    }

    /**
     * Returns the list of fields to be validated for a specific step.
     *
     * @param  int  $index  The index of the step.
     * @return array<int, string> The list of field names for the step.
     */
    public function getStepFields(int $index): array
    {
        $steps = $this->getSteps();
        if (! isset($steps[$index])) {
            return [];
        }

        $viewName = $steps[$index];

        return $this->structure[$viewName] ?? [];
    }

    /**
     * Navigates to the previous step without requiring validation.
     *
     * Useful for allowing users to go back and correct previous entries
     * without being blocked by current step validation errors.
     */
    public function previous(): void
    {
        if ($this->currentStepIndex > 0) {
            $this->currentStepIndex--;
        }
    }

    /**
     * Final submission of the wizard.
     *
     * Performs a full validation of all rules and verifies the wizard structure
     * before delegating to the trait's save method for persistence.
     *
     * @throws LivewireAutoFormException
     */
    public function save(): void
    {
        $this->validate();
        $this->validateStructure();

        $this->traitSave();
    }

    /**
     * Validates that the wizard structure matches the defined rules.
     *
     * This is a critical diagnostic method that ensures the wizard is
     * correctly configured. It checks for:
     * - Rule coverage: All fields in `rules()` must belong to a step.
     * - Rule existence: All fields in steps must have a corresponding rule.
     *
     * @throws LivewireAutoFormException If the structure is inconsistent with rules.
     */
    protected function validateStructure(): void
    {
        $ruleFields = array_keys($this->rules());
        $allStepFields = [];
        $steps = $this->getSteps();
        foreach (array_keys($steps) as $index) {
            $allStepFields = array_merge($allStepFields, $this->getStepFields($index));
        }
        $allStepFields = array_unique($allStepFields);

        $missingFields = array_diff($ruleFields, $allStepFields);
        if (! empty($missingFields)) {
            throw LivewireAutoFormException::fieldsMissingInSteps($missingFields, static::class);
        }

        $invalidFields = array_diff($allStepFields, $ruleFields);
        if (! empty($invalidFields)) {
            throw LivewireAutoFormException::fieldKeyNotDefinedInRules(implode(', ', $invalidFields), static::class);
        }
    }

    /**
     * Handles property updates in the wizard.
     *
     * This override ensures that `autoSave` cannot be enabled for wizards,
     * as wizards are inherently batch-oriented processes that only persist
     * form upon final completion.
     *
     * @param  string  $name  The name of the updated property.
     * @param  mixed  $value  The new value.
     *
     * @throws LivewireAutoFormException If auto-save is attempted.
     */
    public function updated(string $name, mixed $value): void
    {
        $propertyName = $this->getPropertyName();
        if ($name === FormCollection::getPrefixedField('autoSave', $propertyName) && $value) {
            throw LivewireAutoFormException::autoSaveNotAllowedInWizard(static::class);
        }
        $this->traitUpdated($name, $value);
    }
}
