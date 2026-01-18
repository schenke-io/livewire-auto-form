@if($isActive)
    <div class="space-y-4">
        <flux:heading>Account Information</flux:heading>

        <flux:field>
            <flux:label>Full Name</flux:label>
            <flux:input wire:model="form.name" dusk="name" />
            <flux:error name="form.name" />
        </flux:field>

        <flux:field>
            <flux:label>Email Address</flux:label>
            <flux:input type="email" wire:model="form.email" dusk="email" />
            <flux:error name="form.email" />
        </flux:field>
    </div>
@endif
