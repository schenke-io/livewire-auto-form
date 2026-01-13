<?php

namespace Workbench\App\Livewire;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\LivewireAutoFormComponent;
use Workbench\App\Livewire\Traits\EditorHelper;
use Workbench\App\Models\Country;

class CountryShowEditor extends LivewireAutoFormComponent
{
    use EditorHelper;

    public Country $country;

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:10',
            'cities.name' => 'nullable|string|max:255',
            'cities.population' => 'nullable|integer',
            'borders.name' => 'nullable|string|max:255',
            'languages.name' => 'nullable|string|max:255',
            'languages.code' => 'nullable|string|max:10',
        ];
    }

    public function mount(?Model $country = null): void
    {
        if ($country instanceof Country) {
            $this->country = $country;
            parent::mount($country);
        }
        // Manual save mode for Country
        $this->autoSave = false;
    }

    public function render()
    {
        return view('livewire.country-show-editor');
    }
}
