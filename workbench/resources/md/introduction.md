# Concept of Coding

Livewire Auto Form follows a **buffer-based state management** pattern. Instead of binding Livewire properties directly to Eloquent model attributes, it uses an internal `$form` object (an instance of `FormCollection`) to safely stage changes.

### Core Principles

*   **State Isolation:** All form data resides in a single `$form` buffer. This prevents accidental model mutations and allows for easy "undo" or "cancel" operations. Since `$form` is a `FormCollection` (extending Laravel's `Collection` and implementing `Wireable`), it provides rich state management beyond a simple array.
*   **Convention over Configuration:** By extending the abstract class and calling `mount($model)`, the package manages field hydration and state transitions. Relationships and validation rules are defined in the component to maintain full control.
*   **Context Switching:** Swap the active model within the same component seamlessly. You can move between the root model and its relations, or even switch between different instances of the same model type (the **"List & Edit"** pattern). The package manages the state transition and buffer hydration automatically.
*   **Automatic Persistence:** Choose between real-time updates (`autoSave = true`) or manual submission. The package handles Eloquent `save()` calls and validation.

This approach ensures that your components remain clean, predictable, and easy to test.

### Installation

```bash
composer require schenke-io/livewire-auto-form
```
