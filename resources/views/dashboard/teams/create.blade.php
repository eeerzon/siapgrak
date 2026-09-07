@extends('layouts.dashboard')

@section('title', 'Buat Team')

@section('content')
    <div class="mx-auto max-w-xl">
        <h2 class="mb-1 text-lg font-semibold text-slate-800">Buat Team</h2>
        <p class="mb-6 text-sm text-slate-500">Team adalah identitas kompetisi (mis. "Paskibra Utama"), reusable untuk event berikutnya.</p>

        @if ($organizations->isEmpty())
            <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Anda belum tergabung di organisasi manapun. <a href="{{ route('dashboard.organizations.create') }}" class="font-medium underline">Buat organisasi dulu</a> sebelum membuat team.
            </div>
        @else
            <form method="POST" action="{{ route('dashboard.teams.store') }}" class="space-y-4"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf

                <div>
                    <label for="organization_id" class="block text-sm font-medium text-slate-700">Organisasi Pemilik</label>
                    <select id="organization_id" name="organization_id" required
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                        <option value="">Pilih organisasi</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}" @selected((string) old('organization_id') === (string) $organization->id)>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('organization_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama Team</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="short_name" class="block text-sm font-medium text-slate-700">Nama Singkat (opsional)</label>
                    <input id="short_name" name="short_name" type="text" value="{{ old('short_name') }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="province_code" class="block text-sm font-medium text-slate-700">Provinsi (opsional)</label>
                        <input id="province_code" name="province_code" type="text" value="{{ old('province_code') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="city_code" class="block text-sm font-medium text-slate-700">Kota (opsional)</label>
                        <input id="city_code" name="city_code" type="text" value="{{ old('city_code') }}"
                               class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">Deskripsi (opsional)</label>
                    <textarea id="description" name="description" rows="3"
                              class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">{{ old('description') }}</textarea>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-50">
                        Buat Team
                    </button>
                    <a href="{{ route('dashboard.teams.index') }}" class="text-sm text-slate-500 hover:underline">Batal</a>
                </div>
            </form>
        @endif
    </div>
@endsection
