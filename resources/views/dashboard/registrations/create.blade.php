@extends('layouts.dashboard')

@section('title', 'Tambah Peserta — ' . $event->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Events', 'href' => route('dashboard.events.index')],
        ['label' => $event->name, 'href' => route('dashboard.events.show', $event)],
        ['label' => 'Peserta', 'href' => route('dashboard.events.registrations.index', $event)],
        ['label' => 'Tambah'],
    ]" />

    <x-layout.page-header
        title="Tambah Peserta"
        description="Pilih team + kategori. Roster dan dokumen dilengkapi di langkah berikutnya, lalu submit untuk direview."
    />

    <div class="mx-auto max-w-xl">
        @if ($teams->isEmpty())
            <x-ui.card>
                <p class="text-sm text-slate-600">
                    Belum ada team di organisasi Anda.
                    <a href="{{ route('dashboard.teams.create') }}" class="font-medium text-sky-700 underline">Buat team dulu</a>.
                </p>
            </x-ui.card>
        @elseif ($categories->isEmpty())
            <x-ui.card>
                <p class="text-sm text-slate-600">
                    Event ini belum punya kategori lomba.
                    <a href="{{ route('dashboard.events.show', $event) }}" class="font-medium text-sky-700 underline">Tambah kategori dulu</a>.
                </p>
            </x-ui.card>
        @else
            <form method="POST" action="{{ route('dashboard.events.registrations.store', $event) }}" class="space-y-4"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf

                <x-ui.select name="team_id" label="Team" required>
                    <option value="">Pilih team</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected((string) old('team_id') === (string) $team->id)>{{ $team->name }}</option>
                    @endforeach
                </x-ui.select>

                <x-ui.select name="event_category_id" label="Kategori" required>
                    <option value="">Pilih kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('event_category_id') === (string) $category->id)>
                            {{ $category->name }}@if ($category->quota) (kuota {{ $category->quota }})@endif
                        </option>
                    @endforeach
                </x-ui.select>

                <div class="flex items-center gap-3">
                    <x-ui.button type="submit">Buat Pendaftaran</x-ui.button>
                    <a href="{{ route('dashboard.events.registrations.index', $event) }}" class="text-sm text-slate-500 hover:underline">Batal</a>
                </div>
            </form>
        @endif
    </div>
@endsection
