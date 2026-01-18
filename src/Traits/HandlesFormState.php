<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

use SchenkeIo\LivewireAutoForm\Helpers\FormCollection;

/**
 * Handles the internal form buffer and state management for the form.
 *
 * This trait provides a centralized mechanism for managing the form's state,
 * abstracting the complexities of interacting with the `FormCollection` buffer.
 * It implements `ArrayAccess` and magic methods to allow the Component
 * to treat itself as a transparent form container while internally
 * routing form through the robust buffering system.
 *
 * Key Responsibilities:
 * - Initialization of the `FormCollection` buffer.
 * - Routing property access and assignment between the component and the buffer.
 * - Implementation of `ArrayAccess` for flexible form interaction.
 * - Providing utility methods for form retrieval and state checking.
 *
 * Role in Architecture:
 * It serves as the form foundation for both `AutoForm` and `AutoWizardForm`,
 * ensuring that form is consistently buffered and sanitized before persistence.
 */
trait HandlesFormState
{
    /**
     * The internal form buffer for form state and relationship management.
     */
    public FormCollection $form;

    /**
     * Determines if changes are persisted immediately to the database.
     *
     * When set to true, any update to a field will trigger an immediate
     * save operation through the CRUD processor, provided the model exists.
     */
    protected bool $autoSave = false;

    /**
     * Boot the trait and initialize the form buffer.
     * This is automatically called by Livewire.
     */
    public function bootHandlesFormState(): void
    {
        if (! isset($this->form)) {
            $this->form = new FormCollection;
        }
    }

    /**
     * Initializes the internal form buffer manually if needed.
     */
    public function initializeHasAutoForm(): void
    {
        $this->bootHandlesFormState();
    }

    /**
     * ArrayAccess: Checks if a property exists in the buffer or state.
     *
     * This allows the component to use `isset($form['field'])` to check for
     * the existence of form or internal state properties.
     */
    public function offsetExists(mixed $offset): bool
    {
        $offset = (string) $offset;

        return $this->form->has($offset) || in_array($offset, ['activeContext', 'activeId', 'rootModelClass', 'rootModelId', 'nullables', 'autoSave']);
    }

    /**
     * ArrayAccess: Retrieves a property from the buffer or state.
     *
     * Enables accessing form via array syntax, e.g., `$value = $form['field'];`.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->form->{(string) $offset};
    }

    /**
     * ArrayAccess: Sets a property in the buffer or state.
     *
     * Enables setting form via array syntax, e.g., `$form['field'] = $value;`.
     * It intelligently routes the assignment either to a class property
     * or directly into the form buffer.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $offset = (string) $offset;
        if (property_exists($this, $offset)) {
            $this->{$offset} = $value;
        } else {
            $this->form->setNested($offset, $value);
        }
    }

    /**
     * ArrayAccess: Removes a property from the buffer.
     *
     * Enables removing form via array syntax, e.g., `unset($form['field']);`.
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->form->forget((string) $offset);
    }

    /**
     * Returns all form from the buffer.
     *
     * Useful for retrieving the entire dataset for validation or debugging.
     *
     * @return array<int|string, mixed>
     */
    public function all(): array
    {
        return $this->form->all();
    }

    /**
     * Gets a value from the buffer with an optional default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->form->get($key, $default);
    }

    /**
     * Checks if a key exists in the buffer.
     */
    public function has(string $key): bool
    {
        return $this->form->has($key);
    }

    // deleted magic methods
}
