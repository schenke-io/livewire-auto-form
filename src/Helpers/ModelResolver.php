<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Handles the dynamic resolution and re-hydration of Eloquent model instances.
 *
 * This class is responsible for navigating the Eloquent model tree to instantiate
 * or retrieve model records based on the current form state and context.
 *
 * Navigation Logic:
 * - **Root Resolution**: Identifies and re-hydrates the root model using the stored
 *   class name and ID. It can optionally apply the current buffered changes to the instance.
 * - **Relationship Traversal**: Navigates nested relationship paths (e.g., `user.profile.address`)
 *   by sequentially resolving each model in the chain. It supports both existing
 *   related records (via `find($id)`) and new instances (for adding related records).
 * - **State Application**: Ensures that as it traverses the tree, the current form state
 *   (including unsaved foreign keys) is applied to intermediate models to ensure
 *   accurate relationship resolution.
 */
class ModelResolver
{
    protected DataProcessor $processor;

    public function __construct(?DataProcessor $processor = null)
    {
        $this->processor = $processor ?? new DataProcessor;
    }

    /**
     * Resolves the model instance based on the context and ID.
     *
     * This method re-hydrates the root model or a related model based on the
     * provided context (dotted relationship path) and the current form state.
     *
     * @param  FormCollection  $state  The $form buffer containing current form and metadata.
     * @param  string  $context  The context (empty string for root, or relation name like 'city').
     * @param  int|string|null  $id  The ID of the record to resolve.
     * @param  bool  $applyState  Whether to apply the current buffered form state to the model instance.
     * @param  Model|null  $model  Optional model instance to use instead of fetching from the database.
     * @return Model|null The resolved Eloquent model instance, or null if it cannot be found.
     *
     * @throws LivewireAutoFormException If the root model class is not defined in the state.
     */
    public function resolve(FormCollection $state, string $context, int|string|null $id, bool $applyState = true, ?Model $model = null): ?Model
    {
        if (empty($state->rootModelClass)) {
            throw LivewireAutoFormException::rootModelClassMissing(self::class);
        }

        $targetId = ($context === '' || str_contains($context, '.')) ? ($id ?? $state->rootModelId) : $state->rootModelId;

        // Re-hydrate the root model
        /** @var Model|null $root */
        $root = $model ?? ($targetId ? app($state->rootModelClass)->find($targetId) : null);

        /*
         * We ALWAYS apply state to the root model if we are resolving a relationship,
         * because we need the current foreign keys to find the related record.
         * If we are resolving the root itself, we honor the $applyState flag.
         */
        $shouldApplyToRoot = ($context === '' || str_contains($context, '.')) ? $applyState : true;

        if (! $root && $targetId) {
            return null;
        }

        if ($context === '') {
            $root = $root ?? app($state->rootModelClass);
        }

        if ($root instanceof Model && $shouldApplyToRoot) {
            // Apply current root form form to the re-hydrated model
            $rootData = Arr::where($state->all(), fn ($v) => ! is_array($v));
            foreach ($rootData as $k => $v) {
                $k = (string) $k;
                $v = $this->processor->sanitizeValue($k, $v, $state->getNullables(), $root);
                if ($root->isFillable($k) && $root->getAttribute($k) !== $v) {
                    $root->setAttribute($k, $v);
                }
            }
        }

        if ($context === '') {
            return $root instanceof Model ? $root : null;
        }

        // Resolve relationship (handles nested paths like 'cities.name')
        try {
            $parts = Str::of($context)->explode('.');
            $result = $root;

            foreach ($parts as $index => $part) {
                if ($result === null) {
                    break;
                }
                $isLast = ($index === $parts->count() - 1);
                if ($isLast) {
                    $result = ($id !== null)
                        ? $result->{$part}()->find($id)
                        : $result->{$part}()->getRelated()->newInstance();
                } else {
                    $result = $result->{$part};
                }
            }

            if ($result instanceof Model && $applyState) {
                $contextData = data_get($state->all(), $context, []);
                if (is_array($contextData)) {
                    if ($id !== null && isset($contextData[$id]) && is_array($contextData[$id])) {
                        $contextData = $contextData[$id];
                    }
                    $dataToFill = Arr::where($contextData, fn ($v) => ! is_array($v));
                    foreach ($dataToFill as $k => $v) {
                        $k = (string) $k;
                        $v = $this->processor->sanitizeValue("$context.$k", $v, $state->getNullables(), $result);
                        if ($result->isFillable($k) && $result->getAttribute($k) !== $v) {
                            $result->setAttribute($k, $v);
                        }
                    }
                }
            }

            return $result instanceof Model ? $result : null;
        } catch (\BadMethodCallException $e) {
            return null;
        }
    }
}
