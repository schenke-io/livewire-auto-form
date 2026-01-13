@extends('layouts.app')

@section('content')
    <div class="prose max-w-none">
        @livewire('language-show-editor', ['language' => $language])
    </div>
@endsection
