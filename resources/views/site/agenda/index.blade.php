@extends('layouts.site')

@section('title', 'Agenda & Kegiatan')

@php($searchQuery = array_filter(['q' => $search]))

@section('content')
    <x-site.page-intro title="Agenda & Kegiatan" description="Technical meeting, latihan gabungan, workshop juri, dan kegiatan komunitas lainnya.">
        <x-site.filter-tabs :tabs="[
            ['Akan datang', route('site.agenda.index', $searchQuery), ! $showPast],
            ['Sudah lewat', route('site.agenda.index', ['arsip' => 1] + $searchQuery), $showPast],
        ]" />

        <form method="GET" action="{{ route('site.agenda.index') }}" data-auto-filter class="mt-4 max-w-xl" role="search">
            @if ($showPast)
                <input type="hidden" name="arsip" value="1">
            @endif
            @include('site.partials.search-field', ['value' => $search, 'placeholder' => 'Cari nama kegiatan atau lokasi'])
            <noscript><button type="submit" class="mt-2 rounded-md bg-navy-900 px-4 py-2 text-sm font-medium text-white">Cari</button></noscript>
        </form>
        @include('site.partials.auto-filter-script')
    </x-site.page-intro>

    <div class="mx-auto max-w-6xl px-4 py-8 md:px-6">
        @if ($agendaItems->isEmpty())
            @if ($search)
                <x-ui.empty-state title="Tidak ada kegiatan dengan kata kunci “{{ $search }}”." description="Coba kata kunci lain, atau cek tab sebelah." />
            @else
                <x-ui.empty-state :title="$showPast ? 'Belum ada kegiatan yang sudah lewat.' : 'Belum ada agenda dalam waktu dekat.'" />
            @endif
        @else
            {{-- Infinite scroll, no pagination links: the script appends the next 8 rows when the visitor scrolls. --}}
            <div class="grid gap-3 md:grid-cols-2" data-infinite-list data-next="{{ $agendaItems->nextPageUrl() }}">
                @foreach ($agendaItems as $agendaItem)
                    @include('site.partials.agenda-row')
                @endforeach
            </div>
            <p class="mt-6 hidden text-center text-sm text-slate-500" data-infinite-status></p>
            @include('site.partials.infinite-scroll-script')
        @endif
    </div>
@endsection
