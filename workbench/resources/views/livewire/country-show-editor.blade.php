<div class="space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">Edit Country{{ $this->getSaveModeSuffix() }}</flux:heading>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">(Manual save required)</span>
            <flux:button size="sm" icon="check" wire:click="save">
                Save
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Column 1: Main Fields -->
        <flux:callout class="space-y-4">
            <flux:heading size="lg">Main Fields</flux:heading>
            <flux:field>
                <flux:label>Name</flux:label>
                <flux:input wire:model="form.name" dusk="name" />
                <flux:error name="form.name" />
            </flux:field>

            <flux:field>
                <flux:label>Code</flux:label>
                <flux:input wire:model="form.code" dusk="code" />
                <flux:error name="form.code" />
            </flux:field>
        </flux:callout>

        <!-- Column 2: Cities -->
        <flux:callout class="space-y-4">
            <flux:heading size="lg">Cities</flux:heading>

            <flux:navlist>
                @foreach($this->getRelationList('cities') as $row)
                    <div wire:key="city-{{ $row->id }}" class="mb-2">
                        <flux:button.group class="w-full">
                            <flux:button variant="ghost" class="grow justify-start pointer-events-none text-left">
                                {{ $row->name ?? '—' }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" icon="eye" href="{{ route('cities.show', $row->id) }}" dusk="goto-city-{{ $row->id }}" />
                            <flux:button size="sm" icon="pencil" wire:click="edit('cities', {{ $row->id }})" dusk="edit-city-{{ $row->id }}" />
                            <flux:button size="sm" icon="trash" wire:click="delete('cities', {{ $row->id }})" dusk="delete-city-{{ $row->id }}" />
                        </flux:button.group>

                        @if($form->activeContext === 'cities' && $form->activeId == $row->id)
                            <flux:callout variant="outline" class="mt-2 !bg-blue-50/50 border-blue-200">
                                <flux:heading size="sm" class="mb-2">Edit City{{ $this->getSaveModeSuffix() }}</flux:heading>
                                <div class="space-y-4">
                                    @foreach($form['cities'] as $k => $v)
                                        @if(!is_array($v) && $k !== 'id')
                                            <flux:field wire:key="city-field-{{ $k }}">
                                                <flux:label>{{ Str::headline($k) }}</flux:label>
                                                <flux:input dusk="city-field-{{ $k }}" wire:model="form.cities.{{ $k }}" />
                                                <flux:error name="form.cities.{{ $k }}" />
                                            </flux:field>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-2 mt-3">
                                    <flux:button size="sm" wire:click="save">Save</flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                                </div>
                            </flux:callout>
                        @endif
                    </div>
                @endforeach
            </flux:navlist>

            @if($form->activeContext === 'cities' && $form->activeId === null)
                <flux:callout variant="outline" class="mt-2 !bg-blue-50/50 border-blue-200">
                    <flux:heading size="sm" class="mb-2">New City</flux:heading>
                    <div class="space-y-4">
                        @foreach($form['cities'] as $k => $v)
                            @if(!is_array($v) && $k !== 'id')
                                <flux:field wire:key="new-city-field-{{ $k }}">
                                    <flux:label>{{ Str::headline($k) }}</flux:label>
                                    <flux:input dusk="new-city-field-{{ $k }}" wire:model="form.cities.{{ $k }}" />
                                    <flux:error name="form.cities.{{ $k }}" />
                                </flux:field>
                            @endif
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <flux:button size="sm" wire:click="save">Save</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                    </div>
                </flux:callout>
            @endif

            <flux:button size="sm" icon="plus" wire:click="add('cities')" class="w-full" dusk="add-city">Add City</flux:button>
        </flux:callout>

        <!-- Column 3: Borders -->
        <flux:callout class="space-y-4">
            <flux:heading size="lg">Borders</flux:heading>

            <flux:navlist>
                @foreach($this->getRelationList('borders') as $row)
                    <div wire:key="border-{{ $row->id }}" class="mb-2">
                        <flux:button.group class="w-full">
                            <flux:button variant="ghost" class="grow justify-start pointer-events-none text-left">
                                {{ $row->name ?? '—' }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" icon="eye" href="{{ route('countries.show', $row->id) }}" dusk="goto-border-{{ $row->id }}" />
                            <flux:button size="sm" icon="pencil" wire:click="edit('borders', {{ $row->id }})" dusk="edit-border-{{ $row->id }}" />
                            <flux:button size="sm" icon="trash" wire:click="delete('borders', {{ $row->id }})" dusk="delete-border-{{ $row->id }}" />
                        </flux:button.group>

                        @if($form->activeContext === 'borders' && $form->activeId == $row->id)
                            <flux:callout variant="outline" class="mt-2 !bg-blue-50/50 border-blue-200">
                                <flux:heading size="sm" class="mb-2">Edit Border Country{{ $this->getSaveModeSuffix() }}</flux:heading>
                                <div class="space-y-4">
                                    @foreach($form['borders'] as $k => $v)
                                        @if(!is_array($v))
                                            @if($k === 'id')
                                                <flux:field wire:key="border-field-id">
                                                    <flux:label>Country</flux:label>
                                                    <flux:select dusk="border-field-id" wire:model="form.borders.id">
                                                        <flux:select.option value="">Select a country...</flux:select.option>
                                                        @foreach($this->optionsFor('borders') as $option)
                                                            <flux:select.option value="{{ $option[0] }}">{{ $option[1] }}</flux:select.option>
                                                        @endforeach
                                                    </flux:select>
                                                    <flux:error name="form.borders.id" />
                                                </flux:field>
                                            @else
                                                <flux:field wire:key="border-field-{{ $k }}">
                                                    <flux:label>{{ Str::headline($k) }}</flux:label>
                                                    <flux:input dusk="border-field-{{ $k }}" wire:model="form.borders.{{ $k }}" />
                                                    <flux:error name="form.borders.{{ $k }}" />
                                                </flux:field>
                                            @endif
                                        @elseif($k === 'pivot')
                                            @foreach($v as $pk => $pv)
                                                <flux:field wire:key="border-pivot-field-{{ $pk }}">
                                                    <flux:label>{{ Str::headline($pk) }}</flux:label>
                                                    <flux:input dusk="border-field-pivot-{{ $pk }}" wire:model="form.borders.pivot.{{ $pk }}" />
                                                    <flux:error name="form.borders.pivot.{{ $pk }}" />
                                                </flux:field>
                                            @endforeach
                                        @endif
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-2 mt-3">
                                    <flux:button size="sm" wire:click="save" dusk="save-border">Save</flux:button>
                                    <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                                </div>
                            </flux:callout>
                        @endif
                    </div>
                @endforeach
            </flux:navlist>

            @if($form->activeContext === 'borders' && $form->activeId === null)
                <flux:callout variant="outline" class="mt-2 !bg-blue-50/50 border-blue-200">
                    <flux:heading size="sm" class="mb-2">New Border Country</flux:heading>
                    <div class="space-y-4">
                        @foreach($form['borders'] as $k => $v)
                            @if(!is_array($v))
                                @if($k === 'id')
                                    <flux:field wire:key="new-border-field-id">
                                        <flux:label>Country</flux:label>
                                        <flux:select dusk="new-border-field-id" wire:model="form.borders.id">
                                            <flux:select.option value="">Select a country...</flux:select.option>
                                            @foreach($this->optionsFor('borders') as $option)
                                                <flux:select.option value="{{ $option[0] }}">{{ $option[1] }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:error name="form.borders.id" />
                                    </flux:field>
                                @else
                                    <flux:field wire:key="new-border-field-{{ $k }}">
                                        <flux:label>{{ Str::headline($k) }}</flux:label>
                                        <flux:input dusk="new-border-field-{{ $k }}" wire:model="form.borders.{{ $k }}" />
                                        <flux:error name="form.borders.{{ $k }}" />
                                    </flux:field>
                                @endif
                            @elseif($k === 'pivot')
                                @foreach($v as $pk => $pv)
                                    <flux:field wire:key="new-border-pivot-field-{{ $pk }}">
                                        <flux:label>{{ Str::headline($pk) }}</flux:label>
                                        <flux:input dusk="new-border-field-pivot-{{ $pk }}" wire:model="form.borders.pivot.{{ $pk }}" />
                                        <flux:error name="form.borders.pivot.{{ $pk }}" />
                                    </flux:field>
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <flux:button size="sm" wire:click="save" dusk="save-new-border">Save</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                    </div>
                </flux:callout>
            @endif

            <flux:button size="sm" icon="plus" wire:click="add('borders')" class="w-full" dusk="add-border">Add Border</flux:button>
        </flux:callout>
    </div>
</div>
