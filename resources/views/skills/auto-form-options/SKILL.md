---
name: auto-form-options
description: Resolve option lists for selects and radios from Models and Enums.
---

# Livewire Auto Form — Options

## When to use
Use this skill when you need to populate select inputs, radio groups, or checkboxes with options derived from database models or PHP BackedEnums.

## `optionsFor()` — primary API

Call `$this->optionsFor($key, $labelMask)` in Blade to get an indexed array of `[$value, $label]` pairs. It automatically detects whether `$key` refers to a relation (defined in `ruleKeys()`) or an enum cast on the model.

```blade
<select wire:model.blur="form.category_id">
    <option value="">Select category</option>
    @foreach($this->optionsFor('category') as [$value, $label])
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```

## Model options

### `AutoFormModelOptions` trait — simple `id/name` mapping

```php
use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormModelOptions;

class Category extends Model implements AutoFormOptions
{
    use AutoFormModelOptions;
    // optionsFor('category')          → uses 'name' column as label
    // optionsFor('category', 'title') → uses 'title' column as label
}
```

When `AutoFormModelOptions` is used, the `$labelMask` parameter is treated as a **column name**, not a format pattern.

### Column mask (models that do not implement `AutoFormOptions`)

If the model does **not** implement `AutoFormOptions`, you can combine columns with a parenthesis mask:

```blade
@foreach($this->optionsFor('user', '(first_name) (last_name)') as [$value, $label])
    <option value="{{ $value }}">{{ $label }}</option>
@endforeach
```

### `AutoFormLocalisedModelOptions` trait — translated labels

Uses translation keys derived from the model's primary key:

```php
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormLocalisedModelOptions;

class Country extends Model implements AutoFormOptions
{
    use AutoFormLocalisedModelOptions;

    const OPTION_TRANSLATION_PREFIX = 'models';
    // Translation key format: {prefix}.{ClassName}.{id}
    // Example: models.Country.1, models.Country.2, …
}
```

## Enum options

### Auto-detected from model casts

If your model casts an attribute to a BackedEnum, `optionsFor()` resolves it automatically. Without `AutoFormOptions`, case names are converted to headline strings (`MY_CASE` → `My Case`).

```php
// App/Models/Post.php
protected $casts = ['status' => PostStatus::class];
```

```blade
@foreach($this->optionsFor('status') as [$value, $label])
    <option value="{{ $value }}">{{ $label }}</option>
@endforeach
```

> **Note**: The option **value** stored in the buffer and database is the enum **case name** (e.g., `DRAFT`), not the backed value (e.g., `draft`).

### Format mask (enums that do not implement `AutoFormOptions`)

Use `(name)` and `(value)` placeholders in the mask:

```blade
@foreach($this->optionsFor('status', '(name) — (value)') as [$value, $label])
    <option value="{{ $value }}">{{ $label }}</option>
@endforeach
```

### `AutoFormLocalisedEnumOptions` trait — translated labels

```php
use SchenkeIo\LivewireAutoForm\AutoFormOptions;
use SchenkeIo\LivewireAutoForm\Traits\AutoFormLocalisedEnumOptions;

enum PostStatus: string implements AutoFormOptions
{
    use AutoFormLocalisedEnumOptions;

    const OPTION_TRANSLATION_PREFIX = 'enums';
    // Translation key format: {prefix}.{ClassName}.{lowercase_case_name}
    // Example with prefix 'enums' and class PostStatus:
    //   DRAFT      → enums.PostStatus.draft
    //   PUBLISHED  → enums.PostStatus.published

    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}
```

> **Key format**: `{OPTION_TRANSLATION_PREFIX}.{ClassName}.{lowercase_case_name}`.
> The class name (e.g., `PostStatus`) is always included as the second segment.

When `$labelMask` is passed to `optionsFor()` for a localised enum, it overrides the translation prefix entirely.

## Custom `AutoFormOptions` implementation

Implement the interface directly for full control over values and labels:

```php
enum UserStatus: string implements AutoFormOptions
{
    case ACTIVE = 'active';
    case BANNED = 'banned';

    public static function getOptions(?string $labelMask = null): array
    {
        // Keys are stored values; values are human-readable labels.
        // Labels are passed through __() automatically.
        return [
            self::ACTIVE->value => 'Active User',
            self::BANNED->value => 'Banned User',
        ];
    }
}
```

To include an icon, return a `[label, icon]` tuple:

```php
public static function getOptions(?string $labelMask = null): array
{
    return [
        self::ACTIVE->value => ['Active User', 'heroicon-o-check-circle'],
    ];
    // Blade: @foreach($this->optionsFor('status') as [$value, $label, $icon])
}
```