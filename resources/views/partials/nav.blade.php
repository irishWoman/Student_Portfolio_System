{{-- Sidebar navigation. Each role sees only its own area. --}}
@php
    $user = auth()->user();
    $link = 'block px-4 py-2 text-sm hover:bg-navy-800 hover:text-white transition';
    $active = 'bg-navy-800 text-white border-l-2 border-amber-500';
@endphp

<nav class="py-4">
    @if ($user->hasRole('student'))
        <p class="px-4 pb-2 text-[11px] uppercase tracking-wider text-white/40">My portfolio</p>
        <a href="{{ route('portfolio.index') }}" class="{{ $link }} {{ request()->routeIs('portfolio.*') ? $active : '' }}">
            This academic year
        </a>
        @if ($portfolio ?? null)
            <a href="{{ route('export.pdf', $portfolio) }}" class="{{ $link }}">Download as PDF</a>
            <a href="{{ route('export.word', $portfolio) }}" class="{{ $link }}">Download as Word</a>
        @endif
    @endif

    @if ($user->hasAnyRole(['faculty', 'chair', 'admin']))
        <p class="px-4 pt-4 pb-2 text-[11px] uppercase tracking-wider text-white/40">Evaluation</p>
        <a href="{{ route('faculty.queue') }}" class="{{ $link }} {{ request()->routeIs('faculty.*') ? $active : '' }}">
            Review queue
        </a>
    @endif

    @if ($user->hasAnyRole(['chair', 'admin']))
        <p class="px-4 pt-4 pb-2 text-[11px] uppercase tracking-wider text-white/40">Program</p>
        <a href="{{ route('chair.dashboard') }}" class="{{ $link }} {{ request()->routeIs('chair.dashboard') ? $active : '' }}">
            PLO attainment
        </a>
        <a href="{{ route('chair.curriculum') }}" class="{{ $link }} {{ request()->routeIs('chair.curriculum') ? $active : '' }}">
            Curriculum mapping
        </a>
        <a href="{{ route('chair.cqi') }}" class="{{ $link }} {{ request()->routeIs('chair.cqi') ? $active : '' }}">
            Improvement actions
        </a>
        <a href="{{ route('admin.deadlines') }}" class="{{ $link }} {{ request()->routeIs('admin.*') ? $active : '' }}">
            Calendar and deadlines
        </a>
    @endif
</nav>
