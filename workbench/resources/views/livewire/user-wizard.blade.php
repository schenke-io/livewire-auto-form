<div class="space-y-6">
    <flux:heading size="xl">User Profile Wizard</flux:heading>
    <flux:subheading>Manage your account settings in a few simple steps.</flux:subheading>

    @if (session('status'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        @foreach($this->getSteps() as $index => $step)
            <div wire:key="step-indicator-{{ $step }}" class="flex items-center space-x-2 {{ $this->isStepActive($index) ? 'text-blue-600' : 'text-gray-400' }}">
                <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center font-bold {{ $this->isStepActive($index) ? 'border-blue-600' : 'border-gray-200' }}">
                    {{ $index + 1 }}
                </div>
                <flux:heading size="sm" class="capitalize">{{ $step }}</flux:heading>
            </div>
        @endforeach
    </div>

    <div class="p-6 bg-white border border-gray-200 rounded-lg shadow-sm">
        <form wire:submit="submit" class="space-y-6">
            @foreach($this->getSteps() as $index => $step)
                <div wire:key="step-content-{{ $step }}">
                    @include('livewire.user-wizard-steps.' . $step, ['isActive' => $this->isStepActive($index)])
                </div>
            @endforeach

            <div class="flex justify-between pt-4 border-t">
                @if($this->currentStepIndex > 0)
                    <flux:button type="button" wire:click="previous">Previous</flux:button>
                @else
                    <div></div>
                @endif

                @if(! $this->isLastStep())
                    <flux:button type="submit" variant="primary">Next Step</flux:button>
                @else
                    <flux:button type="submit" variant="primary">Save Changes</flux:button>
                @endif
            </div>
        </form>
    </div>
</div>
