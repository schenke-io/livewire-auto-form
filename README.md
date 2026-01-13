<!--

This file was written by 'MakeReadmeCommand.php' line 18 using
SchenkeIo\PackagingTools\Markdown\MarkdownAssembler

Do not edit manually as it will be overwritten.

-->

[![Latest Version](https://img.shields.io/packagist/v/schenke-io/livewire-auto-form?style=plastic)](https://packagist.org/packages/schenke-io/livewire-auto-form)
[![Total Downloads](https://img.shields.io/packagist/dt/schenke-io/livewire-auto-form.svg?style=plastic)](https://packagist.org/packages/schenke-io/livewire-auto-form)


# Livewire Auto Form - Rapid Model Editing

Stop manually mapping every Eloquent attribute to a Livewire property and start focusing on your app's core logic with our buffer-based form management.

### If you struggle with the following problems, we are just for you:

*   **Tedious property definitions:** Tired of manually adding `public string $name` for every model attribute? Our single-buffer architecture handles it all.
*   **"Forgot-to-save" bugs:** Eliminate accidental data loss with centralized state management and predictable auto-save logic.
*   **Relationship boilerplate:** Editing child models shouldn't be hard. Handle relationships with simple method calls and zero extra code.
*   **Rigid workflows:** Switch between real-time "auto-save" and traditional "Save" buttons effortlessly, without rewriting your component.
*   **Complex testing:** Logic consistency means fewer edge cases and easier unit testing for your form components.



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



* [Livewire Auto Form - Rapid Model Editing](#livewire-auto-form---rapid-model-editing)
    * [If you struggle with the following problems, we are just for you:](#if-you-struggle-with-the-following-problems,-we-are-just-for-you:)
* [Concept of Coding](#concept-of-coding)
    * [Core Principles](#core-principles)
    * [Installation](#installation)
* [Code Examples](#code-examples)
    * [1. The Basic Form (Manual Save)](#1.-the-basic-form-(manual-save))
    * [2. Modern "Auto-Save" Experience](#2.-modern-"auto-save"-experience)
    * [3. Handling Relationships](#3.-handling-relationships)
    * [4. Using Enums for Selects](#4.-using-enums-for-selects)
    * [5. Listening for Events (Notifications)](#5.-listening-for-events-(notifications))
    * [6. List & Edit Pattern](#6.-list-&-edit-pattern)
* [API Definitions](#api-definitions)
  * [Public Properties](#public-properties)
    * [The `$form` Object (FormCollection)](#the-`$form`-object-(formcollection))
  * [Mandatory Methods](#mandatory-methods)
    * [`rules()`](#`rules()`)
  * [Public Interface (View Actions)](#public-interface-(view-actions))
    * [`edit(string $relation, int|string $id)`](#`edit(string-$relation,-int|string-$id)`)
    * [`add(string $relation)`](#`add(string-$relation)`)
    * [`save()`](#`save()`)
    * [`cancel()`](#`cancel()`)
    * [`isEdited(string $relation, int|string $id)`](#`isedited(string-$relation,-int|string-$id)`)
    * [`delete(string $relation, int|string $id)`](#`delete(string-$relation,-int|string-$id)`)
    * [`reloadModel(Model $model)`](#`reloadmodel(model-$model)`)
  * [Helper Methods](#helper-methods)
    * [`getModel()`](#`getmodel()`)
    * [`getActiveModel()`](#`getactivemodel()`)
    * [`allOptionsForRelation(string $relation, string $labelColumn = 'name')`](#`alloptionsforrelation(string-$relation,-string-$labelcolumn-=-'name')`)
    * [`getRelationList(string $relation)`](#`getrelationlist(string-$relation)`)
    * [`enumOptionsFor(string $attribute, ?string $relation = null)`](#`enumoptionsfor(string-$attribute,-?string-$relation-=-null)`)
  * [Events](#events)
    * [Event Integration (Alpine.js Example)](#event-integration-(alpine.js-example))
  * [Exceptions](#exceptions)



# Code Examples

This guide provides examples for using the package, ranging from basic forms to more advanced scenarios.

### 1. The Basic Form (Manual Save)

`<livewire:auto.form :model="$post" />`

This is the simplest way to use the package. You extend the `LivewireAutoFormComponent` and define your rules.

**The Livewire Component:**
```php
class EditPost extends LivewireAutoFormComponent
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3',
            'content' => 'required',
        ];
    }

    public function render()
    {
        return view('livewire.edit-post');
    }
}
```

**The Blade View:**
```html
<div>
    <input type="text" wire:model="form.title">
    @error('form.title') <span class="error">{{ $message }}</span> @enderror

    <textarea wire:model="form.content"></textarea>
    @error('form.content') <span class="error">{{ $message }}</span> @enderror
    
    <button wire:click="save">Save Post</button>
</div>
```

---

### 2. Modern "Auto-Save" Experience

`<livewire:auto.form :model="$post" />`

If you want your form to save automatically as the user types (on blur), just set `$autoSave` to `true`.

**The Livewire Component:**
```php
class EditPost extends LivewireAutoFormComponent
{
    public bool $autoSave = true;

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:3',
            'content' => 'required',
        ];
    }
}
```

**The Blade View:**
```html
<div>
    <!-- No "Save" button needed! It saves when you click away from the input (on blur) -->
    <input type="text" wire:model.blur="form.title">
    @error('form.title') <span class="error">{{ $message }}</span> @enderror

    <textarea wire:model.blur="form.content"></textarea>
    @error('form.content') <span class="error">{{ $message }}</span> @enderror
    
    <span wire:loading wire:target="form">Saving...</span>
</div>
```

---

### 3. Handling Relationships

`<livewire:auto.form :model="$brand" />`

This is where the package really shines. Imagine a `Brand` that has many `Products`. You can edit the brand and its products in the same component.

**The Livewire Component:**
```php
class EditBrand extends LivewireAutoFormComponent
{
    public function rules(): array
    {
        return [
            'name' => 'required',
            'products.name' => 'required',
            'products.price' => 'numeric',
        ];
    }

    public function render()
    {
        return view('livewire.edit-brand');
    }
}
```

**The Blade View:**
```html
<div>
    <!-- Main Brand Form -->
    <input type="text" wire:model.blur="form.name">

    <h3>Products</h3>
    <ul>
        @foreach($this->getRelationList('products') as $product)
            <li wire:key="product-{{ $product->id }}" 
                class="{{ $this->isEdited('products', $product->id) ? 'active' : '' }}">
                {{ $product->name }} - ${{ $product->price }}
                <button wire:click="edit('products', {{ $product->id }})">Edit</button>
                <button wire:click="delete('products', {{ $product->id }})">Delete</button>
            </li>
        @endforeach
    </ul>
    
    <button wire:click="add('products')">Add Product</button>

    <!-- This shows up only when we are editing or adding a product -->
    @if($form->activeContext === 'products')
        <div class="modal">
            <h4>{{ $form->activeId ? 'Edit Product' : 'Add Product' }}</h4>
            
            <!-- Relationship data is stored under the relationship name in the $form buffer -->
            <input type="text" wire:model.blur="form.products.name">
            <input type="number" wire:model.blur="form.products.price">
            
            <button wire:click="save">Save</button>
            <button wire:click="cancel">Cancel</button>
        </div>
    @endif
</div>
```

---

### 4. Using Enums for Selects

`<livewire:auto.form :model="$model" />`

If your model uses PHP Enums (like a `Status` enum), the package can automatically generate options for your select dropdowns.

**The Livewire Component:**
```php
// Define rules that include the attribute
public function rules(): array
{
    return [
        'status' => 'required',
    ];
}
```

**The Blade View:**
```html
<select wire:model.blur="form.status">
    <option value="">Select Status</option>
    @foreach($this->enumOptionsFor('status') as $option)
        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
    @endforeach
</select>
```

The package looks at your model's `$casts` to find the Enum and creates readable labels automatically!

---

### 5. Listening for Events (Notifications)

`<livewire:auto.form :model="$model" />`

You can listen for the events dispatched by the component to show "Saved" notifications or other UI feedback.

**The Blade View (using Alpine.js):**
```html
<div x-data="{ show: false, message: '' }"
     x-on:saved.window="show = true; message = 'Changes saved!'; setTimeout(() => show = false, 2000)"
     x-on:field-updated.window="show = true; message = 'Field updated!'; setTimeout(() => show = false, 2000)">
    
    <div x-show="show" class="notification" style="display: none;">
        <span x-text="message"></span>
    </div>

    <!-- your form content ... -->
</div>
```

---

### 6. List & Edit Pattern

`<livewire:auto.form />`

You can use a single component to manage a collection of models, allowing you to select and edit any record from a list, or create a new one, all within the same view state.

**The Livewire Component:**
```php
class ManageProducts extends LivewireAutoFormComponent
{
    public function rules(): array
    {
        return [
            'name' => 'required',
            'price' => 'numeric',
        ];
    }
}
```

**The Blade View:**
```html
<div>
    <!-- 1. The List -->
    <ul>
        @foreach(Product::all() as $product)
            <li>
                {{ $product->name }}
                <button wire:click="edit('', {{ $product->id }})">Edit</button>
                <button wire:click="delete('', {{ $product->id }})">Delete</button>
            </li>
        @endforeach
    </ul>

    <button wire:click="add('')">Create New Product</button>

    <hr>

    <!-- 2. The Edit/Create Form -->
    <h3>{{ $form->rootModelId ? 'Edit Product' : 'New Product' }}</h3>
    
    <input type="text" wire:model.blur="form.name">
    <input type="number" wire:model.blur="form.price">
    
    <button wire:click="save">Save Product</button>
    <button wire:click="cancel">Reset Form</button>
</div>
```



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



