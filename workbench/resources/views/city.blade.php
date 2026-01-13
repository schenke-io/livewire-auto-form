@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        @livewire('city-show-editor', ['city' => $city])
    </div>
@endsection
