<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

use Illuminate\Support\Str;

/**
 * Trait AutoFormLocalisedEnumOptions
 *
 * Provides a default implementation of the AutoFormOptions interface for Enums,
 * automatically generating translation keys based on a prefix and the case values.
 *
 * Usage:
 * ```php
 * enum UserStatus: string implements AutoFormOptions {
 *     use AutoFormLocalisedEnumOptions;
 *     const OPTION_TRANSLATION_PREFIX = 'enums.user_status';
 *
 *     case ACTIVE = 'active';
 *     case PENDING = 'pending';
 * }
 * ```
 * This will look for 'enums.user_status.active' and 'enums.user_status.pending'.
 */
trait AutoFormLocalisedEnumOptions
{
    /**
     * Standard implementation of AutoFormOptions::getOptions() with localization support.
     *
     * It uses the following priority for the translation prefix:
     * 1. The $labelMask parameter (if provided).
     * 2. The OPTION_TRANSLATION_PREFIX constant (if defined in the class).
     * 3. A default prefix derived from the class name (e.g. "UserStatus" -> "user_status").
     *
     * @param  string|null  $labelMask  Optional prefix override
     * @return array<string|int, string>
     */
    public static function getOptions(?string $labelMask = null): array
    {
        $prefix = $labelMask ?? (defined('static::OPTION_TRANSLATION_PREFIX') ? static::OPTION_TRANSLATION_PREFIX : Str::snake(class_basename(static::class)));

        $options = [];
        if (enum_exists(static::class)) {
            foreach (static::cases() as $case) {
                $value = $case instanceof \BackedEnum ? $case->value : $case->name;
                $options[$value] = $prefix ? __("$prefix.$value") : (string) $value;
            }
        }

        return $options;
    }
}
