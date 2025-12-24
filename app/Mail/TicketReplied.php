<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\NotificationTemplateRenderer;
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
        $renderer = app(NotificationTemplateRenderer::class);

        $url = rtrim(config('app.url', env('APP_URL', 'https://ticket-system.test/')), '/') . '/tickets/' . ($this->ticket->id ?? '');

        $template = $renderer->renderForInbox('ticket_replied', $this->ticket->inbox_id ?? null, [
            'ticket' => [
                'number' => $this->ticket->ticket_number ?? 'N/A',
                'subject' => $this->ticket->subject ?? 'Ticket',
                'url' => $url,
            ],
            'message' => [
                'content' => nl2br(e($this->message->content ?? '')),
                'created_at' => optional($this->message->created_at)->toDayDateTimeString() ?? '',
            ],
            'app' => [
                'name' => config('app.name', 'Ticket System'),
            ],
        ]);

        if ($template) {
            return $this->subject($template['subject'])
                ->html($template['html']);
        }

        return $this->subject("[Ticket Reply] " . ($this->ticket->ticket_number ?? 'Ticket'))
            ->markdown('emails.ticket.replied_markdown')
            ->with([
                'ticket' => $this->ticket,
                'reply' => $this->message,
            ]);
    }
}
