<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Traits\UsesMCPServers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    use UsesMCPServers;

    public function __construct()
    {
        $this->setMCPPreferences([
            'dashboard' => true,
            'analytics' => true,
            'call' => true,
            'billing' => true
        ]);
    }

    /**
     * Show portal dashboard.
     */
    public function index(Request $request)
    {
        $user = Auth::guard('portal')->user();
        
        // Check if user is authenticated
        if (!$user && !session('is_admin_viewing')) {
            return redirect('/business/login');
        }
        
        // Handle admin viewing mode
        if (session('is_admin_viewing') && !$user) {
            return $this->handleAdminViewing();
        }
        
        $companyId = $this->getCompanyId();
        $userId = $this->getCurrentUserId();
        
        // Get dashboard statistics via MCP
        $statsResult = $this->executeMCPTask('getDashboardStatistics', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'include_billing' => $user->canViewBilling(),
            'include_appointments' => $user->company->needsAppointmentBooking()
        ]);

        $stats = $statsResult['result']['data'] ?? [];

        // Get recent calls via MCP
        $recentCallsResult = $this->executeMCPTask('getRecentCalls', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'limit' => 10,
            'only_assigned' => $user->hasPermission('calls.view_own') && !$user->hasPermission('calls.view_all')
        ]);

        $recentCalls = $recentCallsResult['result']['data'] ?? [];

        // Get upcoming tasks via MCP
        $tasksResult = $this->executeMCPTask('getUpcomingTasks', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'days_ahead' => 3,
            'limit' => 5
        ]);

        $upcomingTasks = $tasksResult['result']['data'] ?? [];

        // Get team performance if permitted
        $teamPerformance = null;
        if ($user->hasPermission('analytics.view_team')) {
            $performanceResult = $this->executeMCPTask('getTeamPerformance', [
                'company_id' => $companyId,
                'days' => 7,
                'include_details' => false
            ]);
            $teamPerformance = $performanceResult['result']['data'] ?? [];
        }

        // Pass data to the unified dashboard view
        return view('portal.dashboard-unified', compact(
            'user',
            'stats',
            'recentCalls',
            'upcomingTasks',
            'teamPerformance'
        ));
    }

    /**
     * API endpoint for dashboard data.
     */
    public function apiDashboard(Request $request)
    {
        $companyId = $this->getCompanyId();
        $userId = $this->getCurrentUserId();
        
        if (!$companyId) {
            return response()->json(['error' => 'No company context'], 403);
        }

        // Get all dashboard data via MCP
        $data = [];

        // Statistics
        $statsResult = $this->executeMCPTask('getDashboardStatistics', [
            'company_id' => $companyId,
            'user_id' => $userId
        ]);
        $data['statistics'] = $statsResult['result']['data'] ?? [];

        // Call trends
        $trendsResult = $this->executeMCPTask('getCallTrends', [
            'company_id' => $companyId,
            'period' => $request->get('trend_period', 'week'),
            'intervals' => 7
        ]);
        $data['call_trends'] = $trendsResult['result']['data'] ?? [];

        // Quick insights
        $insightsResult = $this->executeMCPTask('getQuickInsights', [
            'company_id' => $companyId,
            'focus_area' => 'all'
        ]);
        $data['insights'] = $insightsResult['result']['data'] ?? [];

        // Recent calls
        $callsResult = $this->executeMCPTask('getRecentCalls', [
            'company_id' => $companyId,
            'user_id' => $userId,
            'limit' => 5
        ]);
        $data['recent_calls'] = $callsResult['result']['data'] ?? [];

        return response()->json($data);
    }

    /**
     * API endpoint for analytics data.
     */
    public function apiAnalytics(Request $request)
    {
        $companyId = $this->getCompanyId();
        
        if (!$companyId) {
            return response()->json(['error' => 'No company context'], 403);
        }

        $type = $request->get('type', 'overview');
        $data = [];

        switch ($type) {
            case 'conversion':
                $result = $this->executeMCPTask('getConversionMetrics', [
                    'company_id' => $companyId,
                    'date_from' => $request->get('date_from'),
                    'date_to' => $request->get('date_to')
                ]);
                $data = $result['result']['data'] ?? [];
                break;

            case 'revenue':
                $result = $this->executeMCPTask('getRevenueSummary', [
                    'company_id' => $companyId,
                    'period' => $request->get('period', 'month')
                ]);
                $data = $result['result']['data'] ?? [];
                break;

            case 'team':
                $result = $this->executeMCPTask('getTeamPerformance', [
                    'company_id' => $companyId,
                    'days' => $request->get('days', 7),
                    'include_details' => true
                ]);
                $data = $result['result']['data'] ?? [];
                break;

            default:
                // Return overview with multiple metrics
                $statsResult = $this->executeMCPTask('getDashboardStatistics', [
                    'company_id' => $companyId
                ]);
                $data['statistics'] = $statsResult['result']['data'] ?? [];

                $trendsResult = $this->executeMCPTask('getCallTrends', [
                    'company_id' => $companyId
                ]);
                $data['trends'] = $trendsResult['result']['data'] ?? [];
        }

        return response()->json($data);
    }

    /**
     * Handle admin viewing mode.
     */
    protected function handleAdminViewing()
    {
        $adminImpersonation = session('admin_impersonation');
        if (!isset($adminImpersonation['company_id'])) {
            abort(403, 'Invalid admin session');
        }

        $company = \App\Models\Company::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->find($adminImpersonation['company_id']);
        
        if (!$company) {
            abort(404, 'Company not found');
        }

        // Create admin user object
        $user = new class($company) {
            public $company;
            public $company_id;
            public $id = 'admin';

            public function __construct($company)
            {
                $this->company = $company;
                $this->company_id = $company->id;
            }

            public function hasPermission($permission)
            {
                return true; // Admin has all permissions
            }

            public function canViewBilling()
            {
                return true;
            }

            public function needsAppointmentBooking()
            {
                return $this->company->needsAppointmentBooking();
            }
        };

        // Get all dashboard data via MCP
        $companyId = $company->id;
        
        // Get dashboard statistics
        $statsResult = $this->executeMCPTask('getDashboardStatistics', [
            'company_id' => $companyId,
            'include_billing' => true,
            'include_appointments' => $company->needsAppointmentBooking()
        ]);

        $stats = $statsResult['result']['data'] ?? [];

        // Get recent calls
        $recentCallsResult = $this->executeMCPTask('getRecentCalls', [
            'company_id' => $companyId,
            'limit' => 10
        ]);

        $recentCalls = $recentCallsResult['result']['data'] ?? [];

        // Get upcoming tasks
        $tasksResult = $this->executeMCPTask('getUpcomingTasks', [
            'company_id' => $companyId,
            'days_ahead' => 3,
            'limit' => 5
        ]);

        $upcomingTasks = $tasksResult['result']['data'] ?? [];

        // Get team performance
        $performanceResult = $this->executeMCPTask('getTeamPerformance', [
            'company_id' => $companyId,
            'days' => 7,
            'include_details' => false
        ]);

        $teamPerformance = $performanceResult['result']['data'] ?? [];

        // Pass data to view
        return view('portal.dashboard-unified', compact(
            'user',
            'stats',
            'recentCalls',
            'upcomingTasks',
            'teamPerformance'
        ));
    }

    /**
     * Get company ID for current context.
     */
    protected function getCompanyId(): ?int
    {
        if (session('is_admin_viewing')) {
            return session('admin_impersonation.company_id');
        }
        
        $user = Auth::guard('portal')->user();
        return $user ? $user->company_id : null;
    }
    
    /**
     * Get current user ID.
     */
    protected function getCurrentUserId(): ?int
    {
        $user = Auth::guard('portal')->user();
        return $user ? $user->id : null;
    }
}
