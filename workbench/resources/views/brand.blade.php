@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        @livewire('brand-show-editor', ['brand' => $brand])
    </div>
@endsection
