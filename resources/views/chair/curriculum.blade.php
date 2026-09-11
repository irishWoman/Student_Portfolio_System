@extends('layouts.app')
@section('title', 'Curriculum mapping')

@section('content')
    <h1 class="text-lg font-semibold text-navy-800 mb-1">Curriculum mapping</h1>
    <p class="text-sm text-slate-600 mb-5">
        Course learning outcomes and the program outcomes they develop. Attainment figures are only as
        good as this mapping, so it is worth keeping current when a syllabus changes.
    </p>

    @livewire('chair.curriculum-mapping')
@endsection
