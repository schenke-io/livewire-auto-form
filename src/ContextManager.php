<?php

namespace SchenkeIo\LivewireAutoForm;

class ContextManager
{
    public function __construct(
        public FormCollection $state,
        protected ModelResolver $resolver,
        protected DataProcessor $processor
    ) {}

    /**
     * Loads data for the currently active context into the $form buffer.
     *
     * @param  string  $context  The context (empty for root, or relation name).
     * @param  int|string|null  $id  The ID of the record.
     * @param  array<string, mixed>  $rules  The validation rules.
     * @param  bool  $preserveRelations  Whether to keep existing relation data in the form.
     *
     * @throws LivewireAutoFormException
     */
    public function loadContext(string $context, int|string|null $id, array $rules, bool $preserveRelations = true): void
    {
        $this->state->setContext($context, $id);

        $model = $this->resolver->resolve($this->state, $context, $id, false);

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
                // Derive relations from rules keys (e.g., "relation.field")
                $relations = [];
                foreach (array_keys($rules) as $ruleKey) {
                    $cleanKey = str_starts_with($ruleKey, 'form.') ? substr($ruleKey, 5) : $ruleKey;
                    if (str_contains($cleanKey, '.')) {
                        $relations[] = explode('.', $cleanKey)[0];
                    }
                }
                $relations = array_unique($relations);

                foreach ($relations as $rel) {
                    if ($this->state->has($rel)) {
                        /*
                         * Background: When the root model is reloaded (e.g., after a save or manual refresh),
                         * we want to preserve the data of any relations that are currently being edited.
                         * This ensures that if a user is mid-edit in a sub-form (like editing a related address),
                         * their unsaved changes in that sub-form are not wiped out when the main model
                         * synchronizes with the database. We check if the relation key exists in the current
                         * state and if so, we temporarily hold it to merge it back into the refreshed data set.
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
            $this->state->put($context, $data);
        }
    }
}
