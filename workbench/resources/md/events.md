# Event System

Livewire Auto Form dispatches several browser events that you can listen for in Alpine.js or Livewire to provide real-time feedback to your users.

## Available Events

### 1. `saved`
Dispatched after the entire form (or the active relationship context) has been successfully persisted to the database.

**Parameters:**
- `context` (string): The relationship context that was saved (empty string for the root model).
- `id` (int|string|null): The ID of the model that was saved.

**Example (Alpine.js):**
```html
<div x-on:saved.window="alert('Saved model ' + $event.detail.id + ' in context ' + $event.detail.context)">
    <!-- ... -->
</div>
```

---

### 2. `field-updated`
Dispatched when an individual field is automatically saved to the database (only when `autoSave` is enabled).

**Parameters:**
- `changed` (string): The name of the field that was updated.
- `context` (string): The relationship context where the change occurred.
- `id` (int|string|null): The ID of the model that was updated.

**Example (Alpine.js):**
```html
<div x-on:field-updated.window="console.log('Field ' + $event.detail.changed + ' was updated!')">
    <!-- ... -->
</div>
```

---

### 3. `confirm-discard-changes`
Dispatched when a user action might result in losing unsaved changes in the buffer. This only happens when `autoSave` is disabled and the form buffer is not empty.

**Parameters:**
None.

---

## Listening in Livewire

You can also listen for these events in other Livewire components using the `#[On]` attribute:

```php
use Livewire\Attributes\On;

#[On('saved')]
public function handleSave($context, $id)
{
    // Do something when a model is saved
}
```
