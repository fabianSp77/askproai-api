<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: application/json');

try {
    // Get appointments count
    $totalAppointments = \App\Models\Appointment::count();
    
    // Get recent appointments
    $recentAppointments = \App\Models\Appointment::latest()
        ->limit(5)
        ->get(['id', 'customer_name', 'service_name', 'appointment_date', 'created_at']);
    
    // Check auth
    $user = null;
    if (auth()->check()) {
        $user = [
            'id' => auth()->user()->id,
            'email' => auth()->user()->email,
            'roles' => auth()->user()->getRoleNames()->toArray()
        ];
    }
    
    // System status
    $systemStatus = [
        'database' => true,
        'redis' => true,
        'cache' => is_writable(storage_path('framework/cache')),
        'sessions' => is_writable(storage_path('framework/sessions')),
        'logs' => is_writable(storage_path('logs'))
    ];
    
    try {
        \Illuminate\Support\Facades\Redis::ping();
    } catch (Exception $e) {
        $systemStatus['redis'] = false;
    }
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_appointments' => $totalAppointments,
            'recent_appointments' => $recentAppointments,
            'auth_user' => $user,
            'system_status' => $systemStatus,
            'timestamp' => now()->toIso8601String()
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}