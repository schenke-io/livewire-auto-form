<?php

namespace Workbench\App\Livewire;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\LivewireAutoFormComponent;
use Workbench\App\Livewire\Traits\EditorHelper;
use Workbench\App\Models\City;

class CityShowEditor extends LivewireAutoFormComponent
{
    use EditorHelper;

    public City $city;

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'background' => 'nullable|string|max:200',
            'population' => 'nullable|integer|min:0',
            'is_capital' => 'boolean',
            'group' => 'nullable|string',
            'brands.name' => 'nullable|string|max:255',
            'brands.group' => 'nullable|string',
            'rivers.name' => 'nullable|string|max:255',
            'rivers.length_km' => 'nullable|integer',
            'country.name' => 'nullable|string|max:255',
            'country.code' => 'nullable|string|max:2',
        ];
    }

    public function mount(?Model $city = null): void
    {
        if ($city instanceof City) {
            $this->city = $city;
            parent::mount($city);
        }
        // Manual save mode for City
        $this->autoSave = false;
    }

    public function render()
    {
        return view('livewire.city-show-editor');
    }
}
