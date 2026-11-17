@extends('layouts.dashboard')

@section('title', 'Konten — Info Lomba')

@section('content')
    <x-layout.page-header title="Events" description="Kelola konten yang tampil di halaman publik Siapgrak.">
        <x-slot:actions>
            <x-ui.button href="{{ route('dashboard.content.competitions.create') }}">+ Tambah Info Lomba</x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @include('dashboard.content.partials.tabs')
    @include('dashboard.content.partials.search', ['placeholder' => 'Cari judul, penyelenggara, atau kota'])

    @if ($competitions->isEmpty() && $search)
        <x-ui.empty-state title="Tidak ada yang cocok dengan “{{ $search }}”." />
    @elseif ($competitions->isEmpty())
        <x-ui.empty-state title="Belum ada info lomba." description="Tambahkan lomba atau kejuaraan pertama untuk ditampilkan di halaman utama." />
    @else
        <div class="hidden md:block">
            <x-ui.table>
                <x-slot:head>
                    <tr>
                        <x-ui.table-th>Judul</x-ui.table-th>
                        <x-ui.table-th>Tanggal</x-ui.table-th>
                        <x-ui.table-th>Pengelolaan</x-ui.table-th>
                        <x-ui.table-th>Status</x-ui.table-th>
                        <x-ui.table-th align="right">Aksi</x-ui.table-th>
                    </tr>
                </x-slot:head>
                @foreach ($competitions as $competition)
                    <tr class="cursor-pointer hover:bg-slate-50" x-data @click="$event.target.closest('a, button, form') || (window.location = @js(route('dashboard.content.competitions.show', $competition)))">
                        <x-ui.table-td>
                            <a href="{{ route('dashboard.content.competitions.show', $competition) }}" class="font-medium text-slate-800 hover:underline">{{ $competition->title }}</a>
                        </x-ui.table-td>
                        <x-ui.table-td muted><x-site.date-range :start="$competition->start_date" :end="$competition->end_date" /></x-ui.table-td>
                        <x-ui.table-td muted>
                            @if ($competition->is_managed)
                                <x-ui.badge variant="info">Kreen</x-ui.badge>
                                <span class="ml-1 text-xs text-slate-500">{{ $competition->kreen_slug }}</span>
                            @else
                                Eksternal
                            @endif
                        </x-ui.table-td>
                        <x-ui.table-td><x-ui.status-badge :status="$competition->status" /></x-ui.table-td>
                        <x-ui.table-td align="right">
                            <div class="flex justify-end gap-3">
                                @if ($competition->kreenUrl())
                                    <a href="{{ $competition->kreenUrl() }}" target="_blank" rel="noopener" class="whitespace-nowrap text-sm font-medium text-sky-700 hover:underline">Kreenconnect ↗</a>
                                @endif
                                <a href="{{ route('dashboard.content.competitions.edit', $competition) }}" class="text-sm font-medium text-sky-700 hover:underline">Edit</a>
                                @include('dashboard.content.partials.delete-form', ['action' => route('dashboard.content.competitions.destroy', $competition)])
                            </div>
                        </x-ui.table-td>
                    </tr>
                @endforeach
            </x-ui.table>
        </div>

        <div class="space-y-3 md:hidden">
            @foreach ($competitions as $competition)
                <div class="cursor-pointer rounded-lg border border-slate-200 bg-white p-4 shadow-sm" x-data @click="$event.target.closest('a, button, form') || (window.location = @js(route('dashboard.content.competitions.show', $competition)))">
                    <div class="flex items-start justify-between gap-2">
                        <a href="{{ route('dashboard.content.competitions.show', $competition) }}" class="font-medium text-slate-800 hover:underline">{{ $competition->title }}</a>
                        <x-ui.status-badge :status="$competition->status" />
                    </div>
                    <p class="mt-1 text-sm text-slate-500">
                        <x-site.date-range :start="$competition->start_date" :end="$competition->end_date" />
                        · {{ $competition->is_managed ? 'Kreen' : 'Eksternal' }}
                    </p>
                    <div class="mt-3 flex items-center gap-4">
                        <a href="{{ route('dashboard.content.competitions.edit', $competition) }}" class="text-sm font-medium text-sky-700 hover:underline">Edit</a>
                        @include('dashboard.content.partials.delete-form', ['action' => route('dashboard.content.competitions.destroy', $competition)])
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $competitions->links('partials.pagination') }}</div>
    @endif
@endsection
