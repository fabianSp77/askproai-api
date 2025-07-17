<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Display analytics dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // If admin viewing, get company from session
        if (session('is_admin_viewing')) {
            $companyId = session('admin_impersonation.company_id');
        } else {
            $companyId = $user->company_id;
        }
        
        // Get date range
        $startDate = $request->has('start_date') 
            ? \Carbon\Carbon::parse($request->get('start_date'))->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->has('end_date')
            ? \Carbon\Carbon::parse($request->get('end_date'))->endOfDay()
            : now()->endOfDay();
        
        // Get branch filter (optional)
        $branchId = $request->get('branch_id');
        
        // Get phone number filter (optional)
        $phoneNumber = $request->get('phone_number');
        
        // Get company branches for filter dropdown
        $branches = \App\Models\Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();
            
        // Get company phone numbers for filter dropdown
        $phoneNumbers = \App\Models\PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('branch')
            ->when($branchId, function ($query) use ($branchId) {
                return $query->where('branch_id', $branchId);
            })
            ->orderBy('is_primary', 'desc')
            ->orderBy('number')
            ->get();
        
        // Call statistics
        $callStats = $this->getCallStatistics($companyId, $startDate, $endDate, $branchId, $phoneNumber);
        
        // Appointment statistics (if enabled)
        $appointmentStats = null;
        if ($this->companyHasAppointments($companyId)) {
            $appointmentStats = $this->getAppointmentStatistics($companyId, $startDate, $endDate, $branchId);
        }
        
        // Hourly distribution
        $hourlyDistribution = $this->getHourlyDistribution($companyId, $startDate, $endDate, $branchId, $phoneNumber);
        
        // Top customers
        $topCustomers = $this->getTopCustomers($companyId, $startDate, $endDate, $branchId, $phoneNumber);
        
        // Load React SPA directly
        return app(\App\Http\Controllers\Portal\ReactDashboardController::class)->index();
    }
    
    /**
     * Get call statistics
     */
    protected function getCallStatistics($companyId, $startDate, $endDate, $branchId = null, $phoneNumber = null)
    {
        // Get all company phone numbers for filtering
        $companyPhoneNumbers = \App\Models\PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
            
        $query = Call::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        // Always filter by company's phone numbers to avoid showing calls from other companies
        if ($phoneNumber) {
            $query->where('to_number', $phoneNumber);
        } else {
            // When "All Numbers" is selected, only show calls to this company's numbers
            $query->whereIn('to_number', $companyPhoneNumbers);
        }
            
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $baseQuery = clone $query;
        
        return [
            'total_calls' => $baseQuery->count(),
                
            'total_duration' => (clone $query)->sum('duration_sec') ?: 0,
                
            'average_duration' => (clone $query)->avg('duration_sec') ?: 0,
                
            'calls_by_status' => DB::table('calls')
                ->select('status', DB::raw('COUNT(*) as count'))
                ->where('company_id', $companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->when($branchId, function ($q) use ($branchId) {
                    return $q->where('branch_id', $branchId);
                })
                ->when($phoneNumber, function ($q) use ($phoneNumber) {
                    return $q->where('to_number', $phoneNumber);
                }, function ($q) use ($companyPhoneNumbers) {
                    // When no specific phone selected, filter by company's numbers
                    return $q->whereIn('to_number', $companyPhoneNumbers);
                })
                ->groupBy('status')
                ->get()
        ];
    }
    
    /**
     * Get appointment statistics
     */
    protected function getAppointmentStatistics($companyId, $startDate, $endDate, $branchId = null)
    {
        return [
            'total_appointments' => Appointment::where('company_id', $companyId)
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->when($branchId, function ($q) use ($branchId) {
                    return $q->where('branch_id', $branchId);
                })
                ->count(),
                
            'appointments_by_status' => DB::table('appointments')
                ->select('status', DB::raw('COUNT(*) as count'))
                ->where('company_id', $companyId)
                ->whereBetween('starts_at', [$startDate, $endDate])
                ->when($branchId, function ($q) use ($branchId) {
                    return $q->where('branch_id', $branchId);
                })
                ->groupBy('status')
                ->get()
        ];
    }
    
    /**
     * Get hourly distribution
     */
    protected function getHourlyDistribution($companyId, $startDate, $endDate, $branchId = null, $phoneNumber = null)
    {
        // Get all company phone numbers for filtering
        $companyPhoneNumbers = \App\Models\PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
            
        return DB::table('calls')
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, function ($q) use ($branchId) {
                return $q->where('branch_id', $branchId);
            })
            ->when($phoneNumber, function ($q) use ($phoneNumber) {
                return $q->where('to_number', $phoneNumber);
            }, function ($q) use ($companyPhoneNumbers) {
                // When no specific phone selected, filter by company's numbers
                return $q->whereIn('to_number', $companyPhoneNumbers);
            })
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }
    
    /**
     * Get top customers
     */
    protected function getTopCustomers($companyId, $startDate, $endDate, $branchId = null, $phoneNumber = null)
    {
        // Get all company phone numbers for filtering
        $companyPhoneNumbers = \App\Models\PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
            
        return DB::table('calls')
            ->join('customers', 'calls.customer_id', '=', 'customers.id')
            ->select('customers.name', 'customers.phone', DB::raw('COUNT(*) as call_count'))
            ->where('calls.company_id', $companyId)
            ->whereBetween('calls.created_at', [$startDate, $endDate])
            ->when($branchId, function ($q) use ($branchId) {
                return $q->where('calls.branch_id', $branchId);
            })
            ->when($phoneNumber, function ($q) use ($phoneNumber) {
                return $q->where('calls.to_number', $phoneNumber);
            }, function ($q) use ($companyPhoneNumbers) {
                // When no specific phone selected, filter by company's numbers
                return $q->whereIn('calls.to_number', $companyPhoneNumbers);
            })
            ->groupBy('customers.id', 'customers.name', 'customers.phone')
            ->orderBy('call_count', 'desc')
            ->limit(10)
            ->get();
    }
    
    /**
     * Check if company has appointments module
     */
    protected function companyHasAppointments($companyId)
    {
        $company = \App\Models\Company::find($companyId);
        return $company && $company->settings && 
               isset($company->settings['needs_appointment_booking']) && 
               $company->settings['needs_appointment_booking'];
    }
}