{{--
    Shared roster table. Read-only on the team detail "Anggota" tab; with a
    delete action on the dedicated roster management page.
    Params: $members (Collection<TeamMember>), $team (Team), $manageable (bool).
--}}
@php($manageable = $manageable ?? false)

@if ($members->isEmpty())
    <x-ui.empty-state
        title="Belum ada anggota di roster."
        description="Roster di-snapshot per pendaftaran event, jadi menambah/menghapus di sini tidak mengubah histori pendaftaran yang sudah lewat."
    />
@else
    <x-ui.table>
        <x-slot:head>
            <tr>
                <x-ui.table-th>Nama</x-ui.table-th>
                <x-ui.table-th>Gender</x-ui.table-th>
                <x-ui.table-th>Role</x-ui.table-th>
                @if ($manageable)
                    <x-ui.table-th align="right">Aksi</x-ui.table-th>
                @endif
            </tr>
        </x-slot:head>

        @foreach ($members as $member)
            <tr>
                <x-ui.table-td>{{ $member->name }}</x-ui.table-td>
                <x-ui.table-td muted>{{ $member->gender ?: '—' }}</x-ui.table-td>
                <x-ui.table-td muted>{{ $member->member_role ?: '—' }}</x-ui.table-td>
                @if ($manageable)
                    <x-ui.table-td align="right">
                        <form method="POST" action="{{ route('dashboard.teams.members.destroy', [$team, $member]) }}"
                              onsubmit="return confirm('Hapus anggota ini dari roster?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cursor-pointer text-sm font-medium text-red-600 hover:underline">Hapus</button>
                        </form>
                    </x-ui.table-td>
                @endif
            </tr>
        @endforeach
    </x-ui.table>
@endif
