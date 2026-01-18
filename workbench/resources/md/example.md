# Code Examples

This guide provides examples for using the package, ranging from basic forms to more advanced scenarios.

> **Note on Method Calls:** Since the form logic is integrated directly into the component, you can call methods like `save()` or `edit()` directly in Blade. This ensures full compatibility with Alpine.js and component libraries like **Flux** without the need for wrapper methods. See [API Definitions](definitions.md#calling-methods-from-blade) for details.

### 1. The Basic Form (Manual Save)

This is the simplest way to use the package. You extend `AutoForm` in your component and initialize it.

**The Livewire Component:**
```php
use SchenkeIo\LivewireAutoForm\AutoForm;

class EditPost extends AutoForm
{
    public function mount(Post $post)
    {
        $this->setModel($post);
    }

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

If you want your form to save automatically as the user types (on blur), just set `$this->autoSave` to `true`.

**The Livewire Component:**
```php
class EditPost extends AutoForm
{
    public function mount(Post $post)
    {
        $this->setModel($post);
        $this->autoSave = true;
    }

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
    
    <span wire:loading wire:target="save">Saving...</span>
</div>
```

---

### 3. Handling Relationships

This is where the package really shines. Imagine a `Brand` that has many `Products`. You can edit the brand and its products in the same component.

**The Livewire Component:**
```php
class EditBrand extends AutoForm
{
    public function mount(Brand $brand)
    {
        $this->setModel($brand);
    }

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
    @if($activeContext === 'products')
        <div class="modal">
            <h4>{{ $activeId ? 'Edit Product' : 'Add Product' }}</h4>
            
            <!-- Relationship form is stored under the relationship name in the $form buffer -->
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
    @foreach($this->optionsFor('status') as $option)
        <option value="{{ $option[0] }}">{{ $option[1] }}</option>
    @endforeach
</select>
```

The package looks at your model's `$casts` to find the Enum and creates readable labels automatically!

---

### 5. Listening for Events (Notifications)

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

You can use a single component to manage a collection of models, allowing you to select and edit any record from a list, or create a new one, all within the same view state.

**The Livewire Component:**
```php
class ManageProducts extends AutoForm
{
    public function mount()
    {
        $this->setModel(new Product);
    }

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

---

### 7. Multi-Step Wizard

For complex forms, extend `AutoWizardForm` to break them into steps. It handles step navigation, explicit field mapping, and per-step validation.

**The Livewire Component:**
```php
class UserWizard extends AutoWizardForm
{
    public array $structure = [
        'profile' => ['name'],
        'address' => ['city'],
    ];
    
    public string $stepViewPrefix = 'livewire.steps.';

    public function rules(): array
    {
        return [
            'name' => 'required',
            'city' => 'required',
        ];
    }
    
    public function mount(User $user)
    {
        $this->setModel($user);
        parent::mount();
    }
}
```

**The Main Blade View:**
```html
<form wire:submit.prevent="submit">
    @foreach($this->getSteps() as $index => $step)
        @include('livewire.steps.' . $step, ['isActive' => $this->isStepActive($index)])
    @endforeach

    <button type="submit">{{ $this->isLastStep() ? 'Finish' : 'Next' }}</button>
</form>
```
