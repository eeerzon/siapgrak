<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Enums\Status;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Ownership is fully derived from events.organization_id via organization_users,
 * same pattern as TeamPolicy / OrganizationPolicy. event_staff is NOT an
 * authorization source in Sprint 3 (fr-event-category.md Keputusan A).
 */
class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can reach the create form; the submitted
     * organization_id is validated against the user's active memberships in
     * StoreEventRequest (fr-event-category.md Keputusan F).
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Event $event): bool
    {
        return $this->membershipRole($user, $event) !== null;
    }

    public function update(User $user, Event $event): bool
    {
        return $this->isOwnerOrAdmin($user, $event);
    }

    public function updateStatus(User $user, Event $event): bool
    {
        return $this->isOwnerOrAdmin($user, $event);
    }

    public function manageCategories(User $user, Event $event): bool
    {
        return $this->isOwnerOrAdmin($user, $event);
    }

    public function manageStaff(User $user, Event $event): bool
    {
        return $this->isOwnerOrAdmin($user, $event);
    }

    public function manageRegistrations(User $user, Event $event): bool
    {
        return $this->isOwnerOrAdmin($user, $event);
    }

    public function manageCompetition(User $user, Event $event): bool
    {
        return $this->isOwnerOrAdmin($user, $event);
    }

    public function manageSchedule(User $user, Event $event): bool
    {
        return $this->isOwnerOrAdmin($user, $event);
    }

    public function manageJudging(User $user, Event $event): bool
    {
        return $this->isOwnerOrAdmin($user, $event);
    }

    /**
     * Reopening a LOCKED score sheet is a privileged action — Owner only,
     * not Admin (fr-score-sheet-lifecycle.md Keputusan A). Reopening a
     * merely-submitted sheet still uses manageJudging like every other
     * Judging action.
     */
    public function reopenLockedScoreSheet(User $user, Event $event): bool
    {
        return $this->membershipRole($user, $event) === OrganizationRole::Owner;
    }

    /**
     * Results/Awards are Phase E, a distinct product phase from Phase D
     * Judging even though the role check is identical (Owner+Admin) —
     * separate method for readability, not a separate Policy class
     * (fr-awards-finalization.md Keputusan D).
     */
    public function manageResults(User $user, Event $event): bool
    {
        return $this->isOwnerOrAdmin($user, $event);
    }

    /**
     * Reopening a PUBLISHED result is a privileged action — Owner only, same
     * pattern as reopenLockedScoreSheet (fr-awards-finalization.md Keputusan B).
     */
    public function reopenPublishedResult(User $user, Event $event): bool
    {
        return $this->membershipRole($user, $event) === OrganizationRole::Owner;
    }

    protected function isOwnerOrAdmin(User $user, Event $event): bool
    {
        return in_array($this->membershipRole($user, $event), [
            OrganizationRole::Owner,
            OrganizationRole::Admin,
        ], true);
    }

    /**
     * Memoized per (user, event) via the array cache store — Gate resolves a
     * fresh EventPolicy instance on every single @can() check, so an
     * instance property wouldn't survive across calls, but the array store
     * lives for the whole request (fr-operational-polish.md N+1 fix). One
     * event page can call this 8 different ability methods dozens of times
     * across nested loops (rounds/criteria/awards) — this collapses all of
     * that back down to a single query per (user, event) pair.
     */
    protected function membershipRole(User $user, Event $event): ?OrganizationRole
    {
        return Cache::store('array')->rememberForever(
            "event-membership:{$user->id_user}:{$event->id}",
            fn () => $event->organization->memberships()
                ->where('user_id', $user->id_user)
                ->where('status', Status::Active)
                ->first()?->role,
        );
    }
}
