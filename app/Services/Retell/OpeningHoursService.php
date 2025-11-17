<?php

namespace App\Services\Retell;

use App\Models\Branch;
use App\Models\Call;
use App\Services\Policy\BranchPolicyEnforcer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * OpeningHoursService
 *
 * ✅ Phase 3: Retell function handler for opening_hours requests
 *
 * Provides branch opening hours information:
 * - Current day hours (today)
 * - Specific day hours (by request)
 * - Weekly schedule
 * - Special closures/holidays
 *
 * Policy Integration:
 * - Checks POLICY_TYPE_OPENING_HOURS before revealing hours
 * - Anonymous callers allowed by default (public information)
 * - Branch can restrict via policy configuration
 */
class OpeningHoursService
{
    public function __construct(
        private BranchPolicyEnforcer $policyEnforcer
    ) {}

    /**
     * Get opening hours for branch
     *
     * @param Branch $branch Branch to get hours for
     * @param Call $call Call record for policy check
     * @param array $parameters Optional parameters (day_of_week, format)
     * @return array Response array for Retell
     */
    public function getOpeningHours(Branch $branch, Call $call, array $parameters = []): array
    {
        Log::info('🕐 Opening Hours Request', [
            'branch_id' => $branch->id,
            'call_id' => $call->id,
            'parameters' => $parameters,
        ]);

        // 1. Policy Check
        $policyCheck = $this->policyEnforcer->isOperationAllowed(
            $branch,
            $call,
            'opening_hours'
        );

        if (!$policyCheck['allowed']) {
            Log::warning('🛑 Opening hours policy denied', [
                'branch_id' => $branch->id,
                'reason' => $policyCheck['reason'],
            ]);

            return [
                'success' => false,
                'error' => $policyCheck['message'] ?? 'Öffnungszeiten sind derzeit nicht verfügbar.',
                'reason' => $policyCheck['reason'],
            ];
        }

        // 2. Check if branch has business hours configured
        if (!$branch->business_hours) {
            Log::warning('⚠️ Branch has no business hours configured', [
                'branch_id' => $branch->id,
            ]);

            return [
                'success' => true,
                'data' => [
                    'message' => 'Öffnungszeiten sind derzeit nicht konfiguriert. Bitte kontaktieren Sie uns direkt.',
                    'has_hours' => false,
                ],
            ];
        }

        // 3. Determine which day(s) to return
        $requestedDay = $parameters['day_of_week'] ?? null;

        if ($requestedDay) {
            // Specific day requested
            return $this->getSpecificDayHours($branch, $requestedDay);
        }

        // Default: Return today's hours + weekly schedule
        return $this->getFullSchedule($branch);
    }

    /**
     * Get hours for specific day
     *
     * @param Branch $branch
     * @param string $dayName Day name (monday, tuesday, etc.)
     * @return array
     */
    private function getSpecificDayHours(Branch $branch, string $dayName): array
    {
        $dayName = strtolower($dayName);
        $businessHours = $branch->business_hours;

        if (!isset($businessHours[$dayName])) {
            return [
                'success' => true,
                'data' => [
                    'day' => ucfirst($dayName),
                    'is_open' => false,
                    'message' => 'An diesem Tag haben wir geschlossen.',
                ],
            ];
        }

        $hours = $businessHours[$dayName];

        if (empty($hours)) {
            return [
                'success' => true,
                'data' => [
                    'day' => ucfirst($dayName),
                    'is_open' => false,
                    'message' => 'An diesem Tag haben wir geschlossen.',
                ],
            ];
        }

        return [
            'success' => true,
            'data' => [
                'day' => ucfirst($dayName),
                'is_open' => true,
                'hours' => $hours,
                'formatted' => $this->formatHoursForSpeech($hours),
            ],
        ];
    }

    /**
     * Get full weekly schedule
     *
     * @param Branch $branch
     * @return array
     */
    private function getFullSchedule(Branch $branch): array
    {
        $now = Carbon::now('Europe/Berlin');
        $today = strtolower($now->format('l')); // 'monday', 'tuesday', etc.
        $businessHours = $branch->business_hours;

        // Today's hours
        $todayHours = $businessHours[$today] ?? null;
        $isTodayOpen = !empty($todayHours);

        // Format weekly schedule
        $weeklySchedule = [];
        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($daysOfWeek as $day) {
            $hours = $businessHours[$day] ?? [];
            $weeklySchedule[$day] = [
                'day_name' => ucfirst($day),
                'is_open' => !empty($hours),
                'hours' => $hours,
                'formatted' => !empty($hours) ? $this->formatHoursForSpeech($hours) : 'Geschlossen',
            ];
        }

        // Check if currently open
        $isCurrentlyOpen = false;
        if ($isTodayOpen) {
            $currentTime = $now->format('H:i');
            foreach ($todayHours as $timeRange) {
                if (str_contains($timeRange, '-')) {
                    [$start, $end] = explode('-', $timeRange);
                    if ($currentTime >= $start && $currentTime <= $end) {
                        $isCurrentlyOpen = true;
                        break;
                    }
                }
            }
        }

        return [
            'success' => true,
            'data' => [
                'branch_name' => $branch->name,
                'today' => [
                    'day_name' => ucfirst($today),
                    'is_open' => $isTodayOpen,
                    'hours' => $todayHours ?? [],
                    'formatted' => $isTodayOpen ? $this->formatHoursForSpeech($todayHours) : 'Heute geschlossen',
                    'is_currently_open' => $isCurrentlyOpen,
                ],
                'weekly_schedule' => $weeklySchedule,
            ],
        ];
    }

    /**
     * Format hours array for speech output
     *
     * Example input: ["09:00-13:00", "14:00-18:00"]
     * Example output: "von 9 Uhr bis 13 Uhr und von 14 Uhr bis 18 Uhr"
     *
     * @param array $hours Time ranges
     * @return string Formatted string for speech
     */
    private function formatHoursForSpeech(array $hours): string
    {
        $formatted = [];

        foreach ($hours as $timeRange) {
            if (str_contains($timeRange, '-')) {
                [$start, $end] = explode('-', $timeRange);
                $formatted[] = sprintf(
                    'von %s Uhr bis %s Uhr',
                    $this->formatTimeForSpeech($start),
                    $this->formatTimeForSpeech($end)
                );
            }
        }

        if (count($formatted) === 1) {
            return $formatted[0];
        }

        // Multiple ranges: "von X bis Y und von A bis B"
        $last = array_pop($formatted);
        return implode(', ', $formatted) . ' und ' . $last;
    }

    /**
     * Format time for speech (remove leading zeros)
     *
     * Example: "09:00" → "9"
     * Example: "14:30" → "14 Uhr 30"
     *
     * @param string $time Time in HH:mm format
     * @return string Formatted for speech
     */
    private function formatTimeForSpeech(string $time): string
    {
        [$hour, $minute] = explode(':', $time);

        $hour = ltrim($hour, '0') ?: '0'; // Remove leading zero

        if ($minute === '00') {
            return $hour;
        }

        return sprintf('%s Uhr %s', $hour, ltrim($minute, '0'));
    }
}
