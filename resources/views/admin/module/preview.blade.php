@extends('layouts.public')

@section('title', 'Прев\'ю модуля: '.$module->name)
@section('robots', 'noindex, nofollow')

@section('content')
    <main class="page-module-preview">
        {!! $module->draw() !!}
    </main>
@endsection
