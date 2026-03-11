<?php

namespace Tests\Feature\Livewire\Components\Models;

use Illuminate\Database\Eloquent\Model;

class ModelWithInvalidEnumCast extends Model
{
    protected $casts = [
        'invalid' => 'NonExistentEnum',
    ];
}
