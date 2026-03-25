<?php

namespace SchenkeIo\LivewireAutoForm\Data;

use Spatie\LaravelData\Data;

class PathInfo extends Data
{
    /**
     * @param  array<int, string>  $relationChain
     */
    public function __construct(
        public array $relationChain,
        public string $targetAttribute,
    ) {}

    public function getRelationPath(): string
    {
        return implode('.', $this->relationChain);
    }
}
