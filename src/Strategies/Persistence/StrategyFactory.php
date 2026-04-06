<?php

namespace SchenkeIo\LivewireAutoForm\Strategies\Persistence;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Class StrategyFactory
 *
 * Resolves the appropriate PersistenceStrategy based on the type of Eloquent relationship.
 */
class StrategyFactory
{
    /**
     * Resolve the persistence strategy for the given Eloquent relationship.
     *
     * @param  mixed  $relation  The Eloquent relationship instance.
     * @return PersistenceStrategy|null The matched strategy or null if not supported.
     */
    public static function make(mixed $relation): ?PersistenceStrategy
    {
        if ($relation instanceof BelongsTo) {
            return new BelongsToStrategy;
        }

        if ($relation instanceof HasMany || $relation instanceof MorphMany) {
            return new HasManyStrategy;
        }

        if ($relation instanceof HasOne || $relation instanceof MorphOne) {
            return new HasOneStrategy;
        }

        if ($relation instanceof BelongsToMany) {
            return new BelongsToManyStrategy;
        }

        return null;
    }
}
