<?php

namespace App\Observers;

use App\Mail\TicketReplied;
use App\Mail\TicketRepliedForOperator;
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
        $operatorRecipients = [];

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
            \Log::info('TicketMessageObserver: Processing message CC', [
                'message_id' => $message->id,
                'cc_emails' => $message->cc,
                'current_recipients' => $recipients,
            ]);
            foreach ($message->cc as $email) {
                if (!in_array($email, $recipients)) {
                    $recipients[] = $email;
                    \Log::info('TicketMessageObserver: Added CC recipient', [
                        'email' => $email,
                    ]);
                }
            }
        } else {
            \Log::info('TicketMessageObserver: No CC emails in message', [
                'message_id' => $message->id,
                'message_cc' => $message->cc,
                'is_array' => is_array($message->cc),
                'is_empty' => empty($message->cc),
            ]);
        }

        // 4. Notify the assignee (if assigned and different from message sender)
        if ($ticket->assigned_to && $ticket->assignee && !empty($ticket->assignee->email)) {
            $assigneeEmail = $ticket->assignee->email;
            // Don't notify assignee if they are the one replying
            if ($message->user_id !== $ticket->assigned_to && !in_array($assigneeEmail, $recipients)) {
                $recipients[] = $assigneeEmail;
            }
        }

        // 5. Notify inbox owners and operators (with separate template)
        $inboxOperators = \App\Models\User::whereHas('inboxRoles', function ($q) use ($ticket) {
            $q->where('inbox_id', $ticket->inbox_id)
                ->whereIn('role', ['owner', 'operator']);
        })->get();

        foreach ($inboxOperators as $operator) {
            if (!empty($operator->email) && !in_array($operator->email, $recipients) && !in_array($operator->email, $operatorRecipients)) {
                $operatorRecipients[] = $operator->email;
            }
        }

        \Log::info('TicketMessageObserver: Final recipients list', [
            'message_id' => $message->id,
            'ticket_id' => $ticket->id,
            'message_cc' => $message->cc,
            'ticket_known_emails' => $ticket->known_emails,
            'total_recipients' => count($recipients),
            'recipients' => $recipients,
            'total_operator_recipients' => count($operatorRecipients),
            'operator_recipients' => $operatorRecipients,
        ]);

        // Send emails to all recipients with proper delays
        $allRecipients = array_merge($recipients, $operatorRecipients);
        foreach ($allRecipients as $index => $email) {
            $delaySeconds = ($index + 1) * $baseDelaySeconds;
            \Log::info('TicketMessageObserver: Dispatching email', [
                'ticket_id' => $ticket->id,
                'message_id' => $message->id,
                'recipient' => $email,
                'delay_seconds' => $delaySeconds,
                'index' => $index,
            ]);
            dispatch(new \App\Jobs\SendTicketReplyEmail($message, $ticket, $email))
                ->delay(now()->addSeconds($delaySeconds));
        }
    }
}
