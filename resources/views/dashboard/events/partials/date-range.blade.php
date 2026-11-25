{{-- Renders "12 Okt 2026" or "8–9 Nov 2026" from two Y-m-d strings. Params: $start, $end. --}}
@php
    $startAt = \Illuminate\Support\Carbon::parse($start);
    $endAt = $end ? \Illuminate\Support\Carbon::parse($end) : null;
@endphp
@if ($endAt && ! $endAt->isSameDay($startAt))
    @if ($startAt->isSameMonth($endAt))
        {{ $startAt->format('j') }}–{{ $endAt->translatedFormat('j M Y') }}
    @else
        {{ $startAt->translatedFormat('j M') }} – {{ $endAt->translatedFormat('j M Y') }}
    @endif
@else
    {{ $startAt->translatedFormat('j M Y') }}
@endif
