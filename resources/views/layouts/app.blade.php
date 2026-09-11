{{--
    Main application shell.

    Structure mirrors the university portal students already use: fixed navy
    header with a gold rule beneath it, a dark sidebar rail for navigation
    (collapsible, remembered per browser), and the working area on a light
    background. Familiarity is the point; nobody should need training to find
    their portfolio.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portfolio') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col"
      x-data="{ sidebarOpen: JSON.parse(localStorage.getItem('portfolio-sidebar-open') ?? 'true') }"
      x-init="$watch('sidebarOpen', value => localStorage.setItem('portfolio-sidebar-open', JSON.stringify(value)))">

<header class="bg-navy-800 text-white shrink-0">
    <div class="flex items-center justify-between px-4 h-14">
        <div class="flex items-center gap-3 min-w-0">
            @auth
                <button type="button" @click="sidebarOpen = !sidebarOpen"
                        class="hidden md:inline-flex p-1.5 -ml-1.5 rounded hover:bg-white/10 transition shrink-0"
                        :aria-label="sidebarOpen ? 'Hide navigation' : 'Show navigation'">
                    <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                        <path d="M3 5h14M3 10h14M3 15h14"/>
                    </svg>
                </button>
            @endauth

            <a href="{{ auth()->check() ? route(auth()->user()->homeRoute()) : url('/') }}"
               class="flex items-center gap-3 min-w-0 shrink-0" title="{{ config('app.name') }}">
                <img src="{{ asset('images/logo-uslt.png') }}" alt="{{ config('app.institution.name') }} seal"
                     class="h-9 w-9 object-contain shrink-0">
                <span class="hidden sm:flex flex-col leading-tight min-w-0">
                    <span class="font-semibold tracking-wide truncate">{{ config('app.institution.name') }}</span>
                    <span class="text-white/60 text-xs truncate">CpE Student Development Portfolio</span>
                </span>
            </a>
        </div>

        <div class="flex items-center gap-4 text-sm shrink-0">
            @auth
                @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
                @if ($unread)
                    <span class="pill bg-amber-500 text-navy-900 ring-amber-600">{{ $unread }} reminder{{ $unread === 1 ? '' : 's' }}</span>
                @endif
                <span class="hidden sm:inline text-white/80">{{ auth()->user()->displayName() }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign out" aria-label="Sign out"
                            class="p-1.5 rounded text-white/80 hover:text-white hover:bg-white/10 transition">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M7 4H4.5A1.5 1.5 0 0 0 3 5.5v9A1.5 1.5 0 0 0 4.5 16H7"/>
                            <path d="M12.5 13.5 16 10l-3.5-3.5"/>
                            <path d="M16 10H7.5"/>
                        </svg>
                    </button>
                </form>
            @endauth
        </div>
    </div>
    <div class="h-1 bg-amber-500"></div>
</header>

<div class="flex flex-1">
    @auth
        <aside x-show="sidebarOpen" x-cloak
               class="w-56 shrink-0 bg-navy-900 text-white/80 hidden md:flex md:flex-col md:justify-between">
            @include('partials.nav')

            <div class="flex items-center gap-2 px-4 py-4 border-t border-white/10">
                <img src="{{ asset('images/logo-icpep.png') }}" alt="ICPEP.se logo" class="h-8 w-8 object-contain shrink-0">
                <span class="text-xs leading-tight text-white/50">
                    Institute of Computer Engineers of the Philippines,<br>Student Edition
                </span>
            </div>
        </aside>
    @endauth

    <main class="flex-1 min-w-0 p-4 sm:p-6 max-w-[1400px] mx-auto">
        @include('partials.flash')
        @yield('content')
    </main>
</div>

<footer class="shrink-0 border-t border-slate-200 bg-white px-4 py-3 text-center text-xs text-slate-500">
    &copy; {{ now()->year }} {{ config('app.institution.name') }} &middot; Made by ICPEP.se
</footer>

@livewireScripts
</body>
</html>
