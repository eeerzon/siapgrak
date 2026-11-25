@extends('layouts.judge')

@section('title', $entry->team->name)

@section('content')
    @php($isDraft = $sheet->status->value === 'draft')
    @php($formatNumber = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', ''), '0'), ','))

    <a href="{{ route('judge.rounds.entries.index', $round) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">← Daftar peserta</a>

    <div class="mt-3 flex items-center gap-3 rounded-xl bg-navy-900 p-4 text-white">
        <span class="flex h-14 min-w-16 shrink-0 items-center justify-center rounded-lg bg-white/10 px-2 text-base font-bold whitespace-nowrap">{{ $entry->competition_number ?? '—' }}</span>
        <div class="min-w-0">
            <h1 class="font-bold leading-snug">{{ $entry->team->name }}</h1>
            <p class="mt-0.5 text-xs text-slate-300">{{ $round->category->name }} · {{ $round->name }}</p>
        </div>
    </div>

    @unless ($isDraft)
        {{-- Read-only once submitted/locked (fr-score-sheet-lifecycle.md
             Keputusan D) — only an organizer reopen brings this back to draft. --}}
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            @if ($sheet->status->value === 'locked')
                <p class="font-semibold">Nilai sudah dikunci panitia.</p>
            @else
                <p class="font-semibold">Nilai sudah dikirim.</p>
            @endif
            <p class="mt-0.5">Kalau ada yang perlu dikoreksi, minta panitia membuka ulang lembar ini.</p>
        </div>
    @endunless

    <form id="score-form" method="POST" action="{{ route('judge.rounds.entries.scores.store', [$round, $entry]) }}" class="mt-4 space-y-3">
        @csrf

        @foreach ($criteria as $criterion)
            @php($existing = $existingScores->get($criterion->id))
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <input type="hidden" name="scores[{{ $criterion->id }}][judging_criteria_id]" value="{{ $criterion->id }}">

                <div class="flex items-start justify-between gap-3">
                    <label for="score-{{ $criterion->id }}" class="font-semibold text-navy-900">{{ $criterion->name }}</label>
                    @if ($criterion->weight !== null)
                        <span class="shrink-0 rounded-full bg-navy-50 px-2 py-0.5 text-xs font-medium text-navy-700">Bobot {{ $formatNumber($criterion->weight) }}%</span>
                    @endif
                </div>
                @if ($criterion->description)
                    <p class="mt-0.5 text-sm text-slate-500">{{ $criterion->description }}</p>
                @endif

                <div class="mt-3">
                    <x-ui.input
                        :id="'score-'.$criterion->id"
                        :name="'scores['.$criterion->id.'][score]'"
                        :error-key="'scores.'.$criterion->id.'.score'"
                        type="number"
                        inputmode="decimal"
                        step="0.001"
                        :min="$criterion->minimum_score"
                        :max="$criterion->maximum_score"
                        :value="$existing ? (float) $existing->score : null"
                        :hint="'Nilai '.$formatNumber($criterion->minimum_score).' sampai '.$formatNumber($criterion->maximum_score)"
                        :disabled="! $isDraft"
                        class="text-center text-2xl! font-bold disabled:bg-slate-50 disabled:text-slate-700"
                    />
                </div>

                <details class="mt-2" @if ($existing?->notes || $errors->has('scores.'.$criterion->id.'.notes')) open @endif>
                    <summary class="cursor-pointer text-xs font-medium text-slate-500">Catatan (opsional)</summary>
                    <div class="mt-2">
                        <x-ui.textarea
                            :id="'notes-'.$criterion->id"
                            :name="'scores['.$criterion->id.'][notes]'"
                            :error-key="'scores.'.$criterion->id.'.notes'"
                            :value="$existing?->notes"
                            rows="2"
                            :disabled="! $isDraft"
                        />
                    </div>
                </details>
            </div>
        @endforeach
    </form>

    @if ($isDraft)
        <form id="submit-form" method="POST" action="{{ route('judge.rounds.entries.submit', [$round, $entry]) }}" data-confirm-title="Kirim nilai final?" data-confirm="Setelah dikirim, nilai tidak bisa diubah kecuali panitia membuka ulang." data-confirm-button="Ya, kirim" data-confirm-icon="question">
            @csrf
        </form>

        @error('submit')
            <p class="mt-3 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>
        @enderror

        {{-- Spacer so the sticky bar never covers the last criterion. --}}
        <div class="h-28"></div>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur">
            <div class="mx-auto flex max-w-lg gap-2 px-4 py-3">
                <button type="submit" form="score-form" class="flex-1 cursor-pointer rounded-md border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan Draft</button>
                <button type="submit" form="submit-form" class="flex-1 cursor-pointer rounded-md bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700">Kirim Skor Final</button>
            </div>
        </div>

        <script>
            (() => {
                // Sending uses a separate form, so unsaved typing would be lost. Ask for a draft save first.
                const scoreForm = document.getElementById('score-form');
                const submitForm = document.getElementById('submit-form');
                let dirty = false;

                scoreForm.addEventListener('input', () => (dirty = true));
                scoreForm.addEventListener('submit', () => (dirty = false));

                submitForm.addEventListener('submit', (event) => {
                    if (dirty) {
                        event.preventDefault();
                        alert('Ada nilai yang belum disimpan. Tekan "Simpan Draft" dulu, lalu kirim.');
                        return;
                    }
                    // Confirmation itself: data-confirm on #submit-form (resources/js/confirm-dialog.js).
                });
            })();
        </script>
    @endif
@endsection
