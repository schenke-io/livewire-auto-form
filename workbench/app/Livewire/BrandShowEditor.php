<?php

namespace Workbench\App\Livewire;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use SchenkeIo\LivewireAutoForm\LivewireAutoFormComponent;
use Workbench\App\Enums\BrandGroup;
use Workbench\App\Livewire\Traits\EditorHelper;
use Workbench\App\Models\Brand;

class BrandShowEditor extends LivewireAutoFormComponent
{
    use EditorHelper;

    public Brand $brand;

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            // Validate against enum values
            'group' => ['nullable', Rule::enum(BrandGroup::class)],
            'city_id' => 'nullable|integer',
            'city.name' => 'nullable|string|max:255',
            'city.population' => 'nullable|integer',
        ];
    }

    public function mount(?Model $brand = null): void
    {
        if ($brand instanceof Brand) {
            $this->brand = $brand;
            parent::mount($brand);
        }
        // Manual save mode for Brand
        $this->autoSave = false;
    }

    public function render()
    {
        return view('livewire.brand-show-editor');
    }
}
