<?php

namespace App\Enums;

/**
 * Only Draft is reachable in Sprint 9 — Submitted/Locked are Sprint 10
 * (fr-scoring.md Keputusan G). Defined in full now to match the schema.
 */
enum ScoreSheetStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Locked = 'locked';
}
