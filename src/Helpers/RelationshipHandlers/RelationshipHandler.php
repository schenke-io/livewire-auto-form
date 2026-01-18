<?php

namespace SchenkeIo\LivewireAutoForm\Helpers\RelationshipHandlers;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

/**
 * Interface for handling relationship-specific persistence logic.
 */
interface RelationshipHandler
{
    /**
     * Persists changes to a related model.
     *
     * @param  mixed  $relation  The relationship object.
     * @param  Model  $root  The root model instance.
     * @param  string  $context  The relationship name.
     * @param  int|string|null  $id  The ID of the related model.
     * @param  array<string, mixed>  $data  The sanitized data to persist for this relationship.
     * @param  FormCollection  $state  The form state.
     */
    public function save(mixed $relation, Model $root, string $context, int|string|null $id, array $data, FormCollection $state): void;

    /**
     * Handles a single field update for a relationship.
     *
     * @param  mixed  $relation  The relationship object.
     * @param  Model  $root  The root model instance.
     * @param  string  $context  The relationship name.
     * @param  int|string|null  $id  The ID of the related model.
     * @param  string  $realKey  The field name (relative to context).
     * @param  mixed  $cleanValue  The sanitized value.
     * @param  FormCollection  $state  The form state.
     * @param  DataProcessor  $processor  The data processor.
     * @param  array<string, mixed>  $rules  The validation rules.
     * @return bool True if the handler handled the update, false otherwise.
     */
    public function updateField(mixed $relation, Model $root, string $context, int|string|null $id, string $realKey, mixed $cleanValue, FormCollection $state, DataProcessor $processor, array $rules): bool;

    /**
     * Deletes or detaches a related model.
     *
     * @param  mixed  $relation  The relationship object.
     * @param  Model  $root  The root model instance.
     * @param  string  $context  The relationship name.
     * @param  int|string|null  $id  The ID of the record to delete.
     */
    public function delete(mixed $relation, Model $root, string $context, int|string|null $id): void;
}
