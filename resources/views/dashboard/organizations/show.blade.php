@extends('layouts.dashboard')

@section('title', $organization->name)

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">{{ $organization->name }}</h2>
            <p class="text-sm text-slate-500">{{ ucfirst($organization->type->value) }} · Status: {{ $organization->status->value }}</p>
        </div>
        <div class="flex gap-2">
            @can('update', $organization)
                <a href="{{ route('dashboard.organizations.edit', $organization) }}"
                   class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Edit Settings
                </a>
            @endcan
            @can('manageMembers', $organization)
                <a href="{{ route('dashboard.organizations.members.index', $organization) }}"
                   class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Kelola Anggota
                </a>
            @endcan
        </div>
    </div>

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
