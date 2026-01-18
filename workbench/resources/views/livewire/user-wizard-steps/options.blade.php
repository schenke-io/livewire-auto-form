@if($isActive)
    <div class="space-y-4">
        <flux:heading>Preferences</flux:heading>

        <flux:field>
            <flux:checkbox label="I want to receive marketing emails" wire:model="form.marketing_opt_in" dusk="marketing_opt_in" />
            <flux:error name="form.marketing_opt_in" />
        </flux:field>
    </div>
@endif
