<?php

namespace SchenkeIo\LivewireAutoForm;

/**
 * Interface AutoFormOptions
 *
 * This interface provides a standardized way to retrieve key-value pairs for use
 * in form selection elements (selects, radios, checkboxes).
 *
 * It is designed to be implemented by:
 *
 * 1. **Eloquent Models**: When a model represents a selectable entity in a relationship.
 *    Example:
 *    ```php
 *    class Country extends Model implements AutoFormOptions {
 *        public static function getOptions(?string $labelMask = null): array {
 *            return self::pluck('name', 'id')->toArray();
 *        }
 *    }
 *    ```
 *
 * 2. **Backed Enums**: When an enum represents the possible values of a model attribute.
 *    Example:
 *    ```php
 *    enum UserStatus: string implements AutoFormOptions {
 *        case ACTIVE = 'active';
 *        case PENDING = 'pending';
 *
 *        public static function getOptions(?string $labelMask = null): array {
 *            return [
 *                self::ACTIVE->value => 'User is Active',
 *                self::PENDING->value => 'User is Pending',
 *            ];
 *        }
 *    }
 *    ```
 *
 * 3. **Custom Option Providers**: Any class that can provide a static list of options.
 *
 * The `HandlesOptions` trait in the `HasAutoForm` system automatically detects
 * and uses this interface if implemented by the target model or enum, providing
 * a powerful way to centralize and customize option generation logic.
 */
interface AutoFormOptions
{
    /**
     * Return an array where the key is the value to be stored and the value is the human-readable label.
     *
     * This method is called by the `HandlesOptions` trait to populate select
     * inputs. It can use the optional `$labelMask` to format labels dynamically.
     *
     * @param  string|null  $labelMask  Optional mask to format the labels (e.g. "%name (%code)")
     * @return array<string|int, string> Associative array of [stored_value => human_label].
     */
    public static function getOptions(?string $labelMask = null): array;
}
