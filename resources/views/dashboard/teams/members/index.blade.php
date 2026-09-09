@extends('layouts.dashboard')

@section('title', 'Roster — ' . $team->name)

@section('content')
    <x-layout.breadcrumb :items="[
        ['label' => 'Teams', 'href' => route('dashboard.teams.index')],
        ['label' => $team->name, 'href' => route('dashboard.teams.show', $team)],
        ['label' => 'Roster'],
    ]" />

    <x-layout.page-header title="Roster {{ $team->name }}" />

    @can('manageMembers', $team)
        <div class="mb-6 rounded-lg border border-slate-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-medium text-slate-700">Tambah Anggota</h3>
            <form method="POST" action="{{ route('dashboard.teams.members.store', $team) }}"
                  class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Nama</label>
                    <input id="name" name="name" type="text" required
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="gender" class="block text-sm font-medium text-slate-700">Gender (opsional)</label>
                    <input id="gender" name="gender" type="text" placeholder="L/P"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                </div>

                <div>
                    <label for="member_role" class="block text-sm font-medium text-slate-700">Role (opsional)</label>
                    <input id="member_role" name="member_role" type="text" placeholder="mis. Danton"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 sm:text-sm">
                </div>

                <div class="flex items-end">
                    <button type="submit"
                            class="w-full rounded-md bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 disabled:opacity-50">
                        Tambah
                    </button>
                </div>
            </form>
        </div>
    @endcan

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Nama</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Gender</th>
                    <th class="px-4 py-2 text-left font-medium text-slate-500">Role</th>
                    @can('manageMembers', $team)
                        <th class="px-4 py-2 text-right font-medium text-slate-500">Aksi</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($members as $member)
                    <tr>
                        <td class="px-4 py-2 text-slate-800">{{ $member->name }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $member->gender ?: '—' }}</td>
                        <td class="px-4 py-2 text-slate-600">{{ $member->member_role ?: '—' }}</td>
                        @can('manageMembers', $team)
                            <td class="px-4 py-2 text-right">
                                <form method="POST" action="{{ route('dashboard.teams.members.destroy', [$team, $member]) }}"
                                      onsubmit="return confirm('Hapus anggota ini dari roster?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        @endcan
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada anggota di roster team ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
