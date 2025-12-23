<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->inboxRoles()->exists();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        // Operators and owners can view any ticket in the inbox
        if ($user->hasInboxRole($ticket->inbox_id, ['owner', 'operator'])) {
            return true;
        }

        // Clients can only view tickets where they are the requester
        if ($user->hasInboxRole($ticket->inbox_id, 'client')) {
            return $user->id === $ticket->requester_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->inboxRoles()->exists();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasInboxRole($ticket->inbox_id, ['owner', 'operator']);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasInboxRole($ticket->inbox_id, ['owner', 'operator']);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        if ($user->hasInboxRole($ticket->inbox_id, ['owner', 'operator'])) {
            return true;
        }

        return $user->id === $ticket->requester_id
            && $user->hasInboxRole($ticket->inbox_id, ['client', 'owner', 'operator']);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->hasInboxRole($ticket->inbox_id, ['owner', 'operator']);
    }
}
