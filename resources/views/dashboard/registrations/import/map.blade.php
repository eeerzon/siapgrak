@extends('layouts.dashboard')

@section('title', 'Cocokkan Kolom — ' . $event->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Events', 'href' => route('dashboard.events.index')],
        ['label' => $event->name, 'href' => route('dashboard.events.show', $event)],
        ['label' => 'Peserta', 'href' => route('dashboard.events.registrations.index', $event)],
        ['label' => 'Cocokkan Kolom'],
    ]" />

    <x-layout.page-header title="Cocokkan Kolom" description="Pilih kolom CSV yang sesuai untuk tiap field." />

    <x-ui.card>
        @if (empty($headers))
            <p class="text-sm text-red-600">File tidak punya baris header. Upload ulang file CSV yang valid.</p>
        @else
            <form method="POST" action="{{ route('dashboard.events.registrations.import.preview', [$event, $token]) }}" class="space-y-4">
                @csrf
                <input type="hidden" name="event_category_id" value="{{ $categoryId }}">

                @foreach ([
                    'team_name' => 'Nama Tim',
                    'member_name' => 'Nama Anggota',
                    'gender' => 'Jenis Kelamin (opsional)',
                    'member_role' => 'Peran/Posisi (opsional)',
                ] as $field => $label)
                    <x-ui.select :name="$field" :label="$label" :required="! str_contains($label, 'opsional')">
                        <option value="">— Tidak ada —</option>
                        @foreach ($headers as $header)
                            <option value="{{ $header }}" @selected(($guessed[$field] ?? null) === $header)>{{ $header }}</option>
                        @endforeach
                    </x-ui.select>
                @endforeach

                <x-ui.button type="submit">Lihat Preview</x-ui.button>
            </form>
        @endif
    </x-ui.card>
@endsection
