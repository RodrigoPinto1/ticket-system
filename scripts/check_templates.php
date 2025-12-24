<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\NotificationTemplate;

echo "=== Templates de Notificação ===\n\n";

$templates = NotificationTemplate::all();

foreach ($templates as $template) {
    echo "Slug: {$template->slug}\n";
    echo "Subject: {$template->subject}\n";
    echo "Inbox ID: " . ($template->inbox_id ?? 'Global') . "\n";
    echo "Enabled: " . ($template->enabled ? 'Sim' : 'Não') . "\n";
    echo "---\n\n";
}
