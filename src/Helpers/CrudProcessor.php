<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers\BelongsToHandler;
use SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers\BelongsToManyHandler;
use SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers\HasManyHandler;
use SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers\RelationshipHandler;

/**
 * CrudProcessor handles the persistence logic for root and related models.
 *
 * It provides the robust Eloquent persistence layer for the package, responsible for:
 * - **Root Model Persistence**: Force-filling and saving the root model while
 *   automatically managing foreign keys for `BelongsTo` relationships discovered in the buffer.
 * - **Relationship Management**: Handles `HasMany`, `BelongsToMany`, `BelongsTo`, and `MorphMany`
 *   persistence through dedicated relationship handlers.
 * - **Real-time Updates**: Manages individual field updates for "auto-save" functionality,
 *   ensuring that data is correctly routed and sanitized before being saved to the database.
 * - **Deletion Logic**: Orchestrates the deletion or dissociation of records, ensuring
 *   consistent state and context cleanup.
 *
 * Role in Architecture:
 * This class decouples the complex Eloquent operations from the Livewire components,
 * providing a centralized, testable service for all database-bound operations. It works
 * in concert with the `FormCollection` for state retrieval and `ModelResolver` for
 * model re-hydration.
 */
class CrudProcessor
{
    public function __construct(
        public FormCollection $state,
        protected ModelResolver $resolver,
        protected DataProcessor $processor
    ) {}

    /**
     * Persists the current $form form (Update or Create) to the database.
     *
     * @param  array<int|string, mixed>  $allData
     *
     * @throws LivewireAutoFormException
     */
    public function save(array $allData): void
    {
        $context = (string) $this->state->getActiveContext();
        $id = ($context === '') ? $this->state->getRootModelId() : $this->state->getActiveId();

        $root = $this->resolver->resolve($this->state, '', $this->state->getRootModelId());

        if (! $root) {
            return;
        }

        // Always save the root model to persist any pending changes (e.g. foreign keys)
        $this->saveRootModel($root, $allData);

        if ($context !== '') {
            $this->saveRelatedModel($root, $context, $id, $allData);
        }
    }

    /**
     * Persists the root model and handles associated foreign key updates.
     *
     * @param  array<int|string, mixed>  $allData
     */
    protected function saveRootModel(Model $root, array $allData): void
    {
        $rootData = [];
        $relationsData = [];

        foreach ($allData as $key => $value) {
            $key = (string) $key;
            if ($root->isRelation($key)) {
                if (is_array($value)) {
                    $relationsData[$key] = $value;
                }
            } elseif (! str_contains($key, '.') && ! property_exists($this->state, $key) && $root->isFillable($key)) {
                $rootData[$key] = $this->processor->sanitizeValue($key, $value, $this->state->getNullables());
            }
        }

        // 1. Handle BelongsTo updates from relations form
        foreach ($relationsData as $relName => $relData) {
            $relation = $root->{$relName}();
            if ($relation instanceof BelongsTo) {
                $idKey = $relation->getRelated()->getKeyName();
                if (isset($relData[$idKey])) {
                    $rootData[$relation->getForeignKeyName()] = $relData[$idKey];
                }
            }
        }

        // 2. Fallback: check all form for any potential BelongsTo foreign keys
        foreach ($allData as $key => $val) {
            $key = (string) $key;
            $context = '';
            $field = '';
            if (str_contains($key, '.')) {
                /*
                 * Background: When saving the root model, we might encounter keys in dot notation.
                 * This happens if the form source is flattened or if custom inputs are used.
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
                     * way fails, we simply move on to the next field in the form set.
                     */
                }
            }
        }

