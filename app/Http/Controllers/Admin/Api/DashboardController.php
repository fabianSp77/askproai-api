<?php

namespace App\Http\Controllers\Admin\Api;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Models\PortalUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends BaseAdminApiController
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $stats = Cache::remember('admin_dashboard_stats', 60, function () {
            $now = Carbon::now();
            $startOfDay = $now->copy()->startOfDay();
            $startOfWeek = $now->copy()->startOfWeek();
            $startOfMonth = $now->copy()->startOfMonth();
            $lastMonth = $now->copy()->subMonth();

            // Calculate trends
            $callsToday = Call::withoutGlobalScopes()->whereDate('created_at', $now->toDateString())->count();
            $callsYesterday = Call::withoutGlobalScopes()->whereDate('created_at', $now->copy()->subDay()->toDateString())->count();
            $callsTrend = $callsYesterday > 0 ? round((($callsToday - $callsYesterday) / $callsYesterday) * 100, 1) : 0;

            $customersThisMonth = Customer::withoutGlobalScopes()->whereBetween('created_at', [$startOfMonth, $now])->count();
            $customersLastMonth = Customer::withoutGlobalScopes()->whereBetween('created_at', [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()])->count();
            $customersTrend = $customersLastMonth > 0 ? round((($customersThisMonth - $customersLastMonth) / $customersLastMonth) * 100, 1) : 0;

            // Calculate positive sentiment percentage
            $totalCallsToday = Call::withoutGlobalScopes()->whereDate('created_at', $now->toDateString())->count();
            $positiveCallsToday = Call::withoutGlobalScopes()
                ->whereDate('created_at', $now->toDateString())
                ->where('sentiment', 'positive')
                ->count();
            $positiveSentimentPercent = $totalCallsToday > 0 ? round(($positiveCallsToday / $totalCallsToday) * 100, 1) : 0;

            // Get appointment statistics
            $appointmentStats = [
                'completed' => Appointment::withoutGlobalScopes()->where('status', 'completed')->whereBetween('starts_at', [$startOfMonth, $now])->count(),
                'scheduled' => Appointment::withoutGlobalScopes()->where('status', 'scheduled')->where('starts_at', '>', $now)->count(),
                'cancelled' => Appointment::withoutGlobalScopes()->where('status', 'cancelled')->whereBetween('starts_at', [$startOfMonth, $now])->count(),
                'no_show' => Appointment::withoutGlobalScopes()->where('status', 'no_show')->whereBetween('starts_at', [$startOfMonth, $now])->count(),
            ];

            $completedRate = ($appointmentStats['completed'] + $appointmentStats['scheduled'] + $appointmentStats['cancelled'] + $appointmentStats['no_show']) > 0
                ? round(($appointmentStats['completed'] / ($appointmentStats['completed'] + $appointmentStats['scheduled'] + $appointmentStats['cancelled'] + $appointmentStats['no_show'])) * 100, 1)
                : 0;

            // Get charts data
            $callsChart = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i);
                $callsChart[] = [
                    'date' => $date->format('d.m'),
                    'count' => Call::withoutGlobalScopes()->whereDate('created_at', $date->toDateString())->count()
                ];
            }

            return [
                'calls' => [
                    'total' => Call::withoutGlobalScopes()->count(),
                    'today' => $callsToday,
                    'trend' => $callsTrend,
                    'positive_sentiment' => $positiveSentimentPercent
                ],
                'appointments' => [
                    'total' => Appointment::withoutGlobalScopes()->count(),
                    'today' => Appointment::withoutGlobalScopes()->whereDate('starts_at', $now->toDateString())->count(),
                    'upcoming' => Appointment::withoutGlobalScopes()->where('starts_at', '>', $now)->count(),
                    'completed_rate' => $completedRate,
                    'completed' => $appointmentStats['completed'],
                    'scheduled' => $appointmentStats['scheduled'],
                    'cancelled' => $appointmentStats['cancelled'],
                    'no_show' => $appointmentStats['no_show']
                ],
                'customers' => [
                    'total' => Customer::withoutGlobalScopes()->count(),
                    'new_this_month' => $customersThisMonth,
                    'active' => Customer::withoutGlobalScopes()->has('appointments')->count(),
                    'trend' => $customersTrend
                ],
                'companies' => [
                    'total' => Company::withoutGlobalScopes()->count(),
                    'active' => Company::withoutGlobalScopes()->where('active', true)->count(),
                    'trial' => Company::withoutGlobalScopes()->where('subscription_status', 'trial')->count(),
                    'premium' => Company::withoutGlobalScopes()->where('subscription_status', 'premium')->count()
                ],
                'charts' => [
                    'calls' => $callsChart,
                    'appointments' => [],
                    'revenue' => []
                ]
            ];
        });

        return response()->json($stats);
    }

    /**
     * Get recent activity
     */
    public function recentActivity(Request $request)
    {
        $limit = $request->input('limit', 20);

        $activities = [];

        // Recent appointments
        $appointments = Appointment::withoutGlobalScopes()
            ->with([
                'customer' => function($q) { $q->withoutGlobalScopes(); },
                'staff' => function($q) { $q->withoutGlobalScopes(); },
                'service' => function($q) { $q->withoutGlobalScopes(); },
                'company' => function($q) { $q->withoutGlobalScopes(); }
            ])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                $customerName = $appointment->customer ? $appointment->customer->name : 'Unbekannt';
                $serviceName = $appointment->service ? $appointment->service->name : 'Unbekannt';
                
                return [
                    'id' => 'appointment-' . $appointment->id,
                    'type' => 'appointment',
                    'description' => "Neuer Termin: {$customerName} - {$serviceName}",
                    'timestamp' => $appointment->created_at,
                    'company' => $appointment->company ? [
                        'id' => $appointment->company->id,
                        'name' => $appointment->company->name
                    ] : null,
                ];
            });

        // Recent calls
        $calls = Call::withoutGlobalScopes()
            ->with(['company' => function($q) { $q->withoutGlobalScopes(); }])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => 'call-' . $call->id,
                    'type' => 'call',
                    'description' => "Anruf von {$call->from_phone_number}",
                    'timestamp' => $call->created_at,
                    'company' => $call->company ? [
                        'id' => $call->company->id,
                        'name' => $call->company->name
                    ] : null,
                ];
            });

        // Recent customers
        $customers = Customer::withoutGlobalScopes()
            ->with(['company' => function($q) { $q->withoutGlobalScopes(); }])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => 'customer-' . $customer->id,
                    'type' => 'customer',
                    'description' => "Neuer Kunde: {$customer->name}",
                    'timestamp' => $customer->created_at,
                    'company' => $customer->company ? [
                        'id' => $customer->company->id,
                        'name' => $customer->company->name
                    ] : null,
                ];
            });

        // Merge and sort by timestamp
        $activities = collect()
            ->merge($appointments)
            ->merge($calls)
            ->merge($customers)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return response()->json($activities);
    }

    /**
     * Get system health status
     */
    public function systemHealth(Request $request)
    {
        $dbStatus = $this->checkDatabase();
        $redisStatus = $this->checkRedis();
        $retellStatus = $this->checkRetellApi();
        $calcomStatus = $this->checkCalcomApi();
        $queuePending = DB::table('jobs')->count();
        $queueFailed = DB::table('failed_jobs')->count();
        
        // Determine overall status
        $overallStatus = 'healthy';
        if (!$dbStatus || !$redisStatus) {
            $overallStatus = 'critical';
        } elseif (!$retellStatus || !$calcomStatus || $queueFailed > 10) {
            $overallStatus = 'warning';
        }

        $health = [
            'status' => $overallStatus,
            'message' => $overallStatus === 'critical' ? 'Kritische Systemkomponenten sind ausgefallen' : 
                        ($overallStatus === 'warning' ? 'Einige Services haben Probleme' : 'Alle Systeme funktionieren normal'),
            'services' => [
                [
                    'name' => 'Database',
                    'status' => $dbStatus ? 'healthy' : 'critical',
                ],
                [
                    'name' => 'Redis Cache',
                    'status' => $redisStatus ? 'healthy' : 'warning',
                ],
                [
                    'name' => 'Retell.ai API',
                    'status' => $retellStatus ? 'healthy' : 'warning',
                ],
                [
                    'name' => 'Cal.com API',
                    'status' => $calcomStatus ? 'healthy' : 'warning',
                ],
                [
                    'name' => 'Queue System',
                    'status' => $queueFailed > 10 ? 'warning' : 'healthy',
                ],
            ],
            'queue' => [
                'pending' => $queuePending,
                'failed' => $queueFailed,
            ],
            'api_response_time' => rand(100, 200), // Mock data for now
        ];

        return response()->json($health);
    }

    /**
     * Calculate revenue for a period
     */
    private function calculateRevenue($start, $end)
    {
        // This is a placeholder - implement based on your billing logic
        return DB::table('invoices')
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'paid')
            ->sum('total') ?? 0;
    }

    /**
     * Check database connection
     */
    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get database response time
     */
    private function getDatabaseResponseTime()
    {
        $start = microtime(true);
        DB::select('SELECT 1');
        return round((microtime(true) - $start) * 1000, 2) . 'ms';
    }

    /**
     * Check Redis connection
     */
    private function checkRedis()
    {
        try {
            Cache::store('redis')->put('health_check', 'ok', 1);
            return Cache::store('redis')->get('health_check') === 'ok';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get Redis response time
     */
    private function getRedisResponseTime()
    {
        try {
            $start = microtime(true);
            Cache::store('redis')->get('health_check');
            return round((microtime(true) - $start) * 1000, 2) . 'ms';
        } catch (\Exception $e) {
            return 'N/A';
        }
    }

    /**
     * Check Retell API status
     */
    private function checkRetellApi()
    {
        // Check last successful API call
        $lastCall = DB::table('api_call_logs')
            ->where('service', 'retell')
            ->where('status_code', 200)
            ->latest()
            ->first();

        if (!$lastCall) {
            return false;
        }

        // If last successful call was within 1 hour, consider it operational
        return Carbon::parse($lastCall->created_at)->isAfter(Carbon::now()->subHour());
    }

    /**
     * Get last Retell sync time
     */
    private function getLastRetellSync()
    {
        $lastSync = DB::table('sync_logs')
            ->where('service', 'retell')
            ->latest()
            ->first();

        return $lastSync ? Carbon::parse($lastSync->created_at)->diffForHumans() : 'Never';
    }

    /**
     * Check Cal.com API status
     */
    private function checkCalcomApi()
    {
        // Similar to Retell check
        $lastCall = DB::table('api_call_logs')
            ->where('service', 'calcom')
            ->where('status_code', 200)
            ->latest()
            ->first();

        if (!$lastCall) {
            return false;
        }

        return Carbon::parse($lastCall->created_at)->isAfter(Carbon::now()->subHour());
    }

    /**
     * Get last Cal.com sync time
     */
    private function getLastCalcomSync()
    {
        $lastSync = DB::table('sync_logs')
            ->where('service', 'calcom')
            ->latest()
            ->first();

        return $lastSync ? Carbon::parse($lastSync->created_at)->diffForHumans() : 'Never';
    }
}