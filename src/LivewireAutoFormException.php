<?php

namespace SchenkeIo\LivewireAutoForm;

use Exception;

class LivewireAutoFormException extends Exception
{
    public static function relationNotDefinedInRules(string $relation): self
    {
        return new self("Relation '{$relation}' not defined in rules.");
    }

    public static function fieldKeyNotDefinedInRules(string $key): self
    {
        return new self("Field key '{$key}' not defined in rules.");
    }

    public static function rootModelNotSet(): self
    {
        /*
         * Background: This exception is thrown when a component method that requires
         * a root model instance is called before that instance has been established.
         * The root model is typically set during the Livewire mount() lifecycle hook.
         * This specific error message is designed to guide the developer to ensure
         * they are passing a valid Eloquent model to the component's mount method,
         * which is a foundational requirement for the LivewireAutoForm logic to function.
         */
        return new self('Root model is not set. Ensure mount(Model $model) is called.');
    }

    public static function invalidRelationType(string $relation, string $type): self
    {
        return new self("Relation [{$relation}] is of type [{$type}] which is not supported for this operation.");
    }

    public static function relationDoesNotExist(string $relation, string $model): self
    {
        return new self("Relation [$relation] does not exist on [$model].");
    }

    public static function missingEnumCast(string $model, string $attribute): self
    {
        return new self("Attribute [{$attribute}] on model [{$model}] is not cast to an enum.");
    }

    public static function forbiddenKey(string $key): self
    {
        return new self("The key '{$key}' is reserved for internal use.");
    }
}
