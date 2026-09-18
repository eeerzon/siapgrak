@extends('layouts.dashboard')

@section('title', $event->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Events', 'href' => route('dashboard.events.index')],
        ['label' => $event->name],
    ]" />

    <x-layout.page-header :title="$event->name">
        <x-slot:actions>
            @can('update', $event)
                <x-ui.button variant="secondary" href="{{ route('dashboard.events.edit', $event) }}">Edit Event</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mb-5 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
        <span>{{ $event->organization?->name ?? '—' }}</span>
        <span class="text-slate-300">·</span>
        <x-ui.status-badge :status="$event->status" />
        <span class="text-slate-300">·</span>
        <span>@include('dashboard.events.partials.date-range', ['start' => $event->start_date, 'end' => $event->end_date])</span>
        <span class="text-slate-300">·</span>
        <span>{{ $event->venue_name ?: 'Venue belum ditentukan' }}</span>
    </div>

    @can('updateStatus', $event)
        <x-ui.card class="mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-medium text-slate-700">Ubah status:</span>
                @if (count($allowedTransitions) === 0)
                    <span class="text-sm text-slate-400">Status <strong>{{ \Illuminate\Support\Str::headline($event->status->value) }}</strong> tidak punya transisi berikutnya.</span>
                @else
                    <form method="POST" action="{{ route('dashboard.events.status', $event) }}" class="flex flex-wrap items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="status" required
                                class="rounded-md border border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            @foreach ($allowedTransitions as $target)
                                <option value="{{ $target->value }}">{{ \Illuminate\Support\Str::headline($target->value) }}</option>
                            @endforeach
                        </select>
                        <x-ui.button type="submit" size="sm" variant="secondary">Terapkan</x-ui.button>
                    </form>
                @endif
                @error('status')
                    <span class="text-sm text-red-600">{{ $message }}</span>
                @enderror
            </div>
        </x-ui.card>
    @endcan

    @php
        // A failed submission from inside a tab's drawer/form (F7-F9, F11)
        // redirects back here — without this, the page always reopens on
        // Overview and the reopened drawer/error is invisible until the
        // user happens to click back to that tab. Every such form carries a
        // hidden `_tab` field naming its own tab; whitelisted against a
        // fixed set of known tab keys before interpolation below — `old()`
        // is unvalidated user input, and naively embedding it inside a
        // single-quoted Alpine x-data string would let a crafted request
        // break out of that string (HTML-entity escaping alone doesn't stop
        // this, since the browser decodes entities before Alpine parses the
        // attribute as JS).
        $validTabs = ['overview', 'participants', 'categories', 'competition', 'schedule', 'judging', 'results', 'staff'];
        $initialTab = in_array(old('_tab'), $validTabs, true) ? old('_tab') : 'overview';
    @endphp

    <div x-data="{ tab: '{{ $initialTab }}' }">
        <nav class="flex flex-nowrap items-center gap-1 overflow-x-auto border-b border-slate-200" aria-label="Detail event">
            @php($entryCount = $event->categories->sum(fn ($category) => $category->competitionEntries->count()))
            <button type="button" @click="tab = 'overview'"
                    :class="tab === 'overview' ? 'border-sky-600 text-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="shrink-0 cursor-pointer border-b-2 px-3 py-2 text-sm font-medium transition-colors">
                Overview
            </button>

            <span class="mx-1 h-5 w-px shrink-0 self-center bg-slate-200" aria-hidden="true"></span>

            {{-- COMPETITION --}}
            @foreach ([
                'participants' => 'Peserta (' . $event->registrations_count . ')',
                'categories' => 'Kategori (' . $event->categories->count() . ')',
                'competition' => 'Kompetisi (' . $entryCount . ')',
                'schedule' => 'Jadwal (' . $event->scheduleSlots->count() . ')',
                'judging' => 'Penjurian (' . $event->categories->sum(fn ($category) => $category->judgingRounds->count()) . ')',
                'results' => 'Hasil',
            ] as $key => $label)
                <button type="button" @click="tab = '{{ $key }}'"
                        :class="tab === '{{ $key }}' ? 'border-sky-600 text-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700'"
                        class="shrink-0 cursor-pointer border-b-2 px-3 py-2 text-sm font-medium transition-colors">
                    {{ $label }}
                </button>
            @endforeach

            <span class="mx-1 h-5 w-px shrink-0 self-center bg-slate-200" aria-hidden="true"></span>

            {{-- OPERATIONS --}}
            <button type="button" @click="tab = 'staff'"
                    :class="tab === 'staff' ? 'border-sky-600 text-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="shrink-0 cursor-pointer border-b-2 px-3 py-2 text-sm font-medium transition-colors">
                Staff ({{ $event->staff->count() }})
            </button>
        </nav>

        {{-- Overview --}}
        <div x-show="tab === 'overview'" class="pt-6">
            <x-ui.card class="mb-4">
                <dl class="flex flex-wrap gap-x-6 gap-y-3">
                    @foreach ($readiness as $item)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $item['label'] }}</dt>
                            <dd class="mt-0.5 flex items-center gap-1 text-sm font-medium {{ $item['ok'] ? 'text-slate-700' : 'text-amber-600' }}">
                                <span aria-hidden="true">{{ $item['ok'] ? '✓' : '!' }}</span>
                                {{ $item['value'] }}
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </x-ui.card>

            @if ($event->registration_start_at || $event->registration_end_at)
                <x-ui.card class="mb-4">
                    <p class="text-sm text-slate-700">
                        <span class="font-medium">Pendaftaran:</span>
                        {{ $event->registration_start_at?->format('j M Y') ?? '—' }} s/d {{ $event->registration_end_at?->format('j M Y') ?? '—' }}
                        — {{ $event->registrations_count }} terdaftar,
                        {{ $registrationCounts['approved'] ?? 0 }} disetujui,
                        {{ $registrationCounts['submitted'] ?? 0 }} menunggu,
                        {{ $registrationCounts['revision_requested'] ?? 0 }} revisi.
                    </p>
                </x-ui.card>
            @endif

            @if ($nextAction)
                <x-ui.card class="mb-4 border-amber-200 bg-amber-50">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-slate-700"><span class="font-medium">Next Action:</span> {{ $nextAction['message'] }}</p>
                        <x-ui.button size="sm" @click="tab = '{{ $nextAction['tab'] }}'">{{ $nextAction['cta'] }}</x-ui.button>
                    </div>
                </x-ui.card>
            @endif

            <x-ui.card>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Deskripsi</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ $event->short_description ?: ($event->description ?: '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Lokasi</dt>
                        <dd class="mt-1 text-sm text-slate-700">{{ collect([$event->venue_name, $event->venue_address, $event->city_code, $event->province_code])->filter()->implode(', ') ?: '—' }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>

        {{-- Peserta --}}
        <div x-show="tab === 'participants'" x-cloak class="pt-6">
            <x-ui.card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-2xl font-semibold text-slate-800">{{ $event->registrations_count }}</div>
                        <div class="text-sm text-slate-500">pendaftaran tercatat di event ini</div>
                    </div>
                    <x-ui.button href="{{ route('dashboard.events.registrations.index', $event) }}">Kelola Peserta</x-ui.button>
                </div>
            </x-ui.card>
        </div>

        {{-- Kategori --}}
        <div x-show="tab === 'categories'" x-cloak class="pt-6">
            @include('dashboard.events.partials.categories')
        </div>

        {{-- Kompetisi --}}
        <div x-show="tab === 'competition'" x-cloak class="pt-6">
            @include('dashboard.events.partials.competition')
        </div>

        {{-- Jadwal --}}
        <div x-show="tab === 'schedule'" x-cloak class="pt-6">
            @include('dashboard.events.partials.schedule')
        </div>

        {{-- Penjurian --}}
        <div x-show="tab === 'judging'" x-cloak class="pt-6">
            @include('dashboard.events.partials.judging')
        </div>

        {{-- Hasil --}}
        <div x-show="tab === 'results'" x-cloak class="pt-6">
            @include('dashboard.events.partials.results')
        </div>

        {{-- Staff --}}
        <div x-show="tab === 'staff'" x-cloak class="pt-6">
            @include('dashboard.events.partials.staff')
        </div>
    </div>
@endsection
