# Livewire Auto Form

Enhanced Livewire abstract class to edit models and their relationships with "Single Buffer" state management and optional auto-saving.

## Installation/Setup

1. Install via composer: `composer require schenke-io/livewire-auto-form`
2. Extend the `LivewireAutoFormComponent` abstract class in your Livewire component.
3. Implement the mandatory `rules()` method.

## Usage

### Basic Setup
In your Livewire component:
```php
use SchenkeIo\LivewireAutoForm\LivewireAutoFormComponent;

class EditPost extends LivewireAutoFormComponent
{
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'content' => 'required',
        ];
    }
}
```

### Blade Binding
Always bind to the `form` property:
```html
<input type="text" wire:model.blur="form.title">
<textarea wire:model.blur="form.content"></textarea>
```

### Handling Relationships
Relationships are automatically discovered via the `rules()` method using dot notation.

```php
// In rules()
'comments.body' => 'required',
'comments.author_name' => 'required',
```

In Blade:
```html
@@foreach($this->getRelationList('comments') as $comment)
    <button wire:click="edit('comments', @{{ $comment->id }})">Edit</button>
@@endforeach

@@if($form->activeContext === 'comments')
    <input type="text" wire:model.blur="form.comments.body">
    <button wire:click="save">Save</button>
@@endif
```

### List & Edit Pattern (Root Model Management)
Manage a collection of models by switching the root context:
```html
<!-- Select a record to edit -->
<button wire:click="edit('', @{{ $model->id }})">Edit</button>

<!-- Prepare for new record -->
<button wire:click="add('')">New</button>

<!-- Delete a record -->
<button wire:click="delete('', @{{ $model->id }})">Delete</button>
```

## Best Practices

- **Always use `wire:model.blur` (or `.live`) on `form.*`**: The package relies on the **"$form buffer"**. Binding directly to model properties will bypass the component's logic.
- **Implement `rules()`**: Validation is mandatory. The keys in `rules()` determine which fields are loaded into the buffer (**Data Loading Strategy**) and which relations are editable.
- **Single Buffer Architecture**: All state (including `activeContext` and `activeId`) is managed within the `$form` object.
- **Context Awareness**: Use `$form->activeContext` and `$form->activeId` in your Blade views to conditionally show forms for related models.
