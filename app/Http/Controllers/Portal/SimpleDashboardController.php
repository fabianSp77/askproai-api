<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;

class SimpleDashboardController extends Controller
{
    /**
     * Show simple portal dashboard without MCP dependencies.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Don't redirect here - let middleware handle it
        // This prevents redirect loops
        if (!$user) {
            \Log::error('SimpleDashboardController: No authenticated user', [
                'guard_check' => Auth::guard('portal')->check(),
                'session_id' => session()->getId(),
                'url' => $request->url(),
            ]);
            
            // Return error instead of redirect
            abort(401, 'Unauthorized - Please login');
        }
        
        $companyId = $user->company_id;
        
        // Simple statistics
        $stats = [
            'total_calls' => Call::where('company_id', $companyId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count(),
            
            'total_appointments' => Appointment::where('company_id', $companyId)
                ->whereMonth('starts_at', Carbon::now()->month)
                ->count(),
            
            'new_customers' => Customer::where('company_id', $companyId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->count(),
            
            'monthly_revenue' => Invoice::where('company_id', $companyId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('total'),
        ];
        
        // Recent calls
        $recentCalls = Call::where('company_id', $companyId)
            ->with(['customer', 'staff'])
            ->latest()
            ->limit(10)
            ->get();
        
        // Upcoming appointments today
        $upcomingTasks = Appointment::where('company_id', $companyId)
            ->whereDate('starts_at', Carbon::today())
            ->where('starts_at', '>', Carbon::now())
            ->with(['customer', 'service', 'staff'])
            ->orderBy('starts_at')
            ->limit(5)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'title' => $appointment->service->name ?? 'Appointment',
                    'customer' => $appointment->customer->name ?? 'Unknown',
                    'time' => $appointment->starts_at->format('H:i'),
                    'staff' => $appointment->staff->name ?? 'Unassigned'
                ];
            });
        
        $teamPerformance = null;
        
        return view('portal.dashboard-simple', compact(
            'user',
            'stats',
            'recentCalls',
            'upcomingTasks',
            'teamPerformance'
        ));
    }
}
