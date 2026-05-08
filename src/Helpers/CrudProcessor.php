<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SchenkeIo\LivewireAutoForm\Strategies\Persistence\PersistenceStrategy;
use SchenkeIo\LivewireAutoForm\Strategies\Persistence\StrategyFactory;

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
    protected PathResolver $pathResolver;

    public function __construct(
        public FormCollection $state,
        protected ModelResolver $resolver,
        protected DataProcessor $processor,
        ?PathResolver $pathResolver = null
    ) {
        $this->pathResolver = $pathResolver ?? new PathResolver;
    }

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
            $pathInfo = $this->pathResolver->resolve($root, $key);

            if ($pathInfo->relationChain !== []) {
                if (count($pathInfo->relationChain) === 1 && $pathInfo->targetAttribute === '') {
                    $relationsData[$pathInfo->relationChain[0]] = $value;
                }

                continue;
            }

            $firstPart = Str::before($pathInfo->targetAttribute, '.');
            if (property_exists($this->state, $key) || ! $root->isFillable($firstPart)) {
                if (! array_key_exists($firstPart, $root->getCasts())) {
                    continue;
                }
            }

            $realKey = $this->processor->translatePath($pathInfo->targetAttribute, $this->state->getJsonColumns(), $root);
            $rootData[$realKey] = $this->processor->sanitizeValue($key, $value, $this->state->getNullables(), $root);
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
            $pathInfo = $this->pathResolver->resolve($root, $key);

            if ($pathInfo->relationChain !== []) {
                $context = $pathInfo->relationChain[0];
                $field = $pathInfo->targetAttribute;
                // only direct relations are considered for BelongsTo updates on the root model
                if (count($pathInfo->relationChain) === 1 && $root->isRelation($context)) {
                    try {
                        $relation = $root->{$context}();
                        if ($relation instanceof BelongsTo) {
                            $idKey = $relation->getRelated()->getKeyName();
                            if ($field === '' || $field === $idKey) {
                                $finalVal = is_array($val) ? ($val[$idKey] ?? null) : $val;
                                if ($finalVal !== null) {
                                    $rootData[$relation->getForeignKeyName()] = $finalVal;
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                    }
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
                if (Str::startsWith($key, "$context.")) {
                    /*
                     * Background: This block handles form that has been flattened into a single-level array
                     * where keys are prefixed with the context name (e.g., 'brands.name'). This is common
                     * in certain form submission scenarios or when using specific Livewire binding patterns.
                     * We use data_set() to correctly reconstruct the nested array structure expected by the
                     * CRUD processor for the related model, ensuring that 'brands.name' becomes $form['name'].
                     */
                    data_set($data, Str::after($key, "$context."), $value);
                }
            }
        }

        if (empty($data)) {
            return;
        }

        $model = $this->resolver->resolve($this->state, $context, $id);

        foreach ($data as $key => $value) {
            $data[$key] = $this->processor->sanitizeValue("$context.$key", $value, $this->state->getNullables(), $model);
        }

        $relation = $this->resolveRelation($root, $context);
        $strategy = $this->getStrategy($relation);

        if ($strategy) {
            $strategy->save($relation, $root, $context, $id, $data, $this->state);
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
        $context = $this->state->getActiveContext();
        $id = $this->state->getActiveId();
        $model = $this->resolver->resolve($this->state, (string) $context, $id);

        $cleanValue = $this->processor->sanitizeValue($key, $value, $this->state->getNullables(), $model);

        // If Auto-Save is OFF, stop here.
        if (! $this->state->isAutoSave()) {
            return ['cleanValue' => $cleanValue, 'saved' => false];
        }

        $root = $this->resolver->resolve($this->state, '', $this->state->getRootModelId());
        if (! $root) {
            return ['cleanValue' => $cleanValue, 'saved' => false];
        }

        // Determine realKey (field name within context)
        $realKey = (string) $key;
        if ($context !== '' && Str::startsWith((string) $key, "$context.")) {
            $realKey = Str::after((string) $key, "$context.");
        }

        if (! $model || ! $model->exists) {
            return ['cleanValue' => $cleanValue, 'saved' => false, 'context' => (string) $context, 'id' => $id];
        }

        try {
            $relation = $this->resolveRelation($root, (string) $context);
            $strategy = $this->getStrategy($relation);

            if ($strategy && $strategy->updateField($relation, $root, (string) $context, $id, $realKey, $cleanValue, $this->state, $this->processor, $rules)) {
                return ['cleanValue' => $cleanValue, 'saved' => true, 'context' => (string) $context, 'id' => $this->state->getActiveId()];
            }
        } catch (\BadMethodCallException|LivewireAutoFormException $e) {
            // fall through to standard field update
        }

        $realKey = $this->processor->translatePath($realKey, $this->state->getJsonColumns(), $model);
        $model->forceFill([$realKey => $cleanValue])->save();
        $model->refresh();

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
        $strategy = $this->getStrategy($rel);

        if ($strategy) {
            $strategy->delete($rel, $root, $relation, $id);
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

            $selectColumns = collect(array_keys($rules))
                ->filter(fn ($ruleKey) => Str::startsWith((string) $ruleKey, "$relation."))
                ->map(fn ($ruleKey) => Str::after((string) $ruleKey, "$relation."))
                ->filter(fn ($field) => ! str_contains($field, '.'))
                ->merge([$idColumn])
                ->unique()
                ->values()
                ->toArray();

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
     * Factory for persistence strategies based on the relationship type.
     */
    protected function getStrategy(mixed $relation): ?PersistenceStrategy
    {
        return StrategyFactory::make($relation);
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
        $pathInfo = $this->pathResolver->resolve($root, $context);
        $chain = $pathInfo->relationChain;
        $attribute = $pathInfo->targetAttribute;

        // The relationship method we want is either the attribute (if it's a relation)
        // or the last part of the relation chain.
        $relationMethod = ($attribute !== '') ? $attribute : array_pop($chain);

        if (! $relationMethod) {
            throw LivewireAutoFormException::relationDoesNotExist($context, get_class($root), self::class);
        }

        $current = collect($chain)->reduce(function ($carry, $part) use ($context, $root) {
            $next = $carry->{$part};
            if (! $next instanceof Model) {
                throw LivewireAutoFormException::relationDoesNotExist($context, get_class($root), self::class);
            }

            return $next;
        }, $root);

        try {
            return $current->{$relationMethod}();
        } catch (\BadMethodCallException|\Error $e) {
            throw LivewireAutoFormException::relationDoesNotExist($context, get_class($root), self::class);
        }
    }
}
