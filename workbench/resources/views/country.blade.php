@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        @livewire('country-show-editor', ['country' => $country])
    </div>
@endsection
