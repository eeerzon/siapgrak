@extends('layouts.judge')

@section('title', $entry->team->name)

@section('content')
    @php($isDraft = $sheet->status->value === 'draft')

    <a href="{{ route('judge.rounds.entries.index', $round) }}" class="text-xs text-slate-500 hover:underline">&larr; Daftar peserta</a>

    <h1 class="mt-1 text-lg font-semibold text-slate-800">{{ $entry->competition_number ?? '—' }} · {{ $entry->team->name }}</h1>
    <p class="text-sm text-slate-500">{{ $round->category->name }} · {{ $round->name }}</p>

    @unless ($isDraft)
        {{-- Read-only once submitted/locked (fr-score-sheet-lifecycle.md
             Keputusan D) — only an organizer reopen brings this back to draft. --}}
        <div class="mt-4 rounded-md bg-sky-50 px-4 py-3 text-sm text-sky-800">
            @if ($sheet->status->value === 'locked')
                Skor sudah dikunci panitia. Hubungi panitia kalau perlu koreksi.
            @else
                Skor sudah dikirim, menunggu panitia. Hubungi panitia kalau perlu koreksi.
            @endif
        </div>
    @endunless

    <form method="POST" action="{{ route('judge.rounds.entries.scores.store', [$round, $entry]) }}" class="mt-6 space-y-4">
        @csrf

        @foreach ($criteria as $criterion)
            @php($existing = $existingScores->get($criterion->id))
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <input type="hidden" name="scores[{{ $criterion->id }}][judging_criteria_id]" value="{{ $criterion->id }}">

                <x-ui.input
                    :name="'scores['.$criterion->id.'][score]'"
                    :error-key="'scores.'.$criterion->id.'.score'"
                    :label="$criterion->name"
                    type="number"
                    step="0.001"
                    :min="$criterion->minimum_score"
                    :max="$criterion->maximum_score"
                    :value="$existing?->score"
                    :hint="'Rentang '.$criterion->minimum_score.'–'.$criterion->maximum_score"
                    :disabled="! $isDraft"
                />

                <div class="mt-2">
                    <x-ui.textarea
                        :name="'scores['.$criterion->id.'][notes]'"
                        :error-key="'scores.'.$criterion->id.'.notes'"
                        label="Catatan (opsional)"
                        :value="$existing?->notes"
                        rows="2"
                        :disabled="! $isDraft"
                    />
                </div>
            </div>
        @endforeach

        @if ($isDraft)
            <x-ui.button type="submit" variant="secondary" class="w-full">Simpan Draft</x-ui.button>
        @endif
    </form>

    @if ($isDraft)
        <form method="POST" action="{{ route('judge.rounds.entries.submit', [$round, $entry]) }}" class="mt-3"
              onsubmit="return confirm('Kirim skor final? Setelah dikirim Anda tidak bisa mengubahnya lagi kecuali panitia membuka ulang.');">
            @csrf
            <x-ui.button type="submit" class="w-full">Kirim Skor Final</x-ui.button>
        </form>
        @error('submit')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    @endif
@endsection
