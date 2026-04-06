<?php

namespace SchenkeIo\LivewireAutoForm\Options;

/**
 * Interface OptionsResolver
 *
 * Defines the contract for resolving options for form fields (e.g., select, radio).
 */
interface OptionsResolver
{
    /**
     * Get the resolved options in a format suitable for the frontend.
     *
     * @param  string|null  $labelMask  Optional mask or column name for the label.
     * @param  string|null  $origin  The origin of the request for error reporting.
     * @return array<int, array{0: string|int, 1: string}> Array of [value, label] pairs.
     */
    public function getOptions(?string $labelMask = null, ?string $origin = null): array;
}
