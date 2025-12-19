<?php
// Dev helper: re-send TicketCreated mailable for the latest ticket.
// Safe to run in local environment for testing. Writes a simple 'sent' or 'no-ticket' to stdout.

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Bootstrap the container fully
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/** @var Ticket|null $t */
$t = Ticket::latest()->first();
if (! $t) {
    echo "no-ticket\n";
    exit(0);
}

// Queue sends with incremental delays to avoid SMTP rate limits
$baseDelayMs = (int) env('MAIL_SEND_DELAY_MS', 1000);
$baseDelaySeconds = (int) max(1, ceil($baseDelayMs / 1000));
$delayCounter = 0;

// Send to requester if available
if ($t->requester_id) {
    $req = User::find($t->requester_id);
    if ($req && $req->email) {
        $delayCounter += $baseDelaySeconds;
        Mail::to($req->email)->queue((new App\Mail\TicketCreated($t))->delay(now()->addSeconds($delayCounter)));
    }
}

// Send to known_emails array
if (is_array($t->known_emails)) {
    foreach ($t->known_emails as $e) {
        $delayCounter += $baseDelaySeconds;
        Mail::to($e)->queue((new App\Mail\TicketCreated($t))->delay(now()->addSeconds($delayCounter)));
    }
}

// Send to assigned user if present
if ($t->assigned_to) {
    $u = User::find($t->assigned_to);
    if ($u && $u->email) {
        $delayCounter += $baseDelaySeconds;
        Mail::to($u->email)->queue((new App\Mail\TicketCreated($t))->delay(now()->addSeconds($delayCounter)));
    }
}

echo "sent\n";
