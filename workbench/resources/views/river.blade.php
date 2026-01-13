@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        @livewire('river-show-editor', ['river' => $river])
    </div>
@endsection
