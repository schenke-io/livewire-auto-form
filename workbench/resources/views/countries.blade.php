@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        <h1 class="text-2xl font-semibold mb-4">Countries</h1>
        @if ($countries->count())
            <flux:navlist>
                @foreach ($countries as $country)
                    @php($tc = $topCities[$country->id] ?? collect())
                    @php($tr = $topRivers[$country->id] ?? collect())
                    @php($parts = [])
                    @php($parts[] = 'Code: ' . $country->code)
                    @if ($tc->count())
                        @php($parts[] = 'Top cities: ' . $tc->pluck('name')->implode(', '))
                    @endif
                    @if ($tr->count())
                        @php($parts[] = 'Top rivers: ' . $tr->pluck('name')->implode(', '))
                    @endif
                    <flux:navlist.item href="{{ route('countries.show', $country) }}">
                        <span class="font-semibold">{{ $country->name }}</span>
                        <span class="opacity-70"> — {{ implode(' · ', $parts) }}</span>
                    </flux:navlist.item>
                @endforeach
            </flux:navlist>
            <div class="mt-4">{{ $countries->links() }}</div>
        @else
            <p class="text-gray-600">No countries found.</p>
        @endif
    </div>
@endsection
