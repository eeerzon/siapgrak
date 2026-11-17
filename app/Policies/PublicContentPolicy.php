<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared by CompetitionListing, AgendaItem and CommunityPost (registered in
 * AppServiceProvider). Each platform admin manages only the content they
 * created (id_user); EnsurePlatformAdmin already gates the whole panel.
 */
class PublicContentPolicy
{
    public function manage(User $user, Model $content): bool
    {
        // Cast: some PDO drivers return foreign keys as strings. Null (creator
        // deleted) never matches, so orphaned content is managed by nobody.
        return (int) $content->id_user === $user->id_user;
    }
}
