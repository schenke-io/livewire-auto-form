---
name: livewire-auto-form-events
description: Handle browser events dispatched by Livewire Auto Form for UI feedback and custom logic.
---

# Livewire Auto Form Events

## When to use this skill
Use this skill when you need to respond to actions within an `AutoForm` component, such as showing notifications after a save, handling auto-save updates, or confirming data loss when navigating away from a dirty form.

## Features
- **Real-time Feedback**: Listen for `saved` and `field-updated` to trigger UI notifications.
- **Context Aware**: Events include the relationship context and model ID for precise handling.
- **Dirty State Protection**: Respond to `confirm-discard-changes` to prevent accidental data loss.

## Available Events

### `saved`
Dispatched when the form (root or relation) is successfully persisted.
- **Parameters**: `context` (string), `id` (mixed)

### `field-updated`
Dispatched when a field is auto-saved.
- **Parameters**: `changed` (string), `context` (string), `id` (mixed)

### `confirm-discard-changes`
Dispatched when attempting to navigate away from an unsaved form buffer (when `autoSave` is false).

## Implementation Example

### Alpine.js Listener
```html
<div x-data="{ notification: '' }"
     x-on:saved.window="notification = 'Saved successfully!'"
     x-on:field-updated.window="notification = 'Field ' + $event.detail.changed + ' updated.'">
    
    <div x-show="notification" x-text="notification"></div>
    
    <!-- form content -->
</div>
```

### Livewire Listener
```php
use Livewire\Attributes\On;

class MyListener extends Component
{
    #[On('saved')]
    public function notify($context, $id)
    {
        session()->flash('message', "Record $id in context $context was saved.");
    }
}
```
