@extends('layouts.guest')
@section('title', 'Industry supervisor evaluation')

@section('content')
<div class="w-full max-w-3xl mx-auto">
    <div class="text-center text-white mb-5">
        <p class="text-sm text-white/70">{{ config('app.institution.name') }}</p>
        <h1 class="text-xl font-semibold mt-1">Industry supervisor evaluation</h1>
    </div>

    <div class="card">
        <div class="h-1 bg-amber-500"></div>
        <div class="p-5 border-b border-slate-200">
            <p class="text-sm text-slate-600">
                You are evaluating <span class="font-medium text-slate-800">{{ $student->fullName() }}</span>,
                a Computer Engineering student who completed on-the-job training at your organization.
            </p>
            <p class="text-xs text-slate-500 mt-2">
                Rate each area from 1 to 4, where 1 is needs substantial guidance and 4 is performs at a professional standard.
                The form takes about three minutes and can be submitted once.
            </p>
        </div>

        <form method="POST" class="p-5 space-y-5">
            @csrf

            <table class="data-table">
                <thead><tr><th>Area</th><th class="w-56">Rating</th></tr></thead>
                <tbody>
                    @foreach ($criteria as $key => $label)
                        <tr>
                            <td class="font-medium">{{ $label }}</td>
                            <td>
                                <div class="flex gap-4">
                                    @for ($i = 1; $i <= 4; $i++)
                                        <label class="text-sm flex items-center gap-1">
                                            <input type="radio" name="{{ $key }}" value="{{ $i }}" required
                                                   class="border-slate-300 text-navy-800 focus:ring-navy-600">
                                            {{ $i }}
                                        </label>
                                    @endfor
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div>
                <label class="field-label">What did this student do well?</label>
                <textarea name="strengths" rows="3" class="textarea"></textarea>
            </div>

            <div>
                <label class="field-label">What should they work on?</label>
                <textarea name="areas_for_improvement" rows="3" class="textarea"></textarea>
            </div>

            <div>
                <label class="field-label">Your name and position</label>
                <input name="signed_by" class="input" required>
            </div>

            <button class="btn-primary w-full justify-center">Submit evaluation</button>
        </form>
    </div>
</div>
@endsection
