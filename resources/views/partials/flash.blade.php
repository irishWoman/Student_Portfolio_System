{{-- Status and error banners. Errors say what to fix, not that something went wrong. --}}
@if (session('status'))
    <div class="mb-4 border-l-4 border-status-ontrack bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 border-l-4 border-status-risk bg-red-50 px-4 py-3 text-sm text-red-900">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
