---
name: livewire-auto-form-relations
description: Manage Eloquent relationships within a single form buffer.
---

# Livewire Auto Form Relations

## When to use this skill
Use this skill when your form needs to edit not just a root model, but also its related models (e.g., a User's Address, a Post's Comments, or a Product's Tags). It allows you to manage multiple related entities within a single Livewire component using a "Single Buffer" approach.

## Features
- **Context Switching**: Seamlessly move between editing the root model and any related model.
- **Implicit Security**: Only relationships with fields defined in the `rules()` method are accessible for editing.
- **Relational Discovery**: Use dot notation (e.g., `relation.field`) in your rules to define fields for related models.

## Implementation Example

### Defining Rules with Relationships
To enable relationship management, define validation rules using dot notation for the relationship fields.

```php
public function rules(): array
{
    return [
        'title' => 'required|string',            // Root model field
        'category_id' => 'required|exists:categories,id', // BelongsTo ID
        'author.name' => 'required|string',       // BelongsTo relationship field
        'comments.body' => 'required|string',     // HasMany relationship field
    ];
}
```

### Context Switching API
The following methods are available on the component to manage contexts:

- **`edit(string $relation, int|string $id)`**: Switches the buffer to edit a specific related record.
- **`add(string $relation)`**: Prepares the buffer to add a new related record.
- **`cancel()`**: Reverts the buffer back to the root model context.
- **`delete(string $relation, int|string $id)`**: Deletes the specified record and handles context cleanup.

### Implementation in Blade

```html
<!-- Root Model Fields -->
<input type="text" wire:model.blur="form.title">

<hr>

<h3>Comments</h3>
@foreach($this->getRelationList('comments') as $comment)
    <div class="flex justify-between">
        <span>{{ $comment->body }}</span>
        <div>
            <button wire:click="edit('comments', {{ $comment->id }})">Edit</button>
            <button wire:click="delete('comments', {{ $comment->id }})">Delete</button>
        </div>
    </div>
@endforeach

<button wire:click="add('comments')">Add New Comment</button>

<!-- Conditional Relationship Form -->
@if($form->activeContext === 'comments')
    <div class="modal">
        <h4>{{ $form->activeId ? 'Edit' : 'Add' }} Comment</h4>
        <input type="text" wire:model.blur="form.comments.body">
        <button wire:click="save">Save Comment</button>
        <button wire:click="cancel">Cancel</button>
    </div>
@endif
```
