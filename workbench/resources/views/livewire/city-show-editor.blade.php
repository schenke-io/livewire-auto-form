<div class="bg-white rounded-md shadow p-4 space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">Edit City{{ $this->getSaveModeSuffix() }}</h3>
        <!-- Manual save action -->
        <flux:button size="sm" icon="check" wire:click="save">
            Save
        </flux:button>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <label class="block">
            <span class="text-sm text-gray-600">Name</span>
            <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.name">
        </label>
        <label class="block">
            <span class="text-sm text-gray-600">Population</span>
            <input type="number" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.population">
        </label>
        <label class="col-span-2 block">
            <span class="text-sm text-gray-600">Background (max 200)</span>
            <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.background">
        </label>
        <label class="block flex items-center gap-2">
            <input type="checkbox" wire:model="form.is_capital">
            <span class="text-sm text-gray-600">Is capital</span>
        </label>
    </div>

    <!-- Relations -->
    <div class="pt-2 border-t border-gray-200 space-y-6">
        <div class="flex items-center justify-between">
            <h4 class="font-medium">Relations</h4>
            <div class="text-xs text-gray-500">Edit inline and Save</div>
        </div>

        <!-- Brands (Many) -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">Brands</div>
                <flux:button size="xs" wire:click="add('brands')">Add</flux:button>
            </div>
            @foreach($this->getRelationList('brands') as $row)
                <div class="border rounded-md @if($form->activeContext === 'brands' && $form->activeId == $row->id) bg-gray-50 p-3 @else px-3 py-2 @endif">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon name="{{ ($form->activeContext === 'brands' && $form->activeId == $row->id) ? 'chevron-down' : 'chevron-right' }}" variant="mini" class="text-gray-400" />
                            <span>#{{ $row->id }} — {{ $row->name ?? '—' }}</span>
                        </div>
                        @if($form->activeContext !== 'brands' || $form->activeId != $row->id)
                            <div class="flex items-center gap-2">
                                <flux:button size="xs" wire:click="edit('brands', {{ $row->id }})">Edit</flux:button>
                            </div>
                        @endif
                    </div>

                    @if($form->activeContext === 'brands' && $form->activeId == $row->id)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="text-sm font-medium mb-2">Edit Brand{{ $this->getSaveModeSuffix() }}</div>
                            @foreach($form['brands'] as $k => $v)
                                @if(!is_array($v))
                                    <label class="block mb-2">
                                        <span class="text-xs text-gray-600">{{ Str::headline($k) }}</span>
                                        <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.brands.{{ $k }}">
                                    </label>
                                @endif
                            @endforeach
                            <div class="flex items-center gap-2 mt-3">
                                <flux:button size="sm" wire:click="save">Save</flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($form->activeContext === 'brands' && $form->activeId === null)
                <div class="rounded-md border p-3 bg-gray-50">
                    <div class="flex items-center gap-2 text-sm mb-4">
                        <flux:icon name="chevron-down" variant="mini" class="text-gray-400" />
                        <span class="font-medium">New Brand</span>
                    </div>
                    @foreach($form['brands'] as $k => $v)
                        @if(!is_array($v))
                            <label class="block mb-2">
                                <span class="text-xs text-gray-600">{{ Str::headline($k) }}</span>
                                <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.brands.{{ $k }}">
                            </label>
                        @endif
                    @endforeach
                    <div class="flex items-center gap-2 mt-3">
                        <flux:button size="sm" wire:click="save">Save</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Rivers (Many) -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">Rivers</div>
                <flux:button size="xs" wire:click="add('rivers')">Add</flux:button>
            </div>
            @foreach($this->getRelationList('rivers') as $row)
                <div class="border rounded-md @if($form->activeContext === 'rivers' && $form->activeId == $row->id) bg-gray-50 p-3 @else px-3 py-2 @endif">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon name="{{ ($form->activeContext === 'rivers' && $form->activeId == $row->id) ? 'chevron-down' : 'chevron-right' }}" variant="mini" class="text-gray-400" />
                            <span>#{{ $row->id }} — {{ $row->name ?? '—' }}</span>
                        </div>
                        @if($form->activeContext !== 'rivers' || $form->activeId != $row->id)
                            <div class="flex items-center gap-2">
                                <flux:button size="xs" wire:click="edit('rivers', {{ $row->id }})">Edit</flux:button>
                            </div>
                        @endif
                    </div>

                    @if($form->activeContext === 'rivers' && $form->activeId == $row->id)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="text-sm font-medium mb-2">Edit River{{ $this->getSaveModeSuffix() }}</div>
                            @foreach($form['rivers'] as $k => $v)
                                @if(!is_array($v))
                                    <label class="block mb-2">
                                        <span class="text-xs text-gray-600">{{ Str::headline($k) }}</span>
                                        <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.rivers.{{ $k }}">
                                    </label>
                                @endif
                            @endforeach
                            <div class="flex items-center gap-2 mt-3">
                                <flux:button size="sm" wire:click="save">Save</flux:button>
                                <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($form->activeContext === 'rivers' && $form->activeId === null)
                <div class="rounded-md border p-3 bg-gray-50">
                    <div class="flex items-center gap-2 text-sm mb-4">
                        <flux:icon name="chevron-down" variant="mini" class="text-gray-400" />
                        <span class="font-medium">New River</span>
                    </div>
                    @foreach($form['rivers'] as $k => $v)
                        @if(!is_array($v))
                            <label class="block mb-2">
                                <span class="text-xs text-gray-600">{{ Str::headline($k) }}</span>
                                <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.rivers.{{ $k }}">
                            </label>
                        @endif
                    @endforeach
                    <div class="flex items-center gap-2 mt-3">
                        <flux:button size="sm" wire:click="save">Save</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Country (BelongsTo) -->
        <div class="space-y-2 border rounded-md p-3 @if($form->activeContext === 'country') bg-gray-50 @endif">
            <div class="flex items-center gap-2">
                <flux:icon name="{{ $form->activeContext === 'country' ? 'chevron-down' : 'chevron-right' }}" variant="mini" class="text-gray-400" />
                <div class="text-sm text-gray-600 font-medium">Country</div>
            </div>
            <div class="flex items-center gap-2">
                <select class="mt-1 w-full border rounded px-2 py-1"
                        wire:model.live="form.country_id"
                        @if($form->activeContext === 'country') disabled @endif>
                    <option value="">-- Select country --</option>
                    @foreach($this->allOptionsForRelation('country') as $opt)
                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                    @endforeach
                </select>
                @if(($form['country_id'] ?? null) && $form->activeContext !== 'country')
                    <flux:button.group size="sm">
                        <flux:button wire:click="edit('country', {{ $form['country_id'] }})">Edit</flux:button>
                        <flux:button variant="ghost" icon="eye" href="{{ route('countries.show', $form['country_id']) }}" />
                    </flux:button.group>
                @endif
                @if(!($form['country_id'] ?? null) && $form->activeContext !== 'country')
                    <flux:button size="sm" wire:click="edit('country', null)">Create New</flux:button>
                @endif
            </div>

            @if($form->activeContext === 'country')
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-sm font-medium mb-2">Edit Country{{ $this->getSaveModeSuffix() }}</div>
                    @foreach($form['country'] as $k => $v)
                        @if(!is_array($v))
                            <label class="block mb-2">
                                <span class="text-xs text-gray-600">{{ Str::headline($k) }}</span>
                                <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.country.{{ $k }}">
                            </label>
                        @endif
                    @endforeach
                    <div class="flex items-center gap-2 mt-3">
                        <flux:button size="sm" wire:click="save">Save</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="cancel">Cancel</flux:button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
