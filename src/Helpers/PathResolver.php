<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use SchenkeIo\LivewireAutoForm\Data\PathInfo;

class PathResolver
{
    /** @var array<string, PathInfo> */
    protected static array $cache = [];

    /**
     * Resolves a dotted path (e.g. 'user.profile.name') into a PathInfo DTO
     * which separates relations from the final attribute.
     */
    public function resolve(Model $root, string $path): PathInfo
    {
        $cacheKey = get_class($root).':'.$path;
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $allParts = Str::of($path)->explode('.');
        $relationChain = [];
        $attributeParts = [];
        $currentModel = $root;

        foreach ($allParts as $index => $part) {
            // Check if this part is a relation on the current model
            if ($currentModel->isRelation($part)) {
                $relationChain[] = $part;

                // Move to next model IF there are more parts
                if ($index < $allParts->count() - 1) {
                    try {
                        $relation = $currentModel->$part();
                        if ($relation instanceof Relation) {
                            $currentModel = $relation->getRelated();
                        } else {
                            // The part is a relation but didn't return a Relation object
                            // This might happen with some custom implementations
                            $attributeParts = $allParts->slice($index + 1)->toArray();
                            break;
                        }
                    } catch (\Throwable) {
                        // Cannot traverse deeper, treat remaining parts as attributes
                        $attributeParts = $allParts->slice($index + 1)->toArray();
                        break;
                    }
                }

                continue;
            }

            // If we reach here, this part and all remaining parts form the target attribute
            $attributeParts = $allParts->slice($index)->toArray();
            break;
        }

        // If all parts were relations, the target attribute is empty
        $targetAttribute = (empty($attributeParts)) ? '' : implode('.', $attributeParts);

        $pathInfo = new PathInfo(
            relationChain: $relationChain,
            targetAttribute: $targetAttribute
        );

        return self::$cache[$cacheKey] = $pathInfo;
    }

    /**
     * Clear the static cache
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
