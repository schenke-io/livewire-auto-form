<?php

namespace SchenkeIo\LivewireAutoForm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class CrudProcessor
{
    public function __construct(
        public FormCollection $state,
        protected ModelResolver $resolver,
        protected DataProcessor $processor
    ) {}

    /**
     * Persists the current $form data (Update or Create) to the database.
     *
     * @param  array<string, mixed>  $allData
     *
     * @throws LivewireAutoFormException
     */
    public function save(array $allData): void
    {
        $root = $this->resolver->resolve($this->state, '', $this->state->rootModelId);
        if (! $root) {
            /*
             * Background: This is a critical safety guard. If the save() method is triggered
             * but the root model instance cannot be resolved from the database (e.g., it
             * was deleted by another process or the session/state has become inconsistent),
             * we must abort the save operation immediately. Attempting to proceed would
             * result in errors when trying to persist data or resolve relationships
             * against a non-existent base model.
             */
            return;
        }

        $context = (string) $this->state->activeContext;
        $id = ($context === '') ? $this->state->rootModelId : $this->state->activeId;

        // Always save the root model to persist any pending changes (e.g. foreign keys)
        $this->saveRootModel($root, $allData);

        if ($context !== '') {
            $this->saveRelatedModel($root, $context, $id, $allData);
        }
    }

