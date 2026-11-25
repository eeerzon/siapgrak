@extends('layouts.dashboard')

@section('title', 'Juri')

@php($field = 'block w-full text-sm')

@section('content')
    <x-layout.page-header title="Events" description="Kelola konten yang tampil di halaman publik Siapgrak." />

    @include('dashboard.content.partials.tabs')

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <div>
            <p class="mb-3 text-sm text-slate-500">Juri milik Anda. Mereka masuk ke Panel Juri di <span class="font-mono text-navy-800">{{ url('/juri') }}</span> memakai kode akses masing-masing, tanpa akun.</p>
            @include('dashboard.content.partials.search', ['placeholder' => 'Cari nama juri'])

            @if ($judges->isEmpty())
                <x-ui.empty-state :title="$search ? 'Tidak ada juri dengan nama “'.$search.'”.' : 'Belum ada juri.'" description="Tambahkan juri di samping, lalu tugaskan ke babak di halaman Penjurian tiap Info Lomba." />
            @else
                <ul class="space-y-3">
                    @foreach ($judges as $judge)
                        <li class="rounded-lg border border-slate-200 bg-white p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-navy-900">{{ $judge->name }}</p>
                                    <p class="text-sm text-slate-500">{{ $judge->bio ?: 'Belum ada keterangan.' }}</p>
                                    <p class="mt-1 text-xs text-slate-400">Ditugaskan di {{ $judge->rounds_count }} babak</p>
                                </div>
                                <div class="sm:text-right">
                                    <p class="text-xs text-slate-500">Kode akses</p>
                                    <p class="font-mono text-lg font-bold tracking-[0.2em] text-navy-900">{{ $judge->access_code }}</p>
                                    @if ($judge->status->value !== 'active')
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Nonaktif, tidak bisa masuk</span>
                                    @endif
                                </div>
                            </div>

                            <details class="mt-3">
                                <summary class="cursor-pointer text-sm font-medium text-sky-700">Edit / ganti kode</summary>
                                <form method="POST" action="{{ route('dashboard.content.judges.update', $judge) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                                    @csrf @method('PUT')
                                    <label class="block text-xs font-medium text-slate-600">Nama<input name="name" value="{{ $judge->name }}" required class="{{ $field }} mt-1"></label>
                                    <label class="block text-xs font-medium text-slate-600">Status
                                        <select name="status" class="{{ $field }} mt-1">
                                            <option value="active" @selected($judge->status->value === 'active')>Aktif</option>
                                            <option value="inactive" @selected($judge->status->value === 'inactive')>Nonaktif</option>
                                        </select>
                                    </label>
                                    <label class="block text-xs font-medium text-slate-600 sm:col-span-2">Keterangan<input name="bio" value="{{ $judge->bio }}" class="{{ $field }} mt-1"></label>
                                    <div class="flex flex-wrap items-center gap-3 sm:col-span-2">
                                        <x-ui.button type="submit" size="sm">Simpan</x-ui.button>
                                        <button type="submit" form="regenerate-{{ $judge->id }}" class="cursor-pointer text-xs font-medium text-red-600 hover:underline">Ganti kode akses</button>
                                    </div>
                                </form>
                                <form id="regenerate-{{ $judge->id }}" method="POST" action="{{ route('dashboard.content.judges.access-code', $judge) }}" data-confirm-title="{{ 'Ganti kode akses '.$judge->name.'?' }}" data-confirm="Kode lama langsung tidak berlaku, dan juri yang sedang masuk dengan kode lama akan keluar." data-confirm-button="Ya, ganti kode" data-confirm-icon="warning">
                                    @csrf @method('PATCH')
                                </form>
                            </details>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <form method="POST" action="{{ route('dashboard.content.judges.store') }}" class="h-fit space-y-3 rounded-lg border border-slate-200 bg-white p-4">
            @csrf
            <h3 class="font-semibold text-navy-900">Tambah juri</h3>
            <label class="block text-xs font-medium text-slate-600">Nama<input name="name" value="{{ old('name') }}" required class="{{ $field }} mt-1"></label>
            <label class="block text-xs font-medium text-slate-600">Keterangan (opsional)<input name="bio" value="{{ old('bio') }}" placeholder="mis. Pelatih PBB, juri provinsi" class="{{ $field }} mt-1"></label>
            <input type="hidden" name="status" value="active">
            <x-ui.button type="submit" size="sm">Tambah Juri</x-ui.button>
            <p class="text-xs text-slate-400">Kode akses 8 karakter dibuat otomatis.</p>
        </form>
    </div>
@endsection
