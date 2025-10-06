<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Appointment;
use App\Models\RetellAgent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConversionTracker
{
    /**
     * Link a call to an appointment for conversion tracking
     */
    public static function linkCallToAppointment(Call $call, Appointment $appointment): void
    {
        $call->update([
            'converted_to_appointment' => true,
            'converted_appointment_id' => $appointment->id,
            'conversion_timestamp' => now(),
            'appointment_made' => true,
        ]);

        Log::info('Call converted to appointment', [
            'call_id' => $call->id,
            'appointment_id' => $appointment->id,
            'conversion_time' => now(),
        ]);
    }

    /**
     * Automatically detect and link conversions
     */
    public static function detectConversions(int $hoursBack = 24): array
    {
        $stats = [
            'calls_checked' => 0,
            'conversions_found' => 0,
            'already_linked' => 0,
        ];

        // Find calls that might have led to appointments
        $calls = Call::where('created_at', '>=', now()->subHours($hoursBack))
            ->whereNull('converted_appointment_id')
            ->whereNotNull('customer_id')
            ->get();

        $stats['calls_checked'] = $calls->count();

        foreach ($calls as $call) {
            // Look for appointments created after this call
            $appointment = Appointment::where('customer_id', $call->customer_id)
                ->where('created_at', '>=', $call->created_at)
                ->where('created_at', '<=', $call->created_at->addHours(24)) // Within 24 hours
                ->first();

            if ($appointment) {
                if ($call->converted_appointment_id == $appointment->id) {
                    $stats['already_linked']++;
                } else {
                    self::linkCallToAppointment($call, $appointment);
                    $stats['conversions_found']++;
                }
            }
        }

        Log::info('Conversion detection completed', $stats);
        return $stats;
    }

    /**
     * Get conversion metrics
     */
    public static function getConversionMetrics(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null,
        ?int $agentId = null,
        ?int $companyId = null
    ): array {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfDay();

        $query = Call::whereBetween('created_at', [$startDate, $endDate]);

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $totalCalls = $query->count();
        $convertedCalls = (clone $query)->where('converted_to_appointment', true)->count();
        $conversionRate = $totalCalls > 0 ? round(($convertedCalls / $totalCalls) * 100, 2) : 0;

        // Average time to conversion
        $avgConversionTime = Call::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('conversion_timestamp')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, conversion_timestamp)) as avg_minutes')
            ->first()
            ->avg_minutes ?? 0;

        // By agent
        $agentMetrics = [];
        if (!$agentId) {
            $agentMetrics = Call::whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('agent_id')
                ->select('agent_id')
                ->selectRaw('COUNT(*) as total_calls')
                ->selectRaw('SUM(CASE WHEN converted_to_appointment = 1 THEN 1 ELSE 0 END) as conversions')
                ->selectRaw('AVG(duration_sec) as avg_duration')
                ->groupBy('agent_id')
                ->with('agent')
                ->get()
                ->map(function($item) {
                    $item->conversion_rate = $item->total_calls > 0
                        ? round(($item->conversions / $item->total_calls) * 100, 2)
                        : 0;
                    return $item;
                });
        }

        // By hour of day
        $conversionsByHour = Call::whereBetween('created_at', [$startDate, $endDate])
            ->where('converted_to_appointment', true)
            ->selectRaw('HOUR(created_at) as hour')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // By day of week
        $conversionsByDayOfWeek = Call::whereBetween('created_at', [$startDate, $endDate])
            ->where('converted_to_appointment', true)
            ->selectRaw('DAYOFWEEK(created_at) as day')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day')
            ->toArray();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'overview' => [
                'total_calls' => $totalCalls,
                'converted_calls' => $convertedCalls,
                'conversion_rate' => $conversionRate,
                'avg_conversion_time_minutes' => round($avgConversionTime, 1),
            ],
            'by_agent' => $agentMetrics,
            'by_hour' => $conversionsByHour,
            'by_day_of_week' => $conversionsByDayOfWeek,
        ];
    }

    /**
     * Get performance metrics for agents
     */
    public static function getAgentPerformance(
        ?Carbon $startDate = null,
        ?Carbon $endDate = null
    ): array {
        $startDate = $startDate ?? now()->startOfMonth();
        $endDate = $endDate ?? now()->endOfDay();

        $agents = RetellAgent::where('is_active', true)
            ->select('id', 'name', 'agent_id')
            ->withCount([
                'calls as total_calls' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                },
                'calls as converted_calls' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate])
                          ->where('converted_to_appointment', true);
                },
                'calls as successful_calls' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate])
                          ->where('call_successful', true);
                },
            ])
            ->withAvg([
                'calls as avg_duration' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ], 'duration_sec')
            ->withAvg([
                'calls as avg_sentiment_score' => function($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate, $endDate]);
                }
            ], 'sentiment_score')
            ->get()
            ->map(function($agent) {
                $agent->conversion_rate = $agent->total_calls > 0
                    ? round(($agent->converted_calls / $agent->total_calls) * 100, 2)
                    : 0;

                $agent->success_rate = $agent->total_calls > 0
                    ? round(($agent->successful_calls / $agent->total_calls) * 100, 2)
                    : 0;

                $agent->avg_duration_formatted = $agent->avg_duration
                    ? gmdate('i:s', $agent->avg_duration)
                    : '00:00';

                return $agent;
            });

        // Rank agents by conversion rate
        $agents = $agents->sortByDesc('conversion_rate')->values();

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'agents' => $agents,
            'best_performer' => $agents->first(),
            'total_agents' => $agents->count(),
        ];
    }

    /**
     * Get customer journey from call to appointment
     */
    public static function getCustomerJourney(int $customerId): array
    {
        $calls = Call::where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'created_at', 'duration_sec', 'sentiment', 'converted_to_appointment', 'agent_id']);

        $appointments = Appointment::where('customer_id', $customerId)
            ->orderBy('starts_at', 'desc')
            ->limit(10)
            ->get(['id', 'starts_at', 'status', 'service_id']);

        $journey = [];

        foreach ($calls as $call) {
            $journey[] = [
                'type' => 'call',
                'timestamp' => $call->created_at,
                'duration' => $call->duration_sec,
                'sentiment' => $call->sentiment,
                'converted' => $call->converted_to_appointment,
                'agent_id' => $call->agent_id,
            ];
        }

        foreach ($appointments as $appointment) {
            $journey[] = [
                'type' => 'appointment',
                'timestamp' => $appointment->starts_at,
                'status' => $appointment->status,
                'service_id' => $appointment->service_id,
            ];
        }

        // Sort by timestamp
        usort($journey, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return [
            'customer_id' => $customerId,
            'total_calls' => $calls->count(),
            'total_appointments' => $appointments->count(),
            'conversion_rate' => $calls->count() > 0
                ? round(($calls->where('converted_to_appointment', true)->count() / $calls->count()) * 100, 2)
                : 0,
            'journey' => $journey,
        ];
    }
}