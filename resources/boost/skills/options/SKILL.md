---
name: livewire-auto-form-options
description: Resolve option lists for selects and radios from Models and Enums.
---

# Livewire Auto Form Options

## When to use this skill
Use this skill when you need to populate select inputs, radio groups, or checkboxes with options derived from database models or PHP BackedEnums. It simplifies the process by automatically resolving labels and values, including support for localization and custom label masks.

## Features
- **Automatic Relation Discovery**: Fetches related models for `BelongsTo` and `BelongsToMany` relations defined in your rules.
- **Smart Enum Support**: Automatically resolves options for attributes cast to BackedEnums in your model.
- **Custom Label Masks**: Combine multiple model columns (e.g., `(first_name) (last_name)`) or Enum properties into a single user-friendly label.
- **Unified Interface**: Use the `AutoFormOptions` interface to provide custom option logic for any class.

## Implementation Example

### Model Options
Implement `AutoFormOptions` and use the `AutoFormModelOptions` trait for standard `id` => `name` mapping.

```php
use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormModelOptions;

class Category extends Model implements AutoFormOptions
{
    use AutoFormModelOptions;

    // The trait defaults to using 'name' as the label.
}
```

### Localized Enum Options
Implement `AutoFormOptions` and use `AutoFormLocalisedEnumOptions` to map cases to translation keys.

```php
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormLocalisedEnumOptions;

enum UserStatus: string implements AutoFormOptions
{
    use AutoFormLocalisedEnumOptions;

    // Translation keys: enums.user_status.active, enums.user_status.pending, etc.
    const OPTION_TRANSLATION_PREFIX = 'enums.user_status';

    case ACTIVE = 'active';
    case PENDING = 'pending';
}
```

### Usage in Blade
Use the `optionsFor()` helper to retrieve an array of `[value, label]` pairs.

```html
<!-- Standard Relationship Select -->
<select wire:model.blur="form.category_id">
    <option value="">Select Category</option>
    @foreach($this->optionsFor('category') as [$value, $label])
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>

<!-- Enum Select with Localization -->
<select wire:model.blur="form.status">
    <option value="">Select Status</option>
    @foreach($this->optionsFor('status') as [$value, $label])
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>

<!-- Relationship with Label Mask -->
<select wire:model.blur="form.user_id">
    <option value="">Select User</option>
    @foreach($this->optionsFor('user', '(first_name) (last_name)') as [$value, $label])
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```
