@extends('layouts.dashboard')

@section('title', $organization->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Organizations', 'href' => route('dashboard.organizations.index')],
        ['label' => $organization->name],
    ]" />

    <x-layout.page-header :title="$organization->name" :description="ucfirst($organization->type->value).' · Status: '.$organization->status->value">
        <x-slot:actions>
            @can('update', $organization)
                <x-ui.button variant="secondary" href="{{ route('dashboard.organizations.edit', $organization) }}">Edit Settings</x-ui.button>
            @endcan
            @can('manageMembers', $organization)
                <x-ui.button variant="secondary" href="{{ route('dashboard.organizations.members.index', $organization) }}">Kelola Anggota</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="rounded-lg border border-slate-200 bg-white p-6">
        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Deskripsi</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $organization->description ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Email</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $organization->email ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Telepon</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $organization->phone ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Website</dt>
                <dd class="mt-1 text-sm text-slate-700">{{ $organization->website ?: '—' }}</dd>
            </div>
        </dl>
    </div>

    <div class="mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-6 text-sm text-slate-500">
        Events, Teams, Judging, dan Results untuk organisasi ini akan tersedia di sprint berikutnya (Phase A Sprint 2+).
    </div>
@endsection
