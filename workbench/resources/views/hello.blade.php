@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        <h1 class="text-2xl font-semibold mb-4">hello word</h1>
        {{-- You can remove this paragraph later; it just shows how to use passed data. --}}
        @isset($message)
            <p class="text-gray-600">{{ $message }}</p>
        @endisset
    </div>
@endsection
