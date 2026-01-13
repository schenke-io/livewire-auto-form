<?php

namespace SchenkeIo\LivewireAutoForm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Throwable;

/**
 * Abstract class for context-aware Livewire form components.
 * Utilizes a "Single Buffer" architecture to manage model and relationship state.
 */
abstract class LivewireAutoFormComponent extends Component
{
    /**
     * The "$form" buffer (Single Buffer architecture) for form input and state.
     * All view inputs should bind here: wire:model="form.field_name"
     * For relations: wire:model="form.relation_name.field_name"
     */
    public FormCollection $form;

    /**
     * Public toggle for "Live Edit" vs "Batch Save" mode.
     */
    public bool $autoSave = false;

    public function reloadModel(Model $model): void
    {
        /*
         * Background: This method serves as a manual trigger to synchronize the
         * component's internal form state with the latest data from the database
         * for the given root model. It is particularly useful in scenarios where
         * background jobs, database triggers, or other parts of the application
         * might have modified the underlying record, and the UI needs to reflect
         * these changes without requiring a full page refresh. By calling
         * loadContext with an empty string, we target the root model context specifically.
         */
        $this->loadContext('', $model->getKey());
    }

    /**
     * @throws LivewireAutoFormException
     */
    public function mount(Model $model): void
    {
        if (! isset($this->form)) {
            $this->form = new FormCollection;
        }

        $this->form->autoSave = &$this->autoSave;

        $this->form->setRootModel($model::class, $model->exists ? $model->getKey() : null);

        // Load the root model into the form buffer immediately
        $this->loadContext('', $this->form->rootModelId);
    }

    /**
     * @return array<string, string|array<int, mixed>>
     */
    abstract public function rules(): array;

    /**
     * @return array<string, string|array<int, mixed>>
     */
    public function getRules(): array
    {
        $rules = $this->rules();
        $prefixedRules = [];
        foreach ($rules as $key => $rule) {
            if ($key === FormCollection::SYSTEM_KEY) {
                throw LivewireAutoFormException::forbiddenKey($key);
            }
            $prefixedRules['form.'.$key] = $rule;
        }

        return $prefixedRules;
    }

    // =========================================================================
    // 0. Internal Helpers & Specialized Classes
    // =========================================================================

    /**
     * Get the context manager instance.
     */
    protected function getContextManager(): ContextManager
    {
        return new ContextManager(
            $this->form,
            new ModelResolver,
            new DataProcessor
        );
    }

    /**
     * Get the CRUD processor instance.
     */
    protected function getCrudProcessor(): CrudProcessor
    {
        return new CrudProcessor(
            $this->form,
            new ModelResolver,
            new DataProcessor
        );
    }

    // =========================================================================
    // 2. Context Switching (Edit / Add / Cancel)
    // =========================================================================

    /**
     * Loads a record into $form for editing.
     *
     * To edit a related record, provide the relation name and its ID.
     * To switch the root model being edited (e.g. from a list), use an empty string
     * for $relation and the model's ID.
     *
     * @param  string  $relation  The name of the relationship (or '' for root model).
     * @param  int|string  $id  The ID of the record to edit.
     *
     * @throws LivewireAutoFormException
     */
    public function edit(string $relation, int|string $id): void
    {
        $this->guardDirtyBuffer(); // Safety check for manual save mode
        $this->ensureRelationAllowed($relation);

        $this->loadContext($relation, $id);
    }

    /**
     * Prepares $form for creating a new record.
     *
     * To add a related record, provide the relation name.
     * To prepare for a new root model (clear the form), use an empty string for $relation.
     *
     * @param  string  $relation  The name of the relationship (or '' for root model).
     *
     * @throws LivewireAutoFormException
     */
    public function add(string $relation): void
    {
        $this->guardDirtyBuffer();
        $this->ensureRelationAllowed($relation);

        $this->loadContext($relation, null);
    }

