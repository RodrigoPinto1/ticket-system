<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketCreated extends Mailable
{
    use Queueable, SerializesModels;

    public Ticket $ticket;

    /**
     * Create a new message instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $renderer = app(NotificationTemplateRenderer::class);

        $url = rtrim(config('app.url', env('APP_URL', 'https://ticket-system.test/')), '/') . '/tickets/' . ($this->ticket->id ?? '');

        $template = $renderer->renderForInbox('ticket_created', $this->ticket->inbox_id ?? null, [
            'ticket' => [
                'number' => $this->ticket->ticket_number ?? 'N/A',
                'subject' => $this->ticket->subject ?? 'Ticket',
                'content' => nl2br(e($this->ticket->content ?? '— Sem descrição —')),
                'created_at' => optional($this->ticket->created_at)->toDayDateTimeString() ?? '',
                'url' => $url,
            ],
            'app' => [
                'name' => config('app.name', 'App'),
            ],
        ]);

        if ($template) {
            return $this->subject($template['subject'])
                ->html($template['html']);
        }

        return $this->subject("[Ticket] " . ($this->ticket->ticket_number ?? 'New ticket'))
            ->view('emails.ticket.created')
            ->with(['ticket' => $this->ticket]);
    }
}
