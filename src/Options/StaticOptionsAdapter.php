<?php

namespace SchenkeIo\LivewireAutoForm\Options;

/**
 * Class StaticOptionsAdapter
 *
 * Resolves options from a static array.
 */
class StaticOptionsAdapter extends BaseOptionsAdapter
{
    /**
     * @param  array<string|int, string|array<string|int, mixed>>  $options
     */
    public function __construct(protected array $options) {}

    /**
     * Get options from the static array.
     *
     * @param  string|null  $labelMask  (Ignored for static arrays)
     * @param  string|null  $origin  Origin context for error reporting.
     * @return array<int, array{0: string|int, 1: string, 2?: string}>
     */
    public function getOptions(?string $labelMask = null, ?string $origin = null): array
    {
        return $this->mapOptions($this->options);
    }
}
