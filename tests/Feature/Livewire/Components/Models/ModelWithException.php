<?php

namespace Tests\Feature\Livewire\Components\Models;

class ModelWithException extends \Illuminate\Database\Eloquent\Model
{
    /**
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        throw new \Exception('Test Exception');
    }
}
