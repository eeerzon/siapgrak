@extends('layouts.dashboard')

@section('title', 'Teams')

@section('content')
    <x-layout.page-header title="Teams" description="Tim yang dipakai untuk mendaftarkan peserta di kompetisi.">
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.teams.create') }}">+ Buat Team</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @php($isFiltered = request()->filled('search') || request()->filled('status'))

    <form method="GET" action="{{ route('dashboard.teams.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-64">
            <x-ui.input name="search" label="Cari" :value="is_string(request('search')) ? request('search') : ''" placeholder="Nama team" />
        </div>
        <div class="w-full sm:w-44">
            <x-ui.select name="status" label="Status">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-ui.select>
        </div>
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
        @if ($isFiltered)
            <a href="{{ route('dashboard.teams.index') }}" class="pb-2 text-sm text-slate-500 hover:underline">Reset</a>
        @endif
    </form>

    @if ($teams->isEmpty())
        <x-ui.empty-state
            :title="$isFiltered ? 'Tidak ada team yang cocok dengan filter.' : 'Belum ada team di organisasi Anda.'"
            :description="$isFiltered
                ? 'Coba ubah kata kunci atau status, atau reset filter.'
                : 'Buat team untuk mulai mendaftarkan peserta di event mendatang.'"
        >
            @unless ($isFiltered)
                <x-slot:action>
                    <x-ui.button href="{{ route('dashboard.teams.create') }}">Buat Team</x-ui.button>
                </x-slot:action>
            @endunless
        </x-ui.empty-state>
    @else
        {{-- Desktop: table (>= md). Mobile: card list below — same rule for every F2-F12 list page. --}}
        <div class="hidden md:block">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <x-ui.table-th>Team</x-ui.table-th>
                        <x-ui.table-th>Organisasi</x-ui.table-th>
                        <x-ui.table-th>Lokasi</x-ui.table-th>
                        <x-ui.table-th align="right">Anggota</x-ui.table-th>
                        <x-ui.table-th>Status</x-ui.table-th>
                        <x-ui.table-th align="right">Aksi</x-ui.table-th>
                    </tr>
                </x-slot:head>

                @foreach ($teams as $team)
                    <tr class="hover:bg-slate-50">
                        <x-ui.table-td>
                            <a href="{{ route('dashboard.teams.show', $team) }}" class="font-medium text-slate-800 hover:underline">
                                {{ $team->name }}
                            </a>
                            @if ($team->short_name)
                                <span class="ml-1.5 text-xs text-slate-400">{{ $team->short_name }}</span>
                            @endif
                        </x-ui.table-td>
                        <x-ui.table-td muted>{{ $team->organization?->name ?? '—' }}</x-ui.table-td>
                        <x-ui.table-td muted>{{ collect([$team->city_code, $team->province_code])->filter()->implode(', ') ?: '—' }}</x-ui.table-td>
                        <x-ui.table-td align="right" muted>{{ $team->members_count }}</x-ui.table-td>
                        <x-ui.table-td><x-ui.status-badge :status="$team->status" /></x-ui.table-td>
                        <x-ui.table-td align="right">
                            <a href="{{ route('dashboard.teams.show', $team) }}" class="text-sm font-medium text-sky-700 hover:underline">Lihat</a>
                        </x-ui.table-td>
                    </tr>
                @endforeach
            </x-ui.table>
        </div>

        {{-- Mobile: cards (< md) --}}
        <div class="space-y-3 md:hidden">
            @foreach ($teams as $team)
                <a href="{{ route('dashboard.teams.show', $team) }}"
                   class="block rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium text-slate-800">{{ $team->name }}</span>
                        <x-ui.status-badge :status="$team->status" />
                    </div>
                    <dl class="mt-2 space-y-1 text-sm text-slate-500">
                        <div class="flex justify-between gap-2">
                            <dt>Organisasi</dt>
                            <dd class="text-right text-slate-600">{{ $team->organization?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt>Lokasi</dt>
                            <dd class="text-right text-slate-600">{{ collect([$team->city_code, $team->province_code])->filter()->implode(', ') ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt>Anggota</dt>
                            <dd class="text-right text-slate-600">{{ $team->members_count }}</dd>
                        </div>
                    </dl>
                </a>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $teams->links() }}
        </div>
    @endif
@endsection