    /**
     * Persists the root model and handles associated foreign key updates.
     *
     * @param  array<string, mixed>  $allData
     */
    protected function saveRootModel(Model $root, array $allData): void
    {
        $rootData = [];
        $relationsData = [];

        foreach ($allData as $key => $value) {
            if ($root->isRelation($key)) {
                if (is_array($value)) {
                    $relationsData[$key] = $value;
                }
            } elseif (! str_contains($key, '.') && ! property_exists($this->state, $key) && $root->isFillable($key)) {
                $rootData[$key] = $this->processor->sanitizeValue($key, $value, $this->state->nullables);
            }
        }

        // 1. Handle BelongsTo updates from relations data
        foreach ($relationsData as $relName => $relData) {
            $relation = $root->{$relName}();
            if ($relation instanceof BelongsTo) {
                $idKey = $relation->getRelated()->getKeyName();
                if (isset($relData[$idKey])) {
                    $rootData[$relation->getForeignKeyName()] = $relData[$idKey];
                }
            }
        }

        // 2. Fallback: check all data for any potential BelongsTo foreign keys
        foreach ($allData as $key => $val) {
            $context = '';
            $field = '';
            if (str_contains($key, '.')) {
                /*
                 * Background: When saving the root model, we might encounter keys in dot notation.
                 * This happens if the data source is flattened or if custom inputs are used.
                 * By splitting the key, we can identify if the first part corresponds to a
                 * relationship (like 'city.id'), which allows us to specifically handle
                 * foreign key updates on the root model for BelongsTo relationships.
                 */
                [$context, $field] = explode('.', $key, 2);
            } elseif ($root->isRelation($key)) {
                $context = $key;
                $field = 'id';
            }

            if ($context) {
                try {
                    $relation = $root->{$context}();
                    if ($relation instanceof BelongsTo) {
                        $idKey = $relation->getRelated()->getKeyName();
                        if ($field === $idKey) {
                            $finalVal = is_array($val) ? ($val[$idKey] ?? null) : $val;
                            if ($finalVal !== null) {
                                $rootData[$relation->getForeignKeyName()] = $finalVal;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    /*
                     * Background: This is a best-effort discovery phase for relationship-based fields.
                     * We try to invoke the $context as a method on the model to see if it's a relation.
                     * This might fail if the method is not a relation, or if there are logic errors
                     * within the model's relation method. We catch and ignore all exceptions here
                     * because this is just one of several ways we try to find foreign keys; if this
                     * way fails, we simply move on to the next field in the data set.
                     */
                }
            }
        }

        $root->forceFill($rootData)->save();
        $root->refresh();
    }

    /**
     * @param  array<string, mixed>  $allData
     */
    protected function saveRelatedModel(Model $root, string $context, int|string|null $id, array $allData): void
    {
        $data = $allData[$context] ?? [];
        if (empty($data)) {
            // Check if we have flat data like 'brands.name'
            foreach ($allData as $key => $value) {
                if (str_starts_with($key, "$context.")) {
                    /*
                     * Background: This block handles data that has been flattened into a single-level array
                     * where keys are prefixed with the context name (e.g., 'brands.name'). This is common
                     * in certain form submission scenarios or when using specific Livewire binding patterns.
                     * We use data_set() to correctly reconstruct the nested array structure expected by the
                     * CRUD processor for the related model, ensuring that 'brands.name' becomes $data['name'].
                     */
                    data_set($data, substr($key, strlen($context) + 1), $value);
                }
            }
        }

        if (empty($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->processor->sanitizeValue("$context.$key", $value, $this->state->nullables);
        }

        $relation = $root->{$context}();
        $model = $this->resolver->resolve($this->state, $context, $id);

        if ($id === null) {
            // Create mode
            $pivotData = [];
            if (isset($data['pivot'])) {
                $pivotData = $data['pivot'];
                unset($data['pivot']);
            }

            if ($relation instanceof HasMany || $relation instanceof MorphMany) {
                $relation->create($data);
            } elseif ($relation instanceof BelongsToMany) {
                $relation->create($data, $pivotData);
            }
        } else {
            // Update mode
            if (! $model) {
                /*
                 * Background: If we are in update mode but the specific model instance cannot
                 * be found (e.g., it was deleted by another user while this form was open),
                 * we cannot perform any update operations on it. This early return prevents
                 * the application from crashing when it later attempts to call update()
                 * on a null value.
                 */
                return;
            }

            if ($relation instanceof BelongsTo) {
                $idKey = $relation->getRelated()->getKeyName();
                if (isset($data[$idKey]) && $data[$idKey] != $id) {
                    /*
                     * Background: This is a special handling for changing the target of a BelongsTo
                     * relationship during a save operation. If the primary key of the related model
                     * in the form data differs from the ID we currently have, it means the user
                     * has selected a different record for this relationship (e.g., changing the
                     * city of a brand). We must:
                     * 1. Retrieve the new related model instance.
                     * 2. Update our internal state to reflect the new active ID.
                     * 3. Update the foreign key on the root model to point to this new ID and
                     *    persist this change to the database immediately.
                     */
                    $newId = $data[$idKey];
                    $newModel = $relation->getRelated()->find($newId);
                    if ($newModel) {
                        $model = $newModel;
                        $this->state->setActiveId($newId);
                        // Update root model foreign key
                        $root->setAttribute($relation->getForeignKeyName(), $newId);
                        $root->save();
                    }
                }
            }

            $attributes = $data;
            $pivotData = [];
            if (isset($attributes['pivot'])) {
                $pivotData = $attributes['pivot'];
                unset($attributes['pivot']);
            }

            if ($model instanceof Model) {
                $model->update($attributes);
            }

            if (! empty($pivotData) && $relation instanceof BelongsToMany) {
                $relation->updateExistingPivot($id, $pivotData);
            }
        }
    }

    /**
     * Internal helper called by the updated hook to handle individual field persistence.
     *
     * @param  string  $key  The key of the updated field (already stripped from 'form.').
     * @param  mixed  $value  The new value.
     * @param  array<string, mixed>  $rules  The validation rules.
     * @return array<string, mixed> The result of the update (cleanValue, saved, context, id).
     *
     * @throws LivewireAutoFormException
     */
    public function updatedForm(string $key, mixed $value, array $rules): array
    {
        $cleanValue = $this->processor->sanitizeValue($key, $value, $this->state->nullables);

        // If Auto-Save is OFF, stop here.
        if (! $this->state->autoSave) {
            return ['cleanValue' => $cleanValue, 'saved' => false];
        }

        $context = $this->state->activeContext;
        $id = $this->state->activeId;

        // Determine realKey (field name within context)
        $realKey = (string) $key;
        if ($context !== '' && str_starts_with((string) $key, "$context.")) {
            $realKey = substr((string) $key, strlen((string) $context) + 1);
        }

        $model = $this->resolver->resolve($this->state, (string) $context, $id);
        if (! $model || ! $model->exists) {
            return ['cleanValue' => $cleanValue, 'saved' => false, 'context' => (string) $context, 'id' => $id];
        }

        if ($context !== '' && Str::startsWith($realKey, 'pivot.')) {
            $pivotField = Str::after($realKey, 'pivot.');
            $root = $this->resolver->resolve($this->state, '', $this->state->rootModelId);
            $root->{$context}()->updateExistingPivot($id, [
                $pivotField => $cleanValue,
            ]);
        } elseif ($context !== '' && $realKey === $model->getKeyName()) {
            // Primary key update for a relation (usually BelongsTo link change)
            $root = $this->resolver->resolve($this->state, '', $this->state->rootModelId);
            if ($root) {
                try {
                    $relation = $root->{$context}();
                    if ($relation instanceof BelongsTo) {
                        $foreignKey = $relation->getForeignKeyName();
                        $root->forceFill([$foreignKey => $cleanValue])->save();
                        // Update state to match new ID
                        $this->state->setActiveId($cleanValue);
                        $related = $relation->getRelated()->find($cleanValue);
                        if ($related instanceof Model) {
                            $this->state->put((string) $context, $this->processor->extractFilteredData($related, $rules, (string) $context));
                        }
                    }
                } catch (\BadMethodCallException $e) {
                    /*
                     * Background: When a field that resembles a relationship ID is updated, we
                     * try to call the relationship method on the root model to confirm. If that
                     * method does not exist, Eloquent/PHP throws a BadMethodCallException.
                     * We catch this because it simply indicates that the field being updated
                     * is a regular field and not a relationship link. In this case, we
                     * gracefully exit the relationship-specific logic and fall through to the
                     * standard field update logic.
                     */
                }
            }
        } else {
            $model->forceFill([$realKey => $cleanValue])->save();
        }

        return [
            'cleanValue' => $cleanValue,
            'saved' => true,
            'context' => $context,
            'id' => $id,
        ];
    }

    /**
     * Delete a record (root or related).
     */
    public function delete(string $relation, int|string $id): void
    {
        if ($relation === '') {
            $model = app($this->state->rootModelClass)->find($id);
            $model?->delete();

            return;
        }

        $root = $this->resolver->resolve($this->state, '', $this->state->rootModelId);
        if (! $root || ! $root->exists) {
            return;
        }
        $rel = $root->{$relation}();

        if ($rel instanceof BelongsToMany) {
            $rel->detach($id);
        } elseif ($rel instanceof HasMany || $rel instanceof MorphMany) {
            $rel->find($id)?->delete();
        } elseif ($rel instanceof BelongsTo) {
            $root->update([$rel->getForeignKeyName() => null]);
        }
    }
}
