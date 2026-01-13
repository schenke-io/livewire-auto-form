<div class="bg-white rounded-md shadow p-4 space-y-6">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold">Edit Brand{{ $this->getSaveModeSuffix() }}</h3>
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
            <span class="text-sm text-gray-600">Group</span>
            <select class="mt-1 w-full border rounded px-2 py-1" wire:model="form.group">
                <option value="">-- Select group --</option>
                @foreach($this->enumOptionsFor('group') as $opt)
                    <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <!-- Relations -->
    <div class="pt-2 border-t border-gray-200">
        <div class="flex items-center justify-between mb-2">
            <h4 class="font-medium">Relations</h4>
        </div>

        <!-- City (BelongsTo) -->
        <div class="space-y-2 border rounded-md p-3 @if($form->activeContext === 'city') bg-gray-50 @endif">
            <div class="flex items-center gap-2">
                <flux:icon name="{{ $form->activeContext === 'city' ? 'chevron-down' : 'chevron-right' }}" variant="mini" class="text-gray-400" />
                <div class="text-sm text-gray-600 font-medium">City</div>
            </div>
            <div class="flex items-center gap-2">
                <select class="mt-1 w-full border rounded px-2 py-1"
                        wire:model.live="form.city_id"
                        @if($form->activeContext === 'city') disabled @endif>
                    <option value="">-- Select city --</option>
                    @foreach($this->allOptionsForRelation('city') as $opt)
                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                    @endforeach
                </select>
                @if(($form['city_id'] ?? null) && $form->activeContext !== 'city')
                    <flux:button.group size="sm">
                        <flux:button wire:click="edit('city', {{ $form['city_id'] }})">Edit</flux:button>
                        <flux:button variant="ghost" icon="eye" href="{{ route('cities.show', $form['city_id']) }}" />
                    </flux:button.group>
                @endif
                @if(!($form['city_id'] ?? null) && $form->activeContext !== 'city')
                    <flux:button size="sm" wire:click="edit('city', null)">Create New</flux:button>
                @endif
            </div>

            @if($form->activeContext === 'city')
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <div class="text-sm font-medium mb-2">Edit City{{ $this->getSaveModeSuffix() }}</div>
                    @foreach($form['city'] as $k => $v)
                        @if(!is_array($v))
                            <label class="block mb-2">
                                <span class="text-xs text-gray-600">{{ Str::headline($k) }}</span>
                                <input type="text" class="mt-1 w-full border rounded px-2 py-1" wire:model="form.city.{{ $k }}">
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
