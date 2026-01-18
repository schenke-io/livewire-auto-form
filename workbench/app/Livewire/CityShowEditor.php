<?php

namespace Workbench\App\Livewire;

use Livewire\Component;
use Workbench\App\Livewire\Traits\EditorHelper;
use Workbench\App\Models\City;

/**
 * CityShowEditor Component
 *
 * This component provides a live-editing interface for the City model.
 * It utilizes the LivewireAutoForm package to automatically handle form
 * generation, validation, and persistence.
 *
 * Features:
 * - Automatic form rendering based on model schema and rules.
 * - Auto-save functionality enabled by default.
 * - Validation rules defined for city attributes and related country.
 * - Integration with EditorHelper for common workbench UI actions.
 */
class CityShowEditor extends Component
{
    use EditorHelper;
    use \SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;

    public City $city;

    public function boot(): void
    {
        $this->initializeHasAutoForm();
    }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'background' => 'nullable|string|max:200',
            'population' => 'nullable|integer|min:0',
            'is_capital' => 'boolean',
            'group' => 'nullable|string',
            'country_id' => 'required|exists:countries,id',
            'country.name' => 'nullable|string|max:255',
            'country.code' => 'nullable|string|max:2',
        ];
    }

    public function mount(City $city): void
    {
        $this->city = $city;
        $this->autoSave = true;
        $this->setModel($city);
    }

    public function save(): void
    {
        $this->validate();
        $this->getCrudProcessor()->save($this->form->all());
        session()->flash('status', 'Saved successfully');
    }

    public function render()
    {
        return view('livewire.city-show-editor');
    }
}
