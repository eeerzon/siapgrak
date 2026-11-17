@extends('layouts.judge')

@section('title', $round->name)

@php
    $sent = $sheets->filter(fn ($sheet) => ! $sheet->isDraft())->count();
    $percent = $participants->count() ? round($sent / $participants->count() * 100) : 0;
@endphp

@section('content')
    <a href="{{ route('juri.dashboard') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">← Babak saya</a>

    <div class="mt-3 rounded-xl bg-navy-900 p-4 text-white">
        <p class="text-xs font-semibold tracking-wide text-brand-500 uppercase">{{ $round->category->competition->title }}</p>
        <h1 class="mt-1 text-lg font-bold">{{ $round->category->name }} · {{ $round->name }}</h1>
        <div class="mt-3 h-2 overflow-hidden rounded-full bg-white/15">
            <div class="h-full rounded-full bg-emerald-400" style="width: {{ $percent }}%"></div>
        </div>
        <p class="mt-1.5 text-xs text-slate-300">{{ $sent }} dari {{ $participants->count() }} peserta sudah dikirim nilainya</p>
    </div>

    @unless ($ready)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Babak ini belum bisa dinilai.</p>
            <p class="mt-0.5">Panitia masih menyiapkan kriteria dan bobot penilaian. Coba lagi nanti.</p>
        </div>
    @endunless

    @if ($participants->isEmpty())
        <x-ui.empty-state title="Belum ada peserta." description="Peserta akan muncul di sini setelah panitia menambahkannya." class="mt-4" />
    @else
        <div class="mt-4 space-y-2">
            @foreach ($participants as $participant)
                @php($sheet = $sheets->get($participant->id))
                <a href="{{ route('juri.score.show', [$round, $participant]) }}"
                   class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-slate-300 hover:shadow-md">
                    <span class="flex h-12 min-w-16 shrink-0 items-center justify-center rounded-lg bg-navy-50 px-2 text-sm font-bold whitespace-nowrap text-navy-800">
                        {{ $participant->competition_number ?? '—' }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="line-clamp-2 block text-sm leading-snug font-semibold text-navy-900">{{ $participant->name }}</span>
                        @if ($participant->institution)
                            <span class="block truncate text-xs text-slate-500">{{ $participant->institution }}</span>
                        @endif
                    </span>
                    @include('judge.partials.sheet-chip', ['status' => $sheet?->status, 'scored' => $sheet?->scores_count ?? 0, 'total' => $round->criteria_count])
                </a>
            @endforeach
        </div>
    @endif
@endsection
