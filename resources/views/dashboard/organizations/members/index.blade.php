@extends('layouts.dashboard')

@section('title', 'Anggota — ' . $organization->name)

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-slate-800">Anggota {{ $organization->name }}</h2>
            <a href="{{ route('dashboard.organizations.show', $organization) }}" class="text-sm text-slate-500 hover:underline">&larr; Kembali ke organisasi</a>
        </div>
    </div>

    <div class="mb-6 rounded-lg border border-slate-200 bg-white p-4">
        <h3 class="mb-3 text-sm font-medium text-slate-700">Tambah Anggota</h3>
        <form method="POST" action="{{ route('dashboard.organizations.members.store', $organization) }}"
              class="flex flex-wrap items-end gap-3" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf

            <div class="flex-1 min-w-[200px]">
                <label for="email" class="block text-sm font-medium text-slate-700">Email User</label>
                <input id="email" name="email" type="email" required placeholder="user@example.com"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-slate-700">Role</label>
                <select id="role" name="role" required
                        class="mt-1 block rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                    @foreach (\App\Enums\OrganizationRole::cases() as $role)
                        <option value="{{ $role->value }}">{{ ucfirst($role->value) }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-50">
                Tambah
            </button>
        </form>
        <p class="mt-2 text-xs text-slate-400">User harus sudah punya akun Siapgrak (belum ada undangan via email di sprint ini).</p>
    </div>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Nama</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Email</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Role</th>
                    <th class="px-4 py-2 text-right font-medium text-slate-500">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($memberships as $membership)
                    <tr>
                        <td class="px-4 py-2 text-slate-800">{{ $membership->user->name }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $membership->user->email }}</td>
                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('dashboard.organizations.members.update', [$organization, $membership]) }}"
                                  class="inline-flex items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <select name="role" onchange="this.form.submit()"
                                        class="rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                    @foreach (\App\Enums\OrganizationRole::cases() as $role)
                                        <option value="{{ $role->value }}" @selected($membership->role === $role)>{{ ucfirst($role->value) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('dashboard.organizations.members.destroy', [$organization, $membership]) }}"
                                  onsubmit="return confirm('Hapus anggota ini dari organisasi?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
