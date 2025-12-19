<?php

namespace App\Observers;

use App\Models\Ticket;

/**
 * Observer to populate ticket_number after a Ticket is created.
 * We use the auto-increment id to produce a stable, sequential ticket number.
 */
class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        // If ticket_number not provided, generate using ID with zero padding
        if (empty($ticket->ticket_number)) {
            $ticket->ticket_number = 'TC-' . str_pad($ticket->id, 6, '0', STR_PAD_LEFT);
            // saveQuietly avoids firing events again
            $ticket->saveQuietly();
        }
    }
}
