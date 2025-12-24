<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ticket;

echo "=== Tickets Existentes ===\n\n";

$tickets = Ticket::all();

if ($tickets->isEmpty()) {
    echo "Nenhum ticket encontrado.\n";
} else {
    foreach ($tickets as $ticket) {
        echo "ID: {$ticket->id}\n";
        echo "Número: {$ticket->ticket_number}\n";
        echo "Assunto: {$ticket->subject}\n";
        echo "Criado em: {$ticket->created_at}\n";
        echo "---\n\n";
    }
}
