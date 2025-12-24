<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$total = DB::table('jobs')->count();
$available = DB::table('jobs')->where('available_at', '<=', time())->count();
$scheduled = DB::table('jobs')->where('available_at', '>', time())->count();

echo "Total de jobs: $total\n";
echo "Jobs disponíveis agora: $available\n";
echo "Jobs agendados (future): $scheduled\n\n";

if ($scheduled > 0) {
    echo "Próximos jobs:\n";
    $jobs = DB::table('jobs')->where('available_at', '>', time())->orderBy('available_at')->get();
    foreach ($jobs as $job) {
        $availableAt = \DateTime::createFromFormat('U', $job->available_at);
        $payload = json_decode($job->payload, true);
        echo "- {$payload['data']['command']} at " . $availableAt->format('H:i:s') . "\n";
    }
}
