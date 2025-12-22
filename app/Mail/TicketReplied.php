<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketReplied extends Mailable
{
    use Queueable, SerializesModels;

    public TicketMessage $message;
    public Ticket $ticket;

    /**
     * Create a new message instance.
     */
    public function __construct(TicketMessage $message, Ticket $ticket)
    {
        $this->message = $message;
        $this->ticket = $ticket;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject("[Ticket Reply] " . ($this->ticket->ticket_number ?? 'Ticket'))
                    ->markdown('emails.ticket.replied_markdown')
                    ->with([
                        'ticket' => $this->ticket,
                        'reply' => $this->message,
                    ]);
    }
}
