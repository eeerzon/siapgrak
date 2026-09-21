@extends('layouts.dashboard')

@section('title', 'Tambah Juri')

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Juri', 'href' => route('dashboard.judges.index')],
        ['label' => 'Tambah'],
    ]" />

    <x-layout.page-header title="Tambah Juri" description="Juri belum wajib punya akun — cukup nama dan bio untuk mulai." />

    <div class="mx-auto max-w-xl">
        @if ($organizations->isEmpty())
            <x-ui.card>
                <p class="text-sm text-slate-600">
                    Anda belum tergabung di organisasi manapun.
                    <a href="{{ route('dashboard.organizations.create') }}" class="font-medium text-sky-700 underline">Buat organisasi dulu</a>
                    sebelum menambah juri.
                </p>
            </x-ui.card>
        @else
            <form method="POST" action="{{ route('dashboard.judges.store') }}" class="space-y-4"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf

                <x-ui.select name="organization_id" label="Organisasi" required>
                    <option value="">Pilih organisasi</option>
                    @foreach ($organizations as $organization)
                        <option value="{{ $organization->id }}" @selected((string) old('organization_id') === (string) $organization->id)>
                            {{ $organization->name }}
                        </option>
                    @endforeach
                </x-ui.select>

                <x-ui.input name="name" label="Nama Juri" required autofocus />
                <x-ui.textarea name="bio" label="Bio (opsional)" />

                <div class="flex items-center gap-3">
                    <x-ui.button type="submit">Tambah Juri</x-ui.button>
                    <a href="{{ route('dashboard.judges.index') }}" class="text-sm text-slate-500 hover:underline">Batal</a>
                </div>
            </form>
        @endif
    </div>
@endsection
