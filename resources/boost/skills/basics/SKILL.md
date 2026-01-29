---
name: livewire-auto-form-basics
description: Build basic single-model forms using Livewire Auto Form.
---

# Livewire Auto Form Basics

## When to use this skill
Use this skill when you need to create or edit a single Eloquent model record using a Livewire component. This is ideal for standard CRUD operations where complex relationship editing is not required in the same view.

## Features
- **Boilerplate Reduction**: Extends `AutoForm` to handle all form mapping and state management.
- **Rules-Driven**: Validation rules drive both the data loading and the persistence logic.
- **Automatic Error Scoping**: Validation errors are automatically prefixed with `form.` for easy display.

## Implementation Example

### Livewire Component
Extend the `AutoForm` base class and use `setModel()` to initialize the buffer.

```php
use SchenkeIo\LivewireAutoForm\AutoForm;
use App\Models\User;

class UserForm extends AutoForm
{
    public function mount(User $user)
    {
        // Mandatory: Initialize the form buffer with the model
        $this->setModel($user);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->form->rootModelId,
        ];
    }
}
```

### Blade View
Always bind your inputs to the `form` property using `wire:model`.

```html
<form wire:submit.prevent="save">
    <div>
        <label>Name</label>
        <input type="text" wire:model.blur="form.name">
        @error('form.name') <span>{{ $message }}</span> @enderror
    </div>

    <div>
        <label>Email</label>
        <input type="email" wire:model.blur="form.email">
        @error('form.email') <span>{{ $message }}</span> @enderror
    </div>

    <button type="submit">Save</button>
</form>
```
