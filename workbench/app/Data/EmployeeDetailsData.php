<?php

namespace Workbench\App\Data;

use Spatie\LaravelData\Data;

class EmployeeDetailsData extends Data
{
    public function __construct(
        public string $position,
        public int $salary,
    ) {}
}