    /**
     * Deletes a record.
     *
     * To delete a related record, provide the relation name and its ID.
     *
     * @param  string  $relation  The name of the relationship.
     * @param  int|string  $id  The ID of the record to delete.
     *
     * @throws LivewireAutoFormException
     */
    public function delete(string $relation, int|string $id): void
    {
        $this->ensureRelationAllowed($relation);

        $this->getCrudProcessor()->delete($relation, $id);

        // If we were editing this record, go back to root
        if ($this->form->activeContext === $relation && $this->form->activeId == $id) {
            if ($relation === '') {
                $this->add('');
            } else {
                $this->cancel();
            }
        }

        $this->dispatch('saved', context: $relation, id: $id);
    }

    /**
     * Resets context to '' and unsets relationship data from $form.
     *
     * @throws LivewireAutoFormException
     */
    public function cancel(): void
    {
        $this->loadContext('', $this->form->rootModelId, false);
    }

    /**
     * Checks if a specific record (root or relation) is currently being edited.
     *
     * @param  string  $relation  The name of the relationship (or '' for root model).
     * @param  int|string  $id  The ID of the record.
     */
    public function isEdited(string $relation, int|string $id): bool
    {
        return $this->form->activeContext === $relation && $this->form->activeId == $id;
    }

    // =========================================================================
    // 3. Data Loading & Hydration
    // =========================================================================

    /**
     * Internal: Resolves the model and populates the $form array.
     *
     * @param  string  $context  The context (empty for root, or relation name).
     * @param  int|string|null  $id  The ID of the record.
     * @param  bool  $preserveRelations  Whether to keep existing relation data in the form.
     *
     * @throws LivewireAutoFormException
     */
    protected function loadContext(string $context, int|string|null $id, bool $preserveRelations = true): void
    {
        $rules = $this->getRules();
        $nullables = [];
        foreach ($rules as $key => $rule) {
            $ruleArray = is_string($rule) ? explode('|', $rule) : $rule;
            if (in_array('nullable', $ruleArray)) {
                $nullables[] = $key;
            }
        }
        $this->form->setNullables($nullables);
        $this->getContextManager()->loadContext($context, $id, $rules, $preserveRelations);
    }

    /**
     * Returns the root model instance with current form data applied.
     *
     * @throws LivewireAutoFormException
     */
    public function getModel(): ?Model
    {
        /*
         * The root model is resolved without a specific context.
         * Current form data is applied to the re-hydrated model instance.
         */
        return $this->resolveModelInstance('', null);
    }

    /**
     * Returns the model instance for the current active context (root or relation)
     * with current form data applied.
     *
     * @throws LivewireAutoFormException
     */
    public function getActiveModel(): ?Model
    {
        if ($this->form->activeContext) {
            /*
             * When in a sub-context (like editing a relation), the active model
             * is resolved using that context and the active ID.
             */
            return $this->resolveModelInstance($this->form->activeContext, $this->form->activeId);
        } else {
            /*
             * If no active context is set, there is no active model to return.
             */
            return null;
        }
    }

    /**
     * Fetches the actual Eloquent Model instance based on context.
     * Use getModel() or getActiveModel() for standard cases.
     *
     * @param  string  $context  The context (empty for root, or relation name).
     * @param  int|string|null  $id  The ID of the record.
     *
     * @throws LivewireAutoFormException
     */
    public function resolveModelInstance(string $context, int|string|null $id): ?Model
    {
        return (new ModelResolver)->resolve($this->form, $context, $id);
    }

    // =========================================================================
    // 4. Persistence (Auto-Save & Manual Save)
    // =========================================================================

