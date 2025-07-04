<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimpleRetellMonitorController extends Controller
{
    public function index()
    {
        // Simple stats without models
        $stats = [
            'calls_today' => DB::table('calls')->whereDate('created_at', today())->count(),
            'appointments_today' => DB::table('appointments')
                ->whereDate('created_at', today())
                ->where('source', 'phone')
                ->count(),
            'webhooks_today' => DB::table('webhook_events')
                ->whereDate('created_at', today())
                ->where('provider', 'retell')
                ->count(),
            'success_rate' => 0,
        ];

        // Recent webhooks
        $recentWebhooks = DB::table('webhook_events')
            ->where('provider', 'retell')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Recent calls
        $recentCalls = DB::table('calls')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Recent appointments
        $recentAppointments = DB::table('appointments')
            ->join('customers', 'appointments.customer_id', '=', 'customers.id')
            ->leftJoin('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.source', 'phone')
            ->orderBy('appointments.created_at', 'desc')
            ->select(
                'appointments.*',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                'services.name as service_name'
            )
            ->limit(10)
            ->get();

        return view('retell-monitor', compact('stats', 'recentWebhooks', 'recentCalls', 'recentAppointments'));
    }
    
    public function stats()
    {
        // API endpoint for live stats
        $stats = [
            'calls_today' => DB::table('calls')->whereDate('created_at', today())->count(),
            'calls_total' => DB::table('calls')->count(),
            'appointments_today' => DB::table('appointments')
                ->whereDate('created_at', today())
                ->where('source', 'phone')
                ->count(),
            'appointments_total' => DB::table('appointments')
                ->where('source', 'phone')
                ->count(),
            'webhooks_today' => DB::table('webhook_events')
                ->whereDate('created_at', today())
                ->where('provider', 'retell')
                ->count(),
            'webhooks_pending' => DB::table('webhook_events')
                ->where('provider', 'retell')
                ->where('status', 'pending')
                ->count(),
            'success_rate' => $this->calculateSuccessRate(),
            'last_call' => DB::table('calls')
                ->orderBy('created_at', 'desc')
                ->first(),
            'last_webhook' => DB::table('webhook_events')
                ->where('provider', 'retell')
                ->orderBy('created_at', 'desc')
                ->first()
        ];
        
        return response()->json($stats);
    }
    
    private function calculateSuccessRate()
    {
        $totalWebhooks = DB::table('webhook_events')
            ->where('provider', 'retell')
            ->whereDate('created_at', today())
            ->count();
            
        if ($totalWebhooks == 0) {
            return 100;
        }
        
        $completedWebhooks = DB::table('webhook_events')
            ->where('provider', 'retell')
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->count();
            
        return round(($completedWebhooks / $totalWebhooks) * 100, 1);
    }
}