@extends('layouts.dashboard')

@section('title', 'Import CSV Peserta — ' . $event->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Events', 'href' => route('dashboard.events.index')],
        ['label' => $event->name, 'href' => route('dashboard.events.show', $event)],
        ['label' => 'Peserta', 'href' => route('dashboard.events.registrations.index', $event)],
        ['label' => 'Import CSV'],
    ]" />

    <x-layout.page-header title="Import CSV Peserta" :description="$event->name" />

    <x-ui.card>
        <p class="mb-4 text-sm text-slate-500">
            File CSV: satu baris = satu anggota, dikelompokkan lewat kolom nama tim. Tim harus sudah terdaftar
            di halaman Tim — baris dengan nama tim yang tidak cocok akan dilewati saat import.
        </p>

        <form method="POST" action="{{ route('dashboard.events.registrations.import.store', $event) }}"
              enctype="multipart/form-data" class="space-y-4">
            @csrf
            <x-ui.select name="event_category_id" label="Kategori Tujuan" required>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.input name="file" type="file" label="File CSV" accept=".csv,text/csv" required
                        hint="Maksimal 2 MB." />

            <x-ui.button type="submit">Lanjut ke Mapping Kolom</x-ui.button>
        </form>
    </x-ui.card>
@endsection
