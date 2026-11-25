@extends('layouts.judge')

@section('title', $participant->name)

@section('content')
    @php($isDraft = $judging->isDraft())
    @php($formatNumber = fn ($value) => rtrim(rtrim(number_format((float) $value, 3, ',', ''), '0'), ','))

    <a href="{{ route('juri.round', $round) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-600">← Daftar peserta</a>

    <div class="mt-3 flex items-center gap-3 rounded-xl bg-navy-900 p-4 text-white">
        <span class="flex h-14 min-w-16 shrink-0 items-center justify-center rounded-lg bg-white/10 px-2 text-base font-bold whitespace-nowrap">{{ $participant->competition_number ?? '—' }}</span>
        <div class="min-w-0">
            <h1 class="font-bold leading-snug">{{ $participant->name }}</h1>
            <p class="mt-0.5 text-xs text-slate-300">{{ $round->category->name }} · {{ $round->name }}</p>
        </div>
    </div>

    @unless ($isDraft)
        {{-- Read-only once submitted/locked (fr-score-sheet-lifecycle.md
             Keputusan D) — only an organizer reopen brings this back to draft. --}}
        <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{-- Lock feature hidden for now (2026-11-19): a locked sheet reads as sent.
            @if ($judging->status->value === 'locked')
                <p class="font-semibold">Nilai sudah dikunci panitia.</p>
            @else
            --}}
                <p class="font-semibold">Nilai sudah dikirim.</p>
            {{-- @endif --}}
            <p class="mt-0.5">Kalau ada yang perlu dikoreksi, minta panitia membuka ulang lembar ini.</p>
        </div>
    @endunless

    @if (! $ready && $isDraft)
        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <p class="font-semibold">Babak ini belum bisa dinilai.</p>
            <p class="mt-0.5">Panitia masih menyiapkan kriteria dan bobot penilaian. Coba lagi nanti.</p>
        </div>
    @else
    <form id="score-form" method="POST" action="{{ route('juri.score.save', [$round, $participant]) }}" class="mt-4 space-y-3">
        @csrf

        @foreach ($round->criteria as $criterion)
            @php($existing = $existingScores->get($criterion->id))
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">

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
        @error('submit')
            <p class="mt-3 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>
        @enderror

        {{-- Spacer so the sticky bar never covers the last criterion. --}}
        <div class="h-32"></div>

        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 pb-[env(safe-area-inset-bottom)] backdrop-blur">
            <div class="mx-auto max-w-lg px-4 py-3">
                <p class="mb-2 hidden text-center text-xs font-medium text-amber-700" data-missing></p>
                <div class="flex gap-2">
                    <button type="submit" form="score-form" class="flex-1 cursor-pointer rounded-md border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Simpan Draft</button>
                    {{-- Same form + kirim=1: saves what is typed, then sends, in one step. --}}
                    <button type="submit" form="score-form" name="kirim" value="1" data-send
                            data-confirm-title="Kirim nilai final?" data-confirm="Setelah dikirim, nilai tidak bisa diubah kecuali panitia membuka ulang." data-confirm-button="Ya, kirim" data-confirm-icon="question"
                            class="flex-1 cursor-pointer rounded-md bg-brand-600 px-4 py-3 text-sm font-semibold text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300">Kirim Skor Final</button>
                </div>
            </div>
        </div>

        <script>
            (() => {
                const form = document.getElementById('score-form');
                const send = document.querySelector('[data-send]');
                const missing = document.querySelector('[data-missing]');
                const fields = [...form.querySelectorAll('input[type=number]')];

                // Sending needs every criterion, so keep the button disabled until all are filled.
                const update = () => {
                    const empty = fields.filter((field) => field.value.trim() === '').length;
                    send.disabled = empty > 0;
                    missing.textContent = empty ? `${empty} kriteria belum diisi. Isi semua untuk bisa mengirim.` : '';
                    missing.classList.toggle('hidden', !empty);
                };
                form.addEventListener('input', update);
                update();
            })();
        </script>
    @endif
    @endif
@endsection
