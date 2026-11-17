{{-- "12 Okt 2026" or "8–9 Nov 2026". Same shape as events/partials/date-range, in Indonesian for the public site. --}}
@props(['start', 'end' => null])

@php
    $startAt = $start->copy()->locale('id');
    $endAt = $end?->copy()->locale('id');
@endphp

@if ($endAt && ! $endAt->isSameDay($startAt))
    @if ($startAt->isSameMonth($endAt))
        {{ $startAt->translatedFormat('j') }}–{{ $endAt->translatedFormat('j M Y') }}
    @else
        {{ $startAt->translatedFormat('j M') }} – {{ $endAt->translatedFormat('j M Y') }}
    @endif
@else
    {{ $startAt->translatedFormat('j M Y') }}
@endif
