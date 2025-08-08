<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Initialize app
$request = Illuminate\Http\Request::create('/', 'GET');
$response = $kernel->handle($request);

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "Creating sample logs for demonstration...\n";

$levels = ['info', 'warning', 'error', 'critical', 'debug'];
$messages = [
    'info' => [
        'User login successful for user@example.com',
        'API request completed successfully',
        'Cache cleared successfully',
        'Background job ProcessWebhooks completed',
        'Email sent to customer@example.com',
    ],
    'warning' => [
        'Slow query detected: SELECT * FROM calls took 156ms',
        'Memory usage above 70%: 11.2GB of 16GB',
        'API rate limit approaching for Retell.ai',
        'Deprecated function used in CustomerController',
        'Queue size growing: 45 jobs pending',
    ],
    'error' => [
        'Failed to connect to Cal.com API: Connection timeout',
        'Database query failed: SQLSTATE[23000] Integrity constraint violation',
        'Payment processing failed for customer #1234',
        'File not found: /storage/app/exports/report.pdf',
        'Authentication failed for user admin@example.com',
    ],
    'critical' => [
        'Database connection lost: MySQL server has gone away',
        'Disk space critical: Only 5% remaining',
        'Security breach attempt detected from IP 192.168.1.100',
        'Service down: Retell.ai webhook endpoint not responding',
        'Emergency: All queue workers have stopped',
    ],
    'debug' => [
        'Request data: {"action":"create","type":"appointment"}',
        'Cache hit for key: dashboard_metrics_1',
        'SQL query: SELECT * FROM customers WHERE id = 123',
        'Webhook payload received from Retell.ai',
        'Session started for user ID: 456',
    ],
];

$logs = [];
$now = Carbon::now();

// Create logs for the last 24 hours
for ($hours = 0; $hours < 24; $hours++) {
    $timestamp = $now->copy()->subHours($hours);
    
    // Create 1-5 logs per hour
    $logsPerHour = rand(1, 5);
    
    for ($i = 0; $i < $logsPerHour; $i++) {
        $level = $levels[array_rand($levels)];
        $messageArray = $messages[$level];
        $message = $messageArray[array_rand($messageArray)];
        
        // Add some variation to the timestamp
        $logTime = $timestamp->copy()->subMinutes(rand(0, 59))->subSeconds(rand(0, 59));
        
        $logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => json_encode([
                'user_id' => rand(1, 10),
                'ip' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0',
            ]),
            'created_at' => $logTime,
            'updated_at' => $logTime,
        ];
    }
}

// Insert logs in batches
$chunks = array_chunk($logs, 50);
foreach ($chunks as $chunk) {
    DB::table('logs')->insert($chunk);
}

$totalInserted = count($logs);
echo "✅ Created $totalInserted sample log entries\n";

// Show summary
$summary = DB::table('logs')
    ->selectRaw('level, COUNT(*) as count')
    ->groupBy('level')
    ->get();

echo "\nLog Summary:\n";
foreach ($summary as $item) {
    $emoji = match($item->level) {
        'critical' => '🔴',
        'error' => '🟠',
        'warning' => '🟡',
        'info' => '🔵',
        'debug' => '⚪',
        default => '⚫'
    };
    echo "$emoji {$item->level}: {$item->count} entries\n";
}

echo "\n✅ Logs page should now show data at: https://api.askproai.de/telescope/logs\n";