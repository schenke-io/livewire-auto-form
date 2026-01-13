@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        <h1 class="text-2xl font-semibold mb-4">Brands</h1>
        @if ($brands->count())
            <flux:navlist>
                @foreach ($brands as $brand)
                    <flux:navlist.item href="{{ route('brands.show', $brand) }}">
                        <span class="font-semibold">{{ $brand->name }}</span>
                        @php($parts = [])
                        @php($parts[] = 'Group: ' . $brand->group->value)
                        @if ($brand->city)
                            @php($parts[] = 'City: ' . $brand->city->name)
                            @if ($brand->city->country)
                                @php($parts[] = 'Country: ' . $brand->city->country->name)
                            @endif
                        @endif
                        <span class="opacity-70"> — {{ implode(' · ', $parts) }}</span>
                    </flux:navlist.item>
                @endforeach
            </flux:navlist>
            <div class="mt-4">{{ $brands->links() }}</div>
        @else
            <p class="text-gray-600">No brands found.</p>
        @endif
    </div>
@endsection
