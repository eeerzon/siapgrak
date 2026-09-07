@extends('layouts.dashboard')

@section('title', 'Teams')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-slate-800">Teams</h2>
        <a href="{{ route('dashboard.teams.create') }}"
           class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
            + Buat Team
        </a>
    </div>

    <form method="GET" action="{{ route('dashboard.teams.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="search" class="block text-sm font-medium text-slate-700">Cari</label>
            <input id="search" name="search" type="text" value="{{ request('search') }}" placeholder="Nama team"
                   class="mt-1 block rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
        </div>
        <div>
            <label for="status" class="block text-sm font-medium text-slate-700">Status</label>
            <select id="status" name="status"
                    class="mt-1 block rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                <option value="">Semua</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <button type="submit" class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
            Filter
        </button>
        @if (request('search') || request('status'))
            <a href="{{ route('dashboard.teams.index') }}" class="text-sm text-slate-500 hover:underline">Reset</a>
        @endif
    </form>

    @if ($teams->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 bg-white px-6 py-12 text-center">
            <p class="text-base font-medium text-slate-700">
                @if (request('search') || request('status'))
                    Tidak ada team yang cocok dengan filter.
                @else
                    Belum ada team di organisasi Anda.
                @endif
            </p>
            <p class="mt-1 text-sm text-slate-500">Buat team untuk mulai mendaftarkan peserta di event mendatang.</p>
            <a href="{{ route('dashboard.teams.create') }}"
               class="mt-4 inline-block rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">
                Buat Team
            </a>
        </div>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($teams as $team)
                <a href="{{ route('dashboard.teams.show', $team) }}"
                   class="block rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300 hover:shadow">
                    <div class="font-medium text-slate-800">{{ $team->name }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $team->organization?->name ?? '—' }}</div>
                    <div class="mt-2 text-xs uppercase tracking-wide text-slate-400">{{ $team->status->value }}</div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $teams->links() }}
        </div>
    @endif
@endsection
