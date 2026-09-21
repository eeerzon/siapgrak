@extends('layouts.judge')

@section('title', $round->name)

@section('content')
    <a href="{{ route('judge.dashboard') }}" class="text-xs text-slate-500 hover:underline">&larr; Round saya</a>

    <h1 class="mt-1 text-lg font-semibold text-slate-800">{{ $round->category->event->name }}</h1>
    <p class="text-sm text-slate-500">{{ $round->category->name }} · {{ $round->name }}</p>

    @if ($entries->isEmpty())
        <x-ui.empty-state title="Belum ada peserta." description="Peserta akan muncul di sini setelah panitia menambahkan competition entry." class="mt-4" />
    @else
        <div class="mt-4 space-y-2">
            @foreach ($entries as $entry)
                <a href="{{ route('judge.rounds.entries.show', [$round, $entry]) }}"
                   class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white p-4 hover:border-slate-300">
                    <div>
                        <div class="text-sm font-medium text-slate-800">
                            {{ $entry->competition_number ?? '—' }} · {{ $entry->team->name }}
                        </div>
                        <div class="text-xs text-slate-400">{{ $entry->status->value }}</div>
                    </div>
                    <div class="text-xs font-medium {{ $entry->scored_count >= $entry->total_criteria && $entry->total_criteria > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $entry->scored_count }}/{{ $entry->total_criteria }}
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
