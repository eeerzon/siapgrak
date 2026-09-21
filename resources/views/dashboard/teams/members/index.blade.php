@extends('layouts.dashboard')

@section('title', 'Roster — ' . $team->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Teams', 'href' => route('dashboard.teams.index')],
        ['label' => $team->name, 'href' => route('dashboard.teams.show', $team)],
        ['label' => 'Roster'],
    ]" />

    <x-layout.page-header
        :title="'Roster ' . $team->name"
        description="Daftar anggota tim, dipakai sebagai sumber saat mengisi roster pendaftaran event (di-snapshot per pendaftaran)."
    />

    @can('manageMembers', $team)
        <x-ui.card class="mb-6">
            <h2 class="mb-3 text-sm font-medium text-slate-700">Tambah Anggota</h2>
            <form method="POST" action="{{ route('dashboard.teams.members.store', $team) }}"
                  class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <x-ui.input name="name" label="Nama" required />
                <x-ui.input name="gender" label="Gender (opsional)" placeholder="L / P" />
                <x-ui.input name="member_role" label="Role (opsional)" placeholder="mis. Danton" />
                <div class="flex items-end">
                    <x-ui.button type="submit" class="w-full">Tambah</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endcan

    @include('dashboard.teams.partials.roster', [
        'members' => $members,
        'team' => $team,
        'manageable' => auth()->user()->can('manageMembers', $team),
    ])
@endsection
