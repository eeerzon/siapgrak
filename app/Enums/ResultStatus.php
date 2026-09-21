<?php

namespace App\Enums;

/**
 * Only Draft is reachable in Sprint 11 — Finalized/Published are Sprint 12
 * (fr-result-calculator.md Keputusan G). Defined in full now to match the
 * schema, same pattern as ScoreSheetStatus in Sprint 9.
 */
enum ResultStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Published = 'published';
}
