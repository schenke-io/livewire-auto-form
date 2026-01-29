<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

use Illuminate\Support\Collection;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;

/**
 * Handles relationship management logic for the form.
 *
 * This trait provides the functionality to navigate and manipulate Eloquent
 * relationships within the form. It allows the component to switch between
 * editing the main (root) model and any of its defined relationships (like
 * 'address', 'phones', or 'tags').
 *
 * Key Capabilities:
 * - **Context Switching**: Enables moving between the root model and related records using `edit()` and `add()`.
 * - **Context Stack**: Maintains the `activeContext` and `activeId`, which determine which part of the
 *   `FormCollection` buffer is currently being targeted by UI operations.
 * - **Relationship Resolution**: Uses the `ModelResolver` to dynamically re-hydrate related models
 *   and their current buffered changes for accurate traversal and persistence.
 * - **Security Enforcement**: Strictly allows only those relationships that have corresponding
 *   validation rules defined in the component's `rules()` method.
 * - **Data Fetching**: Provides `getRelationList()` to retrieve collections of related models
 *   with optimized column selection based on rule discovery.
 */
trait HandlesRelations
{
    /**
     * The name of the relationship currently being edited (empty for root).
     */
    public string $activeContext = '';

    /**
     * The ID of the record currently being edited in the active context.
     */
    public string|int|null $activeId = null;

    /**
     * Sets the form to edit a specific related record.
     *
     * This method switches the `activeContext` to the specified relation
     * and the `activeId` to the record's primary key. It also triggers
     * a load of the record's form into the form buffer.
     *
     * @param  string  $relation  The name of the relationship (e.g., 'profile').
     * @param  int|string  $id  The ID of the related record.
     */
    public function edit(string $relation, int|string $id): void
    {
        $this->guardDirtyBuffer();
        $this->ensureRelationAllowed($relation);
        $this->loadContext($relation, $id);
    }

    /**
     * Sets the form to add a new related record.
     *
     * Similar to `edit`, but sets `activeId` to null, signaling the form
     * to prepare a fresh instance of the related model.
     *
     * @param  string  $relation  The name of the relationship.
     */
    public function add(string $relation): void
    {
        $this->guardDirtyBuffer();
        $this->ensureRelationAllowed($relation);
        $this->loadContext($relation, null);
    }

    /**
     * Deletes a record and updates the active context.
     *
     * This method handles the deletion of either the root model or a
     * related record. If the record being deleted is the one currently
     * active in the form, it resets the context to prevent errors.
     *
     * @param  string  $relation  The relation name (empty for root).
     * @param  int|string  $id  The ID of the record to delete.
     */
    public function delete(string $relation, int|string $id): void
    {
        $this->ensureRelationAllowed($relation);
        $this->getCrudProcessor()->delete($relation, $id);

        if ($this->form->getActiveContext() === $relation && $this->form->getActiveId() == $id) {
            $relation === '' ? $this->add('') : $this->cancel();
        }

        $this->getComponent()->dispatch('saved', context: $relation, id: $id);
    }

    /**
     * Sets the active context for the form.
     *
     * Synchronizes the internal `form` buffer state with the provided context and ID.
     */
    public function setContext(string $context, string|int|null $id): void
    {
        $this->activeContext = $context;
        $this->activeId = $id;
        $this->form->setContext($context, $id);
    }

    /**
     * Checks if a relation is allowed to be managed via this form.
     *
     * Security Check: A relationship is only considered allowed if it has
     * corresponding dot-notated keys in the `rules()` method of the component.
     */
    public function isRelationAllowed(string $relation): bool
    {
        if (empty($relation)) {
            return false;
        }

        // Logic based on rules - if the relation is present in the rules keys
        $rules = $this->rules();
        foreach (array_keys($rules) as $ruleKey) {
            if (str_starts_with($ruleKey, "$relation.")) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensures a relation is allowed, otherwise throws an exception.
     *
     * @throws LivewireAutoFormException
     */
    public function ensureRelationAllowed(string $relation): void
    {
        if ($relation !== '' && ! $this->isRelationAllowed($relation)) {
            throw LivewireAutoFormException::relationNotDefinedInRules($relation, static::class);
        }
    }

    /**
     * Checks if a specific related record is currently being edited.
     */
    public function isEdited(string $relation, int|string $id): bool
    {
        return $this->form->getActiveContext() === $relation && $this->form->getActiveId() == $id;
    }

    /**
     * Returns a list of related models for a specific relationship.
     *
     * This helper is useful for populating tables or lists of existing
     * related records. It automatically filters columns based on what's
     * defined in the `rules()` to optimize database performance.
     *
     * @param  string  $relation  The name of the relationship.
     * @return Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function getRelationList(string $relation): Collection
    {
        $this->ensureRelationAllowed($relation);

        return $this->getCrudProcessor()->getRelationList($relation, $this->rules());
    }
}
