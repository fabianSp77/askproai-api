<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SimpleCustomerController extends Controller
{
    /**
     * Display customers list
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        $query = Customer::where('company_id', $user->company_id)
            ->withCount('appointments')
            ->with(['appointments' => function($q) {
                $q->latest()->limit(1);
            }]);
            
        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        $customers = $query->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Use unified layout for consistency
        return view('portal.customers.index-unified', compact('customers'));
    }
    
    /**
     * Show customer details
     */
    public function show($id)
    {
        $user = Auth::guard('portal')->user();
        
        if (!$user || !$user->company_id) {
            abort(401, 'Unauthorized');
        }
        
        $customer = Customer::where('company_id', $user->company_id)
            ->where('id', $id)
            ->with(['appointments' => function($q) {
                $q->with(['service', 'staff', 'branch'])
                  ->orderBy('starts_at', 'desc')
                  ->limit(10);
            }])
            ->withCount('appointments')
            ->firstOrFail();
        
        // Calculate stats
        $stats = [
            'total_appointments' => $customer->appointments_count,
            'completed_appointments' => $customer->appointments()->where('status', 'completed')->count(),
            'cancelled_appointments' => $customer->appointments()->where('status', 'cancelled')->count(),
            'no_show_appointments' => $customer->appointments()->where('status', 'no_show')->count(),
        ];
        
        // Use unified layout for consistency
        return view('portal.customers.show-unified', compact('customer', 'stats'));
    }
}