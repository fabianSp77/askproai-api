<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\WebhookEvent;
use App\Models\Company;
use App\Services\Calcom\CalcomV2Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class RetellMonitorController extends Controller
{
    /**
     * Display the monitor dashboard
     */
    public function index()
    {
        // For monitoring, we need to bypass company scope
        // Get statistics across all companies
        $stats = [
            'calls_today' => Call::withoutGlobalScopes()->whereDate('created_at', today())->count(),
            'appointments_today' => Appointment::withoutGlobalScopes()
                ->whereDate('created_at', today())
                ->where('source', 'phone')
                ->count(),
            'webhooks_today' => WebhookEvent::whereDate('created_at', today())
                ->where('source', 'retell')
                ->count(),
            'success_rate' => $this->calculateSuccessRate(),
        ];

        // Get recent data
        $recentWebhooks = WebhookEvent::where('source', 'retell')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $recentCalls = Call::withoutGlobalScopes()
            ->with(['appointment' => function($query) {
                $query->withoutGlobalScopes();
            }])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $recentAppointments = Appointment::withoutGlobalScopes()
            ->with(['customer' => function($query) {
                $query->withoutGlobalScopes();
            }, 'service' => function($query) {
                $query->withoutGlobalScopes();
            }])
            ->where('source', 'phone')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // System status
        $systemStatus = [
            'horizon' => $this->checkHorizon(),
            'database' => $this->checkDatabase(),
            'retell_api' => !empty(config('services.retell.api_key')),
            'calcom_api' => $this->checkCalcomApi(),
        ];

        return view('retell-monitor', compact(
            'stats',
            'recentWebhooks',
            'recentCalls',
            'recentAppointments',
            'systemStatus'
        ));
    }

    /**
     * Get real-time stats for AJAX updates
     */
    public function stats()
    {
        $stats = [
            'calls_today' => Call::withoutGlobalScopes()->whereDate('created_at', today())->count(),
            'appointments_today' => Appointment::withoutGlobalScopes()
                ->whereDate('created_at', today())
                ->where('source', 'phone')
                ->count(),
            'webhooks_today' => WebhookEvent::whereDate('created_at', today())
                ->where('source', 'retell')
                ->count(),
            'success_rate' => $this->calculateSuccessRate(),
        ];

        $recentWebhooks = WebhookEvent::where('source', 'retell')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($webhook) {
                return [
                    'event' => $webhook->payload['event'] ?? 'unknown',
                    'time' => $webhook->created_at->format('H:i:s'),
                    'status' => $webhook->status,
                    'call_id' => $webhook->payload['call']['call_id'] ?? null,
                ];
            });

        $recentCalls = Call::withoutGlobalScopes()
            ->with(['appointment' => function($query) {
                $query->withoutGlobalScopes();
            }])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                return [
                    'from_number' => $call->from_number,
                    'created_at' => $call->created_at->format('d.m.Y H:i'),
                    'duration' => $call->duration,
                    'has_appointment' => !is_null($call->appointment_id),
                ];
            });

        $recentAppointments = Appointment::withoutGlobalScopes()
            ->with(['customer' => function($query) {
                $query->withoutGlobalScopes();
            }, 'service' => function($query) {
                $query->withoutGlobalScopes();
            }])
            ->where('source', 'phone')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'customer_name' => $appointment->customer->name ?? 'Unbekannt',
                    'customer_phone' => $appointment->customer->phone ?? 'N/A',
                    'service_name' => $appointment->service->name ?? 'N/A',
                    'date' => $appointment->date?->format('d.m.Y') ?? 'N/A',
                    'time' => $appointment->start_time ?? 'N/A',
                ];
            });

        return response()->json([
            'stats' => $stats,
            'recent_webhooks' => $recentWebhooks,
            'recent_calls' => $recentCalls,
            'recent_appointments' => $recentAppointments,
        ]);
    }

    /**
     * Check Cal.com status
     */
    public function calcomStatus()
    {
        $company = Company::whereNotNull('calcom_api_key')->first();
        
        if (!$company) {
            return response()->json([
                'connected' => false,
                'message' => 'No company with Cal.com API key found',
            ]);
        }

        try {
            $calcom = new CalcomV2Service();
            $calcom->setCompany($company);
            $me = $calcom->getMe();
            
            return response()->json([
                'connected' => true,
                'user' => $me['username'] ?? 'Unknown',
                'email' => $me['email'] ?? 'N/A',
                'timezone' => $me['timeZone'] ?? 'N/A',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'connected' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Activity data for chart
     */
    public function activity()
    {
        $hours = collect(range(0, 23));
        
        $callsPerHour = Call::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour');

        $appointmentsPerHour = Appointment::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->where('source', 'phone')
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->pluck('count', 'hour');

        $data = [
            'labels' => $hours->map(fn($h) => $h . 'h')->toArray(),
            'calls' => $hours->map(fn($h) => $callsPerHour->get($h, 0))->toArray(),
            'appointments' => $hours->map(fn($h) => $appointmentsPerHour->get($h, 0))->toArray(),
        ];

        return response()->json($data);
    }

    /**
     * Calculate success rate
     */
    private function calculateSuccessRate()
    {
        $totalCalls = Call::withoutGlobalScopes()->whereDate('created_at', today())->count();
        if ($totalCalls === 0) return 0;

        $successfulCalls = Call::withoutGlobalScopes()
            ->whereDate('created_at', today())
            ->whereNotNull('appointment_id')
            ->count();

        return round(($successfulCalls / $totalCalls) * 100);
    }

    /**
     * Check if Horizon is running
     */
    private function checkHorizon()
    {
        try {
            $exitCode = Artisan::call('horizon:status');
            return $exitCode === 0;
        } catch (\Exception $e) {
            return false;
        }
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
     * Check if any company has Cal.com API configured
     */
    private function checkCalcomApi()
    {
        return Company::whereNotNull('calcom_api_key')->exists();
    }
}