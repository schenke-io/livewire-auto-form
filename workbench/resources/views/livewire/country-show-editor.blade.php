<div class="space-y-6">
    @if (session('status'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <flux:heading size="xl">Edit Country{{ $this->getSaveModeSuffix() }}</flux:heading>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500">(Manual save required)</span>
            <flux:button size="sm" icon="check" wire:click="save" dusk="main-save">Save</flux:button>
        </div>
    </div>

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
                                <flux:field>
                                    <flux:label>Name</flux:label>
                                    <flux:input dusk="city-field-name" wire:model="form.cities.name" />
                                    <flux:error name="form.cities.name" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Population</flux:label>
                                    <flux:input dusk="city-field-population" wire:model="form.cities.population" />
                                    <flux:error name="form.cities.population" />
                                </flux:field>

                                <div class="grid grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Is capital</flux:label>
                                        <flux:checkbox dusk="city-field-is-capital" wire:model="form.cities.is_capital" />
                                        <flux:error name="form.cities.is_capital" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Status</flux:label>
                                        <flux:select dusk="city-field-status" wire:model="form.cities.status">
                                            @foreach(\Workbench\App\Enums\CityStatus::cases() as $case)
                                                <flux:select.option value="{{ $case->value }}">{{ $case->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                        <flux:error name="form.cities.status" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Latitude</flux:label>
                                        <flux:input type="number" step="any" dusk="city-field-geo-latitude" wire:model="form.cities.geo.latitude" />
                                        <flux:error name="form.cities.geo.latitude" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Longitude</flux:label>
                                        <flux:input type="number" step="any" dusk="city-field-geo-longitude" wire:model="form.cities.geo.longitude" />
                                        <flux:error name="form.cities.geo.longitude" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Diameter</flux:label>
                                        <flux:input type="number" step="any" dusk="city-field-geo-diameter" wire:model="form.cities.geo.diameter" />
                                        <flux:error name="form.cities.geo.diameter" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label>Height</flux:label>
                                        <flux:input type="number" step="any" dusk="city-field-geo-height" wire:model="form.cities.geo.height" />
                                        <flux:error name="form.cities.geo.height" />
                                    </flux:field>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 mt-3">
                                <flux:button size="sm" wire:click="save" dusk="save-city">Save</flux:button>
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
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input dusk="new-city-field-name" wire:model="form.cities.name" />
                        <flux:error name="form.cities.name" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Population</flux:label>
                        <flux:input dusk="new-city-field-population" wire:model="form.cities.population" />
                        <flux:error name="form.cities.population" />
                    </flux:field>

                    <div class="grid grid-cols-2 gap-4">
                        <flux:field>
                            <flux:label>Is capital</flux:label>
                            <flux:checkbox dusk="new-city-field-is-capital" wire:model="form.cities.is_capital" />
                            <flux:error name="form.cities.is_capital" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Status</flux:label>
                            <flux:select dusk="new-city-field-status" wire:model="form.cities.status">
                                @foreach(\Workbench\App\Enums\CityStatus::cases() as $case)
                                    <flux:select.option value="{{ $case->value }}">{{ $case->name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="form.cities.status" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Latitude</flux:label>
                            <flux:input type="number" step="any" dusk="new-city-field-geo-latitude" wire:model="form.cities.geo.latitude" />
                            <flux:error name="form.cities.geo.latitude" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Longitude</flux:label>
                            <flux:input type="number" step="any" dusk="new-city-field-geo-longitude" wire:model="form.cities.geo.longitude" />
                            <flux:error name="form.cities.geo.longitude" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Diameter</flux:label>
                            <flux:input type="number" step="any" dusk="new-city-field-geo-diameter" wire:model="form.cities.geo.diameter" />
                            <flux:error name="form.cities.geo.diameter" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Height</flux:label>
                            <flux:input type="number" step="any" dusk="new-city-field-geo-height" wire:model="form.cities.geo.height" />
                            <flux:error name="form.cities.geo.height" />
                        </flux:field>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <flux:button size="sm" wire:click="save" dusk="save-new-city">Save</flux:button>
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
                                            <flux:field>
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
                                            <flux:field>
                                                <flux:label>{{ Str::headline($k) }}</flux:label>
                                                <flux:input dusk="border-field-{{ $k }}" wire:model="form.borders.{{ $k }}" />
                                                <flux:error name="form.borders.{{ $k }}" />
                                            </flux:field>
                                        @endif
                                    @endif
                                    @if($k === 'pivot')
                                        @foreach($v as $pk => $pv)
                                            <flux:field>
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
                                    <flux:field>
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
                                <flux:field>
                                    <flux:label>{{ Str::headline($k) }}</flux:label>
                                    <flux:input dusk="new-border-field-{{ $k }}" wire:model="form.borders.{{ $k }}" />
                                    <flux:error name="form.borders.{{ $k }}" />
                                </flux:field>
                            @endif
                        @endif
                        @if($k === 'pivot')
                            @foreach($v as $pk => $pv)
                                <flux:field>
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
