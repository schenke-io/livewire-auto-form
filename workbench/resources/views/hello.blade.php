@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        <flux:heading size="xl" class="mb-6">Welcome to Livewire Auto Form Workbench</flux:heading>

        <p class="text-lg text-gray-700 mb-8">
            This workbench demonstrates the power and simplicity of the <code>Livewire Auto Form</code> package.
            We've simplified the demo to focus on the core features: single-model forms and multi-step wizards.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <flux:heading size="lg">User Wizard</flux:heading>
                <p class="mt-2 text-gray-600 mb-4">
                    Experience the new <code>AutoWizardForm</code>. A 4-step process for managing user profiles,
                    featuring automatic validation between steps and seamless data persistence.
                </p>
                <flux:button href="{{ route('wizard') }}" variant="primary">Try the Wizard</flux:button>
            </div>

            <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
                <flux:heading size="lg">Classic Resources</flux:heading>
                <p class="mt-2 text-gray-600 mb-4">
                    View traditional single-model forms for Cities and Countries. See how relationships
                    like <code>BelongsTo</code> and <code>HasMany</code> are handled automatically.
                </p>
                <div class="flex gap-2">
                    <flux:button href="{{ route('cities.index') }}">Cities</flux:button>
                    <flux:button href="{{ route('countries.index') }}">Countries</flux:button>
                </div>
            </div>
        </div>
    </div>
@endsection
