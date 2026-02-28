# Usage Patterns

## Editing Nested JSON Attributes

Livewire Auto Form seamlessly supports Laravel's JSON-casted attributes. When you have a model with a JSON column, you can use dot-notation in `ruleKeys()` to include specific nested keys in your form buffer.

### Model Setup
Ensure your model has the attribute cast to an array or object.

```php
class User extends Model
{
    protected $casts = [
        'settings' => 'array',
    ];

    public function rules(): array
    {
        return [
            'settings.theme' => 'required|string',
            'settings.notifications.email' => 'boolean',
        ];
    }
}
```

### Component Setup
Use `ruleKeys()` to declare the nested JSON keys you want to edit. The package will automatically hydrate the form buffer with the nested values and handle the dotted state.

```php
class EditUserSettings extends AutoForm
{
    public function mount(User $user)
    {
        $this->setModel($user);
    }

    public function ruleKeys(): array
    {
        return [
            'settings.theme',
            'settings.notifications.email',
        ];
    }

    public function rules(): array
    {
        return $this->scanInheritedRules();
    }
}
```

### Blade View
Bind your inputs to the nested keys in the `$form` object.

```html
<select wire:model.blur="form.settings.theme">
    <option value="light">Light</option>
    <option value="dark">Dark</option>
</select>

<input type="checkbox" wire:model.blur="form.settings.notifications.email">
```

The package's "Single Buffer" architecture ensures that when you save, the entire JSON structure is correctly merged and persisted back to the model attribute.
