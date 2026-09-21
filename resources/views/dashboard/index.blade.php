@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <x-layout.page-header title="Dashboard" description="Ringkasan operasional organisasi Anda." />

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.card>
            <p class="text-sm text-slate-500">Event Aktif</p>
            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $activeEventsCount }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-slate-500">Tim</p>
            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $teamsCount }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-slate-500">Peserta Terdaftar</p>
            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $participantsCount }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-slate-500">Event Mendatang</p>
            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $upcomingCount }}</p>
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h2 class="mb-3 text-sm font-semibold text-slate-700">Event Mendatang Terdekat</h2>
            @if ($upcomingEvent)
                <x-ui.card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <a href="{{ route('dashboard.events.show', $upcomingEvent) }}" class="font-medium text-slate-800 hover:underline">
                                {{ $upcomingEvent->name }}
                            </a>
                            <p class="mt-1 text-sm text-slate-500">{{ $upcomingEvent->organization?->name ?? '—' }}</p>
                        </div>
                        <x-ui.status-badge :status="$upcomingEvent->status" />
                    </div>

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                        <div>
                            <dt class="text-slate-400">Tanggal</dt>
                            <dd class="text-slate-700">@include('dashboard.events.partials.date-range', ['start' => $upcomingEvent->start_date, 'end' => $upcomingEvent->end_date])</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Kategori</dt>
                            <dd class="text-slate-700">{{ $upcomingEvent->categories_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400">Peserta</dt>
                            <dd class="text-slate-700">{{ $upcomingEvent->registrations_count }}</dd>
                        </div>
                    </dl>
                </x-ui.card>
            @else
                <x-ui.empty-state title="Tidak ada event mendatang." description="Buat event baru untuk mulai mengelola lomba.">
                    <x-slot:action>
                        <x-ui.button href="{{ route('dashboard.events.create') }}">+ Buat Event</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            @endif
        </div>

        <div>
            <h2 class="mb-3 text-sm font-semibold text-slate-700">Aktivitas Terbaru</h2>
            @if ($recentActivity->isEmpty())
                <x-ui.empty-state title="Belum ada aktivitas." />
            @else
                <x-ui.card :padding="false" class="divide-y divide-slate-100">
                    @foreach ($recentActivity as $activity)
                        <div class="px-4 py-3">
                            <p class="text-sm text-slate-700">{{ $activity->label() }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $activity->actorLabel() }} · {{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    @endforeach
                </x-ui.card>
            @endif
        </div>
    </div>
@endsection
