{{-- Result Calculator + Ranking (fr-result-calculator.md) + lifecycle
     finalize/publish/reopen + Awards (fr-awards-finalization.md). Gated
     entirely behind manageResults — same sensitivity as Lembar Skor,
     'member' role sees nothing here at all, not even a read-only list.

     F11: added "Review Skor" (spec's [Review Scores] button — jumps to the
     Judging tab's existing per-round score sheet list rather than
     duplicating it here) and a scoped error message for the per-row penalty
     form (previously failed silently — no @error('penalty') anywhere, and
     since every result row has its own <form name="penalty">, a naive fix
     would leak one row's error onto every other row, same hazard as
     F7/F8/F9's multi-form isolation). --}}
@php($failedPenaltyResultId = old('_result_id'))
@can('manageResults', $event)
    @if ($event->categories->isEmpty())
        <x-ui.empty-state title="Belum ada kategori." description="Tambah kategori dulu sebelum ada hasil untuk dihitung." />
    @else
        <div class="space-y-6">
            @foreach ($event->categories as $category)
                @if ($category->judgingRounds->isNotEmpty())
                    <x-ui.card>
                        <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ $category->name }}</h3>

                        <div class="space-y-4">
                            @foreach ($category->judgingRounds as $round)
                                @php($roundStatus = $round->results->first()?->status?->value)
                                <div class="rounded-md border border-slate-200 p-3">
                                    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                            {{ $round->name }}
                                            @if ($roundStatus)
                                                <x-ui.status-badge :status="$roundStatus" />
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            <x-ui.button type="button" size="sm" variant="ghost" @click="tab = 'judging'">Review Skor Juri</x-ui.button>

                                            @if (! $roundStatus || $roundStatus === 'draft')
                                                <form method="POST" action="{{ route('dashboard.judging-rounds.results.calculate', $round) }}"
                                                      onsubmit="return confirm('Hitung ulang hasil round ini dari skor yang sudah disubmit/dikunci juri?');">
                                                    @csrf
                                                    <input type="hidden" name="_tab" value="results">
                                                    <x-ui.button type="submit" size="sm" variant="secondary">Hitung Ulang Hasil</x-ui.button>
                                                </form>
                                            @endif

                                            @if ($roundStatus === 'draft')
                                                <form method="POST" action="{{ route('dashboard.judging-rounds.results.finalize', $round) }}"
                                                      onsubmit="return confirm('Finalisasi hasil round ini? Hitung Ulang tidak bisa lagi sampai dibuka ulang.');">
                                                    @csrf
                                                    <input type="hidden" name="_tab" value="results">
                                                    <x-ui.button type="submit" size="sm">Finalize</x-ui.button>
                                                </form>
                                            @endif

                                            @if ($roundStatus === 'finalized')
                                                <form method="POST" action="{{ route('dashboard.judging-rounds.results.publish', $round) }}">
                                                    @csrf
                                                    <input type="hidden" name="_tab" value="results">
                                                    <x-ui.button type="submit" size="sm">Publish</x-ui.button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>

                                    @error('calculate')
                                        <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('finalize')
                                        <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('publish')
                                        <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    @error('reopen')
                                        <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
                                    @enderror

                                    @if ($round->results->isEmpty())
                                        <p class="text-xs text-slate-400">Belum ada hasil. Klik "Hitung Ulang Hasil" setelah juri submit/dikunci skornya.</p>
                                    @else
                                        <div class="overflow-x-auto">
                                            <table class="w-full text-sm">
                                                <thead>
                                                    <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-400">
                                                        <th class="py-2 pr-2">Rank</th>
                                                        <th class="py-2 pr-2">Peserta</th>
                                                        <th class="py-2 pr-2 text-right">Raw</th>
                                                        <th class="py-2 pr-2 text-right">Weighted</th>
                                                        <th class="py-2 pr-2 text-right">Penalti</th>
                                                        <th class="py-2 pr-2 text-right">Final</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($round->results as $result)
                                                        <tr class="border-b border-slate-100">
                                                            <td class="py-2 pr-2 font-medium text-slate-800">{{ $result->rank ?? '—' }}</td>
                                                            <td class="py-2 pr-2 text-slate-700">
                                                                {{ $result->entry?->competition_number ?? '—' }} · {{ $result->entry?->team?->name ?? '—' }}
                                                            </td>
                                                            <td class="py-2 pr-2 text-right text-slate-600">{{ $result->raw_score }}</td>
                                                            <td class="py-2 pr-2 text-right text-slate-600">{{ $result->weighted_score }}</td>
                                                            <td class="py-2 pr-2 text-right">
                                                                @if ($roundStatus === 'draft')
                                                                    @php($isFailedPenaltyRow = $failedPenaltyResultId !== null && (int) $failedPenaltyResultId === $result->id)
                                                                    <form method="POST" action="{{ route('dashboard.results.penalty', $result) }}" class="flex items-center justify-end gap-1">
                                                                        @csrf @method('PATCH')
                                                                        <input type="hidden" name="_result_id" value="{{ $result->id }}">
                                                                        <input type="hidden" name="_tab" value="results">
                                                                        <input type="number" name="penalty" step="0.001" min="0" value="{{ $result->penalty_score }}"
                                                                               class="w-20 rounded-md border text-right text-sm shadow-sm focus:ring-slate-500 {{ $isFailedPenaltyRow ? 'border-red-400 focus:border-red-500' : 'border-slate-300 focus:border-slate-500' }}">
                                                                        <button type="submit" class="cursor-pointer text-xs font-medium text-sky-700 hover:underline">Set</button>
                                                                    </form>
                                                                    @if ($isFailedPenaltyRow)
                                                                        @error('penalty')
                                                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                                                        @enderror
                                                                    @endif
                                                                @else
                                                                    {{ $result->penalty_score }}
                                                                @endif
                                                            </td>
                                                            <td class="py-2 pr-2 text-right font-medium text-slate-800">{{ $result->final_score }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        @if (in_array($roundStatus, ['finalized', 'published'], true))
                                            @php($canReopen = $roundStatus === 'finalized' || auth()->user()->can('reopenPublishedResult', $event))
                                            @if ($canReopen)
                                                <form method="POST" action="{{ route('dashboard.judging-rounds.results.reopen', $round) }}" class="mt-3 space-y-2">
                                                    @csrf
                                                    <input type="hidden" name="_tab" value="results">
                                                    <x-ui.textarea name="reason" label="Alasan Reopen (wajib)" rows="2" required />
                                                    <x-ui.button type="submit" variant="danger" size="sm">Buka Kembali untuk Dihitung Ulang</x-ui.button>
                                                </form>
                                            @else
                                                <p class="mt-3 text-xs text-slate-400">Hasil yang sudah dipublikasikan hanya bisa dibuka kembali oleh Owner organisasi.</p>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endif
            @endforeach
        </div>
    @endif

    {{-- Awards (fr-awards-finalization.md) --}}
    <x-ui.card class="mt-6">
        <h3 class="mb-3 text-sm font-semibold text-slate-800">Penghargaan</h3>

        @php($allEntries = $event->categories->flatMap(fn ($category) => $category->competitionEntries))

        @if ($event->awards->isEmpty())
            <p class="mb-4 text-xs text-slate-400">Belum ada penghargaan.</p>
        @else
            <ul class="mb-4 space-y-2 text-sm">
                @foreach ($event->awards as $award)
                    <li class="flex items-center justify-between gap-2 border-b border-slate-100 pb-2 last:border-0">
                        <span class="text-slate-700">
                            {{ $award->name }}
                            <span class="text-xs text-slate-400">
                                ({{ $award->award_type }}
                                @if ($award->category) · {{ $award->category->name }} @endif
                                @if ($award->entry) · {{ $award->entry->team?->name }} @endif)
                            </span>
                        </span>
                        <form method="POST" action="{{ route('dashboard.awards.destroy', $award) }}"
                              onsubmit="return confirm('Hapus penghargaan ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="cursor-pointer text-xs text-red-600 hover:underline">Hapus</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('dashboard.events.awards.store', $event) }}"
              class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4"
              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
            @csrf
            <input type="hidden" name="_tab" value="results">
            <x-ui.input name="name" label="Nama Penghargaan" placeholder="mis. Juara 1" required />
            <x-ui.input name="award_type" label="Jenis" placeholder="mis. ranking, special" required />
            <x-ui.select name="event_category_id" label="Kategori (opsional)">
                <option value="">— Seluruh event —</option>
                @foreach ($event->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="competition_entry_id" label="Peserta (opsional)">
                <option value="">— Tidak ada —</option>
                @foreach ($allEntries as $entry)
                    <option value="{{ $entry->id }}">{{ $entry->competition_number ?? '—' }} · {{ $entry->team?->name }}</option>
                @endforeach
            </x-ui.select>
            <div class="sm:col-span-2 lg:col-span-4">
                <x-ui.button type="submit" size="sm">Tambah Penghargaan</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@else
    <x-ui.empty-state title="Tidak ada akses." description="Hasil hanya bisa dilihat oleh Owner/Admin organisasi." />
@endcan
