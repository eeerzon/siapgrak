{{-- Event staff: add + list + remove. Role is recorded but not yet an
     authorization source (fr-event-category.md Keputusan A). Role-change UI
     comes later (endpoint exists). Uses $event and $eligibleStaffUsers. --}}

@can('manageStaff', $event)
    <x-ui.card class="mb-6">
        <h2 class="mb-3 text-sm font-medium text-slate-700">Tambah Staff</h2>
        @if ($eligibleStaffUsers->isEmpty())
            <p class="text-sm text-slate-500">
                Semua anggota aktif organisasi sudah menjadi staff event ini. Tambah anggota baru lewat
                <a href="{{ route('dashboard.organizations.members.index', $event->organization) }}" class="font-medium text-sky-700 underline">Kelola Anggota</a>.
            </p>
        @else
            <form method="POST" action="{{ route('dashboard.events.staff.store', $event) }}"
                  class="grid grid-cols-1 gap-3 sm:grid-cols-3"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <input type="hidden" name="_tab" value="staff">
                <x-ui.select name="user_id" label="Anggota" required>
                    <option value="">Pilih anggota</option>
                    @foreach ($eligibleStaffUsers as $user)
                        <option value="{{ $user->id_user }}" @selected((string) old('user_id') === (string) $user->id_user)>{{ $user->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="role" label="Peran" required>
                    <option value="">Pilih peran</option>
                    @foreach (\App\Enums\EventStaffRole::cases() as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ \Illuminate\Support\Str::headline($role->value) }}</option>
                    @endforeach
                </x-ui.select>
                <div class="flex items-end">
                    <x-ui.button type="submit" class="w-full">Tambah Staff</x-ui.button>
                </div>
            </form>
        @endif
    </x-ui.card>
@endcan

@if ($event->staff->isEmpty())
    <x-ui.empty-state
        title="Belum ada staff event."
        description="Staff hanya bisa diambil dari anggota aktif organisasi penyelenggara."
    />
@else
    <x-ui.table>
        <x-slot:head>
            <tr>
                <x-ui.table-th>Nama</x-ui.table-th>
                <x-ui.table-th>Peran</x-ui.table-th>
                @can('manageStaff', $event)
                    <x-ui.table-th align="right">Aksi</x-ui.table-th>
                @endcan
            </tr>
        </x-slot:head>

        @foreach ($event->staff as $member)
            <tr>
                <x-ui.table-td>{{ $member->user?->name ?? '—' }}</x-ui.table-td>
                <x-ui.table-td muted>{{ \Illuminate\Support\Str::headline($member->role->value) }}</x-ui.table-td>
                @can('manageStaff', $event)
                    <x-ui.table-td align="right">
                        <form method="POST" action="{{ route('dashboard.events.staff.destroy', [$event, $member]) }}"
                              onsubmit="return confirm('Hapus staff ini dari event?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cursor-pointer text-sm font-medium text-red-600 hover:underline">Hapus</button>
                        </form>
                    </x-ui.table-td>
                @endcan
            </tr>
        @endforeach
    </x-ui.table>
@endif
