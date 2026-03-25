<?php

namespace SchenkeIo\LivewireAutoForm\Strategies\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

class HasOneStrategy implements PersistenceStrategy
{
    /**
     * @param  HasOne<Model, Model>|MorphOne<Model, Model>  $relation
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
     * @param  HasOne<Model, Model>|MorphOne<Model, Model>  $relation
     * @param  array<string, mixed>  $rules
     */
    public function updateField(mixed $relation, Model $root, string $context, int|string|null $id, string $realKey, mixed $cleanValue, FormCollection $state, DataProcessor $processor, array $rules): bool
    {
        return false;
    }

    /**
     * @param  HasOne<Model, Model>|MorphOne<Model, Model>  $relation
     */
    public function delete(mixed $relation, Model $root, string $context, int|string|null $id): void
    {
        $model = $relation->find($id);
        if ($model instanceof Model) {
            $model->delete();
        }
    }
}