    /**
     * Livewire Lifecycle Hook: Triggered whenever $form.* changes.
     * Handles "Auto-Save" logic.
     *
     * @param  string  $name  The name of the property that was updated.
     * @param  mixed  $value  The new value.
     *
     * @throws LivewireAutoFormException
     */
    public function updated(string $name, mixed $value): void
    {
        if (str_starts_with($name, 'form.')) {
            $key = substr($name, 5);
            // Ignore internal FormCollection properties and nested property updates
            $internalProperties = [
                'activeContext',
                'activeId',
                'rootModelClass',
                'rootModelId',
                'nullables',
                'autoSave',
                FormCollection::SYSTEM_KEY,
            ];
            foreach ($internalProperties as $prop) {
                if ($key === $prop || str_starts_with($key, $prop.'.') || $name === 'form.'.$prop) {
                    return;
                }
            }
            if ($key === '' || $key === 'form') {
                /*
                 * Background: In certain Livewire data binding scenarios, the updated hook
                 * might be triggered for the entire 'form' object or with an empty key
                 * if the binding path is partially resolved. Since our logic is designed
                 * to handle updates to specific fields (e.g., 'form.name'), we ignore
                 * these broader or malformed update events. This prevents unnecessary
                 * processing and avoids potential errors when trying to resolve
                 * rules or CRUD operations against an incomplete or generic key.
                 */
                return;
            }
            if (! array_key_exists((string) $key, $this->rules())) {
                throw LivewireAutoFormException::fieldKeyNotDefinedInRules((string) $key);
            }
            $this->updatedForm($value, $key);
        } elseif ($name === 'autoSave') {
            $this->form->autoSave = $value;
        }
    }

    /**
     * Internal helper called by the updated hook to handle individual field persistence.
     *
     * @throws LivewireAutoFormException
     */
    public function updatedForm(mixed $value, string $key): void
    {
        $result = $this->getCrudProcessor()->updatedForm((string) $key, $value, $this->getRules());

        $cleanValue = $result['cleanValue'];

        // Always reflect value back to the form buffer to handle nested updates correctly
        $this->form->setNested($key, $cleanValue);

        if (! $result['saved']) {
            return;
        }

        // 4. Validate ONLY the changed field
        $this->validateOnly('form.'.$key, $this->getRules());

        // Dispatch a lightweight UI feedback event for auto-save
        $this->dispatch('field-updated',
            changed: $key,
            context: $result['context'],
            id: $result['id']
        );
    }

    /**
     * Manually persists the current $form data (Update or Create).
     *
     * @throws LivewireAutoFormException
     */
    public function save(): void
    {
        $this->validate($this->getRules());

        /** @var array<string, mixed> $allData */
        $allData = $this->form->all();
        $this->getCrudProcessor()->save($allData);

        $this->dispatch('saved', context: $this->form->activeContext, id: $this->form->activeId);

        if ($this->form->activeContext !== '') {
            $this->cancel();
        }
    }

    // =========================================================================
    // 5. Helper Methods
    // =========================================================================

    /**
     * Returns all possible options for a relationship (for selects).
     *
     * @param  string  $relation  The name of the relationship.
     * @param  string  $labelColumn  The column to use as label.
     * @return array<int, array{value: string|int, label: string}> The list of options.
     *
     * @throws LivewireAutoFormException
     */
    public function allOptionsForRelation(string $relation, string $labelColumn = 'name'): array
    {
        $this->ensureRelationAllowed($relation);
        $root = $this->resolveModelInstance('', $this->form->rootModelId);
        if ($root === null) {
            /*
             * Background: Several operations, such as retrieving options for a relationship
             * dropdown, require an instantiated root model to inspect its relationship
             * definitions. If this method is invoked before the component has been
             * properly initialized with a model (via the mount() method), we cannot
             * proceed. Throwing this exception provides a clear, actionable error
             * message to the developer, indicating that the component's lifecycle
             * expectations have not been met.
             */
            throw LivewireAutoFormException::rootModelNotSet();
        }
        $relationObj = $root->{$relation}();
        $relatedModel = $relationObj->getRelated();

        if ($relationObj instanceof BelongsTo || $relationObj instanceof BelongsToMany) {
            $idColumn = $relatedModel->getKeyName();

            return $relatedModel::all([$idColumn, $labelColumn])->map(fn ($m) => [
                'value' => $m->{$idColumn},
                'label' => __($m->{$labelColumn}),
            ])->toArray();
        }

        throw LivewireAutoFormException::invalidRelationType($relation, $relationObj::class);
    }

