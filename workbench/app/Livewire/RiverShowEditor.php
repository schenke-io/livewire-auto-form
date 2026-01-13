<?php

namespace Workbench\App\Livewire;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\LivewireAutoFormComponent;
use Workbench\App\Livewire\Traits\EditorHelper;
use Workbench\App\Models\River;

class RiverShowEditor extends LivewireAutoFormComponent
{
    use EditorHelper;

    public River $river;

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'length_km' => 'nullable|integer|min:0',
            'cities.name' => 'nullable|string|max:255',
        ];
    }

    public function mount(Model $river): void
    {
        if ($river instanceof River) {
            $this->river = $river;
        }
        parent::mount($river);
        // Manual save mode for River
        $this->autoSave = false;
    }

    public function render()
    {
        return view('livewire.river-show-editor');
    }
}
