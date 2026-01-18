<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;
use Throwable;

/**
 * Handles option resolution logic for the form (Enums, Models, etc.).
 *
 * This trait provides the necessary logic to automatically generate option arrays
 * for select inputs, radio buttons, or checkbox groups. It can resolve options
 * from several sources:
 * - PHP BackedEnums (including those implementing `AutoFormOptions`).
 * - Eloquent relationships (BelongsTo, BelongsToMany).
 * - Custom option providers implementing the `AutoFormOptions` interface.
 *
 * Role in Architecture:
 * It bridges the gap between the form model (Enums/Relations) and the UI,
 * allowing developers to generate user-friendly labels and values for form
 * inputs with minimal effort, often just by providing a field name.
 */
trait HandlesOptions
{
    /**
     * Generates option arrays for selects based on Enums or Eloquent relations.
     *
     * This is the primary entry point for generating options in Blade views.
     * It intelligently determines whether to fetch options from an Enum
     * (based on model casts) or from a related Model.
     *
     * @param  string  $key  The field name or relation name.
     * @param  string|null  $labelMask  Optional mask for labels (e.g., '(first_name) (last_name)').
     * @return array<int, array{0: string|int, 1: string}> Array of [value, label] pairs.
     */
    public function optionsFor(string $key, ?string $labelMask = null): array
    {
        if (str_contains($key, '.')) {
            [$relation, $attribute] = explode('.', $key, 2);

            return $this->enumOptionsFor($attribute, $relation, $labelMask);
        }

        return $this->isRelationAllowed($key)
            ? $this->modelOptionsFor($key, $labelMask)
            : $this->enumOptionsFor($key, null, $labelMask);
    }

    /**
     * Resolves options for BelongsTo or BelongsToMany relationships.
     *
     * Fetches all records from the related model and maps them to [ID, Label].
     * If the related model implements `AutoFormOptions`, its `getOptions()`
     * method is used instead of the default behavior.
     *
     * @param  string  $relation  The name of the relationship.
     * @param  string|null  $labelMask  Optional mask for labels or the column name to use as label.
     * @return array<int, array{0: string|int, 1: string}>
     *
     * @throws LivewireAutoFormException
     */
    public function modelOptionsFor(string $relation, ?string $labelMask = null): array
    {
        $this->ensureRelationAllowed($relation);

        if (! $this->form->rootModelClass) {
            throw LivewireAutoFormException::rootModelNotSet(static::class);
        }

        $root = (new ModelResolver)->resolve($this->form, '', $this->form->rootModelId);

        if (! $root) {
            throw LivewireAutoFormException::rootModelNotSet(static::class);
        }

        try {
            $relationObj = $root->{$relation}();
        } catch (\BadMethodCallException) {
            throw LivewireAutoFormException::relationDoesNotExist($relation, get_class($root), static::class);
        }

        $relatedModel = $relationObj->getRelated();

        if (is_subclass_of($relatedModel::class, AutoFormOptions::class)) {
            return $this->mapOptions($relatedModel::getOptions($labelMask));
        }

        if ($relationObj instanceof BelongsTo || $relationObj instanceof BelongsToMany) {
            $idColumn = $relatedModel->getKeyName();

            if ($labelMask && str_contains($labelMask, '(')) {
                // It's a mask
                preg_match_all("/\((.*?)\)/", $labelMask, $matches);
                if (empty($matches[1])) {
                    throw LivewireAutoFormException::optionsMaskSyntax($labelMask, static::class);
                }
                $columns = array_unique(array_merge([$idColumn], $matches[1]));

                return $relatedModel::all($columns)->map(function ($m) use ($idColumn, $labelMask, $matches) {
                    $label = $labelMask;
                    foreach ($matches[1] as $col) {
                        $label = str_replace("($col)", (string) $m->{$col}, $label);
                    }

                    return [
                        $m->{$idColumn},
                        __($label),
                    ];
                })->toArray();
            } else {
                // It's a column name (or null)
                $labelColumn = $labelMask ?: 'name';

                return $relatedModel::all([$idColumn, $labelColumn])->map(fn ($m) => [
                    $m->{$idColumn},
                    __($m->{$labelColumn}),
                ])->toArray();
            }
        }

        throw LivewireAutoFormException::invalidRelationType($relation, $relationObj::class, static::class);
    }

    /**
     * Resolves options for attributes cast to BackedEnums.
     *
     * Scans the model's `$casts` to find the Enum class for the attribute.
     * Supports both root model and related model attributes.
     *
     * @param  string  $attribute  The attribute name.
     * @param  string|null  $relation  Optional relation name if the attribute belongs to a relation.
     * @param  string|null  $labelMask  Optional mask for labels (e.g., '(name) - (value)').
     * @return array<int, array{0: string|int, 1: string}>
     *
     * @throws LivewireAutoFormException
     */
    public function enumOptionsFor(string $attribute, ?string $relation = null, ?string $labelMask = null): array
    {
        if (! $this->form->rootModelClass) {
            return [];
        }

        try {
            $resolver = new ModelResolver;
            $root = $resolver->resolve($this->form, '', $this->form->rootModelId);
            if (! $root) {
                return [];
            }
            $model = $relation
                ? $root->{$relation}()->getRelated()
                : $root;

            $enumClass = $model->getCasts()[$attribute] ?? null;

            if (! $enumClass) {
                throw LivewireAutoFormException::missingEnumCast($model::class, $attribute, static::class);
            }

            if (! enum_exists($enumClass)) {
                return [];
            }

            if ($labelMask && ! str_contains($labelMask, '(name)') && ! str_contains($labelMask, '(value)')) {
                throw LivewireAutoFormException::optionsMaskSyntax($labelMask, static::class);
            }

            if (is_subclass_of($enumClass, AutoFormOptions::class)) {
                return $this->mapOptions($enumClass::getOptions($labelMask));
            }

            return collect($enumClass::cases())->map(function ($case) use ($labelMask) {
                $value = $case instanceof \BackedEnum ? $case->value : $case->name;

                return [
                    $value,
                    $labelMask ? str_replace(['(name)', '(value)'], [(string) $case->name, (string) $value], $labelMask) : Str::headline($case->name),
                ];
            })->toArray();
        } catch (LivewireAutoFormException $e) {
            throw $e;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Maps an associative array of options to the format expected by the frontend.
     *
     * @param  array<string|int, string>  $options
     * @return array<int, array{0: string|int, 1: string}>
     */
    private function mapOptions(array $options): array
    {
        return collect($options)
            ->map(fn ($label, $value) => [$value, __((string) $label)])
            ->values()->toArray();
    }
}
