<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketRepliedForOperator extends Mailable
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

        $template = $renderer->renderForInbox('ticket_replied_operator', $this->ticket->inbox_id ?? null, [
            'ticket' => [
                'number' => $this->ticket->ticket_number ?? 'N/A',
                'subject' => $this->ticket->subject ?? 'Ticket',
                'content' => $this->ticket->content ?? '',
                'url' => $url,
            ],
            'message' => [
                'content' => $this->message->content ?? '',
                'author_name' => $this->message->user?->name ?? 'Desconhecido',
                'author_email' => $this->message->user?->email ?? 'N/A',
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

        return $this->subject("💬 Nova resposta no ticket {{ticket.number}}")
            ->view('emails.ticket.replied_for_operator')
            ->with(['message' => $this->message, 'ticket' => $this->ticket, 'url' => $url]);
    }
}
