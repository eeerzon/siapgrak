@extends('layouts.dashboard')

@section('title', 'Edit ' . $judge->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Juri', 'href' => route('dashboard.judges.index')],
        ['label' => $judge->name, 'href' => route('dashboard.judges.show', $judge)],
        ['label' => 'Edit'],
    ]" />

    <x-layout.page-header
        title="Edit Juri"
        :description="'Organisasi: ' . ($judge->organization?->name ?? '—') . ' — tidak bisa dipindah di sini.'"
    />

    <div class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('dashboard.judges.update', $judge) }}" class="space-y-4"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            @method('PUT')

            <x-ui.input name="name" label="Nama Juri" :value="$judge->name" required autofocus />
            <x-ui.textarea name="bio" label="Bio (opsional)" :value="$judge->bio" />

            <div class="flex items-center gap-3">
                <x-ui.button type="submit">Simpan Perubahan</x-ui.button>
                <a href="{{ route('dashboard.judges.show', $judge) }}" class="text-sm text-slate-500 hover:underline">Batal</a>
            </div>
        </form>
    </div>
@endsection
