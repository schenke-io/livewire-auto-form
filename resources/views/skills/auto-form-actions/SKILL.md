---
name: auto-form-actions
description: Override save() and cancel(), inject action classes via boot(), and extend AutoForm lifecycle methods.
---

# Livewire Auto Form — Actions & Lifecycle

## When to use
Use this skill when you need to go beyond the default form behaviour: redirect after saving, run an action class after persistence, gate access in `mount()`, or wire up external services (mailers, notifiers, domain handlers) without polluting the component state.

---

## Overriding `save()`

Call `parent::save()` first. It validates all rules, persists the buffer via `CrudProcessor`, dispatches the `saved` event, and automatically returns to the root context if a relation was being edited.

```php
use SchenkeIo\LivewireAutoForm\AutoForm;
use App\Models\Post;

class PostForm extends AutoForm
{
    public function mount(Post $post): void
    {
        $this->setModel($post);
    }

    public function ruleKeys(): array
    {
        return ['title', 'body'];
    }

    public function save(): void
    {
        parent::save(); // validate → persist → dispatch 'saved' → cancel relation if active

        $this->redirect(route('posts.index'), navigate: true);
    }
}
```

You can also run domain logic between `parent::save()` and the redirect:

```php
public function save(): void
{
    parent::save();

    // getModel() returns the root model with the just-saved state
    SendPostPublishedNotification::dispatch($this->getModel());

    $this->redirect(route('posts.index'), navigate: true);
}
```

> **Tip**: `$this->getModel()` returns the root model with the current buffer applied. After `parent::save()` the database record is up to date, so `$this->getModel()->refresh()` is not needed.

---

## Overriding `cancel()`

`cancel()` resets the buffer back to the root model state and is intended for user-initiated cancellation (e.g. a "Cancel" button). Override it to redirect or perform cleanup.

Unlike internal state transitions, `cancel()` is **not** called automatically after saving a relation; instead, the internal `returnToRootContext()` method is used. This means you can safely redirect in `cancel()` without interfering with relationship saves.

```php
public function cancel(): void
{
    parent::cancel(); // reloads root model state into buffer

    $this->redirect(route('posts.index'), navigate: true);
}
```

---

## Injecting action classes via `boot()`

Livewire's `boot()` lifecycle hook runs **on every request** (both the initial render and all subsequent Livewire network calls), making it the correct place to inject services that are needed in `save()`, `updated()`, or event handlers — not just during initialisation.

Action classes must be stored as `private` or `protected` properties so Livewire does not attempt to serialize them across requests.

```php
use SchenkeIo\LivewireAutoForm\AutoForm;
use App\Actions\NotifySubscribersAction;
use App\Models\Post;

class PostForm extends AutoForm
{
    private NotifySubscribersAction $notifySubscribers;

    // DI parameters in boot() are resolved by the service container on every request
    public function boot(NotifySubscribersAction $notifySubscribers): void
    {
        $this->notifySubscribers = $notifySubscribers;
    }

    public function mount(Post $post): void
    {
        $this->setModel($post);
    }

    public function ruleKeys(): array
    {
        return ['title', 'body'];
    }

    public function save(): void
    {
        parent::save();

        $this->notifySubscribers->execute($this->getModel());
        $this->redirect(route('posts.index'), navigate: true);
    }
}
```

### Using `HasAutoForm` directly (without extending `AutoForm`)

When you use the `HasAutoForm` trait on a plain `Livewire\Component` (e.g., because you need multiple inheritance or a specific base class), the internal form collection is automatically initialized by the trait boot hooks. No manual initialization is required.

```php
use Livewire\Component;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;
use App\Actions\AuditLogAction;
use App\Models\Country;

class CountryEditor extends Component
{
    use HasAutoForm;

    private AuditLogAction $auditLog;

    public function boot(AuditLogAction $auditLog): void
    {
        $this->auditLog = $auditLog;
    }

    public function ruleKeys(): array
    {
        return ['name', 'code'];
    }

    public function mount(Country $country): void
    {
        $this->setModel($country);
    }

    public function save(): void
    {
        $this->validate();
        $this->traitSave();
        $this->auditLog->record('country.updated', $this->form->rootModelId);
    }

    public function render()
    {
        return view('livewire.country-editor');
    }
}
```

---

## Using action classes in `mount()`

`mount()` supports the same Laravel DI as `boot()`. Use it for actions that are only needed **once** at initialisation — such as authorization guards or factory actions that create a default model instance.

### Authorization guard

```php
use SchenkeIo\LivewireAutoForm\AutoForm;
use App\Actions\AuthorizePostEditAction;
use App\Models\Post;

class PostForm extends AutoForm
{
    public function mount(Post $post, AuthorizePostEditAction $authorize): void
    {
        $authorize->execute($post); // throws AuthorizationException if denied

        $this->setModel($post);
    }
    // ...
}
```

### Preparing a new record with defaults

```php
use SchenkeIo\LivewireAutoForm\AutoForm;
use App\Actions\PrepareNewPostAction;

class CreatePostForm extends AutoForm
{
    public function mount(PrepareNewPostAction $prepare): void
    {
        // Action returns an unsaved Post with defaults applied (e.g. current user, defaults)
        $post = $prepare->execute();

        $this->setModel($post);
    }

    public function ruleKeys(): array
    {
        return ['title', 'body', 'status'];
    }

    public function save(): void
    {
        parent::save();
        $this->redirect(route('posts.index'), navigate: true);
    }
}
```

---

## `boot()` vs `mount()` — when to use each

| Concern | `boot()` | `mount()` |
|---------|----------|-----------|
| Called on every request | Yes | No (first render only) |
| Suitable for services used in `save()` | Yes | No — not available on subsequent calls |
| Suitable for one-time initialisation | Yes (but redundant) | Yes |
| Receives route model binding | No | Yes |
