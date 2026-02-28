# Concept of Coding

Livewire Auto Form follows a **buffer-based state management** pattern. Instead of binding Livewire properties directly to Eloquent model attributes, it uses an internal `$form` object (an instance of `FormCollection`) to safely stage changes.

### Core Principles

*   **State Isolation:** All form data resides in a single `$form` buffer. This prevents accidental model mutations and allows for easy "undo" or "cancel" operations. Since `$form` is a `FormCollection` (implementing `ArrayAccess`, `Countable`, `IteratorAggregate`, and `Wireable`), it provides rich state management beyond a simple array.
*   **Convention over Configuration:** By extending `AutoForm` and calling `setModel($model)`, the package manages field hydration and state transitions. Relationships and validation rules are defined in the component to maintain full control.
*   **Context Switching:** Swap the active model within the same component seamlessly. You can move between the root model and its relations, or even switch between different instances of the same model type (the **"List & Edit"** pattern). The package manages the state transition and buffer hydration automatically.
*   **Automatic Persistence:** Choose between real-time updates (`autoSave = true`) or manual submission. The package handles Eloquent `save()` calls and validation.
*   **Automatic Data Flattening:** When loading data from models, attributes implementing `Livewire\Wireable`, `Stringable`, or PHP Enums are automatically flattened to their scalar/string representation when possible. This ensures consistent data handling in the front-end and avoids serialization issues.
*   **Standardized Options:** Use the `AutoFormOptions` interface to centralize option generation for selects and radios, with an automatic fallback for quick setups.
*   **Multi-Step Workflows:** Use `AutoWizardForm` to split large forms into sequential steps with per-step validation and explicit field mapping.

This approach ensures that your components remain clean, predictable, and easy to test.

### Rule Inheritance & Value Normalization

`AutoForm` can inherit validation rules from your Eloquent model and normalize complex value objects in a predictable way.

- Rule Inheritance: If your model exposes a `rules(): array` method, the trait will inherit those rules based on the keys you provide in `ruleKeys()`. You can also override inherited rules directly in the `rules()` method by passing them to `scanInheritedRules()`.

  Example:
  ```php
  // In your model
  public function rules(): array
  {
      return [
          'name' => 'required|string',
          'email' => 'email',
          'internal_code' => 'string',
      ];
  }

  // In your component
  public function ruleKeys(): array
  {
      return [
          'name',
          'email',
      ];
  }

  public function rules(): array
  {
      return $this->scanInheritedRules([
          'name' => 'sometimes', // overrides model rule
      ]);
  }
  ```

- Value Normalization: The internal `DataProcessor` handles special value objects:
  - Enums are flattened to their scalar representation.
  - `Stringable` objects are converted to strings.
  - `Wireable` objects that do not return an array are flattened via `toLivewire()`.

### Installation

```bash
composer require schenke-io/livewire-auto-form
```
