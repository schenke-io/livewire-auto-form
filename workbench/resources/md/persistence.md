# Persistence & Relationship Discovery

Livewire Auto Form features a hardened persistence layer that ensures data is correctly routed between your form buffer and the database, with a specific focus on Eloquent relationships.

## Strict Relationship Validation

The package distinguishes between actual Eloquent relationships and other dot-notated data (such as JSON casts or nested arrays) by using the model's `isRelation()` method.

When you define dot-notated rules like:

```php
public function rules(): array
{
    return [
        'author.name' => 'required', // Eloquent relation
        'settings.theme' => 'string', // JSON cast field
    ];
}
```

The package performs the following validations:

1.  **Rule Discovery**: During initialization and rule inheritance, only keys that correspond to verified Eloquent relations are treated as relationship contexts.
2.  **Persistence Protection**: When saving data, the package verifies that a path segment is an actual relationship before attempting to invoke it as a method on the model.

If a dot-notated key does not correspond to a relationship, it is treated as a standard model attribute, allowing it to work seamlessly with Laravel's built-in support for JSON casts.

## Relationship Hardening

The `CrudProcessor` has been hardened to prevent common errors when dealing with complex relationship trees:

-   **Path Resolution**: When resolving nested relationships (e.g., `brand.category.name`), every segment of the path is validated against the model's relationship definitions.
-   **Error Handling**: If a path segment is not a valid relation, a `LivewireAutoFormException` is thrown, providing clear feedback on the configuration error instead of failing with a `BadMethodCallException`.
-   **Safe Method Invocation**: Before calling a relationship method during the save process, the package explicitly checks if it exists and is a valid relation, preventing accidental execution of other model methods.
