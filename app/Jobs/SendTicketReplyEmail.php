<?php

namespace App\Jobs;

use App\Mail\TicketReplied;
use App\Models\TicketMessage;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendTicketReplyEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120; // 2 minutes between retries

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new \Illuminate\Queue\Middleware\ThrottlesExceptions(1, 5)];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected TicketMessage $message,
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
        // Rate limit: wait 1 second before each email to avoid Mailtrap throttling
        sleep(1);

        Log::info('SendTicketReplyEmail: Sending email', [
            'ticket_id' => $this->ticket->id,
            'message_id' => $this->message->id,
            'recipient_email' => $this->email,
        ]);

        Mail::to($this->email)->send(new TicketReplied($this->message, $this->ticket));

        Log::info('SendTicketReplyEmail: Email sent successfully', [
            'ticket_id' => $this->ticket->id,
            'message_id' => $this->message->id,
            'recipient_email' => $this->email,
        ]);
    }
}
