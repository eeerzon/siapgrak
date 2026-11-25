@extends('layouts.site')

@section('title', 'Info Lomba & Kejuaraan')

@php
    $filterQuery = request()->only(['q', 'jenjang', 'provinsi', 'buka']);
@endphp

@section('content')
    <x-site.page-intro title="Info Lomba & Kejuaraan" description="Lomba PBB, LKBB, dan kejuaraan Paskibra dari berbagai daerah. Pakai filter jenjang dan provinsi biar lebih cepat ketemu.">
        <x-site.filter-tabs :tabs="[
            ['Akan datang', route('site.competitions.index', $filterQuery), ! $showPast],
            ['Sudah lewat', route('site.competitions.index', ['arsip' => 1] + $filterQuery), $showPast],
        ]" />

        {{-- Filters apply on change (selects, checkbox) or after typing stops (search); see script below. --}}
        <form method="GET" action="{{ route('site.competitions.index') }}" data-auto-filter
              class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto]">
            @if ($showPast)
                <input type="hidden" name="arsip" value="1">
            @endif
            <div class="sm:col-span-2 lg:col-span-1">
                @include('site.partials.search-field', ['value' => $filters['q'], 'placeholder' => 'Cari lomba, penyelenggara, atau kota'])
            </div>
            <x-ui.combobox name="jenjang" sr-label="Jenjang" :searchable="false"
                :value="$filters['level']?->value"
                :options="['' => 'Semua jenjang'] + collect(App\Enums\CompetitionLevel::cases())->mapWithKeys(fn ($level) => [$level->value => $level->label()])->all()" />
            <x-ui.combobox name="provinsi" sr-label="Provinsi" search-placeholder="Cari provinsi..."
                :value="$filters['province']"
                :options="['' => 'Semua provinsi'] + collect($provinces)->mapWithKeys(fn ($province) => [$province => $province])->all()" />
            <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-1">
                @unless ($showPast)
                    <label class="flex cursor-pointer items-center gap-2 text-sm whitespace-nowrap text-slate-600">
                        <input type="checkbox" name="buka" value="1" @checked($filters['open']) class="h-4 w-4 rounded border-slate-300">
                        Pendaftaran dibuka
                    </label>
                @endunless
                <noscript>
                    <button type="submit" class="cursor-pointer rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Cari</button>
                </noscript>
                @if ($isFiltered)
                    <a href="{{ route('site.competitions.index', $showPast ? ['arsip' => 1] : []) }}"
                       class="inline-flex items-center gap-1.5 rounded-full border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium whitespace-nowrap text-slate-600 hover:border-slate-400 hover:bg-slate-50 hover:text-slate-800">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                        Reset filter
                    </a>
                @endif
            </div>
        </form>

        @include('site.partials.auto-filter-script')
    </x-site.page-intro>

    <div class="mx-auto max-w-6xl px-4 py-8 md:px-6">
        @if ($competitions->isEmpty())
            @if ($isFiltered)
                <x-ui.empty-state title="Lomba yang kamu cari belum ada." description="Coba kata kunci lain, atau longgarkan filter jenjang dan provinsinya." />
            @else
                <x-ui.empty-state :title="$showPast ? 'Belum ada arsip lomba.' : 'Belum ada lomba yang akan datang.'" />
            @endif
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($competitions as $competition)
                    @include('site.partials.competition-card')
                @endforeach
            </div>
            <div class="mt-6">{{ $competitions->links('partials.pagination') }}</div>
        @endif
    </div>
@endsection
