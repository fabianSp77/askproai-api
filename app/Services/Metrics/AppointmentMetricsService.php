<?php

namespace App\Services\Metrics;

use App\Models\AppointmentModification;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ADR-005 Metrics Service
 *
 * Tracks 4 key metrics for reschedule-first flow:
 * 1. reschedule_offered - How many times reschedule was offered before cancel
 * 2. reschedule_accepted - How many times customer accepted reschedule
 * 3. reschedule_declined - How many times customer declined and proceeded with cancel
 * 4. branch_notified - How many branch notifications were sent
 */
class AppointmentMetricsService
{
    /**
     * Get reschedule-first metrics for a date range
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @param int|null $branchId Filter by branch
     * @param int|null $serviceId Filter by service
     * @return array
     */
    public function getRescheduleFirstMetrics(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $branchId = null,
        ?int $serviceId = null
    ): array {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        // Counter 1: Reschedule Offered (from cancel metadata)
        $rescheduleOffered = AppointmentModification::where('modification_type', 'cancel')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereJsonContains('metadata->reschedule_offered', true)
            ->when($branchId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('branch_id', $branchId)))
            ->when($serviceId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('service_id', $serviceId)))
            ->count();

        // Counter 2: Reschedule Accepted (from reschedule metadata)
        $rescheduleAccepted = AppointmentModification::where('modification_type', 'reschedule')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereJsonContains('metadata->from_reschedule_first_flow', true)
            ->when($branchId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('branch_id', $branchId)))
            ->when($serviceId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('service_id', $serviceId)))
            ->count();

        // Counter 3: Reschedule Declined (from cancel metadata)
        $rescheduleDeclined = AppointmentModification::where('modification_type', 'cancel')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereJsonContains('metadata->reschedule_declined', true)
            ->when($branchId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('branch_id', $branchId)))
            ->when($serviceId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('service_id', $serviceId)))
            ->count();

        // Counter 4: Branch Notified (cancel + reschedule events)
        // All modifications trigger branch notifications (ADR-005)
        $branchNotifiedCancel = AppointmentModification::where('modification_type', 'cancel')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('branch_id', $branchId)))
            ->when($serviceId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('service_id', $serviceId)))
            ->count();

        $branchNotifiedReschedule = AppointmentModification::where('modification_type', 'reschedule')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('branch_id', $branchId)))
            ->when($serviceId, fn($q) => $q->whereHas('appointment', fn($q2) => $q2->where('service_id', $serviceId)))
            ->count();

        $branchNotified = $branchNotifiedCancel + $branchNotifiedReschedule;

        // Conversion rate: accepted / offered (avoid division by zero)
        $conversionRate = $rescheduleOffered > 0
            ? round(($rescheduleAccepted / $rescheduleOffered) * 100, 2)
            : 0;

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'metrics' => [
                'reschedule_offered' => $rescheduleOffered,
                'reschedule_accepted' => $rescheduleAccepted,
                'reschedule_declined' => $rescheduleDeclined,
                'branch_notified' => $branchNotified,
            ],
            'derived' => [
                'conversion_rate_percent' => $conversionRate,
                'decline_rate_percent' => $rescheduleOffered > 0
                    ? round(($rescheduleDeclined / $rescheduleOffered) * 100, 2)
                    : 0,
            ],
            'filters' => [
                'branch_id' => $branchId,
                'service_id' => $serviceId,
            ],
        ];
    }

    /**
     * Get detailed breakdown by call
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return \Illuminate\Support\Collection
     */
    public function getDetailedBreakdown(?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        return AppointmentModification::with(['appointment.customer', 'appointment.service', 'appointment.branch'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('modification_type', ['cancel', 'reschedule'])
            ->where(function($query) {
                $query->whereJsonContains('metadata->reschedule_offered', true)
                      ->orWhereJsonContains('metadata->reschedule_declined', true)
                      ->orWhereJsonContains('metadata->from_reschedule_first_flow', true);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($mod) {
                return [
                    'id' => $mod->id,
                    'date' => $mod->created_at->format('Y-m-d H:i'),
                    'type' => $mod->modification_type,
                    'customer' => $mod->appointment->customer->name ?? 'Unknown',
                    'service' => $mod->appointment->service->name ?? 'Unknown',
                    'branch' => $mod->appointment->branch->name ?? 'Unknown',
                    'call_id' => $mod->metadata['call_id'] ?? null,
                    'reschedule_offered' => $mod->metadata['reschedule_offered'] ?? false,
                    'reschedule_declined' => $mod->metadata['reschedule_declined'] ?? false,
                    'from_reschedule_first_flow' => $mod->metadata['from_reschedule_first_flow'] ?? false,
                ];
            });
    }

    /**
     * Get metrics by branch
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return \Illuminate\Support\Collection
     */
    public function getMetricsByBranch(?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30);
        $endDate = $endDate ?? Carbon::now();

        return DB::table('appointment_modifications as am')
            ->join('appointments as a', 'am.appointment_id', '=', 'a.id')
            ->join('branches as b', 'a.branch_id', '=', 'b.id')
            ->select('b.id as branch_id', 'b.name as branch_name')
            ->selectRaw('COUNT(CASE WHEN am.modification_type = \'cancel\' AND JSON_EXTRACT(am.metadata, "$.reschedule_offered") = true THEN 1 END) as reschedule_offered')
            ->selectRaw('COUNT(CASE WHEN am.modification_type = \'reschedule\' AND JSON_EXTRACT(am.metadata, "$.from_reschedule_first_flow") = true THEN 1 END) as reschedule_accepted')
            ->selectRaw('COUNT(CASE WHEN am.modification_type = \'cancel\' AND JSON_EXTRACT(am.metadata, "$.reschedule_declined") = true THEN 1 END) as reschedule_declined')
            ->selectRaw('COUNT(am.id) as branch_notified')
            ->whereBetween('am.created_at', [$startDate, $endDate])
            ->whereIn('am.modification_type', ['cancel', 'reschedule'])
            ->groupBy('b.id', 'b.name')
            ->get();
    }
}
