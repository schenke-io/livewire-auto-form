<?php

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;
use Workbench\App\Data\EmployeeDetailsData;

class Employee extends Model
{
    protected $guarded = [];

    protected $casts = [
        'details' => EmployeeDetailsData::class,
    ];
}
