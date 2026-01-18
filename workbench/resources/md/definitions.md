# API Definitions

The package provides two main classes for managing form state and persistence: `AutoForm` and `AutoWizardForm`. These classes are **Base Components**, offering a context-aware **"Single Buffer"** architecture.

## Using AutoForm

To use the package, extend `AutoForm` in your Livewire component and initialize it in the `mount()` method:

```php
use SchenkeIo\LivewireAutoForm\AutoForm;

class MyComponent extends AutoForm
{
    public function mount(User $user)
    {
        $this->setModel($user);
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email',
            'posts.title' => 'required' // Relation support
        ];
    }
}
```

## Using AutoWizardForm

For multi-step workflows, extend `AutoWizardForm`. It provides step management and per-step validation.

See the [Multi-Step Wizards](wizard.md) guide for details.

### Public Properties (AutoWizardForm)

| Property | Type | Description |
| --- | --- | --- |
| `$currentStepIndex` | `int` | The zero-based index of the current active step. |
| `$structure` | `array` | Map of Blade view names to field names. |
| `$stepViewPrefix` | `string` | Prefix for the step Blade views. |

### Public Methods (AutoWizardForm)

| Method | Description |
| --- | --- |
| `submit()` | Handles navigation (next step) or final submission (if on last step). |
| `next()` | Validates current step and moves forward. |
| `previous()` | Moves to the previous step. |
| `isLastStep()` | Returns `true` if on the final step. |
| `getSteps()` | Returns the list of defined step views. |
| `isStepActive(int $index)` | Checks if a step is currently active. |

## Public Properties (AutoForm)

| Property | Type | Description |
| --- | --- | --- |
| `$autoSave` | `bool` | Default `false`. If `true`, fields are saved on every update (on blur). If `false`, you must call `save()` manually. |
| `$form` | `FormCollection` | The internal state container (read-only from outside). |

### The $form object (FormCollection)

The `$form` object contains the following state properties:

| Property | Type | Description |
| --- | --- | --- |
| `activeContext` | `string` | Current editing context: `''` for root model or a relation name. |
| `activeId` | `int|string|null` | ID of the record being edited. `null` indicates "Add Mode". |
| `rootModelClass` | `string` | The class name of the main model. |
| `rootModelId` | `int|string|null` | The ID of the main model instance. |
| `autoSave` | `bool` | Whether auto-save is currently enabled. |

## View Actions (Public Interface)

### `edit(string $relation, int|string $id)`
Switches the context to edit a record.
- **Related record**: `wire:click="edit('posts', {{ $post->id }})"`
- **Root model**: `wire:click="edit('', {{ $otherId }})"`

### `add(string $relation)`
Switches the context to "Add Mode".
- **Related record**: `wire:click="add('posts')"`
- **New root model**: `wire:click="add('')"`

### `save()`
Validates and persists the current buffer data.
- If editing a relation, it returns to root context after saving.
- If creating a root model, it updates `rootModelId` after the first save.

### `cancel()`
Resets context to root (`''`) and reloads data to discard changes.

### `delete(string $relation, int|string $id)`
Deletes or detaches a record and updates the active context.

## Calling Methods from Blade

Since the form logic is now part of the Component itself, you can call methods directly in Blade without any special wrappers:

```html
<button wire:click="save">Save</button>
```

This is fully compatible with Alpine.js and component libraries like **Flux**.

## Helper Methods

### `getRelationList(string $relation)`
Returns a collection of related models for the given relation name.
- **Example**: `@foreach($this->getRelationList('posts') as $post)`

### `isEdited(string $relation, int|string $id)`
Returns `true` if the specified related record is currently being edited.
- **Example**: `<li class="{{ $this->isEdited('posts', $post->id) ? 'active' : '' }}">`

### `getModel()`
Returns the root model instance with current buffer data applied.

### `getActiveModel()`
Returns the model instance for the current active context (root or relation) with current buffer data applied.

### `optionsFor(string $key, ?string $labelMask = null)`
Universal helper for Enums or Relations.
- Labels are automatically localized.
- **For Enums**: Use `(name)` or `(value)` masks.
- **For Models**: Use column name (e.g., `'title'`) or mask with placeholders (e.g., `'(code) - (name)'`).

## Events

| Event | Parameters | When Dispatched |
| --- | --- | --- |
| `saved` | `context`, `id` | After successful `save()` or `delete()`. |
| `field-updated` | `changed`, `context`, `id` | After auto-save (when `autoSave` is `true`). |
| `confirm-discard-changes` | - | When switching context with unsaved changes. |

## Exceptions

The package throws `SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException` for:
- **Configuration Integrity**: Errors in setup.
- **Rules Discrepancy**: Mismatches between data and `rules()`.
- **Relation Errors**: Unsupported relationship types.
- **Enum Errors**: Missing enum casts.
