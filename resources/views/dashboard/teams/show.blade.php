@extends('layouts.dashboard')

@section('title', $team->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">{{ $team->name }}</h2>
            <p class="text-sm text-slate-500">{{ $team->organization?->name ?? '—' }} · Status: {{ $team->status->value }}</p>
        </div>
        <div class="flex gap-2">
            @can('update', $team)
                <a href="{{ route('dashboard.teams.edit', $team) }}"
                   class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Edit Team
                </a>
            @endcan
            <a href="{{ route('dashboard.teams.members.index', $team) }}"
               class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                Roster Anggota
            </a>
        </div>
    </div>

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
