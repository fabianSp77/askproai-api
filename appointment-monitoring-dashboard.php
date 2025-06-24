#!/usr/bin/env php
<?php

use App\Models\Call;
use App\Models\Appointment;
use App\Models\WebhookEvent;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// ANSI color codes
$colors = [
    'reset' => "\033[0m",
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'magenta' => "\033[35m",
    'cyan' => "\033[36m",
    'white' => "\033[37m",
    'bold' => "\033[1m",
];

function color($text, $color) {
    global $colors;
    return $colors[$color] . $text . $colors['reset'];
}

function clearScreen() {
    echo "\033[2J\033[H";
}

function getMetrics($timeframe = 'hour') {
    $intervals = [
        'hour' => ['interval' => '1 HOUR', 'format' => 'H:i'],
        'day' => ['interval' => '24 HOUR', 'format' => 'd.m H:i'],
        'week' => ['interval' => '7 DAY', 'format' => 'd.m'],
    ];
    
    $interval = $intervals[$timeframe]['interval'];
    $dateFormat = $intervals[$timeframe]['format'];
    
    // Total calls
    $totalCalls = Call::withoutGlobalScopes()
        ->where('created_at', '>', DB::raw("NOW() - INTERVAL $interval"))
        ->count();
    
    // Calls with appointment intent
    $callsWithIntent = Call::withoutGlobalScopes()
        ->where('created_at', '>', DB::raw("NOW() - INTERVAL $interval"))
        ->where(function($query) {
            $query->whereNotNull('appointment_id')
                ->orWhereRaw("JSON_EXTRACT(metadata, '$.appointment_intent_detected') = true");
        })
        ->count();
    
    // Successful appointments
    $appointments = Appointment::withoutGlobalScopes()
        ->where('created_at', '>', DB::raw("NOW() - INTERVAL $interval"))
        ->count();
    
    // Conversion rate
    $conversionRate = $totalCalls > 0 ? round(($appointments / $totalCalls) * 100, 1) : 0;
    
    // Webhook status
    $webhookStats = WebhookEvent::withoutGlobalScopes()
        ->where('created_at', '>', DB::raw("NOW() - INTERVAL $interval"))
        ->where('provider', 'retell')
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();
    
    // Recent appointments
    $recentAppointments = Appointment::withoutGlobalScopes()
        ->with(['customer', 'service', 'branch'])
        ->where('created_at', '>', DB::raw("NOW() - INTERVAL 1 HOUR"))
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    // Company stats
    $companyStats = Company::withoutGlobalScopes()
        ->join('calls', 'companies.id', '=', 'calls.company_id')
        ->where('calls.created_at', '>', DB::raw("NOW() - INTERVAL $interval"))
        ->selectRaw('companies.name, COUNT(calls.id) as call_count')
        ->groupBy('companies.id', 'companies.name')
        ->orderByDesc('call_count')
        ->limit(5)
        ->get();
    
    // Error count
    $errors = DB::table('failed_jobs')
        ->where('failed_at', '>', DB::raw("NOW() - INTERVAL $interval"))
        ->count();
    
    return [
        'timeframe' => $timeframe,
        'total_calls' => $totalCalls,
        'calls_with_intent' => $callsWithIntent,
        'appointments' => $appointments,
        'conversion_rate' => $conversionRate,
        'webhook_stats' => $webhookStats,
        'recent_appointments' => $recentAppointments,
        'company_stats' => $companyStats,
        'errors' => $errors,
        'last_update' => now()->format($dateFormat),
    ];
}

