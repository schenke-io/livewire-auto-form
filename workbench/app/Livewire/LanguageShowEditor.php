<?php

namespace Workbench\App\Livewire;

use Illuminate\Database\Eloquent\Model;
use SchenkeIo\LivewireAutoForm\LivewireAutoFormComponent;
use Workbench\App\Livewire\Traits\EditorHelper;
use Workbench\App\Models\Language;

class LanguageShowEditor extends LivewireAutoFormComponent
{
    use EditorHelper;

    public Language $language;

    public function rules(): array
    {
        return [
            'code' => 'nullable|string|max:10',
            'name' => 'nullable|string|max:255',
            'countries.name' => 'nullable|string|max:255',
        ];
    }

    public function mount(?Model $language = null): void
    {
        if ($language instanceof Language) {
            $this->language = $language;
            parent::mount($language);
        }
        // Manual save mode for Language
        $this->autoSave = false;
    }

    public function render()
    {
        return view('livewire.language-show-editor');
    }
}
