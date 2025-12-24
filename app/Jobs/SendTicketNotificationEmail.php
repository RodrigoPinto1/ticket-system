<?php

namespace App\Jobs;

use App\Mail\TicketCreated;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTicketNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120; // 2 minutes between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Ticket $ticket,
        protected string $email
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SendTicketNotificationEmail: Sending email', [
            'ticket_id' => $this->ticket->id,
            'recipient_email' => $this->email,
        ]);

        Mail::to($this->email)->send(new TicketCreated($this->ticket));

        Log::info('SendTicketNotificationEmail: Email sent successfully', [
            'ticket_id' => $this->ticket->id,
            'recipient_email' => $this->email,
        ]);
    }
}
