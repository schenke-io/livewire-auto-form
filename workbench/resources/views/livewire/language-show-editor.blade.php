<div class="bg-white rounded-md shadow p-4 space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">Edit Language{{ $this->getSaveModeSuffix() }}</h3>
        <flux:button size="sm"  icon="check" wire:click="save">
            Save
        </flux:button>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <label class="block">
            <span class="text-sm text-gray-600">Code</span>
            <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.code">
        </label>
        <label class="block">
            <span class="text-sm text-gray-600">Name</span>
            <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.name">
        </label>
    </div>

    <!-- Relations -->
    <div class="pt-2 border-t border-gray-200 space-y-6">
        <div class="flex items-center justify-between">
            <h4 class="font-medium">Relations</h4>
        </div>

        <!-- Countries (Many) -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">Countries</div>
                <flux:button size="xs" wire:click="add('countries')">Add</flux:button>
            </div>
            @foreach($this->getRelationList('countries') as $row)
                <div class="border rounded-md @if($form->activeContext === 'countries' && $form->activeId == $row->id) bg-gray-50 p-3 @else px-3 py-2 @endif">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 text-sm">
                            <flux:icon name="{{ ($form->activeContext === 'countries' && $form->activeId == $row->id) ? 'chevron-down' : 'chevron-right' }}" variant="mini" class="text-gray-400" />
                            <span>#{{ $row->id }} — {{ $row->name ?? '—' }}</span>
                        </div>
                        @if($form->activeContext !== 'countries' || $form->activeId != $row->id)
                            <div class="flex items-center gap-2">
                                <flux:button size="xs" wire:click="edit('countries', {{ $row->id }})">Edit</flux:button>
                            </div>
                        @endif
                    </div>

                    @if($form->activeContext === 'countries' && $form->activeId == $row->id)
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="text-sm font-medium mb-2">Edit Country{{ $this->getSaveModeSuffix() }}</div>
                            @foreach($form['countries'] as $k => $v)
                                @if(!is_array($v))
                                    <label class="block mb-2">
                                        <span class="text-xs text-gray-600">{{ Str::headline($k) }}</span>
                                        <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.countries.{{ $k }}">
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

            @if($form->activeContext === 'countries' && $form->activeId === null)
                <div class="rounded-md border p-3 bg-gray-50">
                    <div class="flex items-center gap-2 text-sm mb-4">
                        <flux:icon name="chevron-down" variant="mini" class="text-gray-400" />
                        <span class="font-medium">New Country</span>
                    </div>
                    @foreach($form['countries'] as $k => $v)
                        @if(!is_array($v))
                            <label class="block mb-2">
                                <span class="text-xs text-gray-600">{{ Str::headline($k) }}</span>
                                <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.countries.{{ $k }}">
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
