@extends('layouts.dashboard')

@section('title', 'Edit ' . $event->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Events', 'href' => route('dashboard.events.index')],
        ['label' => $event->name, 'href' => route('dashboard.events.show', $event)],
        ['label' => 'Edit'],
    ]" />

    <x-layout.page-header
        title="Edit Event"
        :description="'Penyelenggara: ' . ($event->organization?->name ?? '—') . ' — tidak bisa dipindah di sini.'"
    />

    <div class="mx-auto max-w-2xl">
        <form method="POST" action="{{ route('dashboard.events.update', $event) }}" class="space-y-6"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            @method('PUT')

            <x-ui.card>
                <h2 class="mb-4 text-sm font-semibold text-slate-800">Informasi Event</h2>
                <div class="space-y-4">
                    <x-ui.input name="name" label="Nama Event" :value="$event->name" required autofocus />
                    <x-ui.input name="slug" label="Slug" :value="$event->slug" required />
                    <x-ui.input name="short_description" label="Deskripsi Singkat (opsional)" :value="$event->short_description" />
                    <x-ui.textarea name="description" label="Deskripsi Lengkap (opsional)" :value="$event->description" :rows="4" />
                </div>
            </x-ui.card>

            <x-ui.card>
                <h2 class="mb-4 text-sm font-semibold text-slate-800">Tanggal & Lokasi</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input name="start_date" label="Tanggal Mulai" type="date" :value="$event->start_date?->toDateString()" required />
                        <x-ui.input name="end_date" label="Tanggal Selesai" type="date" :value="$event->end_date?->toDateString()" required />
                    </div>
                    <x-ui.input name="venue_name" label="Nama Venue (opsional)" :value="$event->venue_name" />
                    <x-ui.input name="venue_address" label="Alamat Venue (opsional)" :value="$event->venue_address" />
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <x-ui.input name="province_code" label="Provinsi (opsional)" :value="$event->province_code" />
                        <x-ui.input name="city_code" label="Kota (opsional)" :value="$event->city_code" />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <h2 class="mb-4 text-sm font-semibold text-slate-800">Pendaftaran</h2>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input name="registration_start_at" label="Pendaftaran Dibuka (opsional)" type="datetime-local"
                                :value="$event->registration_start_at?->format('Y-m-d\TH:i')" />
                    <x-ui.input name="registration_end_at" label="Pendaftaran Ditutup (opsional)" type="datetime-local"
                                :value="$event->registration_end_at?->format('Y-m-d\TH:i')" />
                </div>
            </x-ui.card>

            <div class="flex items-center gap-3">
                <x-ui.button type="submit">Simpan Perubahan</x-ui.button>
                <a href="{{ route('dashboard.events.show', $event) }}" class="text-sm text-slate-500 hover:underline">Batal</a>
            </div>
        </form>
    </div>
@endsection
