<?php

namespace SchenkeIo\LivewireAutoForm\Strategies\Persistence;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\Helpers\DataProcessor;
use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

interface PersistenceStrategy
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
     * @param  array<string, mixed>  $rules
     */
    public function updateField(mixed $relation, Model $root, string $context, int|string|null $id, string $realKey, mixed $cleanValue, FormCollection $state, DataProcessor $processor, array $rules): bool;

    /**
     * Deletes or detaches a related model.
     */
    public function delete(mixed $relation, Model $root, string $context, int|string|null $id): void;
}
