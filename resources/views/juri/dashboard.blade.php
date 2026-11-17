@extends('layouts.judge')

@section('title', 'Penilaian Saya')

@section('content')
    <h1 class="text-xl font-bold text-navy-900">Halo, {{ $judge->name }}</h1>
    <p class="mt-1 text-sm text-slate-500">Pilih babak yang mau dinilai.</p>

    @if ($rounds->isEmpty())
        <x-ui.empty-state title="Belum ada penugasan." description="Hubungi panitia kalau Anda seharusnya sudah ditugaskan menilai." class="mt-4" />
    @else
        <div class="mt-5 space-y-3">
            @foreach ($rounds as $round)
                @php($percent = $round->participants_count ? round($round->sent_count / $round->participants_count * 100) : 0)
                <a href="{{ route('juri.round', $round) }}"
                   class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow-md">
                    <p class="text-xs font-semibold tracking-wide text-brand-700 uppercase">{{ $round->category->competition->title }}</p>
                    <div class="mt-1 flex items-center justify-between gap-3">
                        <p class="font-semibold text-navy-900">{{ $round->category->name }} · {{ $round->name }}</p>
                        <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7" /></svg>
                    </div>
                    @unless ($round->ready)
                        <p class="mt-2 rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-800">Belum bisa dinilai: panitia masih menyiapkan kriteria.</p>
                    @endunless
                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $percent }}%"></div>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">{{ $round->sent_count }} dari {{ $round->participants_count }} peserta sudah dikirim nilainya</p>
                </a>
            @endforeach
        </div>
    @endif
@endsection
