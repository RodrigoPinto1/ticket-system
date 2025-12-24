<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get last 5 ticket messages with CC
$messages = DB::table('ticket_messages')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "=== Last 5 Ticket Messages ===\n\n";

foreach ($messages as $message) {
    echo "ID: {$message->id}\n";
    echo "Ticket ID: {$message->ticket_id}\n";
    echo "User ID: {$message->user_id}\n";
    echo "Content: " . substr($message->content, 0, 50) . "...\n";
    echo "CC: {$message->cc}\n";
    $ccArray = json_decode($message->cc, true);
    echo "CC (decoded): " . print_r($ccArray, true) . "\n";
    echo "Created: {$message->created_at}\n";
    echo "---\n\n";
}
