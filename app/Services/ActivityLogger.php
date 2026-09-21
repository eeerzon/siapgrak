<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Covers Sprint 12's own actions only (finalize/publish/reopen/award
 * changes) — not a retrofit of every action since Sprint 1
 * (fr-awards-finalization.md Keputusan E, deferred to Sprint 13 Polish).
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(string $action, ?Event $event = null, ?Model $subject = null, array $metadata = []): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'organization_id' => $event?->organization_id,
            'event_id' => $event?->id,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
        ]);
    }
}
