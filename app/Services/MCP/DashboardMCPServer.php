<?php

namespace App\Services\MCP;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\PhoneNumber;
use App\Models\CallPortalData;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardMCPServer
{
    protected string $name = 'Dashboard Analytics MCP Server';
    protected string $version = '1.0.0';

    /**
     * Get available tools for this MCP server.
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'getDashboardStatistics',
                'description' => 'Get comprehensive dashboard statistics for the current company',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'user_id' => ['type' => 'integer'],
                        'include_billing' => ['type' => 'boolean', 'default' => true],
                        'include_appointments' => ['type' => 'boolean', 'default' => true]
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'getRecentCalls',
                'description' => 'Get recent calls with portal data and related information',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'user_id' => ['type' => 'integer'],
                        'limit' => ['type' => 'integer', 'default' => 10],
                        'only_assigned' => ['type' => 'boolean', 'default' => false]
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'getUpcomingTasks',
                'description' => 'Get upcoming tasks and follow-ups',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'user_id' => ['type' => 'integer'],
                        'days_ahead' => ['type' => 'integer', 'default' => 3],
                        'limit' => ['type' => 'integer', 'default' => 5]
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'getTeamPerformance',
                'description' => 'Get team performance metrics for the last 7 days',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'days' => ['type' => 'integer', 'default' => 7],
                        'include_details' => ['type' => 'boolean', 'default' => true]
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'getCallTrends',
                'description' => 'Get call volume trends over time',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'period' => ['type' => 'string', 'enum' => ['day', 'week', 'month'], 'default' => 'week'],
                        'intervals' => ['type' => 'integer', 'default' => 7]
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'getConversionMetrics',
                'description' => 'Get conversion metrics from calls to appointments',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'date_from' => ['type' => 'string', 'format' => 'date'],
                        'date_to' => ['type' => 'string', 'format' => 'date']
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'getRevenueSummary',
                'description' => 'Get revenue summary and projections',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'period' => ['type' => 'string', 'enum' => ['today', 'week', 'month', 'quarter'], 'default' => 'month']
                    ],
                    'required' => ['company_id']
                ]
            ],
            [
                'name' => 'getQuickInsights',
                'description' => 'Get AI-generated insights and recommendations',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'company_id' => ['type' => 'integer', 'required' => true],
                        'focus_area' => ['type' => 'string', 'enum' => ['performance', 'revenue', 'efficiency', 'all'], 'default' => 'all']
                    ],
                    'required' => ['company_id']
                ]
            ]
        ];
    }

    /**
     * Handle tool execution.
     */
    public function executeTool(string $name, array $arguments): array
    {
        try {
            switch ($name) {
                case 'getDashboardStatistics':
                    return $this->getDashboardStatistics($arguments);
                case 'getRecentCalls':
                    return $this->getRecentCalls($arguments);
                case 'getUpcomingTasks':
                    return $this->getUpcomingTasks($arguments);
                case 'getTeamPerformance':
                    return $this->getTeamPerformance($arguments);
                case 'getCallTrends':
                    return $this->getCallTrends($arguments);
                case 'getConversionMetrics':
                    return $this->getConversionMetrics($arguments);
                case 'getRevenueSummary':
                    return $this->getRevenueSummary($arguments);
                case 'getQuickInsights':
                    return $this->getQuickInsights($arguments);
                default:
                    throw new \Exception("Unknown tool: {$name}");
            }
        } catch (\Exception $e) {
            \Log::error("DashboardMCPServer error in {$name}", [
                'error' => $e->getMessage(),
                'arguments' => $arguments
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get comprehensive dashboard statistics.
     */
    protected function getDashboardStatistics(array $params): array
    {
        $companyId = $params['company_id'];
        $userId = $params['user_id'] ?? null;
        $cacheKey = "dashboard.stats.{$companyId}" . ($userId ? ".{$userId}" : "");

        return Cache::remember($cacheKey, 300, function () use ($companyId, $userId, $params) {
            $stats = [];

            // Get company phone numbers for filtering
            $companyPhoneNumbers = PhoneNumber::where('company_id', $companyId)
                ->where('is_active', true)
                ->pluck('number')
                ->toArray();

            // Call statistics
            $callStats = $this->getCallStatistics($companyId, $companyPhoneNumbers, $userId);
            $stats = array_merge($stats, $callStats);

            // Appointment statistics
            if ($params['include_appointments'] ?? true) {
                $company = Company::find($companyId);
                if ($company && $company->needsAppointmentBooking()) {
                    $appointmentStats = $this->getAppointmentStatistics($companyId, $userId);
                    $stats = array_merge($stats, $appointmentStats);
                }
            }

            // Billing statistics
            if ($params['include_billing'] ?? true) {
                $billingStats = $this->getBillingStatistics($companyId);
                $stats = array_merge($stats, $billingStats);
            }

            // Additional metrics
            $stats['response_rate'] = $this->calculateResponseRate($companyId, $companyPhoneNumbers);
            $stats['avg_resolution_time'] = $this->calculateAvgResolutionTime($companyId, $companyPhoneNumbers);

            return [
                'success' => true,
                'data' => $stats,
                'cached_until' => now()->addMinutes(5)->toIso8601String()
            ];
        });
    }

    /**
     * Get call statistics.
     */
    protected function getCallStatistics($companyId, $phoneNumbers, $userId = null): array
    {
        $stats = [];

        // Base query
        $baseQuery = Call::where('company_id', $companyId)
            ->whereIn('to_number', $phoneNumbers);

        // Today's calls
        $stats['total_calls_today'] = (clone $baseQuery)
            ->whereDate('created_at', today())
            ->count();

        // Open calls
        $openCallsQuery = DB::table('calls')
            ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
            ->where('calls.company_id', $companyId)
            ->whereIn('calls.to_number', $phoneNumbers)
            ->whereNotIn('call_portal_data.status', ['completed', 'abandoned']);

        if ($userId) {
            $openCallsQuery->where('call_portal_data.assigned_to', $userId);
        }

        $stats['open_calls'] = $openCallsQuery->count();

        // Calls by status
        $callsByStatus = DB::table('calls')
            ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
            ->where('calls.company_id', $companyId)
            ->whereIn('calls.to_number', $phoneNumbers)
            ->when($userId, function ($q) use ($userId) {
                $q->where('call_portal_data.assigned_to', $userId);
            })
            ->groupBy('call_portal_data.status')
            ->select('call_portal_data.status', DB::raw('COUNT(*) as count'))
            ->pluck('count', 'status')
            ->toArray();

        $stats['calls_by_status'] = $callsByStatus;

        // Average call duration
        $avgDuration = (clone $baseQuery)
            ->whereDate('created_at', today())
            ->avg('duration_sec');

        $stats['avg_call_duration_today'] = round($avgDuration ?? 0);

        // Missed calls today
        $stats['missed_calls_today'] = (clone $baseQuery)
            ->whereDate('created_at', today())
            ->where('duration_sec', 0)
            ->count();

        return $stats;
    }

    /**
     * Get appointment statistics.
     */
    protected function getAppointmentStatistics($companyId, $userId = null): array
    {
        $stats = [];

        $baseQuery = Appointment::where('company_id', $companyId);

        // Upcoming appointments
        $stats['upcoming_appointments'] = (clone $baseQuery)
            ->where('starts_at', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->count();

        // Today's appointments
        $stats['appointments_today'] = (clone $baseQuery)
            ->whereDate('starts_at', today())
            ->where('status', '!=', 'cancelled')
            ->count();

        // This week's appointments
        $stats['appointments_this_week'] = (clone $baseQuery)
            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', '!=', 'cancelled')
            ->count();

        // No-show rate (last 30 days)
        $totalCompleted = (clone $baseQuery)
            ->whereDate('starts_at', '<=', today())
            ->whereDate('starts_at', '>=', today()->subDays(30))
            ->whereIn('status', ['completed', 'no_show'])
            ->count();

        $noShows = (clone $baseQuery)
            ->whereDate('starts_at', '<=', today())
            ->whereDate('starts_at', '>=', today()->subDays(30))
            ->where('status', 'no_show')
            ->count();

        $stats['no_show_rate'] = $totalCompleted > 0 
            ? round(($noShows / $totalCompleted) * 100, 1) 
            : 0;

        return $stats;
    }

    /**
     * Get billing statistics.
     */
    protected function getBillingStatistics($companyId): array
    {
        $stats = [];

        // Open invoices
        $stats['open_invoices'] = Invoice::where('company_id', $companyId)
            ->where('status', 'open')
            ->count();

        // Total amount due
        $stats['total_due'] = Invoice::where('company_id', $companyId)
            ->where('status', 'open')
            ->sum('total');

        // Revenue this month
        $stats['revenue_this_month'] = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        // Average invoice value
        $stats['avg_invoice_value'] = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->avg('total') ?? 0;

        return $stats;
    }

    /**
     * Calculate response rate.
     */
    protected function calculateResponseRate($companyId, $phoneNumbers): float
    {
        $totalCalls = Call::where('company_id', $companyId)
            ->whereIn('to_number', $phoneNumbers)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->count();

        $answeredCalls = Call::where('company_id', $companyId)
            ->whereIn('to_number', $phoneNumbers)
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->where('duration_sec', '>', 0)
            ->count();

        return $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100, 1) : 0;
    }

    /**
     * Calculate average resolution time.
     */
    protected function calculateAvgResolutionTime($companyId, $phoneNumbers): float
    {
        $avgHours = DB::table('calls')
            ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
            ->where('calls.company_id', $companyId)
            ->whereIn('calls.to_number', $phoneNumbers)
            ->where('call_portal_data.status', 'completed')
            ->whereDate('calls.created_at', '>=', now()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, calls.created_at, call_portal_data.updated_at)) as avg_hours')
            ->value('avg_hours');

        return round($avgHours ?? 0, 1);
    }

    /**
     * Get recent calls with portal data.
     */
    protected function getRecentCalls(array $params): array
    {
        $companyId = $params['company_id'];
        $userId = $params['user_id'] ?? null;
        $limit = $params['limit'] ?? 10;
        $onlyAssigned = $params['only_assigned'] ?? false;

        // Get company phone numbers
        $companyPhoneNumbers = PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();

        $query = Call::with(['branch', 'customer', 'callPortalData', 'appointment'])
            ->where('company_id', $companyId)
            ->whereIn('to_number', $companyPhoneNumbers);

        // Apply user filter if specified
        if ($userId && $onlyAssigned) {
            $query->whereHas('callPortalData', function ($q) use ($userId) {
                $q->where('assigned_to', $userId);
            });
        }

        $calls = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($call) {
                $portalData = $call->callPortalData;
                
                return [
                    'id' => $call->id,
                    'phone_number' => $call->phone_number,
                    'from_number' => $call->from_number,
                    'duration_sec' => $call->duration_sec,
                    'created_at' => $call->created_at->toIso8601String(),
                    'status' => $portalData->status ?? 'new',
                    'priority' => $portalData->priority ?? 'medium',
                    'assigned_to' => $portalData->assigned_to ?? null,
                    'customer' => $call->customer ? [
                        'id' => $call->customer->id,
                        'name' => $call->customer->name,
                        'email' => $call->customer->email
                    ] : null,
                    'branch' => $call->branch ? [
                        'id' => $call->branch->id,
                        'name' => $call->branch->name
                    ] : null,
                    'appointment' => $call->appointment ? [
                        'id' => $call->appointment->id,
                        'starts_at' => $call->appointment->starts_at->toIso8601String(),
                        'status' => $call->appointment->status
                    ] : null,
                    'transcript_summary' => $call->transcript_summary
                ];
            });

        return [
            'success' => true,
            'data' => $calls,
            'total' => $calls->count()
        ];
    }

    /**
     * Get upcoming tasks and follow-ups.
     */
    protected function getUpcomingTasks(array $params): array
    {
        $companyId = $params['company_id'];
        $userId = $params['user_id'] ?? null;
        $daysAhead = $params['days_ahead'] ?? 3;
        $limit = $params['limit'] ?? 5;

        $tasks = collect();

        // Get company phone numbers
        $companyPhoneNumbers = PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();

        // Get calls requiring action
        $callsQuery = DB::table('calls')
            ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
            ->where('calls.company_id', $companyId)
            ->whereIn('calls.to_number', $companyPhoneNumbers)
            ->whereNotNull('call_portal_data.next_action_date')
            ->where('call_portal_data.next_action_date', '<=', now()->addDays($daysAhead));

        if ($userId) {
            $callsQuery->where('call_portal_data.assigned_to', $userId);
        }

        $calls = $callsQuery->select([
            'calls.id as call_id',
            'calls.phone_number',
            'call_portal_data.status',
            'call_portal_data.next_action_date',
            'call_portal_data.internal_notes',
        ])->get();

        foreach ($calls as $call) {
            $tasks->push([
                'id' => "call_followup_{$call->call_id}",
                'type' => 'call_followup',
                'title' => "Follow up call: {$call->phone_number}",
                'description' => $call->internal_notes ?? 'No notes available',
                'due_date' => Carbon::parse($call->next_action_date)->toIso8601String(),
                'priority' => 'medium',
                'status' => $call->status,
                'link' => "/business/calls/{$call->call_id}"
            ]);
        }

        // Get upcoming appointments requiring preparation
        if (Company::find($companyId)->needsAppointmentBooking()) {
            $appointments = Appointment::where('company_id', $companyId)
                ->where('starts_at', '>=', now())
                ->where('starts_at', '<=', now()->addDays($daysAhead))
                ->where('status', '!=', 'cancelled')
                ->when($userId, function ($q) use ($userId) {
                    $q->where('staff_id', $userId);
                })
                ->with(['customer', 'service'])
                ->get();

            foreach ($appointments as $appointment) {
                $tasks->push([
                    'id' => "appointment_prep_{$appointment->id}",
                    'type' => 'appointment_preparation',
                    'title' => "Prepare for appointment with {$appointment->customer->name}",
                    'description' => $appointment->service->name ?? 'Service appointment',
                    'due_date' => $appointment->starts_at->toIso8601String(),
                    'priority' => 'high',
                    'link' => "/business/appointments/{$appointment->id}"
                ]);
            }
        }

        // Sort by due date and limit
        $sortedTasks = $tasks->sortBy('due_date')->take($limit)->values();

        return [
            'success' => true,
            'data' => $sortedTasks,
            'total' => $tasks->count()
        ];
    }

    /**
     * Get team performance metrics.
     */
    protected function getTeamPerformance(array $params): array
    {
        $companyId = $params['company_id'];
        $days = $params['days'] ?? 7;
        $includeDetails = $params['include_details'] ?? true;

        // Get all team members for the company
        $teamMembers = User::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        // Get company phone numbers
        $companyPhoneNumbers = PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();

        $performance = [];

        foreach ($teamMembers as $member) {
            $stats = DB::table('calls')
                ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
                ->where('calls.company_id', $companyId)
                ->whereIn('calls.to_number', $companyPhoneNumbers)
                ->where('call_portal_data.assigned_to', $member->id)
                ->whereDate('calls.created_at', '>=', now()->subDays($days))
                ->selectRaw('
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN call_portal_data.status = "completed" THEN 1 ELSE 0 END) as completed_calls,
                    SUM(CASE WHEN call_portal_data.status = "in_progress" THEN 1 ELSE 0 END) as in_progress_calls,
                    SUM(CASE WHEN call_portal_data.status = "requires_action" THEN 1 ELSE 0 END) as requires_action_calls,
                    AVG(CASE WHEN call_portal_data.status = "completed" 
                        THEN TIMESTAMPDIFF(HOUR, calls.created_at, call_portal_data.updated_at) 
                        ELSE NULL END) as avg_resolution_hours,
                    SUM(calls.duration_sec) as total_talk_time
                ')
                ->first();

            $performanceData = [
                'user_id' => $member->id,
                'user_name' => $member->name,
                'user_email' => $member->email,
                'total_calls' => $stats->total_calls,
                'completed_calls' => $stats->completed_calls,
                'in_progress_calls' => $stats->in_progress_calls,
                'requires_action_calls' => $stats->requires_action_calls,
                'completion_rate' => $stats->total_calls > 0
                    ? round(($stats->completed_calls / $stats->total_calls) * 100, 1)
                    : 0,
                'avg_resolution_hours' => round($stats->avg_resolution_hours ?? 0, 1),
                'total_talk_time_minutes' => round($stats->total_talk_time / 60, 1)
            ];

            if ($includeDetails) {
                // Get daily breakdown
                $dailyStats = DB::table('calls')
                    ->join('call_portal_data', 'calls.id', '=', 'call_portal_data.call_id')
                    ->where('calls.company_id', $companyId)
                    ->whereIn('calls.to_number', $companyPhoneNumbers)
                    ->where('call_portal_data.assigned_to', $member->id)
                    ->whereDate('calls.created_at', '>=', now()->subDays($days))
                    ->groupBy(DB::raw('DATE(calls.created_at)'))
                    ->selectRaw('
                        DATE(calls.created_at) as date,
                        COUNT(*) as calls_count,
                        SUM(CASE WHEN call_portal_data.status = "completed" THEN 1 ELSE 0 END) as completed_count
                    ')
                    ->orderBy('date')
                    ->get();

                $performanceData['daily_breakdown'] = $dailyStats;
            }

            $performance[] = $performanceData;
        }

        // Sort by completion rate descending
        usort($performance, function ($a, $b) {
            return $b['completion_rate'] <=> $a['completion_rate'];
        });

        return [
            'success' => true,
            'data' => $performance,
            'period_days' => $days,
            'team_size' => count($teamMembers)
        ];
    }

    /**
     * Get call volume trends.
     */
    protected function getCallTrends(array $params): array
    {
        $companyId = $params['company_id'];
        $period = $params['period'] ?? 'week';
        $intervals = $params['intervals'] ?? 7;

        // Get company phone numbers
        $companyPhoneNumbers = PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();

        $trends = [];
        
        // Determine date range and grouping
        switch ($period) {
            case 'day':
                $startDate = now()->subDays($intervals);
                $groupBy = 'DATE(created_at)';
                $dateFormat = '%Y-%m-%d';
                break;
            case 'week':
                $startDate = now()->subWeeks($intervals);
                $groupBy = 'YEARWEEK(created_at)';
                $dateFormat = '%Y-W%u';
                break;
            case 'month':
                $startDate = now()->subMonths($intervals);
                $groupBy = 'DATE_FORMAT(created_at, "%Y-%m")';
                $dateFormat = '%Y-%m';
                break;
            default:
                $startDate = now()->subDays($intervals);
                $groupBy = 'DATE(created_at)';
                $dateFormat = '%Y-%m-%d';
        }

        $data = DB::table('calls')
            ->where('company_id', $companyId)
            ->whereIn('to_number', $companyPhoneNumbers)
            ->where('created_at', '>=', $startDate)
            ->groupBy(DB::raw($groupBy))
            ->selectRaw("
                DATE_FORMAT(created_at, '{$dateFormat}') as period,
                COUNT(*) as total_calls,
                SUM(CASE WHEN duration_sec > 0 THEN 1 ELSE 0 END) as answered_calls,
                SUM(CASE WHEN duration_sec = 0 THEN 1 ELSE 0 END) as missed_calls,
                AVG(duration_sec) as avg_duration,
                SUM(duration_sec) as total_duration
            ")
            ->orderBy('period')
            ->get();

        return [
            'success' => true,
            'data' => $data,
            'period_type' => $period,
            'intervals' => $intervals
        ];
    }

    /**
     * Get conversion metrics from calls to appointments.
     */
    protected function getConversionMetrics(array $params): array
    {
        $companyId = $params['company_id'];
        $dateFrom = $params['date_from'] ?? now()->subMonth()->format('Y-m-d');
        $dateTo = $params['date_to'] ?? now()->format('Y-m-d');

        // Get company phone numbers
        $companyPhoneNumbers = PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();

        // Total calls in period
        $totalCalls = Call::where('company_id', $companyId)
            ->whereIn('to_number', $companyPhoneNumbers)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        // Calls with appointments
        $callsWithAppointments = Call::where('company_id', $companyId)
            ->whereIn('to_number', $companyPhoneNumbers)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereNotNull('appointment_id')
            ->count();

        // Total appointments created
        $totalAppointments = Appointment::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->count();

        // Appointment sources breakdown
        $appointmentSources = Appointment::where('company_id', $companyId)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->groupBy('source')
            ->selectRaw('source, COUNT(*) as count')
            ->pluck('count', 'source')
            ->toArray();

        // Calculate conversion rate
        $conversionRate = $totalCalls > 0 
            ? round(($callsWithAppointments / $totalCalls) * 100, 1)
            : 0;

        // Average time from call to appointment
        $avgTimeToAppointment = DB::table('calls')
            ->join('appointments', 'calls.appointment_id', '=', 'appointments.id')
            ->where('calls.company_id', $companyId)
            ->whereIn('calls.to_number', $companyPhoneNumbers)
            ->whereBetween('calls.created_at', [$dateFrom, $dateTo])
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, calls.created_at, appointments.created_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'success' => true,
            'data' => [
                'period' => [
                    'from' => $dateFrom,
                    'to' => $dateTo
                ],
                'total_calls' => $totalCalls,
                'calls_with_appointments' => $callsWithAppointments,
                'total_appointments' => $totalAppointments,
                'conversion_rate' => $conversionRate,
                'avg_time_to_appointment_hours' => round($avgTimeToAppointment ?? 0, 1),
                'appointment_sources' => $appointmentSources
            ]
        ];
    }

    /**
     * Get revenue summary and projections.
     */
    protected function getRevenueSummary(array $params): array
    {
        $companyId = $params['company_id'];
        $period = $params['period'] ?? 'month';

        $stats = [];

        // Determine date range
        switch ($period) {
            case 'today':
                $startDate = today();
                $endDate = today();
                $prevStartDate = today()->subDay();
                $prevEndDate = today()->subDay();
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                $prevStartDate = now()->subWeek()->startOfWeek();
                $prevEndDate = now()->subWeek()->endOfWeek();
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                $prevStartDate = now()->subMonth()->startOfMonth();
                $prevEndDate = now()->subMonth()->endOfMonth();
                break;
            case 'quarter':
                $startDate = now()->firstOfQuarter();
                $endDate = now()->lastOfQuarter();
                $prevStartDate = now()->subQuarter()->firstOfQuarter();
                $prevEndDate = now()->subQuarter()->lastOfQuarter();
                break;
            default:
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                $prevStartDate = now()->subMonth()->startOfMonth();
                $prevEndDate = now()->subMonth()->endOfMonth();
        }

        // Current period revenue
        $currentRevenue = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->sum('total');

        // Previous period revenue
        $previousRevenue = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$prevStartDate, $prevEndDate])
            ->sum('total');

        // Calculate growth
        $growth = $previousRevenue > 0 
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;

        // Outstanding revenue
        $outstandingRevenue = Invoice::where('company_id', $companyId)
            ->where('status', 'open')
            ->sum('total');

        // Get revenue by service
        $revenueByService = DB::table('invoices')
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.company_id', $companyId)
            ->where('invoices.status', 'paid')
            ->whereBetween('invoices.paid_at', [$startDate, $endDate])
            ->groupBy('invoice_items.description')
            ->selectRaw('invoice_items.description as service, SUM(invoice_items.total) as revenue')
            ->orderBy('revenue', 'desc')
            ->limit(5)
            ->get();

        // Calculate projected revenue (simple projection based on current run rate)
        $daysInPeriod = $startDate->diffInDays($endDate) + 1;
        $daysPassed = $startDate->diffInDays(now()) + 1;
        $dailyRate = $daysPassed > 0 ? $currentRevenue / $daysPassed : 0;
        $projectedRevenue = $dailyRate * $daysInPeriod;

        return [
            'success' => true,
            'data' => [
                'period' => $period,
                'date_range' => [
                    'from' => $startDate->format('Y-m-d'),
                    'to' => $endDate->format('Y-m-d')
                ],
                'current_revenue' => round($currentRevenue, 2),
                'previous_revenue' => round($previousRevenue, 2),
                'growth_percentage' => $growth,
                'outstanding_revenue' => round($outstandingRevenue, 2),
                'projected_revenue' => round($projectedRevenue, 2),
                'revenue_by_service' => $revenueByService,
                'average_invoice_value' => Invoice::where('company_id', $companyId)
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [$startDate, $endDate])
                    ->avg('total') ?? 0
            ]
        ];
    }

    /**
     * Get AI-generated insights and recommendations.
     */
    protected function getQuickInsights(array $params): array
    {
        $companyId = $params['company_id'];
        $focusArea = $params['focus_area'] ?? 'all';

        $insights = [];

        // Get various metrics for analysis
        $metrics = $this->gatherMetricsForInsights($companyId);

        // Performance insights
        if (in_array($focusArea, ['performance', 'all'])) {
            $insights = array_merge($insights, $this->generatePerformanceInsights($metrics));
        }

        // Revenue insights
        if (in_array($focusArea, ['revenue', 'all'])) {
            $insights = array_merge($insights, $this->generateRevenueInsights($metrics));
        }

        // Efficiency insights
        if (in_array($focusArea, ['efficiency', 'all'])) {
            $insights = array_merge($insights, $this->generateEfficiencyInsights($metrics));
        }

        // Sort insights by priority
        usort($insights, function ($a, $b) {
            $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return $priorityOrder[$a['priority']] <=> $priorityOrder[$b['priority']];
        });

        return [
            'success' => true,
            'data' => array_slice($insights, 0, 10), // Return top 10 insights
            'focus_area' => $focusArea,
            'generated_at' => now()->toIso8601String()
        ];
    }

    /**
     * Gather metrics for insight generation.
     */
    protected function gatherMetricsForInsights($companyId): array
    {
        $companyPhoneNumbers = PhoneNumber::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('number')
            ->toArray();

        return [
            'response_rate' => $this->calculateResponseRate($companyId, $companyPhoneNumbers),
            'avg_resolution_time' => $this->calculateAvgResolutionTime($companyId, $companyPhoneNumbers),
            'conversion_rate' => $this->calculateConversionRate($companyId),
            'team_utilization' => $this->calculateTeamUtilization($companyId),
            'revenue_trend' => $this->calculateRevenueTrend($companyId),
            'customer_satisfaction' => $this->estimateCustomerSatisfaction($companyId)
        ];
    }

    /**
     * Generate performance insights.
     */
    protected function generatePerformanceInsights($metrics): array
    {
        $insights = [];

        // Response rate insight
        if ($metrics['response_rate'] < 80) {
            $insights[] = [
                'type' => 'performance',
                'priority' => 'high',
                'title' => 'Low Call Response Rate',
                'description' => "Your response rate is {$metrics['response_rate']}%. Consider adjusting staffing during peak hours.",
                'action' => 'Review call patterns and optimize staff scheduling',
                'metric' => 'response_rate',
                'value' => $metrics['response_rate']
            ];
        }

        // Resolution time insight
        if ($metrics['avg_resolution_time'] > 24) {
            $insights[] = [
                'type' => 'performance',
                'priority' => 'medium',
                'title' => 'Long Resolution Times',
                'description' => "Average resolution time is {$metrics['avg_resolution_time']} hours. This may impact customer satisfaction.",
                'action' => 'Implement SLAs and automate follow-up reminders',
                'metric' => 'avg_resolution_time',
                'value' => $metrics['avg_resolution_time']
            ];
        }

        return $insights;
    }

    /**
     * Generate revenue insights.
     */
    protected function generateRevenueInsights($metrics): array
    {
        $insights = [];

        // Revenue trend insight
        if ($metrics['revenue_trend'] < -5) {
            $insights[] = [
                'type' => 'revenue',
                'priority' => 'critical',
                'title' => 'Declining Revenue Trend',
                'description' => "Revenue has decreased by " . abs($metrics['revenue_trend']) . "% compared to last period.",
                'action' => 'Analyze service performance and customer retention',
                'metric' => 'revenue_trend',
                'value' => $metrics['revenue_trend']
            ];
        } elseif ($metrics['revenue_trend'] > 20) {
            $insights[] = [
                'type' => 'revenue',
                'priority' => 'low',
                'title' => 'Strong Revenue Growth',
                'description' => "Revenue has increased by {$metrics['revenue_trend']}%. Great performance!",
                'action' => 'Identify growth drivers to maintain momentum',
                'metric' => 'revenue_trend',
                'value' => $metrics['revenue_trend']
            ];
        }

        return $insights;
    }

    /**
     * Generate efficiency insights.
     */
    protected function generateEfficiencyInsights($metrics): array
    {
        $insights = [];

        // Team utilization insight
        if ($metrics['team_utilization'] < 60) {
            $insights[] = [
                'type' => 'efficiency',
                'priority' => 'medium',
                'title' => 'Low Team Utilization',
                'description' => "Team utilization is at {$metrics['team_utilization']}%. Consider optimizing task distribution.",
                'action' => 'Review workload distribution and automate routine tasks',
                'metric' => 'team_utilization',
                'value' => $metrics['team_utilization']
            ];
        } elseif ($metrics['team_utilization'] > 90) {
            $insights[] = [
                'type' => 'efficiency',
                'priority' => 'high',
                'title' => 'Team at Capacity',
                'description' => "Team utilization is at {$metrics['team_utilization']}%. Risk of burnout.",
                'action' => 'Consider hiring additional staff or implementing automation',
                'metric' => 'team_utilization',
                'value' => $metrics['team_utilization']
            ];
        }

        // Conversion rate insight
        if ($metrics['conversion_rate'] < 20) {
            $insights[] = [
                'type' => 'efficiency',
                'priority' => 'high',
                'title' => 'Low Call-to-Appointment Conversion',
                'description' => "Only {$metrics['conversion_rate']}% of calls result in appointments.",
                'action' => 'Review call scripts and train staff on conversion techniques',
                'metric' => 'conversion_rate',
                'value' => $metrics['conversion_rate']
            ];
        }

        return $insights;
    }

    /**
     * Calculate conversion rate helper.
     */
    protected function calculateConversionRate($companyId): float
    {
        $totalCalls = Call::where('company_id', $companyId)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->count();

        $callsWithAppointments = Call::where('company_id', $companyId)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->whereNotNull('appointment_id')
            ->count();

        return $totalCalls > 0 
            ? round(($callsWithAppointments / $totalCalls) * 100, 1)
            : 0;
    }

    /**
     * Calculate team utilization.
     */
    protected function calculateTeamUtilization($companyId): float
    {
        // Simple calculation based on assigned vs completed tasks
        $totalAssigned = DB::table('call_portal_data')
            ->join('calls', 'call_portal_data.call_id', '=', 'calls.id')
            ->where('calls.company_id', $companyId)
            ->whereNotNull('call_portal_data.assigned_to')
            ->whereDate('calls.created_at', '>=', now()->subDays(7))
            ->count();

        $totalCompleted = DB::table('call_portal_data')
            ->join('calls', 'call_portal_data.call_id', '=', 'calls.id')
            ->where('calls.company_id', $companyId)
            ->where('call_portal_data.status', 'completed')
            ->whereDate('calls.created_at', '>=', now()->subDays(7))
            ->count();

        return $totalAssigned > 0 
            ? round(($totalCompleted / $totalAssigned) * 100, 1)
            : 0;
    }

    /**
     * Calculate revenue trend.
     */
    protected function calculateRevenueTrend($companyId): float
    {
        $currentMonth = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('total');

        $lastMonth = Invoice::where('company_id', $companyId)
            ->where('status', 'paid')
            ->whereMonth('paid_at', now()->subMonth()->month)
            ->whereYear('paid_at', now()->subMonth()->year)
            ->sum('total');

        return $lastMonth > 0 
            ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1)
            : 0;
    }

    /**
     * Estimate customer satisfaction.
     */
    protected function estimateCustomerSatisfaction($companyId): float
    {
        // Simple estimation based on no-show rate and resolution time
        $noShowRate = Appointment::where('company_id', $companyId)
            ->whereDate('starts_at', '>=', now()->subDays(30))
            ->where('status', 'no_show')
            ->count();

        $totalAppointments = Appointment::where('company_id', $companyId)
            ->whereDate('starts_at', '>=', now()->subDays(30))
            ->whereIn('status', ['completed', 'no_show'])
            ->count();

        $noShowPercentage = $totalAppointments > 0 
            ? ($noShowRate / $totalAppointments) * 100
            : 0;

        // Estimate satisfaction (100 - penalties)
        $satisfaction = 100;
        $satisfaction -= $noShowPercentage * 2; // Penalty for no-shows
        $satisfaction = max(0, min(100, $satisfaction));

        return round($satisfaction, 1);
    }
}