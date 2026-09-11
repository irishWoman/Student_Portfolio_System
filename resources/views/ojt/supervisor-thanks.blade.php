@extends('layouts.guest')
@section('title', 'Thank you')

@section('content')
    <div class="card">
        <div class="h-1 bg-amber-500"></div>
        <div class="p-8 text-center">
            <h1 class="text-lg font-semibold text-navy-800">Evaluation received</h1>
            <p class="text-sm text-slate-600 mt-2">
                Thank you. Your assessment of {{ $student->first_name }} has been recorded and forms part of their
                Computer Engineering competency portfolio.
            </p>
            <p class="text-xs text-slate-400 mt-4">You can close this window.</p>
        </div>
    </div>
@endsection
