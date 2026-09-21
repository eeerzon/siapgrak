@extends('layouts.judge')

@section('title', 'Penilaian Saya')

@section('content')
    <h1 class="text-lg font-semibold text-slate-800">Round yang Anda Nilai</h1>

    @if ($rounds->isEmpty())
        <x-ui.empty-state title="Belum ada penugasan." description="Hubungi panitia kalau Anda seharusnya sudah ditugaskan menilai." class="mt-4" />
    @else
        <div class="mt-4 space-y-3">
            @foreach ($rounds as $round)
                <a href="{{ route('judge.rounds.entries.index', $round) }}"
                   class="block rounded-lg border border-slate-200 bg-white p-4 hover:border-slate-300">
                    <div class="text-sm font-medium text-slate-800">{{ $round->category->event->name }}</div>
                    <div class="mt-0.5 text-xs text-slate-500">{{ $round->category->name }} · {{ $round->name }}</div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
