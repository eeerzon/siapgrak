@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
    <x-layout.page-header title="Dashboard" description="Ringkasan event yang Anda kelola di halaman publik Siapgrak.">
        @if (auth()->user()->is_platform_admin)
            <x-slot:actions>
                <x-ui.button href="{{ route('dashboard.content.competitions.create') }}">+ Tambah Info Lomba</x-ui.button>
            </x-slot:actions>
        @endif
    </x-layout.page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-ui.card>
            <p class="text-sm text-slate-500">Info Lomba Tayang</p>
            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $stats['published'] }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $stats['draft'] }} draft</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-slate-500">Pendaftaran Dibuka</p>
            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $stats['registrationOpen'] }}</p>
            <p class="mt-1 text-xs text-slate-400">lomba tayang</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-slate-500">Agenda Mendatang</p>
            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $stats['upcomingAgenda'] }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-sm text-slate-500">Prestasi &amp; Kabar</p>
            <p class="mt-1 text-2xl font-semibold text-slate-800">{{ $stats['communityPosts'] }}</p>
        </x-ui.card>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Lomba Terdekat</h2>
                <a href="{{ route('dashboard.content.competitions.index') }}" class="text-sm text-sky-700 hover:underline">Lihat semua</a>
            </div>
            @if ($upcomingCompetitions->isEmpty())
                <x-ui.empty-state title="Belum ada lomba mendatang." description="Tambahkan info lomba untuk ditampilkan di halaman utama." />
            @else
                <x-ui.card :padding="false" class="divide-y divide-slate-100">
                    @foreach ($upcomingCompetitions as $competition)
                        <a href="{{ route('dashboard.content.competitions.show', $competition) }}" class="flex items-start justify-between gap-3 px-4 py-3 hover:bg-slate-50">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-800">{{ $competition->title }}</p>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    <x-site.date-range :start="$competition->start_date" :end="$competition->end_date" />
                                    @if ($competition->location)
                                        · {{ $competition->location }}
                                    @endif
                                </p>
                                <p class="mt-0.5 text-xs {{ $competition->isRegistrationOpen() ? 'text-emerald-700' : 'text-slate-400' }}">
                                    @if (! $competition->isRegistrationOpen())
                                        Pendaftaran ditutup
                                    @elseif ($competition->registration_deadline)
                                        Pendaftaran sampai {{ $competition->registration_deadline->translatedFormat('j M Y') }}
                                    @else
                                        Pendaftaran dibuka
                                    @endif
                                </p>
                            </div>
                            <x-ui.status-badge :status="$competition->status" />
                        </a>
                    @endforeach
                </x-ui.card>
            @endif
        </div>

        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-700">Agenda Terdekat</h2>
                <a href="{{ route('dashboard.content.agenda.index') }}" class="text-sm text-sky-700 hover:underline">Lihat semua</a>
            </div>
            @if ($upcomingAgenda->isEmpty())
                <x-ui.empty-state title="Belum ada agenda mendatang." />
            @else
                <x-ui.card :padding="false" class="divide-y divide-slate-100">
                    @foreach ($upcomingAgenda as $agendaItem)
                        <a href="{{ route('dashboard.content.agenda.edit', $agendaItem) }}" class="block px-4 py-3 hover:bg-slate-50">
                            <p class="text-sm font-medium text-slate-700">{{ $agendaItem->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-400">{{ $agendaItem->start_at->translatedFormat('j M Y, H:i') }}@if ($agendaItem->location) · {{ $agendaItem->location }}@endif</p>
                        </a>
                    @endforeach
                </x-ui.card>
            @endif
        </div>
    </div>
@endsection
