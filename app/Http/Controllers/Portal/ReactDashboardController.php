<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReactDashboardController extends Controller
{
    /**
     * Display the React dashboard
     */
    public function index(Request $request)
    {
        return view('portal.dashboard-react');
    }
    
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Get stats
        $stats = [
            'calls' => [
                'today' => Call::where('company_id', $user->company_id)
                    ->whereDate('created_at', today())
                    ->count(),
                'week' => Call::where('company_id', $user->company_id)
                    ->whereDate('created_at', '>=', now()->startOfWeek())
                    ->count(),
                'month' => Call::where('company_id', $user->company_id)
                    ->whereDate('created_at', '>=', now()->startOfMonth())
                    ->count(),
                'new' => Call::where('company_id', $user->company_id)
                    ->where('status', 'new')
                    ->count(),
            ],
            'appointments' => [
                'today' => Appointment::where('company_id', $user->company_id)
                    ->whereDate('date', today())
                    ->count(),
                'week' => Appointment::where('company_id', $user->company_id)
                    ->whereDate('date', '>=', now()->startOfWeek())
                    ->whereDate('date', '<=', now()->endOfWeek())
                    ->count(),
                'upcoming' => Appointment::where('company_id', $user->company_id)
                    ->where('date', '>=', today())
                    ->where('status', 'scheduled')
                    ->count(),
                'completed' => Appointment::where('company_id', $user->company_id)
                    ->whereDate('date', today())
                    ->where('status', 'completed')
                    ->count(),
            ],
            'customers' => [
                'total' => Customer::where('company_id', $user->company_id)
                    ->count(),
                'new_this_month' => Customer::where('company_id', $user->company_id)
                    ->whereDate('created_at', '>=', now()->startOfMonth())
                    ->count(),
                'active' => Customer::where('company_id', $user->company_id)
                    ->whereHas('appointments', function($q) {
                        $q->where('date', '>=', now()->subDays(90));
                    })
                    ->count(),
            ],
            'revenue' => [
                'today' => 0, // TODO: Calculate from completed appointments
                'week' => 0,
                'month' => 0,
            ],
        ];
        
        return response()->json($stats);
    }
    
    /**
     * Get recent calls for dashboard
     */
    public function recentCalls(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $calls = Call::where('company_id', $user->company_id)
            ->with(['customer', 'branch'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'phone_number' => $call->phone_number,
                    'status' => $call->status ?? 'new',
                    'duration_sec' => $call->duration_sec ?? 0,
                    'created_at' => $call->created_at->toIso8601String(),
                    'customer' => $call->customer ? [
                        'id' => $call->customer->id,
                        'name' => $call->customer->name,
                    ] : null,
                    'branch' => $call->branch ? [
                        'id' => $call->branch->id,
                        'name' => $call->branch->name,
                    ] : null,
                ];
            });
            
        return response()->json($calls);
    }
    
    /**
     * Get upcoming appointments for dashboard
     */
    public function upcomingAppointments(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $appointments = Appointment::where('company_id', $user->company_id)
            ->where('date', '>=', today())
            ->where('status', 'scheduled')
            ->with(['customer', 'staff', 'service', 'branch'])
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->take(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'date' => $appointment->date,
                    'time' => $appointment->time,
                    'duration' => $appointment->duration ?? 60,
                    'status' => $appointment->status,
                    'customer' => $appointment->customer ? [
                        'id' => $appointment->customer->id,
                        'name' => $appointment->customer->name,
                    ] : null,
                    'staff' => $appointment->staff ? [
                        'id' => $appointment->staff->id,
                        'name' => $appointment->staff->name,
                    ] : null,
                    'service' => $appointment->service ? [
                        'id' => $appointment->service->id,
                        'name' => $appointment->service->name,
                    ] : null,
                    'branch' => $appointment->branch ? [
                        'id' => $appointment->branch->id,
                        'name' => $appointment->branch->name,
                    ] : null,
                ];
            });
            
        return response()->json($appointments);
    }
}