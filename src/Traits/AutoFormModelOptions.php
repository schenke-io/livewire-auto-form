<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

/**
 * Trait AutoFormModelOptions
 *
 * Provides a default implementation of the AutoFormOptions interface for Models,
 * returning 'id' and 'name' columns as value and label respectively.
 *
 * Usage:
 * ```php
 * class Country extends Model implements AutoFormOptions {
 *     use AutoFormModelOptions;
 * }
 * ```
 */
trait AutoFormModelOptions
{
    /**
     * Standard implementation of AutoFormOptions::getOptions() for Models.
     *
     * It returns an associative array of 'id' => 'name' (or custom column) columns.
     *
     * @param  string|null  $labelMask  Optional column name to use as label (defaults to 'name')
     * @return array<string|int, string>
     */
    public static function getOptions(?string $labelMask = null): array
    {
        $model = new static;
        $labelColumn = $labelMask ?: 'name';
        $idColumn = $model->getKeyName();

        return static::query()->pluck($labelColumn, $idColumn)->toArray();
    }
}
