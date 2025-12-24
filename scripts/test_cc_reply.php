<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;

echo "=== Teste de CC em Respostas ===\n\n";

// Encontrar um ticket existente
$ticket = Ticket::with('requester')->first();

if (!$ticket) {
    echo "❌ Nenhum ticket encontrado. Crie um ticket primeiro.\n";
    exit(1);
}

echo "✓ Usando ticket #{$ticket->id} - {$ticket->subject}\n";

// Encontrar um usuário operador
$operator = User::whereHas('inboxRoles', function($q) use ($ticket) {
    $q->where('inbox_id', $ticket->inbox_id)
      ->whereIn('role', ['owner', 'operator']);
})->first();

if (!$operator) {
    echo "❌ Nenhum operador encontrado para esta inbox.\n";
    exit(1);
}

echo "✓ Usando operador: {$operator->name} ({$operator->email})\n\n";

// Criar uma mensagem com CC
$ccEmails = ['teste1@example.com', 'teste2@example.com', 'teste3@example.com'];

echo "Criando mensagem com CCs: " . implode(', ', $ccEmails) . "\n";

$message = TicketMessage::create([
    'ticket_id' => $ticket->id,
    'user_id' => $operator->id,
    'contact_id' => null,
    'content' => 'Esta é uma resposta de teste com múltiplos CCs para verificar se as notificações são enviadas corretamente.',
    'cc' => $ccEmails,
    'is_internal' => false,
]);

echo "\n✓ Mensagem criada com ID: {$message->id}\n";
echo "✓ Observer será acionado automaticamente\n\n";

// Aguardar um pouco para os logs serem escritos
sleep(2);

// Verificar logs
echo "=== Verificando logs recentes ===\n\n";
$logFile = storage_path('logs/laravel.log');
$logs = file($logFile);
$recentLogs = array_slice($logs, -100);

$ccLogs = array_filter($recentLogs, function($line) {
    return strpos($line, 'Processing message CC') !== false
        || strpos($line, 'Final recipients') !== false
        || strpos($line, 'Dispatching email') !== false;
});

if (empty($ccLogs)) {
    echo "⚠ Nenhum log encontrado sobre processamento de CCs\n";
} else {
    foreach ($ccLogs as $log) {
        echo trim($log) . "\n";
    }
}

echo "\n=== Jobs na Fila ===\n";
$jobCount = DB::table('jobs')->count();
echo "Total de jobs pendentes: {$jobCount}\n\n";

if ($jobCount > 0) {
    echo "✓ Jobs foram criados! Execute 'php artisan queue:work' para processar\n";
}

echo "\n✓ Teste concluído!\n";
