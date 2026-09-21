{{-- Competition entries, grouped by category — numbers are isolated per
     category (fr-competition-entry.md Keputusan E). Entries themselves are
     never created here; they appear once a registration in that category is
     approved. Uses $event (categories.competitionEntries.team eager-loaded). --}}
@if ($event->categories->isEmpty())
    <x-ui.empty-state title="Belum ada kategori." description="Tambah kategori dulu di tab Kategori — entry kompetisi muncul otomatis begitu ada pendaftaran yang di-approve." />
@else
    <div class="space-y-6">
        @foreach ($event->categories as $category)
            <x-ui.card>
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-slate-800">{{ $category->name }}</h3>

                    @can('manageCompetition', $event)
                        <form method="POST" action="{{ route('dashboard.events.competition.numbers.generate', $event) }}"
                              class="flex flex-wrap items-end gap-2">
                            @csrf
                            <input type="hidden" name="event_category_id" value="{{ $category->id }}">
                            <input type="hidden" name="_tab" value="competition">
                            <div>
                                <label class="block text-xs text-slate-500">Prefix</label>
                                <input name="prefix" placeholder="A" maxlength="10"
                                       class="mt-0.5 w-16 rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500">Mulai</label>
                                <input name="start" type="number" min="1" value="1" required
                                       class="mt-0.5 w-16 rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500">Digit</label>
                                <input name="digits" type="number" min="1" max="6" value="2" required
                                       class="mt-0.5 w-16 rounded-md border border-slate-300 px-2 py-1.5 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                            </div>
                            <x-ui.button type="submit" size="sm" variant="secondary">Generate Nomor</x-ui.button>
                        </form>
                    @endcan
                </div>

                @if ($category->competitionEntries->isEmpty())
                    <p class="text-sm text-slate-400">Belum ada entry di kategori ini.</p>
                @else
                    <x-ui.table>
                        <x-slot:head>
                            <tr>
                                <x-ui.table-th>Nomor</x-ui.table-th>
                                <x-ui.table-th>Team</x-ui.table-th>
                                <x-ui.table-th>Status</x-ui.table-th>
                                @can('manageCompetition', $event)
                                    <x-ui.table-th align="right">Aksi</x-ui.table-th>
                                @endcan
                            </tr>
                        </x-slot:head>

                        @foreach ($category->competitionEntries as $entry)
                            <tr>
                                <x-ui.table-td>{{ $entry->competition_number ?: '—' }}</x-ui.table-td>
                                <x-ui.table-td>{{ $entry->team?->name ?? '—' }}</x-ui.table-td>
                                <x-ui.table-td><x-ui.status-badge :status="$entry->status" /></x-ui.table-td>
                                @can('manageCompetition', $event)
                                    <x-ui.table-td align="right">
                                        <div class="flex flex-wrap items-center justify-end gap-2">
                                            <form method="POST" action="{{ route('dashboard.competition-entries.number', $entry) }}" class="flex items-center gap-1">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="_tab" value="competition">
                                                <input name="competition_number" value="{{ $entry->competition_number }}" placeholder="Nomor" maxlength="50"
                                                       class="w-20 rounded-md border border-slate-300 px-2 py-1.5 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                                <button type="submit" class="cursor-pointer text-xs font-medium text-sky-700 hover:underline">Simpan</button>
                                            </form>
                                            <form method="POST" action="{{ route('dashboard.competition-entries.status', $entry) }}" class="flex items-center gap-1">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="_tab" value="competition">
                                                <select name="status" class="rounded-md border border-slate-300 px-2 py-1.5 text-xs shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                                    @foreach (\App\Enums\CompetitionEntryStatus::cases() as $case)
                                                        <option value="{{ $case->value }}" @selected($case === $entry->status)>{{ \Illuminate\Support\Str::headline($case->value) }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="cursor-pointer text-xs font-medium text-sky-700 hover:underline">Ubah</button>
                                            </form>
                                        </div>
                                    </x-ui.table-td>
                                @endcan
                            </tr>
                        @endforeach
                    </x-ui.table>
                @endif
            </x-ui.card>
        @endforeach
    </div>
@endif
