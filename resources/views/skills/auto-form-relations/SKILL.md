---
name: auto-form-relations
description: Manage Eloquent relationships within a single form buffer.
---

# Livewire Auto Form — Relations

## When to use
Use this skill when a form needs to edit not just a root model but also its related models — for example, a Post and its Comments, a User and their Address, or a Product and its Tags. All related entities are managed inside a single Livewire component using a "Single Buffer" approach.

## Supported relation types

`HasOne`, `HasMany`, `BelongsTo`, `BelongsToMany`

## Defining rules for relations

Use dot notation (`relation.field`) in `ruleKeys()` to expose a relation. Only relations that have at least one dot-notated rule are accessible — all others are blocked with an exception. The actual validation rules are picked from the models' `rules()` method.

```php
public function ruleKeys(): array
{
    return [
        'title',         // root model field
        'category_id',   // BelongsTo FK
        'author.name',   // BelongsTo field
        'comments.body', // HasMany field
    ];
}
```

## Context API

| Method | Description |
|--------|-------------|
| `edit(string $relation, int\|string $id)` | Load a related record into the buffer for editing |
| `add(string $relation)` | Prepare the buffer for a new related record |
| `save()` | Persist the current buffer (root model or active relation) |
| `cancel()` | Discard the relation buffer and return to the root model |
| `delete(string $relation, int\|string $id)` | Delete a related record; dispatches a `saved` event |
| `getRelationList(string $relation)` | Return a `Collection` of all existing records for that relation |
| `isEdited(string $relation, int\|string $id)` | Return `true` if the given record is currently in the buffer |

## Blade example

```blade
{{-- Root model field --}}
<input type="text" wire:model.blur="form.title">

<h3>Comments</h3>
@foreach($this->getRelationList('comments') as $comment)
    <div class="{{ $this->isEdited('comments', $comment->id) ? 'ring-2 ring-blue-500' : '' }}">
        <span>{{ $comment->body }}</span>
        <button wire:click="edit('comments', {{ $comment->id }})">Edit</button>
        <button wire:click="delete('comments', {{ $comment->id }})">Delete</button>
    </div>
@endforeach

<button wire:click="add('comments')">Add comment</button>

{{-- Inline relation form --}}
@if($form->activeContext === 'comments')
    <div>
        <h4>{{ $form->activeId ? 'Edit' : 'New' }} Comment</h4>
        <input type="text" wire:model.blur="form.comments.body">
        @error('form.comments.body') <span>{{ $message }}</span> @enderror
        <button wire:click="save">Save</button>
        <button wire:click="cancel">Cancel</button>
    </div>
@endif
```

## `$form` context properties

These properties are readable in Blade via the `$form` object (the `FormCollection` buffer):

| Property | Type | Description |
|----------|------|-------------|
| `$form->activeContext` | `string` | Current relation name, or empty string when editing the root model |
| `$form->activeId` | `int\|string\|null` | Primary key of the record currently in the buffer |
| `$form->rootModelId` | `int\|string\|null` | Primary key of the root model |
| `$form->rootModelClass` | `string\|null` | Fully qualified class name of the root model |