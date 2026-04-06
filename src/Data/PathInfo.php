<?php

namespace SchenkeIo\LivewireAutoForm\Data;

use Spatie\LaravelData\Data;

/**
 * Class PathInfo
 *
 * Data Transfer Object representing a resolved dotted path.
 *
 * @property array<int, string> $relationChain The sequence of relationship names.
 * @property string $targetAttribute The final attribute name in the target model.
 */
class PathInfo extends Data
{
    /**
     * @param  array<int, string>  $relationChain
     */
    public function __construct(
        public array $relationChain,
        public string $targetAttribute,
    ) {}

    /**
     * Returns the full relationship path as a dotted string.
     */
    public function getRelationPath(): string
    {
        return implode('.', $this->relationChain);
    }
}
