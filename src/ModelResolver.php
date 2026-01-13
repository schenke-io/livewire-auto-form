<?php

namespace SchenkeIo\LivewireAutoForm;

use Illuminate\Database\Eloquent\Model;

class ModelResolver
{
    /**
     * Resolves the model instance based on the context and ID.
     *
     * @param  FormCollection  $state  The $form buffer.
     * @param  string  $context  The context (empty for root, or relation name).
     * @param  int|string|null  $id  The ID of the record.
     * @param  bool  $applyState  Whether to apply the current form state to the model.
     *
     * @throws LivewireAutoFormException
     */
    public function resolve(FormCollection $state, string $context, int|string|null $id, bool $applyState = true): ?Model
    {
        $targetId = ($context === '') ? ($id ?? $state->rootModelId) : $state->rootModelId;

        // Re-hydrate the root model
        /** @var Model|null $root */
        $root = $targetId ? app($state->rootModelClass)->find($targetId) : null;

        /*
         * We ALWAYS apply state to the root model if we are resolving a relationship,
         * because we need the current foreign keys to find the related record.
         * If we are resolving the root itself, we honor the $applyState flag.
         */
        $shouldApplyToRoot = ($context === '') ? $applyState : true;

        if ($root && $shouldApplyToRoot) {
            // Apply current root form data to the re-hydrated model
            foreach ($state->all() as $k => $v) {
                $stringKey = (string) $k;
                if (! is_array($v) && $root->isFillable($stringKey)) {
                    $root->setAttribute($stringKey, $v);
                }
            }
        }

        if (! $root && $targetId) {
            return null;
        }

        if ($context === '') {
            /** @var Model $rootModelClassInstance */
            $rootModelClassInstance = new $state->rootModelClass;

            return $root ?? $rootModelClassInstance;
        }

        // Resolve relationship
        try {
            if ($id === null) {
                /** @var mixed $root */
                $result = $root?->{$context}()->getRelated()->newInstance();
            } else {
                /** @var mixed $root */
                $result = $root?->{$context}()->find($id);
            }

            if ($result instanceof Model && $applyState) {
                $contextData = $state->get($context) ?? [];
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
            throw LivewireAutoFormException::relationDoesNotExist($context, $className);
        }
    }
}
