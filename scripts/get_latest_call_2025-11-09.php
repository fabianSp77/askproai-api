<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Call;

$latestCall = Call::orderBy('created_at', 'desc')->first();

if (!$latestCall) {
    die("No calls found\n");
}

echo $latestCall->retell_call_id;
