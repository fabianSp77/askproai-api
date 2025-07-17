<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

// Login as admin
$admin = \App\Models\User::where('email', 'admin@askproai.de')->first();
if ($admin) {
    \Illuminate\Support\Facades\Auth::login($admin);
}

// Get widget data directly
$company = $admin->company;
$todayCount = \App\Models\Call::where('company_id', $company->id)
    ->whereDate('start_timestamp', today())
    ->count();
    
$weekCount = \App\Models\Call::where('company_id', $company->id)
    ->whereBetween('start_timestamp', [
        now()->startOfWeek(),
        now()->endOfWeek()
    ])
    ->count();
    
$avgDuration = \App\Models\Call::where('company_id', $company->id)
    ->whereNotNull('duration_sec')
    ->where('duration_sec', '>', 0)
    ->avg('duration_sec');
    
$avgDurationFormatted = $avgDuration 
    ? gmdate('i:s', $avgDuration) 
    : '0:00';
    
$totalCalls = \App\Models\Call::where('company_id', $company->id)
    ->where('start_timestamp', '>=', now()->startOfMonth())
    ->count();
    
$callsWithAppointments = \App\Models\Call::where('company_id', $company->id)
    ->where('start_timestamp', '>=', now()->startOfMonth())
    ->whereNotNull('appointment_id')
    ->count();
    
$conversionRate = $totalCalls > 0 
    ? round(($callsWithAppointments / $totalCalls) * 100) 
    : 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widget Test - Simple</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .stat-card {
            background: linear-gradient(135deg, #f3f4f6 0%, #ffffff 100%);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1;
        }
        .stat-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            margin-top: 0.5rem;
        }
        .stat-description {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }
        .stat-icon {
            width: 3rem;
            height: 3rem;
            padding: 0.75rem;
            background: #f3f4f6;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Call KPI Widget - Simplified Test</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Anrufe heute -->
            <div class="stat-card rounded-lg p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="stat-value text-blue-600"><?php echo $todayCount; ?></div>
                        <div class="stat-label">Anrufe heute</div>
                        <div class="stat-description"><?php echo $todayCount === 1 ? 'Anruf' : 'Anrufe'; ?></div>
                    </div>
                    <div class="stat-icon">
                        <svg class="w-full h-full text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Diese Woche -->
            <div class="stat-card rounded-lg p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="stat-value text-indigo-600"><?php echo $weekCount; ?></div>
                        <div class="stat-label">Diese Woche</div>
                        <div class="stat-description"><?php echo $weekCount === 1 ? 'Anruf empfangen' : 'Anrufe empfangen'; ?></div>
                    </div>
                    <div class="stat-icon">
                        <svg class="w-full h-full text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Durchschnittsdauer -->
            <div class="stat-card rounded-lg p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="stat-value text-yellow-600"><?php echo $avgDurationFormatted; ?></div>
                        <div class="stat-label">Ø Gesprächsdauer</div>
                        <div class="stat-description">Minuten:Sekunden</div>
                    </div>
                    <div class="stat-icon">
                        <svg class="w-full h-full text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Konversionsrate -->
            <div class="stat-card rounded-lg p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="stat-value <?php echo $conversionRate >= 20 ? 'text-green-600' : ($conversionRate >= 10 ? 'text-yellow-600' : 'text-red-600'); ?>">
                            <?php echo $conversionRate; ?>%
                        </div>
                        <div class="stat-label">Konversionsrate</div>
                        <div class="stat-description">Termine aus Anrufen</div>
                    </div>
                    <div class="stat-icon">
                        <svg class="w-full h-full <?php echo $conversionRate >= 20 ? 'text-green-600' : ($conversionRate >= 10 ? 'text-yellow-600' : 'text-red-600'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg p-6 shadow">
            <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
            <dl class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="font-medium text-gray-500">User</dt>
                    <dd><?php echo $admin->email; ?></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Company</dt>
                    <dd><?php echo $company->name; ?></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Total Calls (All Time)</dt>
                    <dd><?php echo \App\Models\Call::where('company_id', $company->id)->count(); ?></dd>
                </div>
                <div>
                    <dt class="font-medium text-gray-500">Calls This Month</dt>
                    <dd><?php echo $totalCalls; ?></dd>
                </div>
            </dl>
        </div>
        
        <div class="mt-8 text-center">
            <a href="/admin/calls" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Zurück zu Calls
            </a>
        </div>
    </div>
</body>
</html>