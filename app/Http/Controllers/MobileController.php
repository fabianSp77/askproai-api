<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MobileController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        $stats = [
            'todayAppointments' => Appointment::whereDate('start', $today)->count(),
            'upcomingAppointments' => Appointment::where('start', '>', now())
                ->whereDate('start', $today)
                ->count(),
            'todayCalls' => Call::whereDate('created_at', $today)->count(),
            'answeredCalls' => Call::whereDate('created_at', $today)
                ->where('status', 'completed')
                ->count(),
            'newCustomersThisWeek' => Customer::where('created_at', '>=', now()->startOfWeek())
                ->count(),
        ];
        
        $todaySchedule = Appointment::with(['customer', 'staff', 'service'])
            ->whereDate('start', $today)
            ->orderBy('start')
            ->limit(5)
            ->get();
            
        $recentCalls = Call::with('customer')
            ->latest()
            ->limit(3)
            ->get();
        
        return view('mobile.dashboard', compact('stats', 'todaySchedule', 'recentCalls'));
    }
    
    public function appointments(Request $request)
    {
        $view = $request->get('view', 'week');
        $date = $request->get('date', now());
        
        $appointments = Appointment::with(['customer', 'staff', 'service'])
            ->when($view === 'day', function ($query) use ($date) {
                return $query->whereDate('start', $date);
            })
            ->when($view === 'week', function ($query) use ($date) {
                $startOfWeek = Carbon::parse($date)->startOfWeek();
                $endOfWeek = Carbon::parse($date)->endOfWeek();
                return $query->whereBetween('start', [$startOfWeek, $endOfWeek]);
            })
            ->orderBy('start')
            ->get();
        
        return view('mobile.appointments', compact('appointments', 'view', 'date'));
    }
    
    public function customers(Request $request)
    {
        $search = $request->get('search');
        $filter = $request->get('filter', 'all');
        
        $customers = Customer::query()
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filter === 'recent', function ($query) {
                return $query->where('created_at', '>=', now()->subDays(30));
            })
            ->when($filter === 'vip', function ($query) {
                return $query->where('is_vip', true);
            })
            ->when($filter === 'birthday', function ($query) {
                return $query->whereMonth('birthdate', now()->month);
            })
            ->orderBy('name')
            ->paginate(50);
        
        $stats = [
            'total' => Customer::count(),
            'newThisMonth' => Customer::whereMonth('created_at', now()->month)->count(),
            'birthdays' => Customer::whereMonth('birthdate', now()->month)->count(),
        ];
        
        return view('mobile.customers', compact('customers', 'stats', 'search', 'filter'));
    }
    
    public function profile()
    {
        $user = Auth::user();
        $company = $user->company;
        
        return view('mobile.profile', compact('user', 'company'));
    }
    
    public function calls()
    {
        $calls = Call::with(['customer', 'agent'])
            ->latest()
            ->paginate(20);
        
        return view('mobile.calls', compact('calls'));
    }
    
    // API endpoints for mobile app
    public function apiDashboardStats()
    {
        $today = Carbon::today();
        
        return response()->json([
            'appointments' => [
                'today' => Appointment::whereDate('start', $today)->count(),
                'upcoming' => Appointment::where('start', '>', now())
                    ->whereDate('start', $today)
                    ->count(),
                'week' => Appointment::whereBetween('start', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
            ],
            'calls' => [
                'today' => Call::whereDate('created_at', $today)->count(),
                'answered' => Call::whereDate('created_at', $today)
                    ->where('status', 'completed')
                    ->count(),
                'missed' => Call::whereDate('created_at', $today)
                    ->where('status', 'no-answer')
                    ->count(),
            ],
            'customers' => [
                'total' => Customer::count(),
                'new_week' => Customer::where('created_at', '>=', now()->startOfWeek())->count(),
                'new_month' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
        ]);
    }
    
    public function apiUpcomingAppointments()
    {
        $appointments = Appointment::with(['customer', 'staff', 'service'])
            ->where('start', '>=', now())
            ->orderBy('start')
            ->limit(10)
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'start' => $appointment->start->toIso8601String(),
                    'end' => $appointment->end->toIso8601String(),
                    'customer' => [
                        'id' => $appointment->customer->id,
                        'name' => $appointment->customer->name,
                        'phone' => $appointment->customer->phone,
                    ],
                    'service' => $appointment->service->name ?? null,
                    'staff' => $appointment->staff->name ?? null,
                    'status' => $appointment->status,
                ];
            });
        
        return response()->json(['data' => $appointments]);
    }
    
    public function apiSearchCustomers(Request $request)
    {
        $query = $request->get('q');
        
        if (strlen($query) < 2) {
            return response()->json(['data' => []]);
        }
        
        $customers = Customer::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'email' => $customer->email,
                    'visits' => $customer->appointments()->count(),
                    'last_visit' => $customer->appointments()->latest()->first()?->start,
                ];
            });
        
        return response()->json(['data' => $customers]);
    }
}