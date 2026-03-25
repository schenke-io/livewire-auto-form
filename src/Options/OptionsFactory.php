<?php

namespace SchenkeIo\LivewireAutoForm\Options;

use Illuminate\Database\Eloquent\Model;

class OptionsFactory
{
    /**
     * @param  class-string  $enumClass
     */
    public static function forEnum(string $enumClass): OptionsResolver
    {
        return new EnumOptionsAdapter($enumClass);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function forModel(string $modelClass): OptionsResolver
    {
        return new EloquentOptionsAdapter($modelClass);
    }

    /**
     * @param  array<string|int, string|array<string|int, mixed>>  $options
     */
    public static function forArray(array $options): OptionsResolver
    {
        return new StaticOptionsAdapter($options);
    }
}
