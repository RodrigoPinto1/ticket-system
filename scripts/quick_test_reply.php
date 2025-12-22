<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\DB;

echo "=== Teste Rápido de Resposta ===\n\n";

$ticket = Ticket::first();

if (!$ticket) {
    echo "❌ Nenhum ticket encontrado. Crie um ticket primeiro.\n";
    exit(1);
}

echo "Ticket: {$ticket->ticket_number}\n";
echo "Assunto: {$ticket->subject}\n";
echo "CCs: " . json_encode($ticket->known_emails) . "\n\n";

echo "Criando resposta...\n";
$message = TicketMessage::create([
    'ticket_id' => $ticket->id,
    'user_id' => 1,
    'content' => 'Nova resposta de teste após correção - ' . now(),
    'is_internal' => false,
]);

echo "✅ Mensagem criada com ID: {$message->id}\n\n";

$jobCount = DB::table('jobs')->count();
echo "📬 Jobs enfileirados: {$jobCount}\n\n";

if ($jobCount > 0) {
    echo "Execute: php artisan queue:work --once\n";
} else {
    echo "⚠️ Nenhum job foi enfileirado. Verifique a configuração QUEUE_CONNECTION.\n";
}
