<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SimpleGoalMetricsController extends Controller
{
    /**
     * Get goal progress data.
     */
    public function progress(Request $request, $goalId)
    {
        $user = Auth::guard('portal')->user() ?? Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $goal = CompanyGoal::where('company_id', $user->company_id)
            ->findOrFail($goalId);

        // Calculate current progress
        $current = $this->calculateCurrentValue($goal);
        $percentage = $goal->target_value > 0 ? ($current / $goal->target_value) * 100 : 0;
        
        // Calculate time progress
        $totalDays = Carbon::parse($goal->starts_at)->diffInDays(Carbon::parse($goal->ends_at));
        $elapsedDays = Carbon::parse($goal->starts_at)->diffInDays(Carbon::now());
        $timeProgress = $totalDays > 0 ? ($elapsedDays / $totalDays) * 100 : 0;
        
        // Calculate projected completion
        $projectedDate = null;
        if ($current > 0 && $elapsedDays > 0) {
            $dailyRate = $current / $elapsedDays;
            $daysNeeded = $goal->target_value / $dailyRate;
            $projectedDate = Carbon::parse($goal->starts_at)->addDays($daysNeeded)->format('Y-m-d');
        }

        return response()->json([
            'progress' => [
                'current_value' => $current,
                'target_value' => $goal->target_value,
                'percentage' => round($percentage, 2),
                'time_progress' => round($timeProgress, 2),
                'days_remaining' => max(0, Carbon::now()->diffInDays(Carbon::parse($goal->ends_at), false)),
                'projected_completion_date' => $projectedDate,
                'on_track' => $percentage >= $timeProgress
            ]
        ]);
    }

    /**
     * Get goal metrics data.
     */
    public function metrics(Request $request, $goalId)
    {
        $user = Auth::guard('portal')->user() ?? Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $goal = CompanyGoal::where('company_id', $user->company_id)
            ->findOrFail($goalId);

        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        // Generate daily data points
        $dailyData = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        while ($current <= $end) {
            $value = $this->calculateValueForDate($goal, $current);
            $dailyData[] = [
                'date' => $current->format('Y-m-d'),
                'value' => $value
            ];
            $current->addDay();
        }

        // Generate trend data for different periods
        $trend = [];
        $periods = ['Woche 1', 'Woche 2', 'Woche 3', 'Woche 4'];
        foreach ($periods as $i => $period) {
            $weekStart = Carbon::parse($startDate)->addWeeks($i);
            $weekEnd = Carbon::parse($startDate)->addWeeks($i + 1)->subDay();
            
            $actual = $this->calculateValueForPeriod($goal, $weekStart, $weekEnd);
            $target = $goal->target_value / 4; // Weekly target
            
            $trend[] = [
                'period' => $period,
                'actual' => $actual,
                'target' => $target
            ];
        }

        return response()->json([
            'metrics' => [
                'daily' => $dailyData,
                'trend' => $trend
            ]
        ]);
    }

    /**
     * Calculate current value for a goal.
     */
    private function calculateCurrentValue($goal)
    {
        $startDate = $goal->starts_at;
        $endDate = min($goal->ends_at, now());

        return $this->calculateValueForPeriod($goal, $startDate, $endDate);
    }

    /**
     * Calculate value for a specific date.
     */
    private function calculateValueForDate($goal, $date)
    {
        return $this->calculateValueForPeriod($goal, $date->startOfDay(), $date->endOfDay());
    }

    /**
     * Calculate value for a period.
     */
    private function calculateValueForPeriod($goal, $startDate, $endDate)
    {
        switch ($goal->type) {
            case 'calls':
                return DB::table('calls')
                    ->where('company_id', $goal->company_id)
                    ->when($goal->branch_id, function ($query) use ($goal) {
                        return $query->where('branch_id', $goal->branch_id);
                    })
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

            case 'appointments':
                return DB::table('appointments')
                    ->where('company_id', $goal->company_id)
                    ->when($goal->branch_id, function ($query) use ($goal) {
                        return $query->where('branch_id', $goal->branch_id);
                    })
                    ->when($goal->staff_id, function ($query) use ($goal) {
                        return $query->where('staff_id', $goal->staff_id);
                    })
                    ->whereBetween('starts_at', [$startDate, $endDate])
                    ->count();

            case 'conversion':
                $calls = DB::table('calls')
                    ->where('company_id', $goal->company_id)
                    ->when($goal->branch_id, function ($query) use ($goal) {
                        return $query->where('branch_id', $goal->branch_id);
                    })
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                if ($calls == 0) return 0;

                $appointments = DB::table('calls')
                    ->where('company_id', $goal->company_id)
                    ->when($goal->branch_id, function ($query) use ($goal) {
                        return $query->where('branch_id', $goal->branch_id);
                    })
                    ->whereNotNull('appointment_created')
                    ->where('appointment_created', true)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

                return round(($appointments / $calls) * 100, 2);

            case 'revenue':
                return DB::table('appointments')
                    ->join('services', 'appointments.service_id', '=', 'services.id')
                    ->where('appointments.company_id', $goal->company_id)
                    ->when($goal->branch_id, function ($query) use ($goal) {
                        return $query->where('appointments.branch_id', $goal->branch_id);
                    })
                    ->when($goal->staff_id, function ($query) use ($goal) {
                        return $query->where('appointments.staff_id', $goal->staff_id);
                    })
                    ->where('appointments.status', 'completed')
                    ->whereBetween('appointments.starts_at', [$startDate, $endDate])
                    ->sum('services.price') ?? 0;

            case 'customers':
                return DB::table('customers')
                    ->where('company_id', $goal->company_id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count();

            default:
                return 0;
        }
    }
}