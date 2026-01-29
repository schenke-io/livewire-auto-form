<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Illuminate\Database\Eloquent\Model;

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
        $allowedFields = $this->getAllowedFields($rules, $context);
        $filteredData = [];

        foreach ($allowedFields as $field) {
            $value = data_get($model, $field);
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
     * @return array<int, string> The list of allowed fields.
     */
    public function getAllowedFields(array $rules, string $context): array
    {
        $allowedFields = [];
        foreach ($rules as $ruleKey => $rule) {
            if (str_starts_with($ruleKey, 'form.')) {
                $ruleKey = substr($ruleKey, 5);
            }

            if ($context === '') {
                // For root context, we allow:
                // 1. Simple fields: "name"
                // 2. Nested relationship fields: "cities.name"
                // 3. Pivot fields: "pivot.status"
                $allowedFields[] = $ruleKey;
            } else {
                if (str_starts_with($ruleKey, "$context.")) {
                    $field = substr($ruleKey, strlen($context) + 1);
                    $allowedFields[] = $field;
                }
            }
        }

        foreach ($this->findRelations($rules, $context) as $relation) {
            if ($relation !== 'pivot') {
                $allowedFields[] = $relation.'_id';
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
     * @return mixed The sanitized value.
     */
    public function sanitizeValue(string $key, mixed $value, array $nullables): mixed
    {
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
            ->filter(function ($r) {
                if (is_array($r)) {
                    foreach ($r as $rule) {
                        if (is_string($rule) && str_contains($rule, 'nullable')) {
                            return true;
                        }
                    }

                    return false;
                }

                return is_string($r) && str_contains($r, 'nullable');
            })
            ->keys()
            ->toArray();
    }

    /**
     * Extracts unique relation names from the validation rules for a given context.
     *
     * A relation name is identified as the first part of a dot-notated rule key
     * relative to the context.
     *
     * @param  array<string, mixed>  $rules  The validation rules.
     * @param  string  $context  The context (empty for root, or relation name).
     * @return array<int, string> The list of unique relation names.
     */
    public function findRelations(array $rules, string $context = ''): array
    {
        $relations = [];
        $prefix = $context === '' ? '' : $context.'.';
        foreach (array_keys($rules) as $ruleKey) {
            $cleanKey = str_starts_with($ruleKey, 'form.') ? substr($ruleKey, 5) : $ruleKey;
            if ($prefix !== '' && ! str_starts_with($cleanKey, $prefix)) {
                continue;
            }
            $relativeKey = substr($cleanKey, strlen($prefix));
            if (str_contains($relativeKey, '.')) {
                $relations[] = explode('.', $relativeKey)[0];
            }
        }

        return array_values(array_unique($relations));
    }
}
