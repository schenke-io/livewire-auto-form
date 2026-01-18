@if($isActive)
    <div class="space-y-4">
        <flux:heading>Contact Details</flux:heading>

        <flux:field>
            <flux:label>Phone Number</flux:label>
            <flux:input type="tel" wire:model="form.phone" dusk="phone" />
            <flux:error name="form.phone" />
        </flux:field>
    </div>
@endif
