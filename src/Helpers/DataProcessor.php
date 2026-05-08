<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Stringable;

/**
 * Handles the extraction, filtering, and sanitization of model form.
 *
 * This class implements the Data Loading Strategy and provides robust
 * input processing logic:
 * - **Rule Discovery**: Parses Livewire validation rules to identify which fields
 *   and relationships are eligible for loading into the form buffer. It supports
 *   both simple and dot-notated relational rules.
 * - **Field Extraction**: Intelligently extracts only allowed data from Eloquent
 *   models, optimizing the form buffer size and ensuring data security.
 * - **Field Sanitization**: Applies transformations to input values, including
 *   trimming strings and converting empty strings to `null` for fields explicitly
 *   marked as `nullable` in the rules.
 * - **Shadowing Prevention**: Manages precedence between root model attributes and
 *   relationship-aware fields.
 */
class DataProcessor
{
    /**
     * Implements the Data Loading Strategy by extracting model form allowed by rules().
     *
     * @param  Model  $model  The model instance.
     * @param  array<string, mixed>  $rules  The validation rules.
     * @param  string  $context  The context (empty for root, or relation name).
     * @return array<string, mixed> The filtered form.
     */
    public function extractFilteredData(Model $model, array $rules, string $context): array
    {
        $allowedFields = $this->getAllowedFields($rules, $context, $model);
        $filteredData = [];
        $nullables = $this->findNullables($rules);

        foreach ($allowedFields as $field) {
            $value = data_get($model, $field);
            $value = $this->sanitizeValue($field, $value, $nullables, $model);
            data_set($filteredData, $field, $value);
        }

        // Always ensure the primary key is included even if not in rules, for internal logic
        $idKey = $model->getKeyName();
        if ($model->exists && ! isset($filteredData[$idKey])) {
            $filteredData[$idKey] = $model->getKey();
        }

        return $filteredData;
    }

    /**
     * Get the fields allowed by rules() for a given context.
     *
     * Handles Shadowing: precedence is determined by the presence of a dot in the rule key.
     * Keys prefixed with 'form.' are automatically cleaned.
     *
     * @param  array<string, mixed>  $rules  The validation rules.
     * @param  string  $context  The context (empty for root, or relation name).
     * @param  Model|null  $model  The model instance to verify relations.
     * @return array<int, string> The list of allowed fields.
     */
    public function getAllowedFields(array $rules, string $context, ?Model $model = null): array
    {
        $allowedFields = [];
        foreach ($rules as $ruleKey => $rule) {
            $ruleKey = Str::after($ruleKey, 'form.');

            if ($context === '') {
                // For root context, we allow:
                // 1. Simple fields: "name"
                // 2. Nested relationship fields: "cities.name"
                // 3. Pivot fields: "pivot.status"
                if (str_contains($ruleKey, '.')) {
                    $firstPart = Str::before($ruleKey, '.');
                    if ($model && $model->isRelation($firstPart)) {
                        continue;
                    }
                }
                $allowedFields[] = $ruleKey;
            } else {
                if (str_starts_with($ruleKey, "$context.")) {
                    $field = Str::after($ruleKey, "$context.");
                    $allowedFields[] = $field;
                }
            }
        }

        foreach ($this->findRelations($rules, $context, $model) as $relationName) {
            if ($relationName === 'pivot') {
                continue;
            }

            if ($model) {
                try {
                    $relation = $model->{$relationName}();
                    if ($relation instanceof BelongsTo) {
                        $allowedFields[] = $relation->getForeignKeyName();
                    }
                } catch (\BadMethodCallException|\Error|\Exception) {
                    // Ignore relations that are not methods
                }
            } else {
                // Fallback if model is not provided
                $allowedFields[] = $relationName.'_id';
            }
        }

        return array_values(array_unique($allowedFields));
    }

