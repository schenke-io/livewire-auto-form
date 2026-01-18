# Livewire Auto Form

Compositional form management for Livewire to edit models and their relationships with "Single Buffer" state management and optional auto-saving.

## Installation/Setup

1. Install via composer: `composer require schenke-io/livewire-auto-form`
2. Add the `AutoForm` property to your Livewire component.
3. Initialize the form in `mount()` using `$this->form->setModel($model)`.

## Usage

### Basic Setup
In your Livewire component:
```php
use Livewire\Component;
use SchenkeIo\LivewireAutoForm\AutoForm;

class EditPost extends Component
{
    public AutoForm $form;

    public function mount(Post $post)
    {
        $this->form->setModel($post);
    }

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
'category_id' => 'required|exists:categories,id',
```

In Blade:
```html
@@foreach($form->getRelationList('comments') as $comment)
    <button wire:click="form.edit('comments', @{{ $comment->id }})">Edit</button>
@@endforeach

@@if($form->data->activeContext === 'comments')
    <input type="text" wire:model.blur="form.comments.body">
    <button wire:click="form.save">Save</button>
@@endif

<!-- Select for relationship -->
<select wire:model.blur="form.category_id">
    <option value="">Select Category</option>
    @@foreach($form->optionsFor('category') as $option)
        <option value="@{{ $option[0] }}">@{{ $option[1] }}</option>
    @@endforeach
</select>
```

### Enums and Options
Use `optionsFor()` to get labels for Enum-casted attributes or Models.

```html
<!-- Enum options -->
<select wire:model.blur="form.status">
    @@foreach($form->optionsFor('status') as $option)
        <option value="@{{ $option[0] }}">@{{ $option[1] }}</option>
    @@endforeach
</select>

<!-- Custom mask for relationship -->
@@foreach($form->optionsFor('category', '(code) - (name)') as $option)
    ...
@@endforeach
```

### List & Edit Pattern (Root Model Management)
Manage a collection of models by switching the root context:
```html
<!-- Select a record to edit -->
<button wire:click="form.edit('', @{{ $model->id }})">Edit</button>

<!-- Prepare for new record -->
<button wire:click="form.add('')">New</button>

<!-- Delete a record -->
<button wire:click="form.delete('', @{{ $model->id }})">Delete</button>
```

### Multi-Step Wizards
Use `AutoWizardForm` to split large forms into sequential steps.
```php
class UserWizardForm extends AutoWizardForm {
    protected array $structure = ['step-one' => [], 'step-two' => []];
    protected string $stepViewPrefix = 'livewire.steps.';
}
```
In Blade:
```html
<form wire:submit.prevent="submit">
    @@foreach($form->getSteps() as $index => $step)
        @@include('livewire.steps.' . $step, ['isActive' => $form->isStepActive($index)])
    @@endforeach
    <button type="submit">@{{ $form->isLastStep() ? 'Finish' : 'Next' }}</button>
</form>
```

## Best Practices

- **Always use `wire:model.blur` (or `.live`) on `form.*`**: The package relies on the **"$form buffer"**. Binding directly to model properties will bypass the component's logic.
- **Implement `rules()`**: Validation is mandatory. The keys in `rules()` determine which fields are loaded into the buffer (**Data Loading Strategy**) and which relations are editable.
- **Single Buffer Architecture**: All state (including `activeContext` and `activeId`) is managed within the `$form` object.
- **Context Awareness**: Use `$form->activeContext` and `$form->activeId` in your Blade views to conditionally show forms for related models.
