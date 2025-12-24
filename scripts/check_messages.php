<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Últimas 10 Mensagens ===\n\n";

$messages = DB::table('ticket_messages')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

if ($messages->isEmpty()) {
    echo "Nenhuma mensagem encontrada.\n";
} else {
    foreach ($messages as $message) {
        echo "ID: {$message->id}\n";
        echo "Ticket ID: {$message->ticket_id}\n";
        echo "User ID: {$message->user_id}\n";
        echo "Content: " . substr($message->content, 0, 50) . "...\n";
        echo "CC: " . ($message->cc ?? 'null') . "\n";
        echo "Is Internal: " . ($message->is_internal ? 'sim' : 'não') . "\n";
        echo "Created: {$message->created_at}\n";
        echo "---\n\n";
    }
}

echo "\n=== Jobs na Fila ===\n\n";
$jobs = DB::table('jobs')->orderBy('id', 'desc')->limit(10)->get();
echo "Total de jobs pendentes: " . $jobs->count() . "\n";

echo "\n=== Jobs Falhados ===\n\n";
$failedJobs = DB::table('failed_jobs')->orderBy('id', 'desc')->limit(5)->get();
echo "Total de jobs falhados: " . $failedJobs->count() . "\n";
