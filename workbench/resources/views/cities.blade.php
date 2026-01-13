@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        <h1 class="text-2xl font-semibold mb-4">Cities</h1>
        @if ($cities->count())
            <flux:navlist>
                @foreach ($cities as $city)
                    <flux:navlist.item href="{{ route('cities.show', $city) }}">
                        <span class="font-semibold">{{ $city->name }}</span>
                        @php($parts = [])
                        @php($parts[] = 'Population: ' . number_format((int) $city->population))
                        @if ($city->country)
                            @php($parts[] = 'Country: ' . $city->country->name)
                        @endif
                        @if ($city->relationLoaded('rivers') && $city->rivers->count())
                            @php($parts[] = 'Rivers: ' . $city->rivers->pluck('name')->implode(', '))
                        @endif
                        <span class="opacity-70"> — {{ implode(' · ', $parts) }}</span>
                    </flux:navlist.item>
                @endforeach
            </flux:navlist>
            <div class="mt-4">{{ $cities->links() }}</div>
        @else
            <p class="text-gray-600">No cities found.</p>
        @endif
    </div>
@endsection
