# API Definitions

The `LivewireAutoFormComponent` abstract class provides several public properties and methods to manage form state and persistence using a **"Single Buffer"** architecture.

## Public Properties

| Property | Type | Description |
| --- | --- | --- |
| `$form` | `FormCollection` | The **"$form buffer"** (Single Buffer architecture) for form input and state. All view inputs should bind here: `wire:model="form.field_name"`. |
| `$autoSave` | `bool` | Default `false`. If `true`, fields are saved on every update (on blur). If `false`, you must call `save()` manually. |

### The `$form` Object (FormCollection)

The `$form` object contains the following state properties (read-only from outside the component):

| Property | Type | Description |
| --- | --- | --- |
| `activeContext` | `string` | Current editing context: `''` (empty string) for the root model or a relation name. |
| `activeId` | `int\|string\|null` | ID of the record being edited in the active context. `null` indicates "Add Mode". |
| `rootModelClass` | `string` | The class name of the main model. |
| `rootModelId` | `int\|string\|null` | The ID of the main model instance. |
| `nullables` | `array` | List of fields that should be converted to `null` when empty strings are submitted (derived from `rules()`). |
| `autoSave` | `bool` | Whether auto-save is currently enabled for this form. |

> [!WARNING]
> These properties are read-only. Use the provided methods (like `edit()`, `add()`, `cancel()`) to change the state. Direct modification is not allowed.

## Mandatory Methods

### `rules()`
You **must** implement this method in your component. It should return an array of validation rules for your form fields.

**Important:** The keys of the `rules()` array are used to determine which fields are loaded into the `$form` buffer (implementing the **Data Loading Strategy**) and which relations are permitted for editing.

*   Use plain keys for root model fields: `'name' => 'required'`.
*   Use dotted keys for relation fields: `'posts.title' => 'required'`.
*   **Shadowing**: Relation fields take precedence over root fields with the same name if dots are present in the rule key.

## Public Interface (View Actions)

### `edit(string $relation, int|string $id)`
Switches the context to edit a record.
- To edit a **related record**, provide the relation name and its ID.
- To switch the **root model** (e.g. from a list), use an empty string (`''`) for `$relation` and the model's ID.

Raises an exception if `$relation` is not in `rules()` (unless it's an empty string).

```php
<!-- Edit a related post -->
<button wire:click="edit('posts', {{ $post->id }})">Edit Post</button>

<!-- Switch the root model -->
<button wire:click="edit('', {{ $otherModel->id }})">Select This Record</button>
```

### `add(string $relation)`
Switches the context to "Add Mode".
- To add a **related record**, provide the relation name.
- To prepare for a **new root model** (clear the form), use an empty string (`''`) for `$relation`.

Raises an exception if `$relation` is not in `rules()` (unless it's an empty string).

```php
<!-- Add a new related post -->
<button wire:click="add('posts')">Add Post</button>

<!-- Prepare for a new root model -->
<button wire:click="add('')">New Record</button>
```

### `save()`
Manually persists the current `$form` data (Update or Create). If editing a relation, it returns to the root context after saving. If creating a new root model, it updates the `rootModelId` after the first save.

```php
<button wire:click="save">Save Changes</button>
```

### `cancel()`
Resets context to `''` and reloads the root model data into the `$form` buffer to discard changes.

```php
<button wire:click="cancel">Cancel</button>
```

### `isEdited(string $relation, int|string $id)`
Returns `true` if the specified record (root or relation) is currently being edited. This is useful for highlighting the active item in a list.

```php
@foreach($this->getRelationList('posts') as $post)
    <li class="{{ $this->isEdited('posts', $post->id) ? 'bg-blue-100' : '' }}">
        {{ $post->title }}
        <button wire:click="edit('posts', {{ $post->id }})">Edit</button>
    </li>
@endforeach
```

### `delete(string $relation, int|string $id)`
Deletes a record.
- To delete a **related record**, provide the relation name and its ID.
- To delete the **root model**, use an empty string (`''`) for `$relation` and the model's ID.

If the deleted record was the active context, the component automatically resets to root context (for relations) or "Add Mode" (for root model).

Raises an exception if `$relation` is not in `rules()` (unless it's an empty string).

```php
<!-- Delete a related post -->
<button wire:click="delete('posts', {{ $post->id }})">Delete</button>

<!-- Delete a root model -->
<button wire:click="delete('', {{ $model->id }})">Delete This</button>
```

### `reloadModel(Model $model)`
Synchronizes the form state with the latest data from the database for the given model.

## Helper Methods

### `getModel()`
Returns the root model instance with current form data applied.

### `getActiveModel()`
Returns the model instance for the current active context (root or relation) with current form data applied.

### `allOptionsForRelation(string $relation, string $labelColumn = 'name')`
Returns an array of all available records for a relationship (for selects). Supports `BelongsTo` and `BelongsToMany` relations. Labels are automatically localized.

```php
<select wire:model="form.category_id">
    @foreach($this->allOptionsForRelation('category') as $option)
        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
    @endforeach
</select>
```

### `getRelationList(string $relation)`
Returns a `Collection` of related models with columns filtered by `rules()`.

```php
@foreach($this->getRelationList('posts') as $post)
    <li>{{ $post->title }}</li>
@endforeach
```

### `enumOptionsFor(string $attribute, ?string $relation = null)`
Returns an array of options for an enum-casted attribute. Labels are automatically localized.

```php
<select wire:model="form.status">
    @foreach($this->enumOptionsFor('status') as $option)
        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
    @endforeach
</select>
```

## Events

The component dispatches several events that can be used for UI feedback or integration with other scripts.

| Event | Parameters | When Dispatched |
| --- | --- | --- |
| `saved` | `context`, `id` | After a successful manual `save()` or a `delete()` operation. |
| `field-updated` | `changed`, `context`, `id` | After a successful auto-save of an individual field (when `$autoSave` is `true`). |
| `confirm-discard-changes` | - | When switching context if there are unsaved changes and `$autoSave` is `false`. |

### Event Integration (Alpine.js Example)

You can listen for these events using Alpine.js for real-time notifications:

```html
<div x-data="{ show: false, message: '' }"
     x-on:saved.window="show = true; message = 'Changes saved!'; setTimeout(() => show = false, 2000)"
     x-on:field-updated.window="show = true; message = 'Field updated!'; setTimeout(() => show = false, 2000)">

    <div x-show="show" class="notification">
        <span x-text="message"></span>
    </div>
</div>
```

## Exceptions

The package throws `SchenkeIo\LivewireAutoForm\LivewireAutoFormException` for various error conditions:
- **Configuration Integrity**: Errors in setup (e.g. missing root model).
- **Rules Discrepancy**: Mismatches between model data and `rules()`.
- **Relation Errors**: Missing or unsupported relationship types.
- **Enum Errors**: Missing enum casts for attributes.
