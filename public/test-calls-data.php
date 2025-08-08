<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);

header('Content-Type: application/json');

// Check if authenticated
if (!auth()->check()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user = auth()->user();
$companyId = $user->company_id;

// Get calls data
$calls = \App\Models\Call::where('company_id', $companyId)
    ->orderBy('created_at', 'desc')
    ->take(10)
    ->get();

$result = [
    'authenticated' => true,
    'user' => $user->email,
    'company_id' => $companyId,
    'total_calls' => \App\Models\Call::where('company_id', $companyId)->count(),
    'recent_calls' => $calls->map(function($call) {
        return [
            'id' => $call->id,
            'date' => $call->created_at->format('Y-m-d H:i:s'),
            'duration' => $call->duration_sec,
            'status' => $call->status,
            'from' => $call->from_phone,
            'to' => $call->to_phone
        ];
    })
];

echo json_encode($result, JSON_PRETTY_PRINT);