        $root->forceFill($rootData)->save();
        $root->refresh();
    }

    /**
     * Persists changes to a related model.
     *
     * Supports Create and Update operations for various relationship types:
     * - HasMany / MorphMany
     * - BelongsToMany (including pivot form)
     * - BelongsTo (handling target record changes)
     *
     * @param  array<int|string, mixed>  $allData  The full dataset from the form.
     */
    protected function saveRelatedModel(Model $root, string $context, int|string|null $id, array $allData): void
    {
        $data = data_get($allData, $context) ?? [];
        if (empty($data)) {
            // Check if we have flat form like 'brands.name'
            foreach ($allData as $key => $value) {
                $key = (string) $key;
                if (str_starts_with($key, "$context.")) {
                    /*
                     * Background: This block handles form that has been flattened into a single-level array
                     * where keys are prefixed with the context name (e.g., 'brands.name'). This is common
                     * in certain form submission scenarios or when using specific Livewire binding patterns.
                     * We use data_set() to correctly reconstruct the nested array structure expected by the
                     * CRUD processor for the related model, ensuring that 'brands.name' becomes $form['name'].
                     */
                    data_set($data, substr($key, strlen($context) + 1), $value);
                }
            }
        }

        if (empty($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $data[$key] = $this->processor->sanitizeValue("$context.$key", $value, $this->state->getNullables());
        }

        $relation = $this->resolveRelation($root, $context);
        $handler = $this->getHandler($relation);

        if ($handler) {
            $handler->save($relation, $root, $context, $id, $data, $this->state);
        } else {
            $model = $this->resolver->resolve($this->state, $context, $id);
            $model?->update($data);
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
        $cleanValue = $this->processor->sanitizeValue($key, $value, $this->state->getNullables());

        // If Auto-Save is OFF, stop here.
        if (! $this->state->isAutoSave()) {
            return ['cleanValue' => $cleanValue, 'saved' => false];
        }

        $root = $this->resolver->resolve($this->state, '', $this->state->getRootModelId());
        if (! $root) {
            return ['cleanValue' => $cleanValue, 'saved' => false];
        }

        $context = $this->state->getActiveContext();
        $id = $this->state->getActiveId();

        // Determine realKey (field name within context)
        $realKey = (string) $key;
        if ($context !== '' && str_starts_with((string) $key, "$context.")) {
            $realKey = substr((string) $key, strlen((string) $context) + 1);
        }

        $model = $this->resolver->resolve($this->state, (string) $context, $id);
        if (! $model || ! $model->exists) {
            return ['cleanValue' => $cleanValue, 'saved' => false, 'context' => (string) $context, 'id' => $id];
        }

        try {
            $relation = $this->resolveRelation($root, (string) $context);
            $handler = $this->getHandler($relation);

            if ($handler && $handler->updateField($relation, $root, (string) $context, $id, $realKey, $cleanValue, $this->state, $this->processor, $rules)) {
                return ['cleanValue' => $cleanValue, 'saved' => true, 'context' => (string) $context, 'id' => $this->state->getActiveId()];
            }
        } catch (\BadMethodCallException|LivewireAutoFormException $e) {
            // fall through to standard field update
        }

        $model->forceFill([$realKey => $cleanValue])->save();

        return [
            'cleanValue' => $cleanValue,
            'saved' => true,
            'context' => $context,
            'id' => $id,
        ];
    }

    /**
     * Delete a record (root model or related model).
     *
     * Handles different relationship types during deletion:
     * - Root model: Deletes the record directly.
     * - BelongsToMany: Detaches the related record.
     * - HasMany/MorphMany: Deletes the child record.
     * - BelongsTo: Dissociates the relationship by setting the foreign key to null.
     *
     * @param  string  $relation  The relationship name (empty for root model).
     * @param  int|string  $id  The ID of the record to delete.
     */
    public function delete(string $relation, int|string $id): void
    {
        if ($relation === '') {
            $model = app($this->state->getRootModelClass())->find($id);
            if ($model) {
                /*
                 * Background: When deleting the root model, we instantiate it via the app container
                 * using the stored class name. If the model exists, we call delete() on it,
                 * which will also trigger any Eloquent model events (like 'deleting' or 'deleted')
                 * defined in the model class.
                 */
                $model->delete();
            }

            return;
        }

        $root = $this->resolver->resolve($this->state, '', $this->state->getRootModelId());
        if (! $root || ! $root->exists) {
            return;
        }
        $rel = $this->resolveRelation($root, $relation);
        $handler = $this->getHandler($rel);

        if ($handler) {
            $handler->delete($rel, $root, $relation, $id);
        }
    }

    /**
     * Returns a list of related models for a specific relationship.
     *
     * @param  string  $relation  The name of the relationship.
     * @param  array<string, mixed>  $rules  The validation rules.
     * @return Collection<int, Model>
     */
    public function getRelationList(string $relation, array $rules): Collection
    {
        if (! $this->state->getRootModelClass()) {
            return collect();
        }

        $root = $this->resolver->resolve($this->state, '', $this->state->getRootModelId());

        if (! $root || ! $root->exists) {
            return collect();
        }

        try {
            $relationQuery = $this->resolveRelation($root, $relation);
            $relatedModel = $relationQuery->getRelated();
            $tableName = $relatedModel->getTable();
            $idColumn = $relatedModel->getKeyName();

            $selectColumns = [$idColumn];
            foreach (array_keys($rules) as $ruleKey) {
                if (str_starts_with($ruleKey, "$relation.")) {
                    $field = substr($ruleKey, strlen($relation) + 1);
                    if (! str_contains($field, '.')) {
                        $selectColumns[] = $field;
                    }
                }
            }
            $selectColumns = array_unique($selectColumns);

            $qualifiedColumns = array_map(function ($column) use ($tableName) {
                return str_contains($column, '.') ? $column : "$tableName.$column";
            }, $selectColumns);

            return $relationQuery
                ->select($qualifiedColumns)
                ->get();
        } catch (\BadMethodCallException|LivewireAutoFormException $e) {
            return collect();
        }
    }

    /**
     * Factory for relationship handlers based on the relationship type.
     */
    protected function getHandler(mixed $relation): ?RelationshipHandler
    {
        return match (true) {
            $relation instanceof BelongsToMany => new BelongsToManyHandler,
            $relation instanceof HasMany, $relation instanceof MorphMany => new HasManyHandler,
            $relation instanceof BelongsTo => new BelongsToHandler,
            default => null,
        };
    }

    /**
     * Resolves a relationship object from a model, supporting dot notation for nested relations.
     *
     * @param  Model  $root  The root model instance.
     * @param  string  $context  The dot-notated relationship path.
     * @return mixed The relationship object.
     *
     * @throws LivewireAutoFormException If a part of the path cannot be resolved.
     */
    protected function resolveRelation(Model $root, string $context): mixed
    {
        $parts = explode('.', $context);
        $current = $root;

        foreach ($parts as $index => $part) {
            if ($index === count($parts) - 1) {
                return $current->{$part}();
            }

            $current = $current->{$part};
            if (! $current instanceof Model) {
                break;
            }
        }

        throw LivewireAutoFormException::relationDoesNotExist($context, get_class($root), self::class);
    }
}
