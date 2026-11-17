@extends('layouts.dashboard')

@section('title', 'Konten — Agenda')

@section('content')
    <x-layout.page-header title="Events" description="Kelola konten yang tampil di halaman publik Siapgrak.">
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.content.agenda.create') }}">+ Tambah Agenda</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @include('dashboard.content.partials.tabs')
    @include('dashboard.content.partials.search', ['placeholder' => 'Cari judul atau lokasi'])

    @if ($agendaItems->isEmpty() && $search)
        <x-ui.empty-state title="Tidak ada yang cocok dengan “{{ $search }}”." />
    @elseif ($agendaItems->isEmpty())
        <x-ui.empty-state title="Belum ada agenda." description="Tambahkan kegiatan pertama untuk ditampilkan di halaman utama." />
    @else
        <div class="hidden md:block">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <x-ui.table-th>Judul</x-ui.table-th>
                        <x-ui.table-th>Waktu</x-ui.table-th>
                        <x-ui.table-th>Lokasi</x-ui.table-th>
                        <x-ui.table-th>Status</x-ui.table-th>
                        <x-ui.table-th align="right">Aksi</x-ui.table-th>
                    </tr>
                </x-slot:head>
                @foreach ($agendaItems as $agendaItem)
                    <tr class="hover:bg-slate-50">
                        <x-ui.table-td>
                            <a href="{{ route('dashboard.content.agenda.edit', $agendaItem) }}" class="font-medium text-slate-800 hover:underline">{{ $agendaItem->title }}</a>
                        </x-ui.table-td>
                        <x-ui.table-td muted>{{ $agendaItem->start_at->copy()->locale('id')->translatedFormat('j M Y, H.i') }}</x-ui.table-td>
                        <x-ui.table-td muted>{{ $agendaItem->location ?: '—' }}</x-ui.table-td>
                        <x-ui.table-td><x-ui.status-badge :status="$agendaItem->status" /></x-ui.table-td>
                        <x-ui.table-td align="right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('dashboard.content.agenda.edit', $agendaItem) }}" class="text-sm font-medium text-sky-700 hover:underline">Edit</a>
                                @include('dashboard.content.partials.delete-form', ['action' => route('dashboard.content.agenda.destroy', $agendaItem)])
                            </div>
                        </x-ui.table-td>
                    </tr>
                @endforeach
            </x-ui.table>
        </div>

        <div class="space-y-3 md:hidden">
            @foreach ($agendaItems as $agendaItem)
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-2">
                        <span class="font-medium text-slate-800">{{ $agendaItem->title }}</span>
                        <x-ui.status-badge :status="$agendaItem->status" />
                    </div>
                    <p class="mt-1 text-sm text-slate-500">{{ $agendaItem->start_at->copy()->locale('id')->translatedFormat('j M Y, H.i') }}</p>
                    <div class="mt-3 flex items-center gap-4">
                        <a href="{{ route('dashboard.content.agenda.edit', $agendaItem) }}" class="text-sm font-medium text-sky-700 hover:underline">Edit</a>
                        @include('dashboard.content.partials.delete-form', ['action' => route('dashboard.content.agenda.destroy', $agendaItem)])
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $agendaItems->links('partials.pagination') }}</div>
    @endif
@endsection
