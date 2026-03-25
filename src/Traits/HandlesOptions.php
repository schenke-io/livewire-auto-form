<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use SchenkeIo\LivewireAutoForm\Helpers\ModelResolver;
use SchenkeIo\LivewireAutoForm\Options\OptionsFactory;
use Throwable;

/**
 * Handles option resolution logic for the form (Enums, Models, etc.).
 *
 * @method \SchenkeIo\LivewireAutoForm\Helpers\ModelResolver getModelResolver()
 *
 * This trait provides the necessary logic to automatically generate option arrays
 * for select inputs, radio buttons, or checkbox groups. It can resolve options
 * from several sources:
 * - **PHP BackedEnums**: Automatically discovers Enums from model casts and supports
 *   localization and custom label masks (e.g., `(name) - (value)`).
 * - **Eloquent Relationships**: Resolves `BelongsTo` and `BelongsToMany` relations,
 *   fetching related models and mapping them to `id` and `label` pairs.
 * - **Custom Providers**: Supports any class implementing the `AutoFormOptions` interface,
 *   allowing for complex or dynamic option generation.
 * - **Label Masking**: Provides a robust mechanism for combining multiple model columns
 *   into a single label string using parenthesis notation (e.g., `(first_name) (last_name)`).
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
     *
     * @throws LivewireAutoFormException
     */
    public function optionsFor(string $key, ?string $labelMask = null): array
    {
        if (str_contains($key, '.')) {
            $relation = Str::before($key, '.');
            $attribute = Str::after($key, '.');

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

        /** @phpstan-ignore-next-line */
        $resolver = method_exists($this, 'getModelResolver') ? $this->getModelResolver() : new ModelResolver;
        $root = $resolver->resolve($this->form, '', $this->form->rootModelId);

        if (! $root) {
            throw LivewireAutoFormException::rootModelNotSet(static::class);
        }

        try {
            $relationObj = $root->{$relation}();
        } catch (\BadMethodCallException) {
            throw LivewireAutoFormException::relationDoesNotExist($relation, get_class($root), static::class);
        }

        $relatedModel = $relationObj->getRelated();

        if (! is_subclass_of($relatedModel::class, AutoFormOptions::class) &&
            ! ($relationObj instanceof BelongsTo || $relationObj instanceof BelongsToMany)) {
            throw LivewireAutoFormException::invalidRelationType($relation, $relationObj::class, static::class);
        }

        return OptionsFactory::forModel($relatedModel::class)->getOptions($labelMask, static::class);
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
            /** @phpstan-ignore-next-line */
            $resolver = method_exists($this, 'getModelResolver') ? $this->getModelResolver() : new ModelResolver;
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

            return OptionsFactory::forEnum($enumClass)->getOptions($labelMask, static::class);
        } catch (LivewireAutoFormException $e) {
            throw $e;
        } catch (Throwable) {
            return [];
        }
    }
}
