@extends('layouts.dashboard')

@section('title', 'Buat Team')

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Teams', 'href' => route('dashboard.teams.index')],
        ['label' => 'Buat Team'],
    ]" />

    <x-layout.page-header title="Buat Team" description='Team adalah identitas kompetisi (mis. "Paskibra Utama"), reusable untuk event berikutnya.' />

    <div class="mx-auto max-w-xl">
        @if ($organizations->isEmpty())
            <x-ui.card>
                <p class="text-sm text-slate-600">
                    Anda belum tergabung di organisasi manapun.
                    <a href="{{ route('dashboard.organizations.create') }}" class="font-medium text-sky-700 underline">Buat organisasi dulu</a>
                    sebelum membuat team.
                </p>
            </x-ui.card>
        @else
            <form method="POST" action="{{ route('dashboard.teams.store') }}" class="space-y-4"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf

                <x-ui.select name="organization_id" label="Organisasi Pemilik" required>
                    <option value="">Pilih organisasi</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected((string) old('organization_id') === (string) $organization->id)>
                            {{ $organization->name }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.input name="name" label="Nama Team" required autofocus />
                <x-ui.input name="short_name" label="Nama Singkat (opsional)" />

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-ui.input name="province_code" label="Provinsi (opsional)" />
                    <x-ui.input name="city_code" label="Kota (opsional)" />
                </div>

                <x-ui.textarea name="description" label="Deskripsi (opsional)" />

                <div class="flex items-center gap-3">
                    <x-ui.button type="submit">Buat Team</x-ui.button>
                    <a href="{{ route('dashboard.teams.index') }}" class="text-sm text-slate-500 hover:underline">Batal</a>
                </div>
            </form>
        @endif
    </div>
@endsection
