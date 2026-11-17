@extends('layouts.site')

@section('title', $agendaItem->title)

@php
    $startAt = $agendaItem->start_at->copy()->locale('id');
    $endAt = $agendaItem->end_at?->copy()->locale('id');
    $isPast = ($endAt ?? $startAt)->isPast();
@endphp

@section('content')
    {{-- Same arrangement as the competition detail page: poster left, details right. --}}
    <article class="mx-auto max-w-5xl px-4 py-8 md:px-6">
        <a href="{{ route('site.agenda.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">← Agenda</a>

        <div class="mt-4 grid gap-6 md:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] md:gap-8">
            <div>
                <x-site.cover :url="$agendaItem->coverUrl()" ratio="aspect-[4/5]" class="rounded-lg border border-slate-200" />
            </div>

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-navy-900 md:text-3xl">{{ $agendaItem->title }}</h1>

                <div class="mt-3">
                    @if ($isPast)
                        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">Sudah berlangsung</span>
                    @else
                        <span class="rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Akan datang</span>
                    @endif
                </div>

                <dl class="mt-5 grid gap-4 rounded-lg border border-slate-200 bg-white p-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-slate-500">Tanggal</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">
                            {{ $startAt->translatedFormat('l, j F Y') }}
                            @if ($endAt && ! $endAt->isSameDay($startAt))
                                s.d. {{ $endAt->translatedFormat('l, j F Y') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Jam</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">
                            {{ $startAt->translatedFormat('H.i') }}{{ $endAt ? '–'.$endAt->translatedFormat('H.i') : '' }} WIB
                        </dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500">Lokasi</dt>
                        <dd class="mt-0.5 font-medium text-slate-800">{{ $agendaItem->location ?: '—' }}</dd>
                    </div>
                </dl>

                @if ($isPast)
                    <p class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                        Kegiatan ini sudah lewat.
                        <a href="{{ route('site.agenda.index') }}" class="font-medium text-brand-700 hover:text-brand-600">Lihat agenda berikutnya</a>
                    </p>
                @endif
            </div>
        </div>

        @if ($agendaItem->description)
            <div class="mx-auto mt-10 max-w-3xl border-t border-slate-200 pt-8">
                <h2 class="text-lg font-bold text-navy-900">Detail kegiatan</h2>
                <x-site.article-body :text="$agendaItem->description" />
            </div>
        @endif
    </article>
@endsection
