@extends('layouts.guest')
@section('title', 'Sign in')

@section('content')
    <div class="text-center text-white mb-6">
        <p class="text-sm text-white/70">{{ config('app.institution.name') }}</p>
        <h1 class="text-xl font-semibold mt-1">CpE Student Development Portfolio</h1>
        <p class="text-sm text-white/60 mt-1">Program Learning Outcome and competency record</p>
    </div>

    <div class="card">
        <div class="h-1 bg-amber-500"></div>
        <form method="POST" action="{{ route('login') }}" class="p-6 space-y-4">
            @csrf

            <div>
                <label for="email" class="field-label">School email</label>
                <input id="email" name="email" type="email" required autofocus
                       autocomplete="username" value="{{ old('email') }}" class="input">
            </div>

            <div>
                <label for="password" class="field-label">Password</label>
                <input id="password" name="password" type="password" required
                       autocomplete="current-password" class="input">
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="border-slate-300 text-navy-800 focus:ring-navy-600">
                Keep me signed in
            </label>

            @error('email')
                <p class="text-sm text-status-risk">{{ $message }}</p>
            @enderror

            <button type="submit" class="btn-primary w-full justify-center">Sign in</button>
        </form>
    </div>

    <p class="text-center text-xs text-white/50 mt-4">
        Accounts are issued by the department office.
    </p>
@endsection
