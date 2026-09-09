@extends('layouts.dashboard')

@section('title', 'Edit ' . $team->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Teams', 'href' => route('dashboard.teams.index')],
        ['label' => $team->name, 'href' => route('dashboard.teams.show', $team)],
        ['label' => 'Edit'],
    ]" />

    <x-layout.page-header title="Edit Team" :description="'Organisasi pemilik: '.($team->organization?->name ?? '—').' (tidak bisa diubah di sini).'" />

    <div class="mx-auto max-w-xl">
        <form method="POST" action="{{ route('dashboard.teams.update', $team) }}" class="space-y-4"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Nama Team</label>
                <input id="name" name="name" type="text" value="{{ old('name', $team->name) }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-slate-700">Slug</label>
                <input id="slug" name="slug" type="text" value="{{ old('slug', $team->slug) }}" required
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                @error('slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="short_name" class="block text-sm font-medium text-slate-700">Nama Singkat</label>
                <input id="short_name" name="short_name" type="text" value="{{ old('short_name', $team->short_name) }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="province_code" class="block text-sm font-medium text-slate-700">Provinsi</label>
                    <input id="province_code" name="province_code" type="text" value="{{ old('province_code', $team->province_code) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                </div>
                <div>
                    <label for="city_code" class="block text-sm font-medium text-slate-700">Kota</label>
                    <input id="city_code" name="city_code" type="text" value="{{ old('city_code', $team->city_code) }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-700">Deskripsi</label>
                <textarea id="description" name="description" rows="3"
                          class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">{{ old('description', $team->description) }}</textarea>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-50">
                    Simpan Perubahan
                </button>
                <a href="{{ route('dashboard.teams.show', $team) }}" class="text-sm text-slate-500 hover:underline">Batal</a>
            </div>
        </form>
    </div>
@endsection
