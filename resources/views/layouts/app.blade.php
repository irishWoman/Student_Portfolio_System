{{--
    Main application shell.

    Structure mirrors the university portal students already use: fixed navy
    header with a gold rule beneath it, a dark sidebar rail for navigation, and
    the working area on a light background. Familiarity is the point; nobody
    should need training to find their portfolio.
--}}
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portfolio') · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full">

<header class="bg-navy-800 text-white">
    <div class="flex items-center justify-between px-4 h-14">
        <div class="flex items-center gap-3">
            <span class="font-semibold tracking-wide">{{ config('app.institution.name') }}</span>
            <span class="hidden sm:inline text-white/60 text-sm">CpE Student Development Portfolio</span>
        </div>

        <div class="flex items-center gap-4 text-sm">
            @auth
                @php $unread = auth()->user()->unreadNotifications()->count(); @endphp
                @if ($unread)
                    <span class="pill bg-amber-500 text-navy-900 ring-amber-600">{{ $unread }} reminder{{ $unread === 1 ? '' : 's' }}</span>
                @endif
                <span class="hidden sm:inline text-white/80">{{ auth()->user()->displayName() }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="text-white/80 hover:text-white underline underline-offset-4">Sign out</button>
                </form>
            @endauth
        </div>
    </div>
    <div class="h-1 bg-amber-500"></div>
</header>

<div class="flex min-h-[calc(100%-3.75rem)]">
    @auth
        <aside class="w-56 shrink-0 bg-navy-900 text-white/80 hidden md:block">
            @include('partials.nav')
        </aside>
    @endauth

    <main class="flex-1 p-4 sm:p-6 max-w-[1400px]">
        @include('partials.flash')
        @yield('content')
    </main>
</div>

@livewireScripts
</body>
</html>
