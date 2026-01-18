<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\Helpers\ContextManager;
use SchenkeIo\LivewireAutoForm\Helpers\CrudProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;

/**
 * HasAutoForm is the core engine that powers the Livewire Auto Form package.
 *
 * This trait is designed to be used within Livewire Components (like `AutoForm` and `AutoWizardForm`).
 * It manages the complex interplay between Eloquent models, their relationships, and the
 * Livewire component state by providing a centralized form buffer (`FormCollection`).
 *
 * Key Responsibilities:
 * - **State Management**: Orchestrates a single, nested form buffer that mirrors Eloquent model structures.
 * - **Context Switching**: Enables seamless transitions between editing a root model and its related
 *   records (BelongsTo, HasMany, etc.) using a "Single Buffer" approach.
 * - **Validation**: Integrates with Livewire's validation, automatically scoping rules to the
 *   active context and prefixing error messages.
 * - **Persistence**: Handles the creation and updating of models and relationships, supporting
 *   both explicit `save` calls and real-time `auto-save` functionality.
 * - **Option Resolution**: Provides unified methods for fetching dropdown/select options from
 *   Models, Enums, or custom providers.
 * - **Developer API**: Exposes `ArrayAccess` and magic methods to allow the Component to
 *   be used directly in Blade templates (e.g., `wire:model="form.field"`).
 */
trait HasAutoForm
{
    use HandlesFormState, HandlesOptions, HandlesRelations;

    /**
     * Initializes the form state with a root Eloquent model.
     *
     * This method is typically called in the component's `mount()` method.
     * It sets the root model for the form, establishes the root context,
     * and loads the model's form into the form buffer.
     *
     * @param  Model|null  $model  The Eloquent model instance.
     *
     * @throws LivewireAutoFormException If the model is null or any rule keys are forbidden.
     */
    public function setModel(?Model $model): void
    {
        if ($model === null) {
            throw LivewireAutoFormException::rootModelRequired(static::class);
        }

        foreach (array_keys($this->rules()) as $key) {
            if ($key === FormCollection::SYSTEM_KEY) {
                throw LivewireAutoFormException::forbiddenKey($key, static::class);
            }
        }

        $this->form->autoSave = $this->autoSave;
        $class = $model::class;
        $id = $model->exists ? $model->getKey() : null;
        $this->form->setRootModel($class, $id);

        $this->loadContext('', $id, true, $model);
    }

    /**
     * Retrieves validation rules defined in the parent Livewire component.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Validates the form using the parent component's validator.
     *
     * This override is necessary to support dynamic properties in the form buffer.
     * It ensures that errors are correctly prefixed with the form's property name,
     * allowing standard Livewire error display to function.
     *
     * @param  array<string, mixed>|null  $rules  Optional rules to override defaults.
     * @param  array<string, string>  $messages  Optional custom error messages.
     * @param  array<string, string>  $attributes  Optional custom attribute names.
     * @return array<string, mixed> The validated form.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate($rules = null, $messages = [], $attributes = []): array
    {
        $rules = $rules ?? $this->rules();
        $propertyName = $this->getPropertyName();
        $prefixedRules = [];
        foreach ($rules as $key => $value) {
            $prefixedKey = str_starts_with($key, $propertyName.'.') ? $key : $propertyName.'.'.$key;
            $prefixedRules[$prefixedKey] = $value;
        }

        try {
            $data = [$propertyName => $this->form->all()];
            $validated = validator($data, $prefixedRules, $messages, $attributes)->validate();

            foreach (array_keys($prefixedRules) as $key) {
                $this->resetErrorBag($key);
            }

            return $validated[$propertyName] ?? [];
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Validates a single field.
     *
     * Useful for real-time validation feedback during user input.
     *
     * @param  string  $field  The name of the field to validate.
     * @param  array<string, mixed>|null  $rules  Optional rules.
     * @param  array<string, string>  $messages  Optional custom messages.
     * @param  array<string, string>  $attributes  Optional custom attributes.
     * @param  array<string, mixed>  $dataOverrides  Optional form to merge.
     * @return array<string, mixed> The validated form.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateOnly($field, $rules = null, $messages = [], $attributes = [], $dataOverrides = []): array
    {
        $rules = $rules ?? $this->rules();
        $propertyName = $this->getPropertyName();

        $prefixedField = str_starts_with($field, $propertyName.'.') ? $field : $propertyName.'.'.$field;
        $fieldInRules = str_starts_with($field, $propertyName.'.') ? substr($field, strlen($propertyName) + 1) : $field;

        $rule = $rules[$field] ?? $rules[$prefixedField] ?? $rules[$fieldInRules] ?? 'nullable';
        $singleRule = [$prefixedField => $rule];

        try {
            $validated = validator([$propertyName => $this->form->all()], $singleRule, $messages, $attributes)->validate();

            return $validated[$propertyName] ?? [];
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }
    }

    /**
     * Validates and persists the current buffer form.
     *
     * This performs a full validation of all rules and then uses the
     * `CrudProcessor` to persist changes for the active context.
     */
    public function save(): void
    {
        $this->validate();

        $this->traitSave();
    }

