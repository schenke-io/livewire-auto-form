<?php

namespace Tests\Feature\Livewire\Components\Models;

class ModelWithInvalidEnumCast extends \Illuminate\Database\Eloquent\Model
{
    protected $casts = [
        'invalid' => 'NonExistentEnum',
    ];
}
