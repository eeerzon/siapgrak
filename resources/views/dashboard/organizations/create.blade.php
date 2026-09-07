@extends('layouts.dashboard')

@section('title', 'Buat Organisasi')

@section('content')
    <div class="mx-auto max-w-xl">
        <h2 class="mb-1 text-lg font-semibold text-slate-800">Buat Organisasi</h2>
        <p class="mb-6 text-sm text-slate-500">Organisasi adalah wadah penyelenggara event LKBB/Paskibra Anda (mis. FORBASI Kota Bandung). Anda otomatis jadi owner.</p>

        <form method="POST" action="{{ route('dashboard.organizations.store') }}" class="space-y-4"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">Nama Organisasi</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-slate-700">Tipe</label>
                <select id="type" name="type" required
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                    <option value="">Pilih tipe organisasi</option>
                    @foreach (\App\Enums\OrganizationType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ ucfirst($type->value) }}</option>
                    @endforeach
                </select>
                @error('type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-slate-700">Deskripsi (opsional)</label>
                <textarea id="description" name="description" rows="3"
                          class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">Email (opsional)</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700">Telepon (opsional)</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone') }}"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit"
                        class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-50">
                    Buat Organisasi
                </button>
                <a href="{{ route('dashboard.organizations.index') }}" class="text-sm text-slate-500 hover:underline">Batal</a>
            </div>
        </form>
    </div>
@endsection
