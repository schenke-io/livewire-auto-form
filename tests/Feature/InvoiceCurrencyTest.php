<?php

namespace Tests\Feature;

use Livewire\Livewire;
use SchenkeIo\Invoice\Money\Currency;
use Workbench\App\Livewire\InvoiceEditor;
use Workbench\App\Models\Invoice;

it('loads currency value from model into auto-form', function () {
    $invoice = Invoice::create(['amount' => 12.34]);

    Livewire::test(InvoiceEditor::class, ['invoice' => $invoice])
        ->assertSet('draftState.amount', 12.34);
});

it('saves changed currency value from auto-form back to model', function () {
    $invoice = Invoice::create(['amount' => 10.00]);

    Livewire::test(InvoiceEditor::class, ['invoice' => $invoice])
        ->set('draftState.amount', 25.50)
        ->call('save')
        ->assertHasNoErrors();

    expect($invoice->refresh()->amount)->toBeInstanceOf(Currency::class)
        ->and($invoice->amount->toFloat())->toBe(25.5);
});

it('handles localized currency strings in auto-form', function () {
    $invoice = Invoice::create(['amount' => 10.00]);

    Livewire::test(InvoiceEditor::class, ['invoice' => $invoice])
        ->set('draftState.amount', '12,34')
        ->call('save')
        ->assertHasNoErrors();

    expect($invoice->refresh()->amount->toFloat())->toBe(12.34);
});

it('validates currency values', function () {
    $invoice = Invoice::create(['amount' => 10.00]);

    Livewire::test(InvoiceEditor::class, ['invoice' => $invoice])
        ->set('draftState.amount', '')
        ->call('save')
        ->assertHasErrors(['form.amount' => 'required']);
});
