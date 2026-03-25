<?php

namespace SchenkeIo\LivewireAutoForm\Options;

interface OptionsResolver
{
    /**
     * @return array<int, array{0: string|int, 1: string}>
     */
    public function getOptions(?string $labelMask = null, ?string $origin = null): array;
}
