<?php

namespace SchenkeIo\LivewireAutoForm\Options;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OptionsFactory
 *
 * Provides static methods to create various OptionsResolver instances.
 */
class OptionsFactory
{
    /**
     * Create a resolver for a PHP Enum.
     *
     * @param  class-string  $enumClass
     */
    public static function forEnum(string $enumClass): OptionsResolver
    {
        return new EnumOptionsAdapter($enumClass);
    }

    /**
     * Create a resolver for an Eloquent Model.
     *
     * @param  class-string<Model>  $modelClass
     */
    public static function forModel(string $modelClass): OptionsResolver
    {
        return new EloquentOptionsAdapter($modelClass);
    }

    /**
     * Create a resolver for a static array of options.
     *
     * @param  array<string|int, string|array<string|int, mixed>>  $options
     */
    public static function forArray(array $options): OptionsResolver
    {
        return new StaticOptionsAdapter($options);
    }
}
