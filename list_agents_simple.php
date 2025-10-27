<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$token = env('RETELL_TOKEN');

$response = Http::withHeaders(['Authorization' => "Bearer $token"])
    ->get('https://api.retellai.com/list-agents');

echo "Status: {$response->status()}\n";
echo "Response:\n";
echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n";
