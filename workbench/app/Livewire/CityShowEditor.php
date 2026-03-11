<?php

namespace Workbench\App\Livewire;

use Livewire\Component;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;
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
    use HasAutoForm;

    public int $counter = 0;

    public City $city;

    public function boot(): void
    {
        $this->initializeHasAutoForm();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->scanInheritedRules();
    }

    /**
     * @return list<string>
     */
    public function ruleKeys(): array
    {
        return [
            'name',
            'background',
            'population',
            'is_capital',
            'country_id',
            'geo.latitude',
            'geo.longitude',
            'geo.diameter',
            'geo.height',
            'country.name',
            'country.code',
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
