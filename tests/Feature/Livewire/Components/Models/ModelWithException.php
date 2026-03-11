<?php

namespace Tests\Feature\Livewire\Components\Models;

use Illuminate\Database\Eloquent\Model;

class ModelWithException extends Model
{
    /**
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        throw new \Exception('Test Exception');
    }
}
