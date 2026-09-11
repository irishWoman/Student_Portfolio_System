@extends('layouts.app')
@section('title', $definition['title'])

@section('content')
    <a href="{{ route('portfolio.index') }}" class="text-sm text-navy-700 underline underline-offset-2">Back to portfolio</a>

    <div class="card mt-3 mb-5">
        <div class="h-1 bg-amber-500"></div>
        <div class="p-5">
            <p class="text-xs font-semibold text-navy-700">Section {{ $number }}</p>
            <h1 class="text-lg font-semibold text-navy-800">{{ $definition['title'] }}</h1>
            <p class="text-sm text-slate-600 mt-1">{{ $definition['blurb'] }}</p>

            @if ($deadline)
                <p class="text-sm mt-3 {{ $deadline->isOverdueFor($portfolio->student) ? 'text-status-risk' : 'text-slate-600' }}">
                    Due {{ $deadline->dueFor($portfolio->student)->format('F j, Y \a\t g:i a') }}
                    @if ($deadline->isOverdueFor($portfolio->student))
                        · past due, submissions are flagged late
                    @endif
                </p>
            @endif

            @unless ($canEdit)
                <p class="mt-3 text-sm bg-cream-100 border-l-4 border-status-locked px-3 py-2">
                    This section is read-only: it is either under review or past a locking deadline.
                </p>
            @endunless
        </div>
    </div>

    {{--
        Sections are rendered either by the generic field-driven form or by the
        dedicated component named in config/portfolio.php. Adding a section is a
        config change, not a change here.
    --}}
    @if (($definition['editor'] ?? 'fields') === 'fields')
        @livewire('portfolio.section-form', ['entry' => $entry, 'canEdit' => $canEdit])
    @else
        @livewire($definition['component'], ['portfolio' => $portfolio, 'canEdit' => $canEdit])
    @endif

    {{-- Evidence attached to this portfolio, uploadable from any section --}}
    @include('portfolio.partials.evidence', ['portfolio' => $portfolio, 'canEdit' => $canEdit])
@endsection
