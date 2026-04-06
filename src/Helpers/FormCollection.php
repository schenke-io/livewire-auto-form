<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Illuminate\Support\Str;
use IteratorAggregate;
use Livewire\Wireable;
use Traversable;

/**
 * A specialized container for managing Livewire form state and metadata.
 *
 * This class serves as the primary data structure for the "Single Buffer" pattern.
 * It encapsulates both the raw form form and the metadata required to manage
 * complex relationship editing and context switching.
 *
 * Internal Meta-data Structure (`$meta`):
 * - `activeContext`: Identifies the current relationship being edited (empty for root).
 * - `activeId`: The ID of the specific record being edited in the active context.
 * - `rootModelClass`: The FQCN of the root Eloquent model.
 * - `rootModelId`: The ID of the root model instance.
 * - `nullables`: A list of fields that should convert empty strings to null.
 * - `jsonColumns`: A list of fields identified as JSON columns.
 * - `autoSave`: Flag indicating if changes should be persisted immediately.
 *
 * Wireable Compliance:
 * Implements `Livewire\Wireable` to ensure the entire buffer and its metadata
 * can be seamlessly serialized and de-serialized between Livewire server-side
 * and client-side, preserving complex nested state across requests.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 *
 * @property string $activeContext
 * @property int|string|null $activeId
 * @property string|null $rootModelClass
 * @property int|string|null $rootModelId
 * @property array<int, string> $nullables
 * @property array<int, string> $jsonColumns
 * @property bool $autoSave
 * @property array<string, mixed> $__system
 */
class FormCollection implements ArrayAccess, Countable, IteratorAggregate, Wireable
{
    public const string SYSTEM_KEY = '__system';

    /** @var array{activeContext: string, activeId: int|string|null, rootModelClass: string|null, rootModelId: int|string|null, nullables: array<int, string>, jsonColumns: array<int, string>, autoSave: bool} */
    public array $meta = [
        'activeContext' => '',
        'activeId' => null,
        'rootModelClass' => null,
        'rootModelId' => null,
        'nullables' => [],
        'jsonColumns' => [],
        'autoSave' => false,
    ];

    /** @var array<string, mixed> */
    protected array $items = [];

