<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show portal dashboard
     */
    public function index()
    {
        // Check admin viewing FIRST - before checking portal user
        if (session('is_admin_viewing') && session('admin_impersonation')) {
            \Log::info('DashboardController::index - Admin viewing mode', [
                'is_admin_viewing' => true,
                'admin_impersonation' => session('admin_impersonation'),
                'admin_viewing_company' => session('admin_viewing_company'),
                'session_id' => session()->getId(),
            ]);
            
            $adminImpersonation = session('admin_impersonation');
            if (isset($adminImpersonation['company_id'])) {
                // Admin is viewing - ignore any portal user login
                return $this->handleAdminViewing($adminImpersonation);
            }
        }
        
        // Normal portal user flow
        $user = Auth::guard('portal')->user();
        
        // Debug output
        \Log::info('DashboardController::index - Normal flow', [
            'portal_user' => $user ? $user->id : 'none',
            'portal_user_company' => $user ? $user->company_id : 'none',
        ]);
        
        // Original logic for non-admin viewing
        if (!$user) {
            $adminImpersonation = session('admin_impersonation');
            if ($adminImpersonation && isset($adminImpersonation['company_id'])) {
                $company = \App\Models\Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($adminImpersonation['company_id']);
                if (!$company) {
                    abort(404, 'Company not found');
                }
                
                \Log::info('DashboardController - Admin viewing company', [
                    'requested_company_id' => $adminImpersonation['company_id'],
                    'loaded_company_id' => $company->id,
                    'loaded_company_name' => $company->name,
                ]);
                
                // Create a dummy user object for the view with proper methods
                $user = new class($company) {
                    public $company;
                    public $company_id;
                    public $id = 'admin';
                    
                    public function __construct($company) {
                        $this->company = $company;
                        $this->company_id = $company->id;
                    }
                    
                    public function hasPermission($permission) {
                        return true; // Admin has all permissions
                    }
                    
                    public function canViewBilling() {
                        return true;
                    }
                    
                    public function teamMembers() {
                        return collect();
                    }
                };
            } else {
                abort(403, 'Invalid admin session');
            }
        } elseif ($user) {
            $company = $user->company;
            
            \Log::info('DashboardController - Regular portal user', [
                'user_id' => $user->id,
                'user_company_id' => $user->company_id,
                'company_name' => $company->name,
            ]);
        } else {
            abort(403, 'Unauthorized');
        }
        
        // Get statistics based on user permissions
        $stats = $this->getStatistics($user);
        
        // Get recent calls
        $recentCalls = $this->getRecentCalls($user);
        
        // Get upcoming tasks
        $upcomingTasks = $this->getUpcomingTasks($user);
        
        // Get team performance (for managers/owners)
        $teamPerformance = null;
        if ($user->hasPermission('analytics.view_team')) {
            $teamPerformance = $this->getTeamPerformance($user);
        }
        
        return view('portal.dashboard', compact(
            'user',
            'company',
            'stats',
            'recentCalls',
            'upcomingTasks',
            'teamPerformance'
        ));
    }
    
    /**
     * Get statistics based on permissions
     */
    protected function getStatistics($user)
    {
        $stats = [];
        
        // Get company phone numbers for filtering
        $companyPhoneNumbers = PhoneNumber::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
        
        // Call statistics
        if ($user->hasPermission('calls.view_all')) {
            $stats['total_calls_today'] = Call::where('company_id', $user->company_id)
                ->whereIn('to_number', $companyPhoneNumbers)
                ->whereDate('created_at', today())
                ->count();
                
            $stats['open_calls'] = DB::table('calls')
                ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
                ->where('calls.company_id', $user->company_id)
                ->whereIn('calls.to_number', $companyPhoneNumbers)
                ->whereNotIn('call_portal_data.status', ['completed', 'abandoned'])
                ->count();
        } elseif ($user->hasPermission('calls.view_own')) {
            $stats['my_calls_today'] = DB::table('calls')
                ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
                ->where('calls.company_id', $user->company_id)
                ->whereIn('calls.to_number', $companyPhoneNumbers)
                ->where('call_portal_data.assigned_to', $user->id)
                ->whereDate('calls.created_at', today())
                ->count();
                
            $stats['my_open_calls'] = DB::table('calls')
                ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
                ->where('calls.company_id', $user->company_id)
                ->whereIn('calls.to_number', $companyPhoneNumbers)
                ->where('call_portal_data.assigned_to', $user->id)
                ->whereNotIn('call_portal_data.status', ['completed', 'abandoned'])
                ->count();
        }
        
        // Appointment statistics (if enabled)
        if ($user->company->needsAppointmentBooking()) {
            if ($user->hasPermission('appointments.view_all')) {
                $stats['upcoming_appointments'] = Appointment::where('company_id', $user->company_id)
                    ->where('starts_at', '>=', now())
                    ->count();
            }
        }
        
        // Billing statistics (if permitted)
        if ($user->canViewBilling()) {
            $stats['open_invoices'] = Invoice::where('company_id', $user->company_id)
                ->where('status', 'open')
                ->count();
                
            $stats['total_due'] = Invoice::where('company_id', $user->company_id)
                ->where('status', 'open')
                ->sum('total');
        }
        
        return $stats;
    }
    
    /**
     * Get recent calls based on permissions
     */
    protected function getRecentCalls($user)
    {
        // Get company phone numbers for filtering
        $companyPhoneNumbers = PhoneNumber::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
            
        $query = Call::with(['branch', 'customer'])
            ->where('company_id', $user->company_id)
            ->whereIn('to_number', $companyPhoneNumbers);
        
        // Apply permission-based filtering
        if ($user->hasPermission('calls.view_own') && !$user->hasPermission('calls.view_all')) {
            $query->whereHas('callPortalData', function ($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        }
        
        return $query->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($call) {
                // Load portal data
                $portalData = DB::table('call_portal_data')
                    ->where('call_id', $call->id)
                    ->first();
                    
                $call->portal_status = $portalData->status ?? 'new';
                $call->portal_priority = $portalData->priority ?? 'medium';
                
                return $call;
            });
    }
    
    /**
     * Get upcoming tasks
     */
    protected function getUpcomingTasks($user)
    {
        $tasks = collect();
        
        // Get company phone numbers for filtering
        $companyPhoneNumbers = PhoneNumber::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
        
        // Get calls requiring action
        $callsQuery = DB::table('calls')
            ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
            ->where('calls.company_id', $user->company_id)
            ->whereIn('calls.to_number', $companyPhoneNumbers)
            ->whereNotNull('call_portal_data.next_action_date')
            ->where('call_portal_data.next_action_date', '<=', now()->addDays(3));
            
        if (!$user->hasPermission('calls.view_all')) {
            $callsQuery->where('call_portal_data.assigned_to', $user->id);
        }
        
        $calls = $callsQuery->select([
            'calls.id as call_id',
            'calls.phone_number',
            'call_portal_data.status',
            'call_portal_data.next_action_date',
            'call_portal_data.internal_notes'
        ])->get();
        
        foreach ($calls as $call) {
            $tasks->push([
                'type' => 'call_followup',
                'title' => "Anruf nachverfolgen: {$call->phone_number}",
                'due_date' => $call->next_action_date,
                'priority' => 'medium',
                'link' => route('business.calls.show', $call->call_id),
            ]);
        }
        
        return $tasks->sortBy('due_date')->take(5);
    }
    
    /**
     * Get team performance metrics
     */
    protected function getTeamPerformance($user)
    {
        $teamMembers = $user->teamMembers();
        
        // Get company phone numbers for filtering
        $companyPhoneNumbers = PhoneNumber::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();
        
        $performance = [];
        
        foreach ($teamMembers as $member) {
            $stats = DB::table('calls')
                ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
                ->where('calls.company_id', $user->company_id)
                ->whereIn('calls.to_number', $companyPhoneNumbers)
                ->where('call_portal_data.assigned_to', $member->id)
                ->whereDate('calls.created_at', '>=', now()->subDays(7))
                ->selectRaw('
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN call_portal_data.status = "completed" THEN 1 ELSE 0 END) as completed_calls,
                    AVG(CASE WHEN call_portal_data.status = "completed" 
                        THEN TIMESTAMPDIFF(HOUR, calls.created_at, call_portal_data.updated_at) 
                        ELSE NULL END) as avg_resolution_hours
                ')
                ->first();
                
            $performance[] = [
                'user' => $member,
                'total_calls' => $stats->total_calls,
                'completed_calls' => $stats->completed_calls,
                'completion_rate' => $stats->total_calls > 0 
                    ? round(($stats->completed_calls / $stats->total_calls) * 100) 
                    : 0,
                'avg_resolution_hours' => round($stats->avg_resolution_hours ?? 0, 1),
            ];
        }
        
        return collect($performance)->sortByDesc('completion_rate');
    }
    
    /**
     * Handle admin viewing mode
     */
    protected function handleAdminViewing($adminImpersonation)
    {
        $company = \App\Models\Company::withoutGlobalScope(\App\Scopes\TenantScope::class)->find($adminImpersonation['company_id']);
        if (!$company) {
            abort(404, 'Company not found');
        }
        
        \Log::info('DashboardController - Admin viewing company', [
            'requested_company_id' => $adminImpersonation['company_id'],
            'loaded_company_id' => $company->id,
            'loaded_company_name' => $company->name,
        ]);
        
        // Create a dummy user object for the view with proper methods
        $user = new class($company) {
            public $company;
            public $company_id;
            public $id = 'admin';
            
            public function __construct($company) {
                $this->company = $company;
                $this->company_id = $company->id;
            }
            
            public function hasPermission($permission) {
                return true; // Admin has all permissions
            }
            
            public function canViewBilling() {
                return true;
            }
            
            public function teamMembers() {
                return collect();
            }
        };
        
        // Get statistics based on user permissions
        $stats = $this->getStatistics($user);
        
        // Get recent calls
        $recentCalls = $this->getRecentCalls($user);
        
        // Get upcoming tasks
        $upcomingTasks = $this->getUpcomingTasks($user);
        
        // Get team performance (for managers/owners)
        $teamPerformance = $this->getTeamPerformance($user);
        
        return view('portal.dashboard', compact(
            'user',
            'company',
            'stats',
            'recentCalls',
            'upcomingTasks',
            'teamPerformance'
        ));
    }
}