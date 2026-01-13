@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        <h1 class="text-2xl font-semibold mb-4">Rivers</h1>
        @if ($rivers->count())
            <flux:navlist>
                @foreach ($rivers as $river)
                    @php($rc = ($countries[$river->id] ?? collect()))
                    @php($tc = ($topCities[$river->id] ?? collect()))
                    <flux:navlist.item href="{{ route('rivers.show', $river) }}">
                        <span class="font-semibold">{{ $river->name }}</span>
                        @php($parts = [])
                        @php($parts[] = 'Length: ' . number_format((int) $river->length_km) . ' km')
                        @if ($rc->count())
                            @php($parts[] = 'Countries: ' . $rc->pluck('name')->implode(', '))
                        @endif
                        @if ($tc->count())
                            @php($parts[] = 'Top cities: ' . $tc->pluck('name')->implode(', '))
                        @endif
                        <span class="opacity-70"> — {{ implode(' · ', $parts) }}</span>
                    </flux:navlist.item>
                @endforeach
            </flux:navlist>
            <div class="mt-4">{{ $rivers->links() }}</div>
        @else
            <p class="text-gray-600">No rivers found.</p>
        @endif
    </div>
@endsection
