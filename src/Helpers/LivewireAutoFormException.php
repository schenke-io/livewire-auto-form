<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Exception;

/**
 * Custom exception class for the LivewireAutoForm package.
 *
 * This exception is primarily used to signal developer errors or misconfigurations
 * within the Livewire components that use the AutoForm or AutoWizardForm traits.
 * Each static factory method provides a specific, actionable error message
 * to help developers quickly identify and fix the underlying issue.
 */
class LivewireAutoFormException extends Exception
{
    private static function make(string $message, string $origin): self
    {
        return new self("[$origin] $message");
    }

    /**
     * Thrown when a relationship used in a form is not present in the rules() array.
     */
    public static function relationNotDefinedInRules(string $relation, string $origin): self
    {
        return self::make("Relation '{$relation}' not defined in rules.", $origin);
    }

    /**
     * Thrown when a field key used in a form structure is not defined in the rules() array.
     */
    public static function fieldKeyNotDefinedInRules(string $key, string $origin): self
    {
        return self::make("Field key '{$key}' not defined in rules.", $origin);
    }

    /**
     * Thrown when the root model class is missing in FormCollection.
     */
    public static function rootModelClassMissing(string $origin): self
    {
        return self::make('Root model class is missing in FormCollection.', $origin);
    }

    /**
     * Thrown when the root model is missing during an operation that requires it.
     */
    public static function rootModelNotSet(string $origin): self
    {
        /*
         * Background: This exception is thrown when a component method that requires
         * a root model instance is called before that instance has been established.
         * The root model is typically set during the Livewire mount() lifecycle hook.
         * This specific error message is designed to guide the developer to ensure
         * they are passing a valid Eloquent model to the component's mount method,
         * which is a foundational requirement for the LivewireAutoForm logic to function.
         */
        return self::make('Root model is not set. Ensure mount(Model $model) is called.', $origin);
    }

    public static function rootModelRequired(string $origin): self
    {
        return self::make('A valid root model is required. If using route binding, ensure the model exists and was correctly resolved.', $origin);
    }

    /**
     * Thrown when an Eloquent relationship type is not supported by the current operation.
     */
    public static function invalidRelationType(string $relation, string $type, string $origin): self
    {
        return self::make("Relation [{$relation}] is of type [{$type}] which is not supported for this operation.", $origin);
    }

    /**
     * Thrown when a relationship name provided does not exist on the given model.
     */
    public static function relationDoesNotExist(string $relation, string $model, string $origin): self
    {
        return self::make("Relation [$relation] does not exist on [$model].", $origin);
    }

    /**
     * Thrown when an attribute is expected to be an Enum but has no cast defined in the model.
     */
    public static function missingEnumCast(string $model, string $attribute, string $origin): self
    {
        return self::make("Attribute [{$attribute}] on model [{$model}] is not cast to an enum.", $origin);
    }

    /**
     * Thrown when a field name conflicts with reserved internal keys.
     */
    public static function forbiddenKey(string $key, string $origin): self
    {
        return self::make("The key '{$key}' is reserved for internal use.", $origin);
    }

    /**
     * Thrown when auto-save is enabled on an AutoWizardForm instance.
     */
    public static function autoSaveNotAllowedInWizard(string $origin): self
    {
        return self::make('Auto-save is not allowed in AutoWizardForm. Transitions must be handled via save() or submit().', $origin);
    }

    /**
     * Thrown when one or more fields in the rules() array are not covered by any wizard step.
     *
     * @param  string[]  $fields
     */
    public static function fieldsMissingInSteps(array $fields, string $origin): self
    {
        $list = implode(', ', $fields);

        return self::make("The following fields are defined in rules() but were not found in any wizard step: {$list}", $origin);
    }

    /**
     * Thrown when a wizard step view cannot be found.
     */
    public static function wizardViewNotFound(string $viewName, string $origin): self
    {
        return self::make("Wizard view '{$viewName}' not found.", $origin);
    }

    /**
     * Thrown when the syntax for an options label mask is invalid.
     */
    public static function optionsMaskSyntax(string $mask, string $origin): self
    {
        return self::make("Invalid options mask syntax: '{$mask}'. Mask must contain '(name)' or '(value)' for Enums, or '(field_name)' for Models.", $origin);
    }
}
