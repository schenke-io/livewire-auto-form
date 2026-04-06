<?php

namespace SchenkeIo\LivewireAutoForm\Strategies\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

/**
 * Class HasManyStrategy
 *
 * Handles persistence for HasMany and MorphMany relationships.
 */
class HasManyStrategy implements PersistenceStrategy
{
    /**
     * Create or update a related model in the collection.
     *
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
     * Auto-save is not directly supported for HasMany collections via this strategy.
     *
     * @param  HasMany<Model, Model>|MorphMany<Model, Model>  $relation
     * @param  array<string, mixed>  $rules
     */
    public function updateField(mixed $relation, Model $root, string $context, int|string|null $id, string $realKey, mixed $cleanValue, FormCollection $state, DataProcessor $processor, array $rules): bool
    {
        return false;
    }

    /**
     * Delete the related model from the database.
     *
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