    /**
     * @param  array<string, mixed>  $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @return array<string, mixed>
     */
    public function toLivewire(): array
    {
        $data = $this->items;
        $data[self::SYSTEM_KEY] = $this->meta;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function fromLivewire($value): FormCollection
    {
        $meta = $value[self::SYSTEM_KEY] ?? [];
        unset($value[self::SYSTEM_KEY]);
        $instance = new self($value);
        /** @var array{activeContext: string, activeId: int|string|null, rootModelClass: string|null, rootModelId: int|string|null, nullables: array<int, string>, autoSave: bool} $meta */
        $instance->meta = array_merge($instance->meta, $meta);

        return $instance;
    }

    /**
     * Set the current editing context and active ID.
     */
    public function setContext(string $context, int|string|null $id): void
    {
        $this->meta['activeContext'] = $context;
        $this->meta['activeId'] = $id;
        if ($context === '') {
            $this->meta['rootModelId'] = $id;
        }
    }

    /**
     * Set the root model class and ID.
     */
    public function setRootModel(?string $class, int|string|null $id): void
    {
        $this->meta['rootModelClass'] = $class;
        $this->meta['rootModelId'] = $id;
    }

    /**
     * Set the list of fields that should be nullable.
     *
     * @param  array<int, string>  $nullables
     */
    public function setNullables(array $nullables): void
    {
        $this->meta['nullables'] = $nullables;
    }

    /**
     * Set the list of fields that are JSON columns.
     *
     * @param  array<int, string>  $jsonColumns
     */
    public function setJsonColumns(array $jsonColumns): void
    {
        $this->meta['jsonColumns'] = $jsonColumns;
    }

    /**
     * Get the list of JSON columns.
     *
     * @return array<int, string>
     */
    public function getJsonColumns(): array
    {
        return $this->meta['jsonColumns'];
    }

    /**
     * Check if a column is a JSON column.
     */
    public function isJsonColumn(string $column): bool
    {
        return in_array($column, $this->meta['jsonColumns']);
    }

    /**
     * Set the ID of the record being edited in the active context.
     */
    public function setActiveId(int|string|null $id): void
    {
        $this->meta['activeId'] = $id;
    }

    /**
     * Clear all form data (items), preserving meta-data.
     */
    public function clearData(): void
    {
        $this->items = [];
    }

    /**
     * Check if the current context is the root model.
     */
    public function isRoot(): bool
    {
        return $this->meta['activeContext'] === '';
    }

    /**
     * Get the current active context.
     */
    public function getActiveContext(): string
    {
        return $this->meta['activeContext'];
    }

    /**
     * Get the ID of the record being edited in the active context.
     */
    public function getActiveId(): int|string|null
    {
        return $this->meta['activeId'];
    }

    /**
     * Get the root model class name.
     */
    public function getRootModelClass(): ?string
    {
        return $this->meta['rootModelClass'];
    }

    /**
     * Get the root model ID.
     */
    public function getRootModelId(): int|string|null
    {
        return $this->meta['rootModelId'];
    }

    /**
     * Get the list of nullable fields.
     *
     * @return array<int, string>
     */
    public function getNullables(): array
    {
        return $this->meta['nullables'];
    }

    /**
     * Check if auto-save is enabled.
     */
    public function isAutoSave(): bool
    {
        return $this->meta['autoSave'];
    }

    /**
     * Set the auto-save flag.
     */
    public function setAutoSave(bool $autoSave): void
    {
        $this->meta['autoSave'] = $autoSave;
    }

    /**
     * Get all form data items.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Check if a key exists in the collection items.
     */
    public function has(string|int $key): bool
    {
        return array_key_exists((string) $key, $this->items);
    }

    /**
     * Get an item from the collection.
     */
    public function get(string|int $key, mixed $default = null): mixed
    {
        return array_key_exists((string) $key, $this->items) ? $this->items[(string) $key] : $default;
    }

    /**
     * Put an item into the collection.
     */
    public function put(string|int $key, mixed $value): self
    {
        if ($key === self::SYSTEM_KEY) {
            throw LivewireAutoFormException::forbiddenKey((string) $key, self::class);
        }
        $this->items[(string) $key] = $value;

        return $this;
    }

    /**
     * Remove one or more items from the collection.
     *
     * @param  string|int|array<int, string|int>  $keys
     */
    public function forget(string|int|array $keys): self
    {
        foreach ((array) $keys as $key) {
            unset($this->items[(string) $key]);
        }

        return $this;
    }

    /**
     * Magic getter for meta-data and items.
     */
    public function __get(string $key): mixed
    {
        return match ($key) {
            'activeContext' => $this->getActiveContext(),
            'activeId' => $this->getActiveId(),
            'rootModelClass' => $this->getRootModelClass(),
            'rootModelId' => $this->getRootModelId(),
            'nullables' => $this->getNullables(),
            'jsonColumns' => $this->getJsonColumns(),
            'autoSave' => $this->isAutoSave(),
            default => $this->get($key),
        };
    }

    public function __isset(string $key): bool
    {
        return match ($key) {
            'activeContext' => $this->getActiveContext() !== '',
            'activeId', 'rootModelId' => $this->getActiveId() !== null,
            'rootModelClass' => $this->getRootModelClass() !== null,
            'nullables' => $this->getNullables() !== [],
            'jsonColumns' => $this->getJsonColumns() !== [],
            'autoSave' => true,
            default => isset($this->items[$key]),
        };
    }

    /**
     * @throws LivewireAutoFormException
     */
    /**
     * Magic setter for meta-data and items. Supports dotted keys for nested items.
     */
    public function __set(string $key, mixed $value): void
    {
        if ($key === self::SYSTEM_KEY) {
            throw LivewireAutoFormException::forbiddenKey($key, self::class);
        }

        if (str_contains($key, '.')) {
            $this->setNested($key, $value);

            return;
        }

        match ($key) {
            'activeContext' => $this->setContext($value, $this->getActiveId()),
            'activeId' => $this->setActiveId($value),
            'rootModelClass' => $this->setRootModel($value, $this->getRootModelId()),
            'rootModelId' => $this->setRootModel($this->getRootModelClass(), $value),
            'nullables' => $this->setNullables($value),
            'jsonColumns' => $this->setJsonColumns($value),
            'autoSave' => $this->setAutoSave($value),
            default => $this->put($key, $value),
        };
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[(string) $offset]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Helper to prefix all rule keys with a property name (e.g., 'form.').
     *
     * @param  array<string, mixed>  $rules
     * @param  string  $propertyName  The property name to prefix with (e.g., 'form').
     * @return array<string, mixed>
     */
    public static function getPrefixedRules(array $rules, string $propertyName): array
    {
        $prefixedRules = [];
        foreach ($rules as $key => $value) {
            $prefixedRules[self::getPrefixedField((string) $key, $propertyName)] = $value;
        }

        return $prefixedRules;
    }

    /**
     * Helper to prefix a single field key with a property name.
     */
    public static function getPrefixedField(string $field, string $propertyName): string
    {
        return str_starts_with($field, $propertyName.'.') ? $field : $propertyName.'.'.$field;
    }

    /**
     * Set a value in the items array using a dotted path.
     */
    public function setNested(string $key, mixed $value): void
    {
        if (str_starts_with($key, self::SYSTEM_KEY.'.')) {
            throw LivewireAutoFormException::forbiddenKey($key, self::class);
        }

        if (! str_contains($key, '.')) {
            $this->__set($key, $value);

            return;
        }

        $firstKey = Str::before($key, '.');
        $remainingKey = Str::after($key, '.');

        $data = $this->get($firstKey, []);
        if (! is_array($data)) {
            $data = [];
        }

        data_set($data, $remainingKey, $value);
        $this->put($firstKey, $data);
    }
}
