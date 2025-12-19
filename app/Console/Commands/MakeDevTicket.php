<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inbox;
use App\Models\User;
use App\Models\Ticket;

/**
 * Command to quickly create a ticket for local development/testing.
 * Usage: php artisan dev:make-ticket --subject="Teste" --content="..." --known="a@b.com,b@c.com"
 */
class MakeDevTicket extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:make-ticket {--subject=Dev ticket} {--content=Created by dev command} {--known=} {--assigned=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a sample ticket for development and print its data';

    public function handle(): int
    {
        if (!app()->environment('local')) {
            $this->error('This command should only be used in the local environment.');
            return 1;
        }

        // ensure an inbox exists
        $inbox = Inbox::first() ?: Inbox::create(['name' => 'Dev Inbox', 'slug' => 'dev-inbox']);

        // ensure a user exists
        $user = User::first() ?: User::factory()->create(['email' => 'dev@example.com', 'password' => bcrypt('password')]);

        $subject = $this->option('subject');
        $content = $this->option('content');
        $known = $this->option('known');
        $known_emails = $known ? array_filter(array_map('trim', explode(',', $known))) : ['dev-cc@example.com'];
        $assigned = intval($this->option('assigned')) ?: null;

        $ticket = Ticket::create([
            'inbox_id' => $inbox->id,
            'requester_id' => $user->id,
            'subject' => $subject,
            'content' => $content,
            'known_emails' => $known_emails,
            'assigned_to' => $assigned,
        ]);

        $ticket->refresh();

        $this->info('Ticket created:');
        $this->line('ID: ' . $ticket->id);
        $this->line('Number: ' . $ticket->ticket_number);
        $this->line('Subject: ' . $ticket->subject);
        $this->line('Known emails: ' . json_encode($ticket->known_emails));

        $this->info('Check storage/logs/laravel.log for email entries (MAIL_MAILER=log)');

        return 0;
    }
}
