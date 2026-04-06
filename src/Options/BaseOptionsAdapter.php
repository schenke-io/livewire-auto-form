<?php

namespace SchenkeIo\LivewireAutoForm\Options;

/**
 * Abstract class BaseOptionsAdapter
 *
 * Provides common functionality for mapping raw options into the standard [value, label, icon?] format.
 */
abstract class BaseOptionsAdapter implements OptionsResolver
{
    /**
     * Maps an associative array of options to the format expected by the frontend.
     *
     * Handles simple scalar options, icon-based options, and structured arrays with keys/replacements.
     *
     * @param  array<string|int, string|array<string|int, mixed>>  $options
     * @return array<int, array{0: string|int, 1: string, 2?: string}>
     */
    protected function mapOptions(array $options): array
    {
        return collect($options)
            ->map(function ($item, $value) {
                if (is_array($item)) {
                    if (isset($item[0]) && isset($item[1]) && is_string($item[0]) && is_string($item[1]) && ! isset($item['key'])) {
                        /*
                         * this is an icon array: [label, icon]
                         */
                        return [$value, __($item[0]), $item[1]];
                    }
                    /** @var array<string|int, mixed> $item */
                    $key = $item['key'] ?? $item[0] ?? '';
                    /** @var array<string, mixed> $replace */
                    $replace = (array) ($item['replace'] ?? $item[1] ?? []);

                    return [$value, __((string) $key, $replace)];
                }

                return [$value, __((string) $item)];
            })
            ->values()->toArray();
    }
}
