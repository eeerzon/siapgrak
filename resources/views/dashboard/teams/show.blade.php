@extends('layouts.dashboard')

@section('title', $team->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Teams', 'href' => route('dashboard.teams.index')],
        ['label' => $team->name],
    ]" />

    <x-layout.page-header :title="$team->name" :description="($team->organization?->name ?? '—').' · Status: '.$team->status->value">
        <x-slot:actions>
            @can('update', $team)
                <x-ui.button variant="secondary" href="{{ route('dashboard.teams.edit', $team) }}">Edit Team</x-ui.button>
            @endcan
            <x-ui.button variant="secondary" href="{{ route('dashboard.teams.members.index', $team) }}">Roster Anggota</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Nama Singkat</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $team->short_name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Lokasi</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ collect([$team->city_code, $team->province_code])->filter()->implode(', ') ?: '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Deskripsi</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $team->description ?: '—' }}</dd>
            </div>
        </dl>
    </div>

    <div class="mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
        Event history dan competition entries untuk team ini akan tersedia setelah Sprint 3+ (Event) dikerjakan.
    </div>
@endsection
