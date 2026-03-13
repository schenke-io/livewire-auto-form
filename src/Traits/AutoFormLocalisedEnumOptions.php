<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

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
     * @return array<string|int, string|array{0: string, 1: string}>
     */
    public static function getOptions(?string $labelMask = null): array
    {
        $options = [];
        foreach (static::cases() as $case) {
            $label = $case->label($labelMask);
            $value = $case->name;
            if (method_exists($case, 'icon')) {
                $options[$value] = [$label, $case->icon()];
            } else {
                $options[$value] = $label;
            }
        }

        return $options;
    }

    public function label(?string $labelMask = null): string
    {
        $prefix = $labelMask ?: (defined('static::OPTION_TRANSLATION_PREFIX') ? constant('static::OPTION_TRANSLATION_PREFIX') : '');
        $base = class_basename(static::class);
        $name = strtolower($this->name);
        $key = $prefix ? "{$prefix}.{$base}.{$name}" : "{$base}.{$name}";

        return __($key);
    }
}
