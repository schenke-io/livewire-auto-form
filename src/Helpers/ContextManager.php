<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Illuminate\Database\Eloquent\Model;

/**
 * Manages the switching and loading of different form contexts (root or relations).
 *
 * This class coordinates between the FormCollection state, the ModelResolver,
 * and the DataProcessor to ensure that the correct form is loaded into the
 * form's buffer when switching between editing the main model and its
 * associated relationships.
 *
 * Context Stack Management:
 * It handles the transition between the root model (empty context) and related
 * models (named contexts). When switching to a relationship context, it extracts
 * the specific related record's data. When returning to the root context, it
 * optionally preserves any unsaved changes in related contexts, effectively
 * managing a nested state stack within the flat `FormCollection` buffer.
 */
class ContextManager
{
    public function __construct(
        public FormCollection $state,
        protected ModelResolver $resolver,
        protected DataProcessor $processor
    ) {}

    /**
     * Loads form for the currently active context into the $form buffer.
     *
     * @param  string  $context  The context (empty for root, or relation name).
     * @param  int|string|null  $id  The ID of the record.
     * @param  array<string, mixed>  $rules  The validation rules.
     * @param  bool  $preserveRelations  Whether to keep existing relation form in the form.
     * @param  Model|null  $model  Optional model instance to use.
     *
     * @throws LivewireAutoFormException
     */
    public function loadContext(string $context, int|string|null $id, array $rules, bool $preserveRelations = true, ?Model $model = null): void
    {
        $this->state->setContext($context, $id);

        $model = $this->resolver->resolve($this->state, $context, $id, false, $model);

        if (! $model) {
            if ($context === '') {
                $this->state->clearData();
            } else {
                $this->state->forget([$context]);
            }

            return;
        }

        $data = $this->processor->extractFilteredData($model, $rules, $context);

        if ($context === '') {
            // we keep what we have for relations
            $relationsData = [];
            if ($preserveRelations) {
                $relations = $this->processor->findRelations($rules);

                foreach ($relations as $rel) {
                    if ($this->state->has($rel)) {
                        /*
                         * Background: When the root model is reloaded (e.g., after a save or manual refresh),
                         * we want to preserve the form of any relations that are currently being edited.
                         * This ensures that if a user is mid-edit in a sub-form (like editing a related address),
                         * their unsaved changes in that sub-form are not wiped out when the main model
                         * synchronizes with the database. We check if the relation key exists in the current
                         * state and if so, we temporarily hold it to merge it back into the refreshed form set.
                         */
                        $relationsData[$rel] = $this->state->get($rel);
                    }
                }
            }
            $merged = array_merge($data, $relationsData);
            $this->state->forget(array_keys($this->state->all()));
            foreach ($merged as $k => $v) {
                $this->state->put($k, $v);
            }
        } else {
            $this->state->setNested($context, $data);
        }
    }
}
