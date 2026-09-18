{{-- Snapshot roster. Add/remove only while the registration is editable
     (draft / revision_requested). Uses $registration + $event. --}}
@php($canManage = auth()->user()->can('updateStatus', $registration) && $registration->isEditable())

@if ($canManage)
    <x-ui.card class="mb-4">
        <form method="POST" action="{{ route('dashboard.registrations.members.store', $registration) }}"
              class="grid grid-cols-1 gap-3 sm:grid-cols-2"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            <x-ui.input name="name" label="Nama" required />
            <x-ui.input name="member_role" label="Peran (opsional)" placeholder="mis. Danton" />
            <x-ui.input name="gender" label="Gender (opsional)" placeholder="L / P" />
            <div class="flex items-end">
                <x-ui.button type="submit" class="w-full">Tambah Anggota</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endif

@if ($registration->members->isEmpty())
    <x-ui.empty-state title="Belum ada anggota roster." description="Roster di-snapshot saat ini — perubahan roster team nanti tidak mengubahnya." />
@else
    <x-ui.table>
        <x-slot:head>
            <tr>
                <x-ui.table-th>Nama</x-ui.table-th>
                <x-ui.table-th>Peran</x-ui.table-th>
                <x-ui.table-th>Gender</x-ui.table-th>
                @if ($canManage)<x-ui.table-th align="right">Aksi</x-ui.table-th>@endif
            </tr>
        </x-slot:head>
        @foreach ($registration->members as $member)
            <tr>
                <x-ui.table-td>{{ $member->name }}</x-ui.table-td>
                <x-ui.table-td muted>{{ $member->member_role ?: '—' }}</x-ui.table-td>
                <x-ui.table-td muted>{{ $member->gender ?: '—' }}</x-ui.table-td>
                @if ($canManage)
                    <x-ui.table-td align="right">
                        <form method="POST" action="{{ route('dashboard.registrations.members.destroy', [$registration, $member]) }}"
                              onsubmit="return confirm('Hapus anggota ini dari roster pendaftaran?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="cursor-pointer text-sm font-medium text-red-600 hover:underline">Hapus</button>
                        </form>
                    </x-ui.table-td>
                @endif
            </tr>
        @endforeach
    </x-ui.table>
@endif
