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
        // Allow all authenticated users to view ticket details
        // Business decision: visibility is open; edits remain restricted elsewhere
        return true;
    }

    public function create(User $user): bool
    {
        // Operators and owners can create tickets in any inbox
        if ($user->inboxRoles()->whereIn('role', ['owner', 'operator'])->exists()) {
            return true;
        }

        // Clients can create tickets if they have an entity associated
        // They don't need a specific inbox role yet - it will be created when they submit the ticket
        if ($user->entity_id) {
            return true;
        }

        return false;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return true; // Permite que qualquer usuário autenticado edite e salve
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasInboxRole($ticket->inbox_id, ['owner', 'operator']);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        // Operators and owners can always reply
        if ($user->hasInboxRole($ticket->inbox_id, ['owner', 'operator'])) {
            return true;
        }

        // Clients can only reply to tickets where they are the requester AND in the inbox
        if ($user->id === $ticket->requester_id && $user->hasInboxRole($ticket->inbox_id, 'client')) {
            return true;
        }

        return false;
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->hasInboxRole($ticket->inbox_id, ['owner', 'operator']);
    }
}
