<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Staff;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BranchComparisonWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.branch-comparison';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public array $comparisonData = [];

    public string $timeframe = 'today';

    public ?int $companyId = null;

    public function mount(): void
    {
        $this->companyId = session('filter_company_id') ?? auth()->user()?->company_id;
        $this->loadComparisonData();
    }

    public function setTimeframe(string $timeframe): void
    {
        $this->timeframe = $timeframe;
        $this->loadComparisonData();
    }

    protected function loadComparisonData(): void
    {
        $cacheKey = "branch_comparison_{$this->companyId}_{$this->timeframe}";

        $this->comparisonData = Cache::remember($cacheKey, 300, function () {
            $branches = Branch::query()
                ->when($this->companyId, fn ($q) => $q->where('company_id', $this->companyId))
                ->get();

            $data = [];

            foreach ($branches as $branch) {
                $data[] = $this->getBranchMetrics($branch);
            }

            // Sort by performance score
            usort($data, fn ($a, $b) => $b['performance_score'] <=> $a['performance_score']);

            return $data;
        });
    }

    protected function getBranchMetrics(Branch $branch): array
    {
        [$startDate, $endDate] = $this->getDateRange();

        // Appointments metrics
        $appointments = Appointment::where('branch_id', $branch->id)
            ->whereBetween('starts_at', [$startDate, $endDate]);

        $totalAppointments = (clone $appointments)->count();

        $appointmentsByStatus = (clone $appointments)->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $completedAppointments = $appointmentsByStatus['completed'] ?? 0;
        $cancelledAppointments = $appointmentsByStatus['cancelled'] ?? 0;
        $noShowAppointments = $appointmentsByStatus['no_show'] ?? 0;

        // Revenue calculation (use appointment price or calcom_event_type price)
        $revenue = Appointment::where('appointments.branch_id', $branch->id)
            ->whereBetween('appointments.starts_at', [$startDate, $endDate])
            ->where('appointments.status', 'completed')
            ->leftJoin('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
            ->sum(DB::raw('COALESCE(appointments.price, calcom_event_types.price, 0)'));

        // Calls metrics
        $totalCalls = Call::where('branch_id', $branch->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $convertedCalls = Call::where('branch_id', $branch->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('appointment_id')
            ->count();

        // Customer metrics
        $uniqueCustomers = Appointment::where('branch_id', $branch->id)
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->distinct('customer_id')
            ->count('customer_id');

        $newCustomers = Customer::whereHas('appointments', function ($q) use ($branch, $startDate, $endDate) {
            $q->where('branch_id', $branch->id)
                ->whereBetween('starts_at', [$startDate, $endDate]);
        })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Staff metrics
        $activeStaff = Staff::where('home_branch_id', $branch->id)
            ->where('active', true)
            ->count();

        // Calculate metrics
        $completionRate = $totalAppointments > 0
            ? round(($completedAppointments / $totalAppointments) * 100, 1)
            : 0;

        $noShowRate = $totalAppointments > 0
            ? round(($noShowAppointments / $totalAppointments) * 100, 1)
            : 0;

        $conversionRate = $totalCalls > 0
            ? round(($convertedCalls / $totalCalls) * 100, 1)
            : 0;

        $avgRevenuePerAppointment = $completedAppointments > 0
            ? round($revenue / $completedAppointments, 2)
            : 0;

        $utilizationRate = $this->calculateUtilizationRate($branch, $startDate, $endDate);

        // Calculate performance score (0-100)
        $performanceScore = $this->calculatePerformanceScore([
            'completion_rate' => $completionRate,
            'conversion_rate' => $conversionRate,
            'utilization_rate' => $utilizationRate,
            'revenue' => $revenue,
            'no_show_rate' => $noShowRate,
        ]);

        // Get trend data
        $trend = $this->calculateTrend($branch);

        return [
            'branch_id' => $branch->id,
            'branch_name' => $branch->name,
            'city' => $branch->city,
            'performance_score' => $performanceScore,
            'metrics' => [
                'appointments' => [
                    'total' => $totalAppointments,
                    'completed' => $completedAppointments,
                    'cancelled' => $cancelledAppointments,
                    'no_show' => $noShowAppointments,
                ],
                'rates' => [
                    'completion' => $completionRate,
                    'no_show' => $noShowRate,
                    'conversion' => $conversionRate,
                    'utilization' => $utilizationRate,
                ],
                'financial' => [
                    'revenue' => $revenue,
                    'avg_per_appointment' => $avgRevenuePerAppointment,
                ],
                'customers' => [
                    'unique' => $uniqueCustomers,
                    'new' => $newCustomers,
                ],
                'resources' => [
                    'active_staff' => $activeStaff,
                    'total_calls' => $totalCalls,
                ],
            ],
            'trend' => $trend,
        ];
    }

    protected function getDateRange(): array
    {
        $endDate = Carbon::now()->endOfDay();

        switch ($this->timeframe) {
            case 'today':
                $startDate = Carbon::today();

                break;
            case 'week':
                $startDate = Carbon::now()->startOfWeek();

                break;
            case 'month':
                $startDate = Carbon::now()->startOfMonth();

                break;
            case 'quarter':
                $startDate = Carbon::now()->startOfQuarter();

                break;
            default:
                $startDate = Carbon::today();
        }

        return [$startDate, $endDate];
    }

    protected function calculateUtilizationRate(Branch $branch, $startDate, $endDate): float
    {
        // Calculate theoretical capacity
        $workingDays = $startDate->diffInWeekdays($endDate) + 1;
        $hoursPerDay = 8; // Should be configurable per branch
        $slotsPerHour = 2; // 30-minute slots
        $staffCount = Staff::where('home_branch_id', $branch->id)
            ->where('active', true)
            ->count();

        $theoreticalCapacity = $workingDays * $hoursPerDay * $slotsPerHour * $staffCount;

        if ($theoreticalCapacity === 0) {
            return 0;
        }

        // Get actual bookings
        $actualBookings = Appointment::where('branch_id', $branch->id)
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->whereIn('status', ['scheduled', 'confirmed', 'completed'])
            ->count();

        return round(($actualBookings / $theoreticalCapacity) * 100, 1);
    }

    protected function calculatePerformanceScore(array $metrics): int
    {
        $score = 0;

        // Completion rate (30 points max)
        $score += min(30, ($metrics['completion_rate'] / 100) * 30);

        // Conversion rate (25 points max)
        $score += min(25, ($metrics['conversion_rate'] / 100) * 25);

        // Utilization rate (20 points max)
        $score += min(20, ($metrics['utilization_rate'] / 100) * 20);

        // Revenue performance (15 points max)
        // This is simplified - in production, compare against targets
        $revenueScore = min(15, ($metrics['revenue'] / 10000) * 15);
        $score += $revenueScore;

        // No-show rate (10 points max, inverse)
        $noShowScore = max(0, 10 - ($metrics['no_show_rate'] / 10) * 10);
        $score += $noShowScore;

        return round($score);
    }

    protected function calculateTrend(Branch $branch): array
    {
        // Compare current period with previous period
        [$currentStart, $currentEnd] = $this->getDateRange();

        // Calculate previous period
        $periodLength = $currentStart->diffInDays($currentEnd);
        $previousStart = $currentStart->copy()->subDays($periodLength + 1);
        $previousEnd = $currentStart->copy()->subDay();

        // Get metrics for both periods
        $currentRevenue = Appointment::where('appointments.branch_id', $branch->id)
            ->whereBetween('appointments.starts_at', [$currentStart, $currentEnd])
            ->where('appointments.status', 'completed')
            ->leftJoin('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
            ->sum(DB::raw('COALESCE(appointments.price, calcom_event_types.price, 0)'));

        $previousRevenue = Appointment::where('appointments.branch_id', $branch->id)
            ->whereBetween('appointments.starts_at', [$previousStart, $previousEnd])
            ->where('appointments.status', 'completed')
            ->leftJoin('calcom_event_types', 'appointments.calcom_event_type_id', '=', 'calcom_event_types.id')
            ->sum(DB::raw('COALESCE(appointments.price, calcom_event_types.price, 0)'));

        $currentAppointments = Appointment::where('branch_id', $branch->id)
            ->whereBetween('starts_at', [$currentStart, $currentEnd])
            ->count();

        $previousAppointments = Appointment::where('branch_id', $branch->id)
            ->whereBetween('starts_at', [$previousStart, $previousEnd])
            ->count();

        // Calculate changes
        $revenueChange = $previousRevenue > 0
            ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1)
            : 0;

        $appointmentChange = $previousAppointments > 0
            ? round((($currentAppointments - $previousAppointments) / $previousAppointments) * 100, 1)
            : 0;

        return [
            'revenue' => [
                'value' => $revenueChange,
                'direction' => $revenueChange > 0 ? 'up' : ($revenueChange < 0 ? 'down' : 'stable'),
            ],
            'appointments' => [
                'value' => $appointmentChange,
                'direction' => $appointmentChange > 0 ? 'up' : ($appointmentChange < 0 ? 'down' : 'stable'),
            ],
        ];
    }

    public function getTimeframeOptions(): array
    {
        return [
            'today' => 'Heute',
            'week' => 'Diese Woche',
            'month' => 'Dieser Monat',
            'quarter' => 'Dieses Quartal',
        ];
    }

    public static function canView(): bool
    {
        return true;
    }
}