    /**
     * Internal save logic without full validation.
     */
    protected function traitSave(): void
    {
        $this->getCrudProcessor()->save($this->form->all());

        $this->getComponent()->dispatch('saved',
            context: $this->form->getActiveContext(),
            id: $this->form->getActiveId()
        );

        if ($this->form->getActiveContext() !== '') {
            $this->cancel();
        }
    }

    /**
     * Reverts the buffer to the root model state.
     *
     * Discards any unsaved changes in the current context and switches
     * back to the main model editing view.
     */
    public function cancel(): void
    {
        $this->loadContext('', $this->form->getRootModelId(), false);
    }

    /**
     * Synchronizes the form buffer with the latest form from the database.
     *
     * Useful if the underlying model has been changed externally or
     * needs to be refreshed.
     *
     * @param  Model  $model  The model instance to reload.
     */
    public function reloadModel(Model $model): void
    {
        $this->loadContext('', $model->getKey());
    }

    // =========================================================================
    // Lifecycle Hooks
    // =========================================================================

    /**
     * Handles property updates and triggers auto-save logic if enabled.
     *
     * This hook is automatically called by Livewire when a property on the
     * component is updated via `wire:model`. It manages the routing of
     * updates to the buffer and, if `autoSave` is on, to the database.
     *
     * @param  string  $name  The full name of the updated property (e.g. 'form.name').
     * @param  mixed  $value  The new value.
     *
     * @throws LivewireAutoFormException
     */
    public function updated(string $name, mixed $value): void
    {
        $this->traitUpdated($name, $value);
    }

    /**
     * Internal updated logic.
     *
     * @throws LivewireAutoFormException
     */
    protected function traitUpdated(string $name, mixed $value): void
    {
        $propertyName = $this->getPropertyName();

        if ($name === "$propertyName.autoSave") {
            $this->form->setAutoSave((bool) $value);

            return;
        }

        if (str_starts_with($name, "$propertyName.")) {
            $key = substr($name, strlen($propertyName) + 1);

            if ($key === '' || $key === $propertyName || in_array($key, ['activeContext', 'activeId', 'rootModelClass', 'rootModelId', 'nullables', 'autoSave'])) {
                return;
            }

            if (! array_key_exists((string) $key, $this->rules())) {
                throw LivewireAutoFormException::fieldKeyNotDefinedInRules((string) $key, static::class);
            }

            $result = $this->getCrudProcessor()->updatedForm($key, $value, $this->rules());

            $this->form->setNested($key, $result['cleanValue']);

            if ($result['saved']) {
                $this->validateOnly($key);

                $this->getComponent()->dispatch('field-updated',
                    changed: $key,
                    context: $result['context'] ?? '',
                    id: $result['id'] ?? null
                );
            }
        }
    }

    /**
     * Populates the FormCollection based on the active model or relationship.
     */
    protected function loadContext(string $context, string|int|null $id, bool $preserve = true, ?Model $model = null): void
    {
        $rules = $this->rules();
        $processor = new DataProcessor;

        $this->form->setNullables($processor->findNullables($rules));

        (new ContextManager($this->form, new ModelResolver, $processor))
            ->loadContext($context, $id, $rules, $preserve, $model);
    }

    /**
     * Returns the root model instance with current form form applied.
     */
    public function getModel(): ?Model
    {
        return (new ModelResolver)->resolve($this->form, '', $this->form->getRootModelId());
    }

    /**
     * Returns the model instance for the current active context (root or relation)
     * with current form form applied.
     */
    public function getActiveModel(): ?Model
    {
        if ($this->form->getActiveContext()) {
            return (new ModelResolver)->resolve($this->form, $this->form->getActiveContext(), $this->form->getActiveId());
        }

        return null;
    }

    /**
     * Internal factory for the CrudProcessor.
     */
    protected function getCrudProcessor(): CrudProcessor
    {
        return new CrudProcessor($this->form, new ModelResolver, new DataProcessor);
    }

    /**
     * Resolves a model instance.
     */
    public function resolveModelInstance(string $context, int|string|null $id): ?Model
    {
        return (new ModelResolver)->resolve($this->form, $context, $id);
    }

    /**
     * Prevents losing unsaved changes if auto-save is disabled.
     *
     * Dispatches a 'confirm-discard-changes' event if there's form in the buffer.
     */
    protected function guardDirtyBuffer(): void
    {
        if (! $this->autoSave && ! empty($this->form->toArray())) {
            $this->getComponent()->dispatch('confirm-discard-changes');
        }
    }

    /**
     * Returns the parent component (which is this instance).
     */
    public function getComponent(): mixed
    {
        return $this;
    }

    /**
     * Returns the property name prefix for form binding.
     */
    public function getPropertyName(): string
    {
        return 'form';
    }
}
