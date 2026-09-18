@extends('layouts.dashboard')

@section('title', 'Buat Event')

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Events', 'href' => route('dashboard.events.index')],
        ['label' => 'Buat Event'],
    ]" />

    <x-layout.page-header
        title="Buat Event"
        description="Isi info dasar event. Kategori, jadwal, juri, dan peserta diatur di halaman event setelah dibuat — bukan di sini."
    />

    <div class="mx-auto max-w-2xl">
        @if ($organizations->isEmpty())
            <x-ui.card>
                <p class="text-sm text-slate-600">
                    Anda belum tergabung di organisasi manapun.
                    <a href="{{ route('dashboard.organizations.create') }}" class="font-medium text-sky-700 underline">Buat organisasi dulu</a>
                    sebelum membuat event.
                </p>
            </x-ui.card>
        @else
            <form method="POST" action="{{ route('dashboard.events.store') }}" class="space-y-6"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf

                {{-- 1. Informasi Event --}}
                <x-ui.card>
                    <h2 class="mb-4 text-sm font-semibold text-slate-800">1 · Informasi Event</h2>
                    <div class="space-y-4">
                        <x-ui.select name="organization_id" label="Penyelenggara" required>
                            <option value="">Pilih organisasi</option>
                            @foreach ($organizations as $organization)
                                <option value="{{ $organization->id }}" @selected((string) old('organization_id') === (string) $organization->id)>
                                    {{ $organization->name }}
                                </option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.input name="name" label="Nama Event" required autofocus placeholder="mis. LKBB Garuda 2026" />
                        <x-ui.input name="short_description" label="Deskripsi Singkat (opsional)" placeholder="Satu kalimat untuk daftar & kartu event" />
                        <x-ui.textarea name="description" label="Deskripsi Lengkap (opsional)" :rows="4" />
                    </div>
                </x-ui.card>

                {{-- 2. Tanggal & Lokasi --}}
                <x-ui.card>
                    <h2 class="mb-4 text-sm font-semibold text-slate-800">2 · Tanggal & Lokasi</h2>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-ui.input name="start_date" label="Tanggal Mulai" type="date" required />
                            <x-ui.input name="end_date" label="Tanggal Selesai" type="date" required />
                        </div>
                        <x-ui.input name="venue_name" label="Nama Venue (opsional)" placeholder="mis. GOR Pajajaran" />
                        <x-ui.input name="venue_address" label="Alamat Venue (opsional)" />
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <x-ui.input name="province_code" label="Provinsi (opsional)" />
                            <x-ui.input name="city_code" label="Kota (opsional)" />
                        </div>
                    </div>
                </x-ui.card>

                {{-- 3. Pendaftaran --}}
                <x-ui.card>
                    <h2 class="mb-4 text-sm font-semibold text-slate-800">3 · Pendaftaran</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input name="registration_start_at" label="Pendaftaran Dibuka (opsional)" type="datetime-local" />
                        <x-ui.input name="registration_end_at" label="Pendaftaran Ditutup (opsional)" type="datetime-local" />
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Kosongkan kalau belum pasti — bisa diatur nanti lewat Edit event.</p>
                </x-ui.card>

                <div class="flex items-center gap-3">
                    <x-ui.button type="submit">Buat Event</x-ui.button>
                    <a href="{{ route('dashboard.events.index') }}" class="text-sm text-slate-500 hover:underline">Batal</a>
                </div>
            </form>
        @endif
    </div>
@endsection
