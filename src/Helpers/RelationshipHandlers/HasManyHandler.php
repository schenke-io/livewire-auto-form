<?php

namespace SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

/**
 * Handler for HasMany and MorphMany relationships.
 */
class HasManyHandler implements RelationshipHandler
{
    /**
     * @param  HasMany<Model, Model>|MorphMany<Model, Model>  $relation
     * @param  array<string, mixed>  $data
     */
    public function save(mixed $relation, Model $root, string $context, int|string|null $id, array $data, FormCollection $state): void
    {
        if ($id === null) {
            $relation->create($data);
        } else {
            $model = $relation->find($id);
            if ($model instanceof Model) {
                $model->update($data);
            }
        }
    }

    /**
     * @param  HasMany<Model, Model>|MorphMany<Model, Model>  $relation
     * @param  array<string, mixed>  $rules
     */
    public function updateField(mixed $relation, Model $root, string $context, int|string|null $id, string $realKey, mixed $cleanValue, FormCollection $state, DataProcessor $processor, array $rules): bool
    {
        // HasMany doesn't have special field updates like pivot
        return false;
    }

    /**
     * @param  HasMany<Model, Model>|MorphMany<Model, Model>  $relation
     */
    public function delete(mixed $relation, Model $root, string $context, int|string|null $id): void
    {
        $model = $relation->find($id);
        if ($model instanceof Model) {
            $model->delete();
        }
    }
}
