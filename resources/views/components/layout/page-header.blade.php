{{--
    Official header pattern for every F2-F12 page — title + optional description
    on the left, optional actions on the right. Do not hand-roll page headers.
    Usage:
        <x-layout.page-header title="Teams" description="Kelola tim yang digunakan dalam kompetisi.">
            <x-slot:actions>
                <x-ui.button href="{{ route('dashboard.teams.create') }}">+ Buat Team</x-ui.button>
            </x-slot:actions>
        </x-layout.page-header>
--}}
@props(['title', 'description' => null])

<div class="mb-6 flex flex-wrap items-start justify-between gap-3">
    <div class="min-w-0">
        <h1 class="text-lg font-semibold text-slate-800">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
