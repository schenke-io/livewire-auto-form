<?php

namespace SchenkeIo\LivewireAutoForm\Strategies\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

/**
 * Class BelongsToStrategy
 *
 * Handles persistence for BelongsTo relationships.
 */
class BelongsToStrategy implements PersistenceStrategy
{
    /**
     * Update the related model and the root model's foreign key.
     *
     * @param  BelongsTo<Model, Model>  $relation
     * @param  array<string, mixed>  $data
     */
    public function save(mixed $relation, Model $root, string $context, int|string|null $id, array $data, FormCollection $state): void
    {
        $idKey = $relation->getRelated()->getKeyName();
        if (isset($data[$idKey])) {
            $newId = $data[$idKey];
            if ($newId != $id) {
                $newModel = $relation->getRelated()->find($newId);
                if ($newModel instanceof Model) {
                    $state->setActiveId($newId);
                    // Update root model foreign key
                    $root->setAttribute($relation->getForeignKeyName(), $newId);
                    $root->save();
                    $newModel->update($data);

                    return;
                }
            }
            $related = $relation->getRelated()->find($id);
            if ($related instanceof Model) {
                $related->update($data);
            }
        }
    }

    /**
     * Handle auto-save for the foreign key field.
     *
     * @param  BelongsTo<Model, Model>  $relation
     * @param  array<string, mixed>  $rules
     */
    public function updateField(mixed $relation, Model $root, string $context, int|string|null $id, string $realKey, mixed $cleanValue, FormCollection $state, DataProcessor $processor, array $rules): bool
    {
        if ($realKey === $relation->getRelated()->getKeyName()) {
            $root->forceFill([$relation->getForeignKeyName() => $cleanValue])->save();
            $state->setActiveId($cleanValue);

            $related = $relation->getRelated()->find($cleanValue);
            if ($related instanceof Model) {
                $state->put((string) $context, $processor->extractFilteredData($related, $rules, (string) $context));
            }

            return true;
        }

        return false;
    }

    /**
     * Disassociate the relationship by setting the foreign key to null.
     *
     * @param  BelongsTo<Model, Model>  $relation
     */
    public function delete(mixed $relation, Model $root, string $context, int|string|null $id): void
    {
        $root->update([$relation->getForeignKeyName() => null]);
    }
}
