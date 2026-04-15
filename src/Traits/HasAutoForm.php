<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use SchenkeIo\LivewireAutoForm\Helpers\ContextManager;
use SchenkeIo\LivewireAutoForm\Helpers\CrudProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;
use SchenkeIo\LivewireAutoForm\Helpers\PathResolver;

/**
 * HasAutoForm is the core engine that powers the Livewire Auto Form package.
 *
 * @method void resetErrorBag(string $key = null)
 * @method mixed dispatch(string $event, ...$params)
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
     * @var array<string, mixed>
     */
    protected array $cachedRules = [];

    protected ?ModelResolver $modelResolver = null;

    protected ?PathResolver $pathResolver = null;

    /**
     * Lazy-loads the PathResolver instance.
     */
    public function getPathResolver(): PathResolver
    {
        return $this->pathResolver ??= new PathResolver;
    }

    /**
     * Returns a centralized instance of the ModelResolver.
     */
    protected function getModelResolver(): ModelResolver
    {
        return $this->modelResolver ??= new ModelResolver;
    }

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
        $this->clearRuleCache();

        if ($model === null) {
            throw LivewireAutoFormException::rootModelRequired(static::class);
        }

        $this->form->autoSave = $this->autoSave;
        $class = $model::class;
        $id = $model->exists ? $model->getKey() : null;
        $this->form->setRootModel($class, $id);

        foreach (array_keys($this->rules()) as $key) {
            if ($key === FormCollection::SYSTEM_KEY) {
                throw LivewireAutoFormException::forbiddenKey($key, static::class);
            }
        }

        $this->loadContext('', $id, true, $model);
        $this->syncToDraftState();
    }

    /**
     * Retrieves validation rules defined in the parent Livewire component.
     *
     * @return array<string, mixed>
     *
     * @throws LivewireAutoFormException
     */
    public function rules(): array
    {
        if ($this->cachedRules !== []) {
            return $this->cachedRules;
        }

        return $this->cachedRules = $this->scanInheritedRules();
    }

    /**
     * Filters which model fields are included in the form.
     *
     * @return string[]
     */
    public function ruleKeys(): array
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
     * @throws ValidationException|LivewireAutoFormException
     */
    public function validate($rules = null, $messages = [], $attributes = []): array
    {
        $this->syncFromDraftState();
        $rules = $rules ?? $this->rules();
        $propertyName = $this->getPropertyName();
        $prefixedRules = FormCollection::getPrefixedRules($rules, $propertyName);

        $data = [$propertyName => $this->form->all()];
        $validated = validator($data, $prefixedRules, $messages, $attributes)->validate();

        foreach (array_keys($prefixedRules) as $key) {
            $this->resetErrorBag($key);
        }

        return $validated[$propertyName] ?? [];
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
     * @throws ValidationException|LivewireAutoFormException
     */
    public function validateOnly($field, $rules = null, $messages = [], $attributes = [], $dataOverrides = []): array
    {
        $this->syncFromDraftState();
        $rules = $rules ?? $this->rules();
        $propertyName = $this->getPropertyName();

        $prefixedRules = FormCollection::getPrefixedRules($rules, $propertyName);
        $prefixedField = FormCollection::getPrefixedField($field, $propertyName);

        $rule = $prefixedRules[$prefixedField] ?? 'nullable';
        $singleRule = [$prefixedField => $rule];

        $validated = validator([$propertyName => $this->form->all()], $singleRule, $messages, $attributes)->validate();

        return $validated[$propertyName] ?? [];
    }

    /**
     * Validates and persists the current buffer form.
     *
     * This performs a full validation of all rules and then uses the
     * `CrudProcessor` to persist changes for the active context.
     *
     * @throws LivewireAutoFormException
     */
    public function save(): void
    {
        $this->validate();

        $this->traitSave();
    }

    /**
     * Internal save logic without full validation.
     *
     * @throws LivewireAutoFormException
     */
    protected function traitSave(): void
    {
        $this->getCrudProcessor()->save($this->form->all());

        $this->getComponent()->dispatch('saved',
            context: $this->form->getActiveContext(),
            id: $this->form->getActiveId()
        );

        if ($this->form->getActiveContext() !== '') {
            $this->returnToRootContext();
        }
    }

    /**
     * Resets the form buffer to the root context.
     *
     * @throws LivewireAutoFormException
     */
    protected function returnToRootContext(): void
    {
        $this->loadContext('', $this->form->getRootModelId(), false);
    }

    /**
     * Reverts the buffer to the root model state.
     *
     * Discards any unsaved changes in the current context and switches
     * back to the main model editing view.
     *
     * Note: This method is intended to be called by the developer or
     * from a view (e.g. via wire:click="cancel").
     *
     * @throws LivewireAutoFormException
     */
    public function cancel(): void
    {
        $this->returnToRootContext();
    }

    /**
     * Synchronizes the form buffer with the latest form from the database.
     *
     * Useful if the underlying model has been changed externally or
     * needs to be refreshed.
     *
     * @param  Model  $model  The model instance to reload.
     *
     * @throws LivewireAutoFormException
     */
    public function reloadModel(Model $model): void
    {
        $this->clearRuleCache();
        $this->loadContext('', $model->getKey());
    }

    /**
     * Clears the internal rule cache.
     *
     * Call this method if you dynamically change the form structure
     * (e.g. by modifying $ruleKeys) to ensure rules are re-scanned.
     */
    public function clearRuleCache(): void
    {
        $this->cachedRules = [];
        PathResolver::clearCache();
    }

    /**
     * Synchronizes the form collection to the flat draftState array.
     *
     * @throws LivewireAutoFormException
     */
    protected function syncToDraftState(): void
    {
        $this->draftState = Arr::dot($this->form->all());
    }

    protected function syncFromDraftState(?string $key = null, mixed $value = null): void
    {
        if ($key !== null) {
            $this->form->setNested($key, $value);
        } else {
            foreach ($this->draftState as $k => $v) {
                $this->form->setNested($k, $v);
            }
        }
    }

    public function updated(string $name, mixed $value): void
    {
        if (str_starts_with($name, 'draftState.')) {
            return;
        }

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

            if (! array_key_exists((string) $key, $this->rules()) && ! $this->isRelationAllowed((string) $key)) {
                throw LivewireAutoFormException::fieldKeyNotDefinedInRules((string) $key, static::class);
            }

            $result = $this->getCrudProcessor()->updatedForm($key, $value, $this->rules());

            $this->form->setNested($key, $result['cleanValue']);
            $this->draftState[$key] = $result['cleanValue'];

            if ($result['saved']) {
                session()->flash('status', 'Saved successfully');
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
     *
     * @throws LivewireAutoFormException
     */
    protected function loadContext(string $context, string|int|null $id, bool $preserve = true, ?Model $model = null): void
    {
        $rules = $this->rules();
        $processor = new DataProcessor;

        $this->form->setNullables($processor->findNullables($rules));
        $this->form->setJsonColumns($processor->findJsonColumns($rules));

        (new ContextManager($this->form, $this->getModelResolver(), $processor))
            ->loadContext($context, $id, $rules, $preserve, $model);

        $this->syncToDraftState();
    }

    /**
     * Returns the root model instance with current form applied.
     *
     * @throws LivewireAutoFormException
     */
    public function getModel(): ?Model
    {
        return $this->getModelResolver()->resolve($this->form, '', $this->form->getRootModelId());
    }

    /**
     * Returns the model instance for the current active context (root or relation)
     * with the current form applied.
     *
     * @throws LivewireAutoFormException
     */
    public function getActiveModel(): ?Model
    {
        $context = $this->form->getActiveContext();
        $id = $this->form->getActiveId();

        return $this->getModelResolver()->resolve(
            $this->form,
            $context,
            $id,
            true
        );
    }

    /**
     * Internal factory for the CrudProcessor.
     */
    protected function getCrudProcessor(): CrudProcessor
    {
        return new CrudProcessor($this->form, $this->getModelResolver(), new DataProcessor, $this->getPathResolver());
    }

    /**
     * Resolves a model instance.
     *
     * @throws LivewireAutoFormException
     */
    protected function resolveModelInstance(string $context, int|string|null $id): ?Model
    {
        return $this->getModelResolver()->resolve($this->form, $context, $id);
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
     * Helper to get rules from a model instance.
     * Checks for rules() method (static or instance) or rules property.
     *
     * @return array<string, mixed>
     */
    private function getRulesFromModel(Model $model): array
    {
        if (method_exists($model, 'rules')) {
            return $model->rules();
        }

        if (property_exists($model, 'rules')) {
            return $model->rules;
        }

        return [];
    }

    /**
     * Scans for rules from the active model and its relationships.
     *
     * @param  array<string, mixed>  $rules  Initial rules where component overrides may be provided.
     * @return array<string, mixed>
     *
     * @throws LivewireAutoFormException
     */
    protected function scanInheritedRules(array $rules = []): array
    {
        $ruleKeys = $this->ruleKeys();
        if ($ruleKeys === [] || empty($this->form->rootModelClass)) {
            return $rules;
        }

        $rootModel = $this->getModel();
        if (! $rootModel) {
            return $rules;
        }

        foreach ($ruleKeys as $key) {
            if (isset($rules[$key])) {
                continue;
            }

            // 1. Prioritize direct rule matching on the root model
            $rootModelRules = $this->getRulesFromModel($rootModel);
            if (Arr::has($rootModelRules, $key)) {
                $rules[$key] = $this->ensureSometimesRule(Arr::get($rootModelRules, $key));

                continue;
            }

            if ($key === $rootModel->getKeyName()) {
                // to allow for creating a new record
                $rules[$key] = 'nullable';

                continue;
            }

            if (str_contains($key, '.')) {
                $pathInfo = $this->getPathResolver()->resolve($rootModel, $key);

                if ($pathInfo->relationChain !== []) {
                    $relationPath = implode('.', $pathInfo->relationChain);
                    $fieldName = $pathInfo->targetAttribute;

                    // try to resolve the related model
                    $relatedModel = $this->resolveModelInstance($relationPath, null);
                    if ($relatedModel) {
                        $modelRules = $this->getRulesFromModel($relatedModel);
                        if (Arr::has($modelRules, $fieldName)) {
                            $rules[$key] = $this->ensureSometimesRule(Arr::get($modelRules, $fieldName));

                            continue;
                        }
                        if ($fieldName === $relatedModel->getKeyName()) {
                            $rules[$key] = 'nullable';

                            continue;
                        }
                    }
                }
            }

            $rules[$key] = 'nullable';
        }

        return $rules;
    }

    /**
     * @param  string|array<int, string>  $rule
     * @return string|array<int, string>
     */
    private function ensureSometimesRule(string|array $rule): string|array
    {
        if (is_string($rule)) {
            if (! str_contains($rule, 'sometimes')) {
                return 'sometimes|'.$rule;
            }
        } elseif (is_array($rule)) {
            if (! in_array('sometimes', $rule)) {
                array_unshift($rule, 'sometimes');
            }
        }

        return $rule;
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
