<?php

namespace SchenkeIo\LivewireAutoForm\Traits;

trait AutoFormLocalisedModelOptions
{
    /**
     * Standard implementation of AutoFormOptions::getOptions() with localization support.
     *
     * @param  string|null  $labelMask  Optional prefix override
     * @return array<string|int, string|array{0: string, 1: string}>
     */
    public static function getOptions(?string $labelMask = null): array
    {
        $options = [];
        $prefix = $labelMask ?: (defined('static::OPTION_TRANSLATION_PREFIX') ? constant('static::OPTION_TRANSLATION_PREFIX') : '');
        $base = class_basename(static::class);

        foreach (static::all() as $model) {
            $value = $model->getKey();
            $key = $prefix ? "{$prefix}.{$base}.{$value}" : "{$base}.{$value}";
            $label = __($key);

            if (method_exists($model, 'icon')) {
                $options[$value] = [$label, $model->icon()];
            } else {
                $options[$value] = $label;
            }
        }

        return $options;
    }
}
