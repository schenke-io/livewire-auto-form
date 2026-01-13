<?php

namespace Tests\Feature\Livewire\Components\Models;

class ModelWithException extends \Illuminate\Database\Eloquent\Model
{
    public function getCasts()
    {
        throw new \Exception('Test Exception');
    }
}
