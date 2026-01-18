<div class="space-y-6">
    @if (session('status'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Edit City{{ $this->getSaveModeSuffix() }}</flux:heading>
        <span class="text-xs text-gray-500">(Changes are saved automatically)</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        <!-- Main Fields -->
        <flux:callout class="space-y-4">
            <flux:heading size="lg">Main Fields</flux:heading>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Name</flux:label>
                    <flux:input wire:model.blur="form.name" dusk="name" />
                    <flux:error name="form.name" />
                </flux:field>

                <flux:field>
                    <flux:label>Population</flux:label>
                    <flux:input type="number" wire:model.blur="form.population" dusk="population" />
                    <flux:error name="form.population" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Background (max 200)</flux:label>
                <flux:input wire:model.blur="form.background" dusk="background" />
                <flux:error name="form.background" />
            </flux:field>

            <flux:field>
                <flux:checkbox label="Is capital" wire:model.live="form.is_capital" dusk="is_capital" />
                <flux:error name="form.is_capital" />
            </flux:field>
        </flux:callout>

        <!-- Country Relation -->
        <flux:callout class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">Country</flux:heading>
                <div class="text-xs text-gray-500">Auto-saves on change</div>
            </div>

            <div class="flex items-center gap-2">
                <flux:field class="grow">
                    <flux:select wire:model.live="form.country_id" :disabled="$form->activeContext === 'country'" dusk="country_id">
                        <flux:select.option value="">-- Select country --</flux:select.option>
                        @foreach($this->optionsFor('country') as $opt)
                            <flux:select.option wire:key="country-opt-{{ $opt[0] }}" value="{{ $opt[0] }}">{{ $opt[1] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.country_id" />
                </flux:field>

                @if(($form['country_id'] ?? null) && $form->activeContext !== 'country')
                    <flux:button.group size="sm">
                        <flux:button icon="pencil" wire:click="edit('country', {{ $form['country_id'] }})" dusk="edit-country-details" />
                        <flux:button variant="ghost" icon="eye" href="{{ route('countries.show', $form['country_id']) }}" dusk="goto-country" />
                    </flux:button.group>
                @endif

                @if(!($form['country_id'] ?? null) && $form->activeContext !== 'country')
                    <flux:button size="sm" icon="plus" wire:click="edit('country', null)">Create</flux:button>
                @endif
            </div>

            @if($form->activeContext === 'country')
                <flux:callout variant="outline" class="mt-4 !bg-blue-50/50 border-blue-200">
                    <flux:heading size="sm" class="mb-4">Edit Country Details{{ $this->getSaveModeSuffix() }}</flux:heading>
                    <div class="space-y-4">
                        @foreach($form['country'] as $k => $v)
                            @if(!is_array($v) && $k !== 'id')
                                <flux:field wire:key="country-field-{{ $k }}">
                                    <flux:label>{{ Str::headline($k) }}</flux:label>
                                    <flux:input wire:model="form.country.{{ $k }}" dusk="country-field-{{ $k }}" />
                                    <flux:error name="form.country.{{ $k }}" />
                                </flux:field>
                            @endif
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2 mt-4">
                        <flux:button size="sm" wire:click="save">Save</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                    </div>
                </flux:callout>
            @endif
        </flux:callout>
    </div>
</div>
