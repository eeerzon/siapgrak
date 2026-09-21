{{-- Judging setup: rounds + criteria per category (fr-judging-setup.md),
     judge assignment (fr-judge-assignment.md), score sheet status
     (fr-scoring.md, fr-score-sheet-lifecycle.md). Uses $event
     (categories.judgingRounds.{criteria,assignments,scoreSheets} eager-loaded)
     and $judgingReadiness (per-category checklist, computed in EventController).

     F9: criteria went from a plain nested <ul> to a card grid + drawer
     add/edit (frontend-plan.md §Judging Setup — "bukan tampilan
     spreadsheet"). Rounds/Assignments/Score Sheets below are unchanged from
     Sprint 7-13 — not in F9's scope.

     One drawer serves every round's "add criteria" form AND every existing
     criterion's "edit" form, all sharing this one page's global old()/
     $errors bag by field name — same isolation hazard as F7/F8, same
     errorKey fix, just with an extra discriminator (_round_id) since a page
     can have several rounds each with their own create-criteria form (F7/F8
     only ever had one create form total). --}}
@php
    $failedCriterionId = old('_criterion_id');
    $failedRoundId = old('_round_id');
    $criteriaKeyFor = function (string $field, ?int $criterionId, ?int $roundIdForCreate = null) use ($failedCriterionId, $failedRoundId) {
        $isThisForm = $criterionId !== null
            ? ($failedCriterionId !== null && $failedCriterionId !== '' && (int) $failedCriterionId === $criterionId)
            : ($failedCriterionId === '' && $failedRoundId !== null && (int) $failedRoundId === $roundIdForCreate);

        return $isThisForm ? $field : "suppressed_{$field}_".($criterionId ?? "create_round_{$roundIdForCreate}");
    };
    $failedEditingCriterionId = $failedCriterionId !== null && $failedCriterionId !== '' ? (int) $failedCriterionId : null;
    $failedCreateRoundId = $failedCriterionId === '' && $failedRoundId !== null ? (int) $failedRoundId : null;
@endphp

<div x-data="{ open: {{ $failedEditingCriterionId || $failedCreateRoundId ? 'true' : 'false' }}, editing: {{ $failedEditingCriterionId ? $failedEditingCriterionId : ($failedCreateRoundId ? "'round_{$failedCreateRoundId}'" : 'null') }} }">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-slate-500">Direktori juri dikelola terpisah dari organisasi Anda.</p>
        <a href="{{ route('dashboard.judges.index') }}" class="text-sm font-medium text-sky-700 hover:underline">Kelola Juri Organisasi →</a>
    </div>

    @if ($event->categories->isEmpty())
        <x-ui.empty-state title="Belum ada kategori." description="Tambah kategori dulu di tab Kategori sebelum menyusun round dan kriteria penilaian." />
    @else
        <div class="space-y-6">
            @foreach ($event->categories as $category)
                <x-ui.card>
                    <h3 class="text-sm font-semibold text-slate-800">{{ $category->name }}</h3>

                    {{-- Readiness checklist (F9) --}}
                    <div class="mt-1 mb-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                        @foreach ($judgingReadiness[$category->id] as $item)
                            <span class="flex items-center gap-1 {{ $item['ok'] ? 'text-emerald-700' : 'text-amber-600' }}">
                                <span aria-hidden="true">{{ $item['ok'] ? '✓' : '!' }}</span> {{ $item['label'] }}
                            </span>
                        @endforeach
                    </div>

                    @can('manageJudging', $event)
                        <form method="POST" action="{{ route('dashboard.events.categories.rounds.store', [$event, $category]) }}"
                              class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-4"
                              onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                            @csrf
                            <input type="hidden" name="_tab" value="judging">
                            <x-ui.input name="name" label="Nama Round" placeholder="mis. Final" required />
                            <x-ui.input name="code" label="Kode (opsional)" />
                            <x-ui.input name="weight" label="Bobot % (opsional)" type="number" step="0.01" min="0" max="100" />
                            <div class="flex items-end">
                                <x-ui.button type="submit" size="sm" class="w-full">Tambah Round</x-ui.button>
                            </div>
                        </form>
                    @endcan

                    @if ($category->judgingRounds->isEmpty())
                        <p class="text-sm text-slate-400">Belum ada round penilaian di kategori ini.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($category->judgingRounds as $round)
                                <div class="rounded-md border border-slate-200 p-3">
                                    <div class="mb-2 flex items-center justify-between">
                                        <div class="text-sm font-medium text-slate-700">
                                            {{ $round->name }}
                                            @if ($round->weight)
                                                <span class="text-slate-400">· {{ $round->weight }}%</span>
                                            @endif
                                        </div>
                                        @can('manageJudging', $event)
                                            <form method="POST" action="{{ route('dashboard.judging-rounds.destroy', $round) }}"
                                                  onsubmit="return confirm('Hapus round ini beserta semua kriterianya?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="cursor-pointer text-xs font-medium text-red-600 hover:underline">Hapus Round</button>
                                            </form>
                                        @endcan
                                    </div>

                                    {{-- Criteria: card grid, weight + subcriteria per card (F9) --}}
                                    @php($rootCriteria = $round->criteria->whereNull('parent_id'))
                                    <div class="mb-3 flex items-center justify-between">
                                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Kriteria</h4>
                                        @can('manageJudging', $event)
                                            <button type="button" @click="open = true; editing = 'round_{{ $round->id }}'" class="cursor-pointer text-xs font-medium text-sky-700 hover:underline">+ Tambah Kriteria</button>
                                        @endcan
                                    </div>

                                    @if ($rootCriteria->isEmpty())
                                        <p class="mb-3 text-xs text-slate-400">Belum ada kriteria.</p>
                                    @else
                                        <div class="mb-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach ($rootCriteria as $criterion)
                                                <div class="rounded-md border border-slate-200 p-3">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <div>
                                                            <p class="text-sm font-medium text-slate-800">{{ $criterion->name }}</p>
                                                            <p class="text-xs text-slate-400">{{ $criterion->minimum_score }}–{{ $criterion->maximum_score }}@if ($criterion->weight) · {{ $criterion->weight }}%@endif</p>
                                                        </div>
                                                        @can('manageJudging', $event)
                                                            <div class="flex shrink-0 items-center gap-2 text-xs">
                                                                <button type="button" @click="open = true; editing = {{ $criterion->id }}" class="cursor-pointer font-medium text-sky-700 hover:underline">Edit</button>
                                                                <form method="POST" action="{{ route('dashboard.judging-criteria.destroy', $criterion) }}"
                                                                      onsubmit="return confirm('Hapus kriteria ini?');">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="cursor-pointer text-red-600 hover:underline">Hapus</button>
                                                                </form>
                                                            </div>
                                                        @endcan
                                                    </div>

                                                    @if ($criterion->children->isNotEmpty())
                                                        <ul class="mt-2 space-y-1 border-t border-slate-100 pt-2">
                                                            @foreach ($criterion->children as $child)
                                                                <li class="flex items-center justify-between gap-2 text-xs">
                                                                    <span class="text-slate-600">
                                                                        {{ $child->name }}
                                                                        <span class="text-slate-400">({{ $child->minimum_score }}–{{ $child->maximum_score }}@if ($child->weight) · {{ $child->weight }}%@endif)</span>
                                                                    </span>
                                                                    @can('manageJudging', $event)
                                                                        <span class="flex shrink-0 items-center gap-2">
                                                                            <button type="button" @click="open = true; editing = {{ $child->id }}" class="cursor-pointer font-medium text-sky-700 hover:underline">Edit</button>
                                                                            <form method="POST" action="{{ route('dashboard.judging-criteria.destroy', $child) }}"
                                                                                  onsubmit="return confirm('Hapus kriteria ini?');">
                                                                                @csrf @method('DELETE')
                                                                                <button type="submit" class="cursor-pointer text-red-600 hover:underline">Hapus</button>
                                                                            </form>
                                                                        </span>
                                                                    @endcan
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Juri ditugaskan — criteria_id null berarti seluruh round (fr-judge-assignment.md). --}}
                                    <div class="border-t border-slate-100 pt-3">
                                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Juri Ditugaskan</h4>

                                        @if ($round->assignments->isEmpty())
                                            <p class="text-xs text-slate-400">Belum ada juri ditugaskan di round ini.</p>
                                        @else
                                            <ul class="mb-3 space-y-1 text-sm">
                                                @foreach ($round->assignments as $assignment)
                                                    <li class="flex items-center justify-between gap-2">
                                                        <span class="text-slate-700">
                                                            {{ $assignment->judge?->name ?? '—' }}
                                                            <span class="text-xs text-slate-400">
                                                                ({{ $assignment->judging_criteria_id ? ($round->criteria->firstWhere('id', $assignment->judging_criteria_id)?->name ?? '—') : 'Seluruh round' }})
                                                            </span>
                                                        </span>
                                                        @can('manageJudging', $event)
                                                            <form method="POST" action="{{ route('dashboard.judge-assignments.destroy', $assignment) }}"
                                                                  onsubmit="return confirm('Lepas penugasan juri ini?');">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="cursor-pointer text-xs text-red-600 hover:underline">Lepas</button>
                                                            </form>
                                                        @endcan
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        @can('manageJudging', $event)
                                            <form method="POST" action="{{ route('dashboard.judging-rounds.assignments.store', $round) }}"
                                                  class="grid grid-cols-1 gap-2 sm:grid-cols-3"
                                                  onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                                                @csrf
                                                <input type="hidden" name="_tab" value="judging">
                                                <x-ui.select name="judge_id" label="Juri" required>
                                                    <option value="">Pilih juri</option>
                                                    @foreach ($organizationJudges as $judge)
                                                        <option value="{{ $judge->id }}">{{ $judge->name }}</option>
                                                    @endforeach
                                                </x-ui.select>
                                                <x-ui.select name="judging_criteria_id" label="Cakupan">
                                                    <option value="">— Seluruh round —</option>
                                                    @foreach ($rootCriteria as $criterion)
                                                        <option value="{{ $criterion->id }}">{{ $criterion->name }}</option>
                                                        @foreach ($criterion->children as $child)
                                                            <option value="{{ $child->id }}">— {{ $child->name }}</option>
                                                        @endforeach
                                                    @endforeach
                                                </x-ui.select>
                                                <div class="flex items-end">
                                                    <x-ui.button type="submit" size="sm" class="w-full">Tugaskan Juri</x-ui.button>
                                                </div>
                                            </form>
                                        @endcan
                                    </div>

                                    {{-- Lembar Skor — cuma status, bukan angka (fr-score-sheet-lifecycle.md
                                         Keputusan E/F). Angka per kriteria baru terlihat di halaman detail
                                         satu sheet. Seluruh seksi ini di-gate manageJudging, tidak ada akses
                                         member biasa sama sekali (beda dari Round/Criteria di atas). --}}
                                    @can('manageJudging', $event)
                                        <div class="mt-3 border-t border-slate-100 pt-3">
                                            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Lembar Skor</h4>

                                            @if ($round->scoreSheets->isEmpty())
                                                <p class="text-xs text-slate-400">Belum ada juri yang mulai menilai di round ini.</p>
                                            @else
                                                <ul class="space-y-1 text-sm">
                                                    @foreach ($round->scoreSheets as $sheet)
                                                        <li class="flex items-center justify-between gap-2">
                                                            <span class="text-slate-700">
                                                                {{ $sheet->judge?->name ?? '—' }}
                                                                <span class="text-xs text-slate-400">
                                                                    — {{ $sheet->entry?->competition_number ?? $sheet->entry?->team?->name ?? '—' }}
                                                                </span>
                                                            </span>
                                                            <span class="flex items-center gap-2">
                                                                <x-ui.status-badge :status="$sheet->status" />
                                                                <a href="{{ route('dashboard.score-sheets.show', $sheet) }}" class="text-xs font-medium text-sky-700 hover:underline">Detail →</a>
                                                            </span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </div>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    @endif

    @can('manageJudging', $event)
        <x-ui.drawer title="Kriteria Penilaian">
            @foreach ($event->categories as $category)
                @foreach ($category->judgingRounds as $round)
                    {{-- Add criteria for this round --}}
                    <form x-show="editing === 'round_{{ $round->id }}'" x-cloak method="POST"
                          action="{{ route('dashboard.judging-rounds.criteria.store', $round) }}"
                          class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                        @csrf
                        <input type="hidden" name="_criterion_id" value="">
                        <input type="hidden" name="_round_id" value="{{ $round->id }}">
                        <input type="hidden" name="_tab" value="judging">
                        <p class="text-sm text-slate-500">{{ $category->name }} — {{ $round->name }}</p>
                        <x-ui.input name="name" label="Nama Kriteria" required :value="null" :errorKey="$criteriaKeyFor('name', null, $round->id)" />
                        <x-ui.textarea name="description" label="Deskripsi (opsional)" :value="null" :errorKey="$criteriaKeyFor('description', null, $round->id)" />
                        <x-ui.input name="weight" label="Bobot % (opsional)" type="number" step="0.01" min="0" max="100" :value="null" :errorKey="$criteriaKeyFor('weight', null, $round->id)" />
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <x-ui.input name="minimum_score" label="Min" type="number" step="0.001" required :value="null" :errorKey="$criteriaKeyFor('minimum_score', null, $round->id)" />
                            <x-ui.input name="maximum_score" label="Max" type="number" step="0.001" required :value="null" :errorKey="$criteriaKeyFor('maximum_score', null, $round->id)" />
                        </div>
                        <x-ui.select name="parent_id" label="Sub dari (opsional)" :errorKey="$criteriaKeyFor('parent_id', null, $round->id)">
                            <option value="">— Kriteria utama —</option>
                            @foreach ($round->criteria->whereNull('parent_id') as $possibleParent)
                                <option value="{{ $possibleParent->id }}">{{ $possibleParent->name }}</option>
                            @endforeach
                        </x-ui.select>
                        <x-ui.button type="submit" class="w-full">Tambah Kriteria</x-ui.button>
                    </form>

                    {{-- Edit forms — one per existing criterion in this round --}}
                    @foreach ($round->criteria as $criterion)
                        <form x-show="editing === {{ $criterion->id }}" x-cloak method="POST"
                              action="{{ route('dashboard.judging-criteria.update', $criterion) }}"
                              class="space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true;">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_criterion_id" value="{{ $criterion->id }}">
                            <input type="hidden" name="_tab" value="judging">
                            <p class="text-sm text-slate-500">{{ $category->name }} — {{ $round->name }}</p>
                            <x-ui.input name="name" label="Nama Kriteria" required :value="$criterion->name" :errorKey="$criteriaKeyFor('name', $criterion->id)" />
                            <x-ui.textarea name="description" label="Deskripsi (opsional)" :value="$criterion->description" :errorKey="$criteriaKeyFor('description', $criterion->id)" />
                            <x-ui.input name="weight" label="Bobot % (opsional)" type="number" step="0.01" min="0" max="100" :value="$criterion->weight" :errorKey="$criteriaKeyFor('weight', $criterion->id)" />
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <x-ui.input name="minimum_score" label="Min" type="number" step="0.001" required :value="$criterion->minimum_score" :errorKey="$criteriaKeyFor('minimum_score', $criterion->id)" />
                                <x-ui.input name="maximum_score" label="Max" type="number" step="0.001" required :value="$criterion->maximum_score" :errorKey="$criteriaKeyFor('maximum_score', $criterion->id)" />
                            </div>
                            <x-ui.select name="parent_id" label="Sub dari (opsional)" :errorKey="$criteriaKeyFor('parent_id', $criterion->id)">
                                <option value="">— Kriteria utama —</option>
                                @foreach ($round->criteria->whereNull('parent_id') as $possibleParent)
                                    @if ($possibleParent->id !== $criterion->id)
                                        <option value="{{ $possibleParent->id }}" @selected($criterion->parent_id === $possibleParent->id)>{{ $possibleParent->name }}</option>
                                    @endif
                                @endforeach
                            </x-ui.select>
                            <x-ui.button type="submit" class="w-full">Simpan Perubahan</x-ui.button>
                        </form>
                    @endforeach
                @endforeach
            @endforeach
        </x-ui.drawer>
    @endcan
</div>
