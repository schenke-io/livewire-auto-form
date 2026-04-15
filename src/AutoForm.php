<?php

namespace SchenkeIo\LivewireAutoForm;

use Livewire\Component;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;

/**
 * AutoForm is the core of the Livewire Auto Form package.
 *
 * It serves as a base class for Livewire components that want to leverage
 * the automatic form generation and relationship management features.
 * By using the `HasAutoForm` trait, it integrates with the underlying
 * `FormCollection` to provide a seamless data-binding experience.
 *
 * @implements \ArrayAccess<string, mixed>
 */
class AutoForm extends Component implements \ArrayAccess
{
    /**
     * Integrates the core auto-form functionality.
     */
    use HasAutoForm;
}
