---
name: auto-form-basics
description: Build basic single-model forms using Livewire Auto Form.
---

# Livewire Auto Form — Basics

## When to use
Use this skill when you need to create or edit a single Eloquent model record with a Livewire component. Ideal for standard CRUD operations that don't require editing related models in the same view.

## Core concepts

- **`AutoForm`**: Base Livewire component to extend. Internally manages a `FormCollection` buffer (`$this->form`) that mirrors the model's attributes.
- **`ruleKeys()`**: Determines which fields are loaded into the buffer and validated by picking them from the model's `rules()`.
- **`setModel(Model $model)`**: Initialises the buffer from a model instance. Must be called in `mount()`.
- **`rules()`**: Returns the full set of active rules, including those scanned from models based on `ruleKeys()`.
- **`save()`**: Validates and persists the buffer. Bind with `wire:submit="save"`.
- **`form` property**: The Livewire-tracked form buffer. Bind inputs to `wire:model="form.field"`. Validation errors are automatically prefixed as `form.field`.

## Minimal example

### Component

Define `rules()` on your model, then use `ruleKeys()` in the component to pick the fields you need.

```php
// App/Models/User.php
class User extends Model
{
    public function rules(): array
    {
        return [
            'name'  => 'required|string|max:255',
            'email' => 'required|email',
        ];
    }
}

// App/Livewire/UserForm.php
class UserForm extends AutoForm
{
    public function mount(User $user): void
    {
        $this->setModel($user);
    }

    public function ruleKeys(): array
    {
        return ['name', 'email'];
    }
}
```

### Blade

```blade
<form wire:submit="save">
    <input type="text" wire:model.blur="form.name">
    @error('form.name') <span>{{ $message }}</span> @enderror

    <input type="email" wire:model.blur="form.email">
    @error('form.email') <span>{{ $message }}</span> @enderror

    <button type="submit">Save</button>
</form>
```

## Working with rules

The best practice is to define `rules()` (or a `$rules` property) on your Eloquent model and reference the fields by name using `ruleKeys()` in the component. This avoids repeating validation logic in every component.

```php
// App/Livewire/UserForm.php
class UserForm extends AutoForm
{
    public function mount(User $user): void
    {
        $this->setModel($user);
    }

    // Pull rules from the model; override individual keys as needed.
    public function ruleKeys(): array
    {
        return ['name', 'email', 'bio']; // 'bio' will be nullable if not in model rules
    }
}
```

### Overriding rules
You can still define `rules()` in the component to provide additional rules or override those from the model:

```php
public function rules(): array
{
    return array_merge(parent::rules(), [
        'email' => 'required|email|unique:users,email,' . $this->form->rootModelId,
    ]);
}
```

`ruleKeys()` also resolves dot-notated keys (e.g., `'address.street'`) by scanning the related model's rules.

## Auto-save

Enable `$autoSave` to persist each field change immediately without a submit button. A `field-updated` event is dispatched after each successful auto-save.

```php
class UserForm extends AutoForm
{
    protected bool $autoSave = true;

    public function mount(User $user): void
    {
        $this->setModel($user);
    }

    public function ruleKeys(): array
    {
        return ['name', 'email'];
    }
}
```

Auto-save only persists an existing record (i.e., when the model already exists in the database).

## Useful helpers

| Method / Property | Description |
|-------------------|-------------|
| `$this->form->rootModelId` | Primary key of the root model being edited |
| `$this->getModel()` | Root model instance with current buffer data applied |
| `$this->getActiveModel()` | Currently active model (root or relation) with buffer data |
| `$this->cancel()` | Discard any unsaved changes and return to the root context |
| `$this->reloadModel(Model $model)` | Re-synchronise the buffer from a fresh model instance |
| `$this->clearRuleCache()` | Clear the rule cache — call when `ruleKeys()` changes dynamically |