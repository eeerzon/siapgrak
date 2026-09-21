@extends('layouts.dashboard')

@section('title', $team->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Teams', 'href' => route('dashboard.teams.index')],
        ['label' => $team->name],
    ]" />

    <x-layout.page-header :title="$team->name">
        <x-slot:actions>
            @can('update', $team)
                <x-ui.button variant="secondary" href="{{ route('dashboard.teams.edit', $team) }}">Edit Team</x-ui.button>
            @endcan
            <x-ui.button href="{{ route('dashboard.teams.members.index', $team) }}">Kelola Roster</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
        <span>{{ $team->organization?->name ?? '—' }}</span>
        <span class="text-slate-300">·</span>
        <x-ui.status-badge :status="$team->status" />
        <span class="text-slate-300">·</span>
        <span>{{ $team->members->count() }} anggota</span>
    </div>

    <div x-data="{ tab: 'overview' }">
        <nav class="flex gap-1 border-b border-slate-200" aria-label="Detail team">
            <button type="button" @click="tab = 'overview'"
                    :class="tab === 'overview' ? 'border-sky-600 text-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="cursor-pointer border-b-2 px-3 py-2 text-sm font-medium transition-colors">
                Overview
            </button>
            <button type="button" @click="tab = 'members'"
                    :class="tab === 'members' ? 'border-sky-600 text-slate-800' : 'border-transparent text-slate-500 hover:text-slate-700'"
                    class="cursor-pointer border-b-2 px-3 py-2 text-sm font-medium transition-colors">
                Anggota ({{ $team->members->count() }})
            </button>
        </nav>

        {{-- Overview --}}
        <div x-show="tab === 'overview'" class="pt-6">
            <x-ui.card>
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
            </x-ui.card>

            <div class="mt-4 rounded-lg border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">
                Competition history untuk team ini muncul di sini setelah modul Event (Sprint 3+) tersedia.
            </div>
        </div>

        {{-- Anggota --}}
        <div x-show="tab === 'members'" x-cloak class="pt-6">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-sm font-medium text-slate-700">Roster Anggota</h2>
                @can('manageMembers', $team)
                    <x-ui.button size="sm" variant="secondary" href="{{ route('dashboard.teams.members.index', $team) }}">Tambah / Hapus</x-ui.button>
                @endcan
            </div>

            @include('dashboard.teams.partials.roster', ['members' => $team->members, 'team' => $team, 'manageable' => false])
        </div>
    </div>
@endsection
