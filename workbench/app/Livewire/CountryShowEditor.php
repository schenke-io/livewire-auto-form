<?php

namespace Workbench\App\Livewire;

use Livewire\Component;
use SchenkeIo\LivewireAutoForm\Helpers\LivewireAutoFormException;
use Workbench\App\Livewire\Traits\EditorHelper;
use Workbench\App\Models\Country;

/**
 * CountryShowEditor Component
 *
 * This component provides an interface for editing Country models and their
 * related cities and borders. It demonstrates the use of LivewireAutoForm
 * for handling complex relationships and manual save modes.
 *
 * Features:
 * - Form generation for Country attributes.
 * - Support for nested relationships (Cities and Borders).
 * - Manual save mode (autoSave = false) to require explicit user action.
 * - Integration with EditorHelper for workbench navigation.
 */
class CountryShowEditor extends Component
{
    use EditorHelper;
    use \SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;

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
            'code',
            'cities.id',
            'cities.name',
            'cities.population',
            'cities.status',
            'cities.is_capital',
            'cities.geo.latitude',
            'cities.geo.longitude',
            'cities.geo.diameter',
            'cities.geo.height',
            'borders.id',
            'borders.name',
            'borders.pivot.border_length_km',
        ];
    }

    /**
     * @throws LivewireAutoFormException
     */
    public function mount(\Illuminate\Database\Eloquent\Model $country): void
    {
        $this->autoSave = false;
        $this->setModel($country);
    }

    public function save(): void
    {
        $this->validate();
        $this->getCrudProcessor()->save($this->form->all());
        session()->flash('status', 'Saved successfully');
    }

    public function render(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\Support\Htmlable
    {
        return view('livewire.country-show-editor');
    }
}
