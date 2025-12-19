<?php

namespace App\Http\Controllers;

use App\Mail\TicketCreated;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Simple controller to create tickets and send notifications.
 *
 * This is intentionally straightforward: validate input, create ticket,
 * generate ticket_number via observer, then notify requester, known emails and assignee.
 */
class TicketController
{
    /**
     * Store a new ticket.
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $data = $request->validate([
            'inbox_id' => ['required', 'integer', 'exists:inboxes,id'],
            'subject' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            // known_emails accepts an array of email strings
            'known_emails' => ['nullable', 'array'],
            'known_emails.*' => ['email'],
        ]);

        // Ensure requester is the authenticated user (simple flow)
        $requesterId = Auth::id();
        $data['requester_id'] = $requesterId;

        // Create the ticket (known_emails stored as JSON via model cast)
        $ticket = Ticket::create([
            'inbox_id' => $data['inbox_id'],
            'requester_id' => $data['requester_id'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'subject' => $data['subject'],
            'content' => $data['content'] ?? null,
            'known_emails' => $data['known_emails'] ?? null,
            'status_id' => null,
            'type_id' => null,
        ]);

        // Notify recipients by enqueuing Mailables with small delays to avoid provider rate limits.
        // Base delay is configured via MAIL_SEND_DELAY_MS (milliseconds) in .env.
        $baseDelayMs = (int) env('MAIL_SEND_DELAY_MS', 1000);
        $baseDelaySeconds = (int) max(1, ceil($baseDelayMs / 1000));
        $delayCounter = 0; // seconds to add for each recipient

        // Notify the requester (if email exists) - queued
        $requester = User::find($requesterId);
        if ($requester && !empty($requester->email)) {
            $delayCounter += $baseDelaySeconds;
            Mail::to($requester->email)->queue((new TicketCreated($ticket))->delay(now()->addSeconds($delayCounter)));
        }

        // Notify known_emails (queue individually, incrementing the delay)
        if (!empty($ticket->known_emails) && is_array($ticket->known_emails)) {
            foreach ($ticket->known_emails as $email) {
                $delayCounter += $baseDelaySeconds;
                Mail::to($email)->queue((new TicketCreated($ticket))->delay(now()->addSeconds($delayCounter)));
            }
        }

        // Notify assignee (if assigned)
        if (!empty($ticket->assigned_to)) {
            $assignee = User::find($ticket->assigned_to);
            if ($assignee && !empty($assignee->email)) {
                $delayCounter += $baseDelaySeconds;
                Mail::to($assignee->email)->queue((new TicketCreated($ticket))->delay(now()->addSeconds($delayCounter)));
            }
        }

        // Return a simple JSON response for API or Inertia can redirect
        return response()->json(['success' => true, 'ticket_id' => $ticket->id, 'ticket_number' => $ticket->ticket_number]);
    }
}
