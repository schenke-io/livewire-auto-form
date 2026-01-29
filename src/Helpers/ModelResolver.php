<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Illuminate\Database\Eloquent\Model;

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
    /**
     * Resolves the model instance based on the context and ID.
     *
     * @param  FormCollection  $state  The $form buffer.
     * @param  string  $context  The context (empty for root, or relation name).
     * @param  int|string|null  $id  The ID of the record.
     * @param  bool  $applyState  Whether to apply the current form state to the model.
     * @param  Model|null  $model  Optional model instance to use instead of fetching from DB.
     *
     * @throws LivewireAutoFormException
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
            foreach ($state->all() as $k => $v) {
                $stringKey = (string) $k;
                if (! is_array($v) && $root->isFillable($stringKey)) {
                    $root->setAttribute($stringKey, $v);
                }
            }
        }

        if ($context === '') {
            return $root instanceof Model ? $root : null;
        }

        // Resolve relationship (handles nested paths like 'cities.name')
        try {
            $parts = explode('.', $context);
            $result = $root;

            foreach ($parts as $index => $part) {
                if ($result === null) {
                    break;
                }
                $isLast = ($index === count($parts) - 1);
                if ($isLast) {
                    if ($id !== null) {
                        /** @var mixed $result */
                        $result = $result->{$part}()->find($id);
                    } else {
                        /** @var mixed $result */
                        $result = $result->{$part}()->getRelated()->newInstance();
                    }
                } else {
                    $result = $result->{$part};
                }
            }

            if ($result instanceof Model && $applyState) {
                $contextData = data_get($state->all(), $context, []);
                if (is_array($contextData)) {
                    foreach ($contextData as $k => $v) {
                        $stringKey = (string) $k;
                        if (! is_array($v) && $result->isFillable($stringKey)) {
                            $result->setAttribute($stringKey, $v);
                        }
                    }
                }
            }

            return $result instanceof Model ? $result : null;
        } catch (\BadMethodCallException $e) {
            $className = $root instanceof Model ? get_class($root) : 'unknown';
            throw LivewireAutoFormException::relationDoesNotExist($context, $className, self::class);
        }
    }
}
