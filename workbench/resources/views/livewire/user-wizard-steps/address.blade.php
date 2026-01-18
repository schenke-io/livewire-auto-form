@if($isActive)
    <div class="space-y-4">
        <flux:heading>Home Address</flux:heading>

        <flux:field>
            <flux:label>Street Address</flux:label>
            <flux:input wire:model="form.address" dusk="address" />
            <flux:error name="form.address" />
        </flux:field>

        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>ZIP Code</flux:label>
                <flux:input wire:model="form.zip_code" dusk="zip_code" />
                <flux:error name="form.zip_code" />
            </flux:field>

            <flux:field>
                <flux:label>City</flux:label>
                <flux:input wire:model="form.city" dusk="city" />
                <flux:error name="form.city" />
            </flux:field>
        </div>
    </div>
@endif
