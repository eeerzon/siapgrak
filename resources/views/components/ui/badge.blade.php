@props([
    'variant' => 'neutral',
])

@php
    // Status visibility is priority #1 for Siapgrak (panitia reading state fast
    // during an event) — colors map to a fixed meaning across the whole app,
    // never reused for anything else. See frontend-plan.md §Status Badges.
    $variants = [
        'neutral' => 'bg-slate-100 text-slate-700',
        'info' => 'bg-sky-50 text-sky-700',
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-800',
        'danger' => 'bg-red-50 text-red-700',
    ];

    $classes = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium '
        .($variants[$variant] ?? $variants['neutral']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
