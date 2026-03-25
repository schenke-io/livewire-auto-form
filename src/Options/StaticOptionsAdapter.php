<?php

namespace SchenkeIo\LivewireAutoForm\Options;

class StaticOptionsAdapter extends BaseOptionsAdapter
{
    /**
     * @param  array<string|int, string|array<string|int, mixed>>  $options
     */
    public function __construct(protected array $options) {}

    public function getOptions(?string $labelMask = null, ?string $origin = null): array
    {
        return $this->mapOptions($this->options);
    }
}
