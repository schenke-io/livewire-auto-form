<?php

namespace Workbench\App\Livewire;

use Livewire\Component;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;
use Workbench\App\Models\Employee;

class EmployeeEditor extends Component
{
    use HasAutoForm;

    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;
        $this->setModel($employee);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'details.position' => 'required|string',
            'details.salary' => 'required|integer',
        ];
    }

    public function save(): void
    {
        $this->validate();
        $this->traitSave();
    }

    public function render()
    {
        return <<<'HTML'
            <div>
                <input wire:model.live="form.name" />
                <input wire:model.live="form.details.position" />
                <input wire:model.live="form.details.salary" />
            </div>
        HTML;
    }
}
