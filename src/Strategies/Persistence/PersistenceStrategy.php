<?php

namespace SchenkeIo\LivewireAutoForm\Strategies\Persistence;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

/**
 * Interface PersistenceStrategy
 *
 * Defines the contract for persisting related Eloquent models.
 */
interface PersistenceStrategy
{
    /**
     * Persists changes to a related model or relationship.
     *
     * @param  mixed  $relation  The Eloquent relationship instance.
     * @param  Model  $root  The parent model instance.
     * @param  string  $context  The relationship name.
     * @param  int|string|null  $id  The ID of the related model (if updating).
     * @param  array<string, mixed>  $data  The sanitized data to persist.
     * @param  FormCollection  $state  The current form state.
     */
    public function save(mixed $relation, Model $root, string $context, int|string|null $id, array $data, FormCollection $state): void;

    /**
     * Handles a single field update for a relationship (for auto-save).
     *
     * @param  mixed  $relation  The Eloquent relationship instance.
     * @param  Model  $root  The parent model instance.
     * @param  string  $context  The relationship name.
     * @param  int|string|null  $id  The ID of the related model.
     * @param  string  $realKey  The actual key in the model/pivot.
     * @param  mixed  $cleanValue  The sanitized value to update.
     * @param  FormCollection  $state  The current form state.
     * @param  DataProcessor  $processor  The data processor instance.
     * @param  array<string, mixed>  $rules  Validation rules for context.
     * @return bool True if the field was handled by the strategy.
     */
    public function updateField(mixed $relation, Model $root, string $context, int|string|null $id, string $realKey, mixed $cleanValue, FormCollection $state, DataProcessor $processor, array $rules): bool;

    /**
     * Deletes or detaches a related model from the relationship.
     *
     * @param  mixed  $relation  The Eloquent relationship instance.
     * @param  Model  $root  The parent model instance.
     * @param  string  $context  The relationship name.
     * @param  int|string|null  $id  The ID of the related model to remove.
     */
    public function delete(mixed $relation, Model $root, string $context, int|string|null $id): void;
}
