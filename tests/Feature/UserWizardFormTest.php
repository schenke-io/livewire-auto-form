<?php

namespace Tests\Feature;

use Livewire\Livewire;
use Workbench\App\Livewire\Forms\UserWizardForm;

it('UserWizardForm succeeds on mount with correct structure', function () {
    Livewire::test(UserWizardForm::class)->assertHasNoErrors();
});
