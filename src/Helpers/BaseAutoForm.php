<?php

namespace SchenkeIo\LivewireAutoForm\Helpers;

use Livewire\Component;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;

/**
 * BaseAutoForm provides the common foundation for all AutoForm variants.
 *
 * It integrates the core HasAutoForm engine with Livewire's Component system,
 * handling the basic setup and state initialization.
 *
 * @implements \ArrayAccess<string, mixed>
 */
abstract class BaseAutoForm extends Component implements \ArrayAccess
{
    use HasAutoForm;
}
