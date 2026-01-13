<?php

namespace SchenkeIo\LivewireAutoForm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DataProcessor
{
    /**
     * Implements the Data Loading Strategy by extracting model data allowed by rules().
     *
     * @param  Model  $model  The model instance.
     * @param  array<string, mixed>  $rules  The validation rules.
     * @param  string  $context  The context (empty for root, or relation name).
     * @return array<string, mixed> The filtered data.
     */
    public function extractFilteredData(Model $model, array $rules, string $context): array
    {
        $data = $model->toArray();

        $allowedFields = $this->getAllowedFields($rules, $context);
        $filteredData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                data_set($filteredData, $key, $value);
            }
        }

        // Always ensure the primary key is included even if not in rules, for internal logic
        $idKey = $model->getKeyName();
        if (isset($data[$idKey]) && ! isset($filteredData[$idKey])) {
            $filteredData[$idKey] = $data[$idKey];
        }

        return $filteredData;
    }

    /**
     * Get the fields allowed by rules() for a given context.
     * Handles Shadowing: precedence is determined by the presence of a dot in the rule key.
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
                if (! str_contains($ruleKey, '.') || str_starts_with($ruleKey, 'pivot.')) {
                    $allowedFields[] = $ruleKey;
                }
            } else {
                if (str_starts_with($ruleKey, "$context.")) {
                    $field = substr($ruleKey, strlen($context) + 1);
                    $allowedFields[] = $field;
                    // If it's a BelongsTo relation, also allow the foreign key
                    if (Str::endsWith($field, '.id')) {
                        // Not easily decidable here without model knowledge,
                        // but we can allow 'id' for any relation
                    }
                }
            }
        }

        return $allowedFields;
    }

    /**
     * Sanitize a value based on the field name and nullable rules.
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
}
