<?php

namespace SchenkeIo\LivewireAutoForm;

use Illuminate\Support\Collection;
use Livewire\Wireable;

/**
 * @extends Collection<string|int, mixed>
 *
 * @property-read string|null $activeContext
 * @property-read int|string|null $activeId
 * @property-read string $rootModelClass
 * @property-read int|string|null $rootModelId
 * @property-read array<int, string> $nullables
 */
final class FormCollection extends Collection implements Wireable
{
    public const string SYSTEM_KEY = '__system';

    protected ?string $activeContext = '';

    protected int|string|null $activeId = null;

    protected string $rootModelClass = '';

    protected int|string|null $rootModelId = null;

    /** @var array<int, string> */
    protected array $nullables = [];

    public bool $autoSave = false;

    /**
     * Convert the collection to a format that Livewire can use.
     *
     * @return array<string, mixed> The state for Livewire.
     */
    public function toLivewire(): array
    {
        $data = $this->items;
        $data[self::SYSTEM_KEY] = [
            'activeContext' => $this->activeContext,
            'activeId' => $this->activeId,
            'rootModelClass' => $this->rootModelClass,
            'rootModelId' => $this->rootModelId,
            'nullables' => $this->nullables,
            'autoSave' => $this->autoSave,
        ];

        return $data;
    }

    /**
     * Restore the collection from a Livewire state.
     *
     * @param  mixed  $value  The state from Livewire.
     * @return FormCollection The restored collection instance.
     */
    public static function fromLivewire($value): FormCollection
    {
        $state = $value[self::SYSTEM_KEY] ?? [];
        unset($value[self::SYSTEM_KEY]);
        $instance = new self($value);
        foreach ($state as $key => $val) {
            $instance->{$key} = $val;
        }

        return $instance;
    }

    /**
     * Set the active context and record ID.
     *
     * @param  string  $context  The context (empty for root, or relation name).
     * @param  int|string|null  $id  The ID of the record.
     */
    public function setContext(string $context, int|string|null $id): void
    {
        $this->activeContext = $context;
        $this->activeId = $id;
        if ($context === '') {
            $this->rootModelId = $id;
        }
    }

    /**
     * Set the root model class and ID.
     */
    public function setRootModel(string $class, int|string|null $id): void
    {
        $this->rootModelClass = $class;
        $this->rootModelId = $id;
    }

    /**
     * Set the list of nullable fields.
     *
     * @param  array<int, string>  $nullables
     */
    public function setNullables(array $nullables): void
    {
        $this->nullables = $nullables;
    }

    /**
     * Set the active record ID.
     */
    public function setActiveId(int|string|null $id): void
    {
        $this->activeId = $id;
    }

    /**
     * Clear the data collection while preserving state.
     */
    public function clearData(): void
    {
        $this->items = [];
    }

    /**
     * Check if the current context is the root model.
     *
     * @return bool True if root context, false otherwise.
     */
    public function isRoot(): bool
    {
        /*
         * Background: This is a semantic helper method used throughout the component
         * to quickly determine if the user is currently interacting with the main
         * (root) model or a related model sub-form. Having this as a method improves
         * code readability compared to repeated string comparisons against the
         * activeContext property.
         */
        return $this->activeContext === '';
    }

    /**
     * Dynamic getter for properties and collection items.
     *
     * @param  string  $key  The property or item key.
     * @return mixed The value.
     */
    public function __get($key)
    {
        if (property_exists($this, (string) $key)) {
            /*
             * Background: Since FormCollection extends the base Collection class but also
             * introduces its own public properties (like activeContext, rootModelId, etc.),
             * this dynamic getter ensures that accessing these properties works as expected.
             * It prioritizes actual class properties over items stored within the collection's
             * internal items array, allowing the collection to act both as a state container
             * and a data container simultaneously.
             */
            return $this->{$key};
        }

        return $this->get($key);
    }

    /**
     * Dynamic isset check for properties and collection items.
     *
     * @param  string  $key  The property or item key.
     * @return bool True if exists.
     */
    public function __isset($key)
    {
        return property_exists($this, (string) $key) || $this->has($key);
    }

    public function put($key, $value)
    {
        if ($key === self::SYSTEM_KEY) {
            throw LivewireAutoFormException::forbiddenKey($key);
        }

        return parent::put($key, $value);
    }

    /**
     * Dynamic setter for properties and collection items.
     *
     * @param  string  $key  The property or item key.
     * @param  mixed  $value  The value.
     */
    public function __set(string $key, mixed $value)
    {
        if ($key === self::SYSTEM_KEY) {
            throw LivewireAutoFormException::forbiddenKey($key);
        }
        $protected = ['activeContext', 'activeId', 'rootModelClass', 'rootModelId', 'nullables'];
        if (in_array($key, $protected)) {
            /*
             * Background: These four properties define the internal state of the
             * FormCollection (which model and record are currently being edited).
             * They are intentionally protected to prevent accidental or malicious
             * overriding from the frontend via Livewire's data binding. When an
             * attempt is made to set these via the magic __set method (from outside
             * the class), the values are diverted into the collection's items array.
             * This ensures that the component's internal logic, which relies on the
             * protected properties, remains untampered, while still providing a
             * "landing spot" for any external binding attempts to prevent errors.
             */
            $this->put($key, $value);

            return;
        }

        if (property_exists($this, $key)) {
            $this->{$key} = $value;

            return;
        }

        $this->put($key, $value);
    }

    public function offsetSet($key, $value): void
    {
        if ($key === self::SYSTEM_KEY) {
            throw LivewireAutoFormException::forbiddenKey($key);
        }
        parent::offsetSet($key, $value);
    }

    /**
     * Set a value in the collection using dot notation.
     *
     * @param  string  $key  The key (can use dot notation).
     * @param  mixed  $value  The value to set.
     */
    public function setNested(string $key, mixed $value): void
    {
        if (! str_contains($key, '.')) {
            $this->__set($key, $value);

            return;
        }

        $parts = explode('.', $key);
        $firstKey = array_shift($parts);
        $remainingKey = implode('.', $parts);

        $data = $this->get($firstKey, []);
        if (! is_array($data)) {
            /*
             * Background: When setting a nested value (e.g., 'address.city'), we first
             * retrieve the existing value for the top-level key ('address'). If it
             * doesn't exist or is not an array (e.g., it was null or a string), we
             * must initialize it as an empty array. This ensures that data_set()
             * can correctly build the nested structure without encountering type
             * mismatches or errors when trying to use array access on non-array types.
             */
            $data = [];
        }

        data_set($data, $remainingKey, $value);
        $this->put($firstKey, $data);
    }
}
