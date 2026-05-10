<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\Invoice\Casts\CurrencyCast;

class Invoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => CurrencyCast::class,
    ];
}
