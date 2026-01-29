<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use ArrayAccess;
use ArrayIterator;
use Countable;
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
 * - `autoSave`: Flag indicating if changes should be persisted immediately.
 *
 * Wireable Compliance:
 * Implements `Livewire\Wireable` to ensure the entire buffer and its metadata
 * can be seamlessly serialized and de-serialized between Livewire server-side
 * and client-side, preserving complex nested state across requests.
 *
 * @property string $activeContext
 * @property int|string|null $activeId
 * @property string|null $rootModelClass
 * @property int|string|null $rootModelId
 * @property array<int, string> $nullables
 * @property bool $autoSave
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
final class FormCollection implements ArrayAccess, Countable, IteratorAggregate, Wireable
{
    public const string SYSTEM_KEY = '__system';

    /** @var array{activeContext: string, activeId: int|string|null, rootModelClass: string|null, rootModelId: int|string|null, nullables: array<int, string>, autoSave: bool} */
    public array $meta = [
        'activeContext' => '',
        'activeId' => null,
        'rootModelClass' => null,
        'rootModelId' => null,
        'nullables' => [],
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

    public function setContext(string $context, int|string|null $id): void
    {
        $this->meta['activeContext'] = $context;
        $this->meta['activeId'] = $id;
        if ($context === '') {
            $this->meta['rootModelId'] = $id;
        }
    }

    public function setRootModel(?string $class, int|string|null $id): void
    {
        $this->meta['rootModelClass'] = $class;
        $this->meta['rootModelId'] = $id;
    }

    /**
     * @param  array<int, string>  $nullables
     */
    public function setNullables(array $nullables): void
    {
        $this->meta['nullables'] = $nullables;
    }

    public function setActiveId(int|string|null $id): void
    {
        $this->meta['activeId'] = $id;
    }

    public function clearData(): void
    {
        $this->items = [];
    }

    public function isRoot(): bool
    {
        return $this->meta['activeContext'] === '';
    }

    public function getActiveContext(): string
    {
        return $this->meta['activeContext'];
    }

    public function getActiveId(): int|string|null
    {
        return $this->meta['activeId'];
    }

    public function getRootModelClass(): ?string
    {
        return $this->meta['rootModelClass'];
    }

    public function getRootModelId(): int|string|null
    {
        return $this->meta['rootModelId'];
    }

    /**
     * @return array<int, string>
     */
    public function getNullables(): array
    {
        return $this->meta['nullables'];
    }

    public function isAutoSave(): bool
    {
        return $this->meta['autoSave'];
    }

    public function setAutoSave(bool $autoSave): void
    {
        $this->meta['autoSave'] = $autoSave;
    }

    /**
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

    public function has(string|int $key): bool
    {
        return array_key_exists((string) $key, $this->items);
    }

    public function get(string|int $key, mixed $default = null): mixed
    {
        return array_key_exists((string) $key, $this->items) ? $this->items[(string) $key] : $default;
    }

    public function put(string|int $key, mixed $value): self
    {
        if ($key === self::SYSTEM_KEY) {
            throw LivewireAutoFormException::forbiddenKey((string) $key, self::class);
        }
        $this->items[(string) $key] = $value;

        return $this;
    }

    /**
     * @param  string|int|array<int, string|int>  $keys
     */
    public function forget(string|int|array $keys): self
    {
        foreach ((array) $keys as $key) {
            unset($this->items[(string) $key]);
        }

        return $this;
    }

    public function __get(string $key): mixed
    {
        return match ($key) {
            'activeContext' => $this->getActiveContext(),
            'activeId' => $this->getActiveId(),
            'rootModelClass' => $this->getRootModelClass(),
            'rootModelId' => $this->getRootModelId(),
            'nullables' => $this->getNullables(),
            'autoSave' => $this->isAutoSave(),
            default => $this->get($key),
        };
    }

    public function __isset(string $key): bool
    {
        if (in_array($key, ['activeContext', 'activeId', 'rootModelClass', 'rootModelId', 'nullables', 'autoSave'])) {
            return true;
        }

        return $this->has($key);
    }

    public function __set(string $key, mixed $value)
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
            $data = [];
        }

        data_set($data, $remainingKey, $value);
        $this->put($firstKey, $data);
    }
}
