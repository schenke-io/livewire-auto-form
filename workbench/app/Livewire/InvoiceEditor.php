<?php

namespace Workbench\App\Livewire;

use Livewire\Component;
use SchenkeIo\LivewireAutoForm\Traits\HasAutoForm;
use Workbench\App\Models\Invoice;

class InvoiceEditor extends Component
{
    use HasAutoForm;

    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice;
        $this->setModel($invoice);
    }

    public function rules(): array
    {
        return [
            'amount' => 'required',
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
                <input wire:model.live="form.amount" />
            </div>
        HTML;
    }
}
