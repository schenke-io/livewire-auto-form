@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        <h1 class="text-2xl font-semibold mb-4">Languages</h1>
        <div class="bg-white rounded-md shadow divide-y">
            @foreach ($languages as $language)
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <a href="{{ route('languages.show', $language) }}" class="text-indigo-600 hover:text-indigo-800">{{ $language->code }}</a>
                        <span class="text-gray-500 text-sm">— {{ $language->name }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-2">{{ $languages->withQueryString()->links() }}</div>
    </div>
@endsection
