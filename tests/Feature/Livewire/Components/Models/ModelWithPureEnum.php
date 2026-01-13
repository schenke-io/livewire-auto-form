<?php

namespace Tests\Feature\Livewire\Components\Models;

class ModelWithPureEnum extends \Illuminate\Database\Eloquent\Model
{
    protected $casts = [
        'pure' => PureEnum::class,
    ];
}
