{{-- The judge's own sheet state for one entry. Expects $status (?ScoreSheetStatus), $scored, $total. --}}
@php
    // Opening an entry creates an empty draft, so a draft without scores still counts as not started.
    $state = $status?->value === 'draft' && ! $scored ? null : $status?->value;

    [$label, $classes] = match ($state) {
        'locked' => ['Dikunci', 'bg-emerald-50 text-emerald-700'],
        'submitted' => ['Terkirim', 'bg-emerald-50 text-emerald-700'],
        'draft' => ["Draft {$scored}/{$total}", 'bg-amber-50 text-amber-800'],
        default => ['Belum dinilai', 'bg-slate-100 text-slate-500'],
    };
@endphp

<span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap {{ $classes }}">{{ $label }}</span>
