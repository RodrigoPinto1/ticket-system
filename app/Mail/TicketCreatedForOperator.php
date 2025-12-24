<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Services\NotificationTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketCreatedForOperator extends Mailable
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

        $template = $renderer->renderForInbox('ticket_created_operator', $this->ticket->inbox_id ?? null, [
            'ticket' => [
                'number' => $this->ticket->ticket_number ?? 'N/A',
                'subject' => $this->ticket->subject ?? 'Ticket',
                'content' => $this->ticket->content ?? '— Sem descrição —',
                'requester_name' => $this->ticket->requester?->name ?? 'Desconhecido',
                'requester_email' => $this->ticket->requester?->email ?? 'N/A',
                'entity_name' => $this->ticket->entity?->name ?? 'N/A',
                'inbox_name' => $this->ticket->inbox?->name ?? 'N/A',
                'created_at' => optional($this->ticket->created_at)->toDayDateTimeString() ?? '',
                'url' => $url,
            ],
            'message' => [
                'content' => 'Nenhuma mensagem neste momento.',
            ],
            'app' => [
                'name' => config('app.name', 'App'),
            ],
        ]);

        \Log::info('TicketCreatedForOperator: Template render result', [
            'ticket_id' => $this->ticket->id,
            'inbox_id' => $this->ticket->inbox_id,
            'template_found' => $template !== null,
            'template_subject' => $template['subject'] ?? 'N/A',
        ]);

        if ($template) {
            return $this->subject($template['subject'])
                ->html($template['html']);
        }

        \Log::warning('TicketCreatedForOperator: Template not found, using fallback view', [
            'ticket_id' => $this->ticket->id,
        ]);

        return $this->subject("🎫 Novo Ticket: " . ($this->ticket->subject ?? 'Ticket'))
            ->view('emails.ticket.created_for_operator')
            ->with(['ticket' => $this->ticket, 'url' => $url]);
    }
}
