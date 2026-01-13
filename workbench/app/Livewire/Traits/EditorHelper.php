<?php

namespace Workbench\App\Livewire\Traits;

trait EditorHelper
{
    /**
     * Get the save mode suffix for titles.
     */
    public function getSaveModeSuffix(): string
    {
        return $this->autoSave ? ' (live)' : ' (save)';
    }
}