    /**
     * Sanitize a value based on the field name and nullable rules.
     *
     * Performs the following sanitizations:
     * - Converts empty strings to null if the field is marked as nullable.
     * - Trims whitespace from string values.
     *
     * @param  string  $key  The key of the field.
     * @param  mixed  $value  The value to sanitize.
     * @param  array<int, string>  $nullables  The list of nullable fields.
     * @param  Model|null  $model  Optional model instance to check for casts.
     * @return mixed The sanitized value.
     */
    public function sanitizeValue(string $key, mixed $value, array $nullables, ?Model $model = null): mixed
    {
        // Flatten Wireables if they were passed manually
        if (is_object($value) && method_exists($value, 'toLivewire')) {
            $v = $value->toLivewire();
            if (! is_array($v)) {
                $value = $v;
            }
        }

        if ($value instanceof \BackedEnum) {
            $value = $value->value;
        } elseif ($value instanceof \UnitEnum) {
            $value = $value->name;
        } elseif ($value instanceof Stringable) {
            $value = (string) $value;
        }

        // Normalize localized numeric strings (comma to dot)
        if (is_string($value) && ! is_numeric($value) && preg_match('/^-?\d+,\d+$/', $value)) {
            $value = (float) str_replace(',', '.', $value);
        }

        if ($model && is_string($value) && is_numeric($value)) {
            $attribute = Str::afterLast($key, '.');
            $casts = $model->getCasts();
            $cast = $casts[$attribute] ?? null;
            if ($cast && enum_exists($cast)) {
                $reflection = new \ReflectionEnum($cast);
                if ($reflection->isBacked() && $reflection->getBackingType()->getName() === 'int') {
                    $value = (int) $value;
                }
            }
        }

        // Handle "empty string to null" logic
        if ($value === '' && in_array($key, $nullables)) {
            return null;
        }

        // Optional: Trim strings
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * Identifies fields that are marked as nullable in the validation rules.
     *
     * Scans through the rules array (handling both pipe-separated strings and arrays)
     * and returns the keys of any fields that contain the 'nullable' rule.
     *
     * @param  array<string, mixed>  $rules  The validation rules.
     * @return array<int, string> The list of nullable field keys.
     */
    public function findNullables(array $rules): array
    {
        return collect($rules)
            ->filter(fn ($r) => collect((array) $r)
                ->contains(fn ($rule) => is_string($rule) && str_contains($rule, 'nullable'))
            )
            ->keys()
            ->toArray();
    }

    /**
     * Identifies fields that are marked as JSON columns in the validation rules.
     *
     * Scans through the rules array (handling both pipe-separated strings and arrays)
     * and returns the keys of any fields that contain the 'json_column' rule.
     *
     * @param  array<string, mixed>  $rules  The validation rules.
     * @return array<int, string> The list of JSON column keys.
     */
    public function findJsonColumns(array $rules): array
    {
        return collect($rules)
            ->filter(fn ($r) => collect((array) $r)
                ->contains(fn ($rule) => is_string($rule) && str_contains($rule, 'json_column'))
            )
            ->keys()
            ->toArray();
    }

    /**
     * Translates dotted paths to JSON arrows (->) if the root part is a JSON column.
     *
     * Checks explicit declarations (from 'json_column' rule), the model's casts,
     * and model's fillable attributes.
     *
     * @param  string  $path  The dotted path to translate.
     * @param  array<int, string>  $jsonColumns  List of identified JSON columns.
     * @param  Model|null  $model  Optional model instance to check for casts/fillable.
     * @return string The translated path.
     */
    public function translatePath(string $path, array $jsonColumns, ?Model $model = null): string
    {
        // 1. Check explicit declarations
        foreach ($jsonColumns as $jsonColumn) {
            if ($path === $jsonColumn) {
                return $path;
            }
            if (str_starts_with($path, $jsonColumn.'.')) {
                $rest = substr($path, strlen($jsonColumn) + 1);

                return $jsonColumn.'->'.str_replace('.', '->', $rest);
            }
        }

        // 2. Check model casts or fillable
        if ($model && str_contains($path, '.')) {
            $rootPart = Str::before($path, '.');
            if (array_key_exists($rootPart, $model->getCasts()) || $model->isFillable($rootPart)) {
                $rest = Str::after($path, '.');

                return $rootPart.'->'.str_replace('.', '->', $rest);
            }
        }

        return $path;
    }

    /**
     * Extracts unique relation names from the validation rules for a given context.
     *
     * A relation name is identified as the first part of a dot-notated rule key
     * relative to the context.
     *
     * @param  array<string, mixed>  $rules  The validation rules.
     * @param  string  $context  The context (empty for root, or relation name).
     * @param  Model|null  $model  The model instance to verify relations.
     * @return array<int, string> The list of unique relation names.
     */
    public function findRelations(array $rules, string $context = '', ?Model $model = null): array
    {
        $jsonColumns = $this->findJsonColumns($rules);

        return collect(array_keys($rules))
            ->map(fn ($key) => Str::after($key, 'form.'))
            ->filter(fn ($key) => $context === '' || Str::startsWith($key, "$context."))
            ->map(fn ($key) => $context === '' ? $key : Str::after($key, "$context."))
            ->filter(fn ($key) => str_contains($key, '.'))
            ->map(fn ($key) => Str::before($key, '.'))
            ->filter(fn ($rel) => ! in_array($rel, $jsonColumns))
            ->filter(fn ($rel) => ! $model || $model->isRelation($rel))
            ->unique()
            ->values()
            ->toArray();
    }
}
