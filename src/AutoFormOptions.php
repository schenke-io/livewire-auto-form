<?php

namespace SchenkeIo\LivewireAutoForm;

/**
 * Interface for providing options to select inputs.
 */
interface AutoFormOptions
{
    /**
     * @return array<string|int, string|array<string, mixed>>
     */
    public static function getOptions(?string $labelMask = null): array;
}