function displayDashboard($metrics) {
    clearScreen();
    
    // Header
    echo color("╔════════════════════════════════════════════════════════════════╗\n", 'cyan');
    echo color("║          ", 'cyan') . color("AskProAI Appointment Booking Monitor", 'bold') . color("              ║\n", 'cyan');
    echo color("╚════════════════════════════════════════════════════════════════╝\n", 'cyan');
    
    echo "\n";
    echo color("Last Update: ", 'white') . $metrics['last_update'] . " | ";
    echo color("Timeframe: ", 'white') . ucfirst($metrics['timeframe']) . "\n\n";
    
    // Key Metrics
    echo color("📊 KEY METRICS\n", 'bold');
    echo color("─────────────────────────────────────────\n", 'white');
    
    $conversionColor = $metrics['conversion_rate'] > 20 ? 'green' : 
                      ($metrics['conversion_rate'] > 10 ? 'yellow' : 'red');
    
    printf("%-25s %s\n", "Total Calls:", color($metrics['total_calls'], 'cyan'));
    printf("%-25s %s\n", "Calls with Intent:", color($metrics['calls_with_intent'], 'yellow'));
    printf("%-25s %s\n", "Appointments Booked:", color($metrics['appointments'], 'green'));
    printf("%-25s %s%%\n", "Conversion Rate:", color($metrics['conversion_rate'], $conversionColor));
    
    // Webhook Stats
    echo "\n" . color("🔄 WEBHOOK STATUS\n", 'bold');
    echo color("─────────────────────────────────────────\n", 'white');
    
    $webhookStats = $metrics['webhook_stats'];
    printf("%-25s %s\n", "Completed:", color($webhookStats['completed'] ?? 0, 'green'));
    printf("%-25s %s\n", "Processing:", color($webhookStats['processing'] ?? 0, 'yellow'));
    printf("%-25s %s\n", "Pending:", color($webhookStats['pending'] ?? 0, 'yellow'));
    printf("%-25s %s\n", "Failed:", color($webhookStats['failed'] ?? 0, 'red'));
    
    // Recent Appointments
    if (count($metrics['recent_appointments']) > 0) {
        echo "\n" . color("📅 RECENT APPOINTMENTS (Last Hour)\n", 'bold');
        echo color("─────────────────────────────────────────\n", 'white');
        
        foreach ($metrics['recent_appointments'] as $apt) {
            $time = $apt->created_at->format('H:i');
            $customer = $apt->customer ? substr($apt->customer->name, 0, 20) : 'Unknown';
            $service = $apt->service ? substr($apt->service->name, 0, 25) : 'N/A';
            
            printf("%s | %-20s | %-25s\n", 
                color($time, 'white'),
                $customer,
                color($service, 'cyan')
            );
        }
    }
    
    // Company Activity
    if (count($metrics['company_stats']) > 0) {
        echo "\n" . color("🏢 COMPANY ACTIVITY\n", 'bold');
        echo color("─────────────────────────────────────────\n", 'white');
        
        foreach ($metrics['company_stats'] as $company) {
            printf("%-30s %s calls\n", 
                substr($company->name, 0, 30),
                color($company->call_count, 'cyan')
            );
        }
    }
    
    // System Health
    echo "\n" . color("💊 SYSTEM HEALTH\n", 'bold');
    echo color("─────────────────────────────────────────\n", 'white');
    
    $horizonRunning = shell_exec("php artisan horizon:status 2>&1");
    $horizonStatus = strpos($horizonRunning, 'running') !== false ? 
        color('Running', 'green') : color('Stopped', 'red');
    
    $queueSize = DB::table('jobs')->count();
    $queueColor = $queueSize > 100 ? 'red' : ($queueSize > 50 ? 'yellow' : 'green');
    
    printf("%-25s %s\n", "Horizon Status:", $horizonStatus);
    printf("%-25s %s\n", "Queue Size:", color($queueSize, $queueColor));
    printf("%-25s %s\n", "Failed Jobs:", color($metrics['errors'], $metrics['errors'] > 0 ? 'red' : 'green'));
    
    // Footer
    echo "\n" . color("─────────────────────────────────────────\n", 'white');
    echo "Press " . color("[h]", 'yellow') . "our, " . color("[d]", 'yellow') . "ay, " . color("[w]", 'yellow') . "eek | ";
    echo color("[r]", 'green') . "efresh | " . color("[q]", 'red') . "uit\n";
}

// Main loop
$timeframe = 'hour';
$running = true;

// Set up non-blocking input
stream_set_blocking(STDIN, false);

while ($running) {
    $metrics = getMetrics($timeframe);
    displayDashboard($metrics);
    
    // Check for input
    $input = fread(STDIN, 1);
    
    if ($input) {
        switch (strtolower($input)) {
            case 'h':
                $timeframe = 'hour';
                break;
            case 'd':
                $timeframe = 'day';
                break;
            case 'w':
                $timeframe = 'week';
                break;
            case 'r':
                // Refresh immediately
                continue 2;
            case 'q':
                $running = false;
                clearScreen();
                echo color("Dashboard stopped.\n", 'green');
                break;
        }
    }
    
    if ($running) {
        sleep(5); // Refresh every 5 seconds
    }
}