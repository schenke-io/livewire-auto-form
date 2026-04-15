---
name: auto-form-events
description: Handle browser events dispatched by Livewire Auto Form for UI feedback and custom logic.
---

# Livewire Auto Form — Events

## When to use
Use this skill when you need to respond to actions within an `AutoForm` component — for example, showing a toast notification after a save, updating a sibling component after a field auto-saves, or protecting against data loss when the user navigates between related records.

## Available events

### `saved`
Dispatched when the form (root model or active relation) is successfully persisted via `save()`, or when a record is deleted via `delete()`.

| Parameter | Type | Description |
|-----------|------|-------------|
| `context` | `string` | Relation name being saved, or empty string for the root model |
| `id` | `int\|string\|null` | Primary key of the saved or deleted record |

### `field-updated`
Dispatched after a single field is auto-saved. Only fires when `autoSave = true` and the model already exists in the database.

| Parameter | Type | Description |
|-----------|------|-------------|
| `changed` | `string` | The field key that changed (e.g., `'name'` or `'address.street'`) |
| `context` | `string` | Relation context (empty for root model) |
| `id` | `int\|string\|null` | Primary key of the updated record |

### `confirm-discard-changes`
Dispatched when `edit()` or `add()` is called on a non-empty buffer while `autoSave` is disabled. Use this to show a confirmation dialog before the unsaved buffer is discarded.

## Listening with Alpine.js

```html
<div x-data="{ message: '' }"
     x-on:saved.window="message = 'Record saved!'"
     x-on:field-updated.window="message = $event.detail.changed + ' updated'">

    <p x-show="message" x-text="message" x-cloak></p>

    {{-- form fields --}}
</div>
```

## Listening with a Livewire component

```php
use Livewire\Attributes\On;

class NotificationBanner extends Component
{
    public string $message = '';

    #[On('saved')]
    public function onSaved(string $context, int|string|null $id): void
    {
        $this->message = $context
            ? "Relation '{$context}' (ID: {$id}) saved."
            : 'Record saved.';
    }

    #[On('field-updated')]
    public function onFieldUpdated(string $changed, string $context, int|string|null $id): void
    {
        $this->message = "Field '{$changed}' updated.";
    }
}
```

## Protecting against data loss

```html
<div x-data="{ showDialog: false }"
     x-on:confirm-discard-changes.window="showDialog = true">

    <template x-if="showDialog">
        <div>
            <p>You have unsaved changes. Discard them?</p>
            <button @click="showDialog = false; $wire.cancel()">Discard</button>
            <button @click="showDialog = false">Stay</button>
        </div>
    </template>
</div>
```