<?php

namespace Tests\Feature\Livewire\Components\Models;

use Illuminate\Database\Eloquent\Model;

class ModelWithPureEnum extends Model
{
    protected $casts = [
        'pure' => PureEnum::class,
    ];
}
