@extends('layouts.dashboard')

@section('title', $judge->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Juri', 'href' => route('dashboard.judges.index')],
        ['label' => $judge->name],
    ]" />

    <x-layout.page-header :title="$judge->name">
        <x-slot:actions>
            @can('update', $judge)
                <x-ui.button variant="secondary" href="{{ route('dashboard.judges.edit', $judge) }}">Edit Juri</x-ui.button>
            @endcan
        </x-slot:actions>
    </x-layout.page-header>

    <div class="mb-6 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-500">
        <span>{{ $judge->organization?->name ?? '—' }}</span>
        <span class="text-slate-300">·</span>
        <x-ui.status-badge :status="$judge->status" />
    </div>

    <x-ui.card>
        <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Bio</dt>
        <dd class="mt-1 text-sm text-slate-700">{{ $judge->bio ?: '—' }}</dd>
    </x-ui.card>

    @can('update', $judge)
        <x-ui.card class="mt-4">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Kode Akses Judge Area</dt>
            <dd class="mt-1 flex items-center gap-3">
                @if ($judge->access_code)
                    <span class="rounded bg-slate-100 px-3 py-1.5 font-mono text-base tracking-widest text-slate-800">{{ $judge->access_code }}</span>
                @else
                    <span class="text-sm text-slate-400">Belum ada kode.</span>
                @endif
                <form method="POST" action="{{ route('dashboard.judges.access-code', $judge) }}"
                      data-confirm-title="Ganti kode akses?" data-confirm="Kode lama tidak akan berlaku lagi." data-confirm-button="Ya, ganti kode" data-confirm-icon="warning">
                    @csrf @method('PATCH')
                    <button type="submit" class="cursor-pointer text-xs font-medium text-sky-700 hover:underline">Regenerate Kode</button>
                </form>
            </dd>
            <p class="mt-2 text-xs text-slate-400">Bagikan kode ini ke juri untuk masuk ke <span class="font-mono">/judge/access</span> dan mengisi skor.</p>
        </x-ui.card>
    @endcan
@endsection
