@extends('layouts.dashboard')

@section('title', 'Edit ' . $team->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Teams', 'href' => route('dashboard.teams.index')],
        ['label' => $team->name, 'href' => route('dashboard.teams.show', $team)],
        ['label' => 'Edit'],
    ]" />

    <x-layout.page-header
        title="Edit Team"
        :description="'Organisasi pemilik: ' . ($team->organization?->name ?? '—') . ' — tidak bisa dipindah di sini.'"
    />

    <div class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('dashboard.teams.update', $team) }}" class="space-y-4"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            @method('PUT')

            <x-ui.input name="name" label="Nama Team" :value="$team->name" required autofocus />
            <x-ui.input name="slug" label="Slug" :value="$team->slug" required />
            <x-ui.input name="short_name" label="Nama Singkat (opsional)" :value="$team->short_name" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-ui.input name="province_code" label="Provinsi (opsional)" :value="$team->province_code" />
                <x-ui.input name="city_code" label="Kota (opsional)" :value="$team->city_code" />
            </div>

            <x-ui.textarea name="description" label="Deskripsi (opsional)" :value="$team->description" />

            <div class="flex items-center gap-3">
                <x-ui.button type="submit">Simpan Perubahan</x-ui.button>
                <a href="{{ route('dashboard.teams.show', $team) }}" class="text-sm text-slate-500 hover:underline">Batal</a>
            </div>
        </form>
    </div>
@endsection
