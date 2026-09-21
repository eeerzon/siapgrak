{{-- Linear schedule list (fr-schedule.md — V1 list, drag-drop belakangan).
     Slots are pure logistics: hard delete allowed, no "locked after submit"
     rule like Registration/CompetitionEntry. Uses $event
     (scheduleSlots.category / scheduleSlots.entry.team eager-loaded).

     F8: added the missing "Edit Slot" action (update() already existed
     server-side, there was just no UI for it — same gap class as F7
     Categories). Edit lives in a drawer, one form per slot; the inline
     Add-Slot form is unchanged. Both share the page with Laravel's global
     old()/$errors-by-field-name bag, so the same isolation fix as F7
     applies here too — see $keyFor below. --}}
@php
    $failedSlotId = old('_slot_id');
    $keyFor = function (string $field, ?int $slotId) use ($failedSlotId) {
        $isThisForm = $slotId === null
            ? $failedSlotId === ''
            : ($failedSlotId !== null && $failedSlotId !== '' && (int) $failedSlotId === $slotId);

        return $isThisForm ? $field : "suppressed_{$field}_".($slotId ?? 'create');
    };
    $editFailed = $failedSlotId !== null && $failedSlotId !== '';
@endphp

<div x-data="{ open: {{ $editFailed ? 'true' : 'false' }}, editing: {{ $editFailed ? (int) $failedSlotId : 'null' }} }">
    @can('manageSchedule', $event)
        <x-ui.card class="mb-6">
            <h2 class="mb-3 text-sm font-medium text-slate-700">Tambah Slot</h2>
            <form method="POST" action="{{ route('dashboard.events.schedule.store', $event) }}"
                  class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4"
                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                @csrf
                <input type="hidden" name="_slot_id" value="">
                <input type="hidden" name="_tab" value="schedule">
                <x-ui.input name="title" label="Judul (opsional)" placeholder="mis. Opening Ceremony" :value="null" :errorKey="$keyFor('title', null)" />
                <x-ui.select name="slot_type" label="Jenis" required :errorKey="$keyFor('slot_type', null)">
                    @foreach (\App\Enums\ScheduleSlotType::cases() as $case)
                        <option value="{{ $case->value }}">{{ \Illuminate\Support\Str::headline($case->value) }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="event_category_id" label="Kategori (opsional)" :errorKey="$keyFor('event_category_id', null)">
                    <option value="">— Umum —</option>
                    @foreach ($event->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="venue_area" label="Area/Venue (opsional)" placeholder="mis. Panggung Utama" :value="null" :errorKey="$keyFor('venue_area', null)" />
                <x-ui.input name="start_at" label="Mulai" type="datetime-local" required :value="null" :errorKey="$keyFor('start_at', null)" />
                <x-ui.input name="end_at" label="Selesai (opsional)" type="datetime-local" :value="null" :errorKey="$keyFor('end_at', null)" />
                <div class="flex items-end lg:col-span-2">
                    <x-ui.button type="submit" class="w-full sm:w-auto">Tambah Slot</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endcan

    @if ($event->scheduleSlots->isEmpty())
        <x-ui.empty-state title="Belum ada jadwal." description="Tambah slot untuk menyusun rundown hari-H — mulai dari technical meeting, opening ceremony, sampai urutan tampil per kategori." />
    @else
        <div class="space-y-2">
            @foreach ($event->scheduleSlots as $slot)
                <x-ui.card class="!p-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="w-36 shrink-0 text-sm font-medium text-slate-700">
                                {{ $slot->start_at->format('H:i') }}
                                @if ($slot->end_at)
                                    – {{ $slot->end_at->format('H:i') }}
                                @endif
                            </div>
                            <div>
                                <div class="text-sm font-medium text-slate-800">{{ $slot->title ?: \Illuminate\Support\Str::headline($slot->slot_type->value) }}</div>
                                <div class="text-xs text-slate-500">
                                    <x-ui.status-badge :status="$slot->slot_type" />
                                    @if ($slot->category)
                                        <span class="ml-1">· {{ $slot->category->name }}</span>
                                    @endif
                                    @if ($slot->entry?->team)
                                        <span class="ml-1">· {{ $slot->entry->team->name }}</span>
                                    @endif
                                    @if ($slot->venue_area)
                                        <span class="ml-1">· {{ $slot->venue_area }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @can('manageSchedule', $event)
                            {{-- p-2 (not p-1) — F12 audit: these are the buttons most likely
                                 tapped repeatedly on a phone during live event-day reordering,
                                 p-1 sat well under a comfortable touch-target size. --}}
                            <div class="flex items-center gap-1">
                                <button type="button" @click="open = true; editing = {{ $slot->id }}" class="cursor-pointer rounded p-2 text-sm font-medium text-sky-700 hover:underline" aria-label="Edit slot">Edit</button>
                                <form method="POST" action="{{ route('dashboard.events.schedule.move-up', [$event, $slot]) }}">
                                    @csrf
                                    <button type="submit" class="cursor-pointer rounded p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Naikkan urutan">↑</button>
                                </form>
                                <form method="POST" action="{{ route('dashboard.events.schedule.move-down', [$event, $slot]) }}">
                                    @csrf
                                    <button type="submit" class="cursor-pointer rounded p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Turunkan urutan">↓</button>
                                </form>
                                <form method="POST" action="{{ route('dashboard.events.schedule.destroy', [$event, $slot]) }}"
                                      onsubmit="return confirm('Hapus slot jadwal ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="cursor-pointer rounded p-2 text-red-500 hover:bg-red-50" aria-label="Hapus">✕</button>
                                </form>
                            </div>
                        @endcan
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @endif

    @can('manageSchedule', $event)
        <x-ui.drawer title="Edit Slot Jadwal">
            @foreach ($event->scheduleSlots as $slot)
                <form x-show="editing === {{ $slot->id }}" x-cloak method="POST"
                      action="{{ route('dashboard.events.schedule.update', [$event, $slot]) }}"
                      class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_slot_id" value="{{ $slot->id }}">
                    <input type="hidden" name="_tab" value="schedule">
                    <x-ui.input name="title" label="Judul (opsional)" :value="$slot->title" :errorKey="$keyFor('title', $slot->id)" />
                    <x-ui.select name="slot_type" label="Jenis" required :errorKey="$keyFor('slot_type', $slot->id)">
                        @foreach (\App\Enums\ScheduleSlotType::cases() as $case)
                            <option value="{{ $case->value }}" @selected($slot->slot_type->value === $case->value)>{{ \Illuminate\Support\Str::headline($case->value) }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.select name="event_category_id" label="Kategori (opsional)" :errorKey="$keyFor('event_category_id', $slot->id)">
                        <option value="">— Umum —</option>
                        @foreach ($event->categories as $category)
                            <option value="{{ $category->id }}" @selected($slot->event_category_id === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.input name="venue_area" label="Area/Venue (opsional)" :value="$slot->venue_area" :errorKey="$keyFor('venue_area', $slot->id)" />
                    <x-ui.input name="start_at" label="Mulai" type="datetime-local" required :value="$slot->start_at->format('Y-m-d\TH:i')" :errorKey="$keyFor('start_at', $slot->id)" />
                    <x-ui.input name="end_at" label="Selesai (opsional)" type="datetime-local" :value="$slot->end_at?->format('Y-m-d\TH:i')" :errorKey="$keyFor('end_at', $slot->id)" />
                    <x-ui.button type="submit" class="w-full">Simpan Perubahan</x-ui.button>
                </form>
            @endforeach
        </x-ui.drawer>
    @endcan
</div>
