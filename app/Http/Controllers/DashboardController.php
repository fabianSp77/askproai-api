<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistiken - tenant-scoped with optimized queries
        $tenantId = auth()->user()->tenant_id ?? request()->header('X-Tenant-ID');
        
        $totalCalls = Call::where('tenant_id', $tenantId)->count();
        $successfulCalls = Call::where('tenant_id', $tenantId)->where('call_successful', true)->count();
        
        $stats = [
            'total_calls' => $totalCalls,
            'success_rate' => $totalCalls > 0 
                ? round(($successfulCalls / $totalCalls) * 100, 1)
                : 0,
            'unique_customers' => Customer::where('tenant_id', $tenantId)->count(),
            'avg_duration' => Call::where('tenant_id', $tenantId)->avg('duration_sec') 
                ? round(Call::where('tenant_id', $tenantId)->avg('duration_sec'), 0) : 0,
        ];

        // Sentiment-Analyse - tenant-scoped with JSON column access
        $sentiments = [
            'Positive' => Call::where('tenant_id', $tenantId)
                ->whereJsonContains('analysis->sentiment', 'positive')
                ->count(),
            'Neutral' => Call::where('tenant_id', $tenantId)
                ->whereJsonContains('analysis->sentiment', 'neutral')
                ->count(),
            'Negative' => Call::where('tenant_id', $tenantId)
                ->whereJsonContains('analysis->sentiment', 'negative')
                ->count(),
        ];

        // Anrufe nach Tagen - optimized query with proper column name
        $callsByDay = DB::table('calls')
            ->selectRaw('DATE(start_timestamp) as date, COUNT(*) as count')
            ->where('start_timestamp', '>=', now()->subDays(14))
            ->where('tenant_id', $tenantId)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Neueste Anrufe - with eager loading to avoid N+1
        $latestCalls = Call::with(['customer:id,name,phone', 'agent:id,name'])
            ->where('tenant_id', $tenantId)
            ->orderBy('start_timestamp', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'stats',
            'sentiments',
            'callsByDay',
            'latestCalls'
        ));
    }

    public function customers()
    {
        $customers = Customer::select('id', 'name', 'email', 'phone', 'created_at')
            ->where('tenant_id', auth()->user()->tenant_id ?? request()->header('X-Tenant-ID'))
            ->orderBy('name')
            ->paginate(15);
        return view('dashboard.customers', compact('customers'));
    }

    public function appointments()
    {
        $appointments = Appointment::with(['customer:id,name,phone', 'service:id,name', 'staff:id,name'])
            ->where('tenant_id', auth()->user()->tenant_id ?? request()->header('X-Tenant-ID'))
            ->orderBy('starts_at', 'desc')
            ->paginate(15);
        return view('dashboard.appointments', compact('appointments'));
    }

    public function calls()
    {
        $calls = Call::with(['customer:id,name,phone', 'agent:id,name'])
            ->where('tenant_id', auth()->user()->tenant_id ?? request()->header('X-Tenant-ID'))
            ->orderBy('start_timestamp', 'desc')
            ->paginate(15);
        return view('dashboard.calls', compact('calls'));
    }

    public function services() 
    {
        $services = Service::select('id', 'name', 'duration_minutes', 'price_cents', 'created_at')
            ->where('tenant_id', auth()->user()->tenant_id ?? request()->header('X-Tenant-ID'))
            ->orderBy('name')
            ->paginate(15);
        return view('dashboard.services', compact('services'));
    }

    public function staff()
    {
        $staff = Staff::select('id', 'name', 'email', 'phone', 'created_at')
            ->where('tenant_id', auth()->user()->tenant_id ?? request()->header('X-Tenant-ID'))
            ->orderBy('name')
            ->paginate(15);
        return view('dashboard.staff', compact('staff'));
    }
}
