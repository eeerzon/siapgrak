@extends('layouts.dashboard')

@section('title', 'Juri')

@section('content')
    <x-layout.page-header title="Juri" description="Direktori juri organisasi Anda — reusable lintas event.">
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.judges.create') }}">+ Tambah Juri</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @php($isFiltered = request()->filled('search') || request()->filled('status'))

    <form method="GET" action="{{ route('dashboard.judges.index') }}" class="mb-4 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-64">
            <x-ui.input name="search" label="Cari" :value="is_string(request('search')) ? request('search') : ''" placeholder="Nama juri" />
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
            <a href="{{ route('dashboard.judges.index') }}" class="pb-2 text-sm text-slate-500 hover:underline">Reset</a>
        @endif
    </form>

    @if ($judges->isEmpty())
        <x-ui.empty-state
            :title="$isFiltered ? 'Tidak ada juri yang cocok dengan filter.' : 'Belum ada juri di organisasi Anda.'"
            :description="$isFiltered ? 'Coba ubah kata kunci atau status, atau reset filter.' : 'Tambah juri untuk mulai menyusun penjurian event.'"
        >
            @unless ($isFiltered)
                <x-slot:action>
                    <x-ui.button href="{{ route('dashboard.judges.create') }}">Tambah Juri</x-ui.button>
                </x-slot:action>
            @endunless
        </x-ui.empty-state>
    @else
        <div class="hidden md:block">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <x-ui.table-th>Nama</x-ui.table-th>
                        <x-ui.table-th>Organisasi</x-ui.table-th>
                        <x-ui.table-th>Status</x-ui.table-th>
                        <x-ui.table-th align="right">Aksi</x-ui.table-th>
                    </tr>
                </x-slot:head>

                @foreach ($judges as $judge)
                    <tr class="hover:bg-slate-50">
                        <x-ui.table-td>
                            <a href="{{ route('dashboard.judges.show', $judge) }}" class="font-medium text-slate-800 hover:underline">{{ $judge->name }}</a>
                        </x-ui.table-td>
                        <x-ui.table-td muted>{{ $judge->organization?->name ?? '—' }}</x-ui.table-td>
                        <x-ui.table-td><x-ui.status-badge :status="$judge->status" /></x-ui.table-td>
                        <x-ui.table-td align="right">
                            <a href="{{ route('dashboard.judges.show', $judge) }}" class="text-sm font-medium text-sky-700 hover:underline">Lihat</a>
                        </x-ui.table-td>
                    </tr>
                @endforeach
            </x-ui.table>
        </div>

        <div class="space-y-3 md:hidden">
            @foreach ($judges as $judge)
                <a href="{{ route('dashboard.judges.show', $judge) }}"
                   class="block rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:border-slate-300">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium text-slate-800">{{ $judge->name }}</span>
                        <x-ui.status-badge :status="$judge->status" />
                    </div>
                    <div class="mt-2 text-sm text-slate-500">{{ $judge->organization?->name ?? '—' }}</div>
                </a>
            @endforeach
        </div>

        <div class="mt-6">{{ $judges->links() }}</div>
    @endif
@endsection