    /**
     * Returns a Collection of related models with columns filtered by rules().
     *
     * @param  string  $relation  The name of the relationship.
     * @return Collection<int, Model> The collection of related models.
     *
     * @throws LivewireAutoFormException
     */
    public function getRelationList(string $relation): Collection
    {
        $this->ensureRelationAllowed($relation);

        $root = $this->resolveModelInstance('', $this->form->rootModelId);

        if (! $root || ! $root->exists) {
            return collect();
        }

        $relationQuery = $root->{$relation}();
        $relatedModel = $relationQuery->getRelated();
        $tableName = $relatedModel->getTable();
        $idColumn = $relatedModel->getKeyName();

        $rules = $this->rules();
        $selectColumns = [$idColumn];
        foreach (array_keys($rules) as $ruleKey) {
            if (str_starts_with($ruleKey, "$relation.")) {
                $column = explode('.', $ruleKey)[1];
                $selectColumns[] = $column;
            }
        }
        $selectColumns = array_unique($selectColumns);

        $qualifiedColumns = array_map(function ($column) use ($tableName) {
            return str_contains($column, '.') ? $column : "$tableName.$column";
        }, $selectColumns);

        return $relationQuery
            ->select($qualifiedColumns) // Always ensure ID is present
            ->get();
    }

    /**
     * Check if dirty buffer exists when auto-save is off.
     */
    public function guardDirtyBuffer(): void
    {
        if (! $this->form->autoSave && ! empty($this->form->toArray())) {
            $this->dispatch('confirm-discard-changes');
        }
    }

    /**
     * Ensure the relation is allowed to be edited.
     *
     * @param  string  $relation  The name of the relationship (or '' for root model).
     *
     * @throws LivewireAutoFormException
     */
    public function ensureRelationAllowed(string $relation): void
    {
        if ($relation === '') {
            return;
        }

        $rules = $this->rules();
        foreach (array_keys($rules) as $ruleKey) {
            if (str_starts_with($ruleKey, "$relation.")) {
                return;
            }
        }

        throw LivewireAutoFormException::relationNotDefinedInRules($relation);
    }

    /**
     * Sanitize a value based on the field name.
     *
     * @param  string  $key  The field key.
     * @param  mixed  $value  The value to sanitize.
     * @return mixed The sanitized value.
     */
    public function sanitizeValue(string $key, mixed $value): mixed
    {
        return (new DataProcessor)->sanitizeValue($key, $value, $this->form->nullables);
    }

    // =========================================================================
    // 6. Enum helpers (for rendering selects)
    // =========================================================================

    /**
     * Returns options for an enum-casted attribute.
     * Output format: array of ['value' => string|int, 'label' => string]
     *
     * @param  string  $attribute  The name of the attribute.
     * @param  ?string  $relation  The name of the relationship (optional).
     * @return array<int, array{value: string|int, label: string}> The list of options.
     *
     * @throws LivewireAutoFormException
     */
    public function enumOptionsFor(string $attribute, ?string $relation = null): array
    {
        if (empty($this->form->rootModelClass)) {
            return [];
        }

        try {
            if ($relation) {
                $this->ensureRelationAllowed($relation);
                $root = $this->resolveModelInstance('', $this->form->rootModelId);
                /** @var Model $model */
                $model = $root->{$relation}()->getRelated();
            } else {
                /** @var Model $model */
                $model = app($this->form->rootModelClass);
            }

            /** @var array<string, string> $casts */
            $casts = $model->getCasts();

            if (! isset($casts[$attribute])) {
                throw LivewireAutoFormException::missingEnumCast($model::class, $attribute);
            }

            /** @var class-string<\BackedEnum> $enumClass */
            $enumClass = $casts[$attribute];

            if (! is_string($enumClass) || ! enum_exists($enumClass)) {
                return [];
            }

            $options = [];
            foreach ($enumClass::cases() as $case) {
                if ($case instanceof \BackedEnum) {
                    $value = $case->value;
                } else {
                    $value = $case->name;
                }
                $label = is_string($value) ? Str::headline($value) : Str::headline($case->name);
                $options[] = ['value' => $value, 'label' => __($label)];
            }

            return $options;
        } catch (Throwable $e) {
            if ($e instanceof LivewireAutoFormException) {
                throw $e;
            }

            return [];
        }
    }
}
