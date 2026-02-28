<?php

namespace SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

/**
 * Handler for BelongsToMany relationships.
 *
 * This implementation handles the persistence of Many-to-Many relationships,
 * including synchronization and pivot data management. It provides:
 * - **Pivot Management**: Automatically extracting and persisting pivot data
 *   (e.g., using `updateExistingPivot`).
 * - **Context-Aware Pivot Updates**: Handling individual field updates for
 *   pivot attributes when designated in the rule set.
 * - **Dynamic Attachment/Creation**: Intelligently deciding between attaching
 *   an existing record or creating a new related model.
 * - **Detachment Logic**: Correctly detaching records rather than deleting
 *   the target model during deletion operations.
 */
class BelongsToManyHandler implements RelationshipHandler
{
    /**
     * @param  BelongsToMany<Model, Model>  $relation
     * @param  array<string, mixed>  $data
     */
    public function save(mixed $relation, Model $root, string $context, int|string|null $id, array $data, FormCollection $state): void
    {
        $pivotData = $data['pivot'] ?? [];
        foreach ($pivotData as $k => $v) {
            if (is_numeric($v)) {
                $pivotData[$k] = (int) $v;
            }
        }
        unset($data['pivot']);

        $idKey = $relation->getRelated()->getKeyName();
        $relatedId = $id ?? $data[$idKey] ?? null;
        unset($data[$idKey]);

        if ($id === null) {
            if ($relatedId) {
                $relation->attach($relatedId, $pivotData);
            } else {
                $relation->create($data, $pivotData);
            }
        } else {
            if (! empty($data)) {
                $model = $relation->find($relatedId);
                if ($model instanceof Model) {
                    $model->update($data);
                }
            }
            if (! empty($pivotData)) {
                $relation->updateExistingPivot($relatedId, $pivotData);
            }
        }
    }

    /**
     * @param  BelongsToMany<Model, Model>  $relation
     * @param  array<string, mixed>  $rules
     */
    public function updateField(mixed $relation, Model $root, string $context, int|string|null $id, string $realKey, mixed $cleanValue, FormCollection $state, DataProcessor $processor, array $rules): bool
    {
        if (Str::startsWith($realKey, 'pivot.')) {
            $pivotField = Str::after($realKey, 'pivot.');
            $relation->updateExistingPivot($id, [
                $pivotField => $cleanValue,
            ]);

            return true;
        }

        return false;
    }

    /**
     * @param  BelongsToMany<Model, Model>  $relation
     */
    public function delete(mixed $relation, Model $root, string $context, int|string|null $id): void
    {
        $relation->detach($id);
    }
}
