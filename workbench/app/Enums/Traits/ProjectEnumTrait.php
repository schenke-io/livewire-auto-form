<?php

namespace Workbench\App\Enums\Traits;

use SchenkeIo\LivewireAutoForm\Traits\AutoFormLocalisedEnumOptions;

trait ProjectEnumTrait
{
    use AutoFormLocalisedEnumOptions;

    public function icon(): string
    {
        return 'heroicon-o-check';
    }
}
