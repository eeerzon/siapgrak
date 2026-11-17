@extends('layouts.judge')

@section('title', $round->name)

@php
    $sent = $entries->filter(fn ($entry) => in_array($entry->sheet_status?->value, ['submitted', 'locked'], true))->count();
    $percent = $entries->count() ? round($sent / $entries->count() * 100) : 0;
@endphp

@section('content')
    <a href="{{ route('judge.dashboard') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">← Babak saya</a>

    <div class="mt-3 rounded-xl bg-navy-900 p-4 text-white">
        <p class="text-xs font-semibold tracking-wide text-brand-500 uppercase">{{ $round->category->event->name }}</p>
        <h1 class="mt-1 text-lg font-bold">{{ $round->category->name }} · {{ $round->name }}</h1>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/15">
            <div class="h-full rounded-full bg-emerald-400" style="width: {{ $percent }}%"></div>
        </div>
        <p class="mt-1.5 text-xs text-slate-300">{{ $sent }} dari {{ $entries->count() }} peserta sudah dikirim nilainya</p>
    </div>

    @if ($entries->isEmpty())
        <x-ui.empty-state title="Belum ada peserta." description="Peserta akan muncul di sini setelah panitia menambahkan nomor tampil." class="mt-4" />
    @else
        <div class="mt-4 space-y-2">
            @foreach ($entries as $entry)
                <a href="{{ route('judge.rounds.entries.show', [$round, $entry]) }}"
                   class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-slate-300 hover:shadow-md">
                    <span class="flex h-12 min-w-16 shrink-0 items-center justify-center rounded-lg bg-navy-50 px-2 text-sm font-bold whitespace-nowrap text-navy-800">
                        {{ $entry->competition_number ?? '—' }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="line-clamp-2 block text-sm font-semibold leading-snug text-navy-900">{{ $entry->team->name }}</span>
                        <span class="block text-xs text-slate-500">{{ $entry->status->label() }}</span>
                    </span>
                    @include('judge.partials.sheet-chip', ['status' => $entry->sheet_status, 'scored' => $entry->scored_count, 'total' => $entry->total_criteria])
                </a>
            @endforeach
        </div>
    @endif
@endsection
