{{--
    Maps a status enum/string to a fixed badge colour + label. Centralised so
    each status → colour is defined once, not per page. Add match arms here (not
    in views) as more status enums land (registration, score sheet, result, ...).
    Built on ui/badge.blade.php — see its note on status-colour discipline.
--}}
@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;

    $variant = match ($value) {
        // shared Status enum
        'active' => 'success',
        'inactive' => 'neutral',
        // event status (Sprint 3 / frontend F4)
        'registration_open' => 'success',
        'published', 'ongoing' => 'info',
        'registration_closed' => 'warning',
        'cancelled' => 'danger',
        'draft', 'finished' => 'neutral',
        // registration status (Sprint 4)
        'submitted' => 'info',
        'approved' => 'success',
        'revision_requested' => 'warning',
        'rejected' => 'danger',
        'withdrawn' => 'neutral',
        // competition entry status (Sprint 5)
        'registered' => 'neutral',
        'checked_in', 'ready' => 'info',
        'on_deck' => 'warning',
        'performing' => 'success',
        'disqualified' => 'danger',
        // schedule slot type (Sprint 6) — not a "status" per se, but reuses
        // the same badge for consistent visual scanning in the timeline.
        'technical_meeting', 'ceremony' => 'info',
        'announcement' => 'warning',
        // score sheet status (Sprint 9-10) — 'submitted' reuses the registration
        // status arm above (same colour meaning: "waiting on organizer review").
        'locked' => 'success',
        // result status (Sprint 11-12) — 'draft' reuses the event-status arm
        // above, 'published' reuses it too (same colour meaning: final/public).
        'finalized' => 'warning',
        default => 'neutral',
    };
@endphp

<x-ui.badge :variant="$variant">{{ \Illuminate\Support\Str::headline($value) }}</x-ui.badge>
