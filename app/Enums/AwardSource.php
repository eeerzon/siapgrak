<?php

namespace App\Enums;

/**
 * Only Manual is reachable this sprint — Judging/KreenconnectVoting are
 * schema-only placeholders (fr-awards-finalization.md Keputusan I). No
 * auto-generation from rank exists; awards are always a deliberate organizer
 * action, never guessed from competition_results.
 */
enum AwardSource: string
{
    case Judging = 'judging';
    case Manual = 'manual';
    case KreenconnectVoting = 'kreenconnect_voting';
}
