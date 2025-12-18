<?php

namespace App\Policies;

use App\Models\Inbox;
use App\Models\User;

class InboxPolicy
{
    public function view(User $user, Inbox $inbox): bool
    {
        return $user->hasInboxRole($inbox->id);
    }

    public function manage(User $user, Inbox $inbox): bool
    {
        return $user->hasInboxRole($inbox->id, 'owner');
    }

    public function viewAny(User $user): bool
    {
        return $user->inboxRoles()->exists();
    }
}
