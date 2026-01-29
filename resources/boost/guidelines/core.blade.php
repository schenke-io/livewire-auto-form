# Livewire Auto Form AI Guidelines

### Identity
Livewire Auto Form is a high-level abstraction for Livewire components designed to drastically reduce boilerplate when building complex forms for Eloquent models and their relationships. It focuses on a developer-friendly API where validation rules drive both UI discovery and data persistence.

### Core Conventions

#### Single Buffer Pattern
The package utilizes a **Single Buffer** architecture. All form data, including nested relationship data, is held in a centralized `FormCollection` object (typically assigned to the public `$form` property). This buffer acts as a staging area, ensuring that the database is only updated when explicitly saved or through controlled auto-save events. This prevents partial or unintended persistence during multi-step or complex relationship editing.

#### FormCollection
The `FormCollection` is a `Wireable` helper that manages:
- **Active Context**: Tracks whether the user is editing the root model (empty string context) or a relationship (e.g., 'comments').
- **Active ID**: Tracks the ID of the specific record being edited within the active context.
- **Nested State**: Manages a flat map of data, allowing seamless binding with `wire:model="form.field"`.

### Implementation Boilerplate

#### Livewire Component
@verbatim
<code-snippet name="Standard AutoForm Component" lang="php">
use SchenkeIo\LivewireAutoForm\AutoForm;
use App\Models\Post;

class EditPost extends AutoForm
{
    public function mount(Post $post)
    {
        // Mandatory: establish the root model for the buffer
        $this->setModel($post);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3',
            'content' => 'required',
            'category_id' => 'required|exists:categories,id',
            // Nested relationship discovery
            'tags.name' => 'nullable|string',
        ];
    }
}
</code-snippet>
@endverbatim

#### Blade View
@verbatim
<code-snippet name="AutoForm Blade View" lang="html">
<form wire:submit.prevent="save">
    <!-- Root fields binding to the form buffer -->
    <input type="text" wire:model.blur="form.title">
    <textarea wire:model.blur="form.content"></textarea>

    <!-- Relationship Select using optionsFor -->
    <select wire:model.blur="form.category_id">
        <option value="">Select Category</option>
        @foreach($this->optionsFor('category') as [$value, $label])
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    <button type="submit">Save Post</button>
</form>
</code-snippet>
@endverbatim

### Best Practices

- **Mandatory `setModel()`**: You must call `$this->setModel($model)` in the `mount()` method. This initializes the `FormCollection` with the model's current data and sets up the root context.
- **Rules-Driven Discovery**: The keys in your `rules()` method are the single source of truth. They define which fields are loaded into the `$form` buffer and which relationships are eligible for editing.
- **Preferred `optionsFor()`**: Always use `$this->optionsFor('relation_or_enum')` to retrieve options for selects. It automatically resolves options from:
    - **Eloquent Relations**: Fetches related models and uses their configured labels.
    - **Enums**: Handles localized labels for BackedEnums.
    - **Custom Providers**: Supports any class implementing the `AutoFormOptions` interface.
- **Binding with `.blur` or `.live`**: Always bind to `form.*`. Use `.blur` for standard fields to reduce server roundtrips, or `.live` for fields that need to trigger immediate UI updates (like dependent selects).
- **Validation-First**: The package automatically prefixes validation errors with `form.`, making them compatible with standard Livewire `@error('form.field')` directives.
