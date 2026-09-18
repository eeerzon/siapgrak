@if (session('success'))
    <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
        {{ session('success') }}
    </div>
@endif

@if (session('status'))
    <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
        {{ session('status') }}
    </div>
@endif

@if (session('info'))
    <div class="mb-4 rounded-md bg-sky-50 px-4 py-3 text-sm text-sky-800" role="status">
        {{ session('info') }}
    </div>
@endif

@if (session('error'))
    <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
        <ul class="list-inside list-disc space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
