<?php

namespace App\Observers;

use App\Mail\TicketReplied;
use App\Models\TicketMessage;
use App\Models\TicketActivity;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

/**
 * Observer to send notifications when a reply is added to a ticket.
 * Notifies: ticket creator, all CC contacts, and the assignee.
 */
class TicketMessageObserver
{
    /**
     * Handle the TicketMessage "created" event.
     */
    public function created(TicketMessage $message): void
    {
        // Log the reply activity
        $user = $message->user;
        $isInternal = $message->is_internal;

        TicketActivity::log(
            ticketId: $message->ticket_id,
            action: $isInternal ? 'internal_note_added' : 'replied',
            userId: $message->user_id,
            description: $isInternal
                ? "Nota interna adicionada por {$user?->name}"
                : "Resposta adicionada por {$user?->name}",
            metadata: [
                'message_id' => $message->id,
                'has_attachments' => $message->attachments()->count() > 0,
                'cc_count' => count($message->cc ?? []),
            ]
        );

        // Only send notifications for non-internal messages
        if ($message->is_internal) {
            return;
        }

        // Load necessary relationships
        $message->load(['user', 'contact', 'attachments']);
        $ticket = $message->ticket;
        $ticket->load(['requester', 'assignee']);

        // Base delay configuration for queued emails
        $baseDelayMs = (int) env('MAIL_SEND_DELAY_MS', 1000);
        $baseDelaySeconds = (int) max(1, ceil($baseDelayMs / 1000));
        $delayCounter = 0;

        $recipients = [];

        // 1. Notify the ticket creator (requester)
        if ($ticket->requester && !empty($ticket->requester->email)) {
            $recipients[] = $ticket->requester->email;
        }

        // 2. Notify all CC contacts (known_emails from ticket)
        if (!empty($ticket->known_emails) && is_array($ticket->known_emails)) {
            foreach ($ticket->known_emails as $email) {
                if (!in_array($email, $recipients)) {
                    $recipients[] = $email;
                }
            }
        }

        // 3. Notify CC contacts from the message itself
        if (!empty($message->cc) && is_array($message->cc)) {
            foreach ($message->cc as $email) {
                if (!in_array($email, $recipients)) {
                    $recipients[] = $email;
                }
            }
        }

        // 4. Notify the assignee (if assigned and different from message sender)
        if ($ticket->assigned_to && $ticket->assignee && !empty($ticket->assignee->email)) {
            $assigneeEmail = $ticket->assignee->email;
            // Don't notify assignee if they are the one replying
            if ($message->user_id !== $ticket->assigned_to && !in_array($assigneeEmail, $recipients)) {
                $recipients[] = $assigneeEmail;
            }
        }

        // Send queued emails to all recipients
        foreach ($recipients as $email) {
            $delayCounter += $baseDelaySeconds;
            Mail::to($email)->queue(
                (new TicketReplied($message, $ticket))->delay(now()->addSeconds($delayCounter))
            );
        }
    }
}

