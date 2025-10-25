<?php

namespace App\Services\Booking;

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * BookingNoticeValidator
 *
 * Validates booking requests against minimum booking notice requirements.
 *
 * Bug #11 Fix (2025-10-25): Prevents false positive "available" responses
 * when requested time violates Cal.com minimum booking notice period.
 *
 * Configuration Hierarchy:
 * 1. Branch override (branch_service.branch_policies.booking_notice_minutes)
 * 2. Service-specific (services.minimum_booking_notice)
 * 3. Global default (config.calcom.minimum_booking_notice_minutes)
 *
 * @package App\Services\Booking
 */
class BookingNoticeValidator
{
    /**
     * Validate if requested appointment time meets minimum booking notice
     *
     * @param Carbon $requestedTime Desired appointment time (Europe/Berlin timezone)
     * @param Service $service Service being booked
     * @param string|null $branchId Optional branch ID for branch-specific override
     * @return array Validation result with keys:
     *               - 'valid' (bool): Whether booking notice is sufficient
     *               - 'reason' (string|null): Rejection reason if invalid
     *               - 'minimum_notice_minutes' (int): Required minimum notice
     *               - 'earliest_bookable' (Carbon): Earliest bookable time
     *               - 'minutes_until_earliest' (int): Minutes until earliest bookable
     *
     * @example
     * $result = $validator->validateBookingNotice(
     *     Carbon::parse('2025-10-25 19:00'),
     *     $service
     * );
     *
     * if (!$result['valid']) {
     *     return error($result['reason']);
     * }
     */
    public function validateBookingNotice(
        Carbon $requestedTime,
        Service $service,
        ?string $branchId = null
    ): array {
        // Get minimum notice for this service
        $minimumNoticeMinutes = $this->getMinimumNoticeMinutes($service, $branchId);

        // Get current time in Europe/Berlin timezone
        $now = Carbon::now('Europe/Berlin');

        // Calculate earliest bookable time
        $earliestBookable = $now->copy()->addMinutes($minimumNoticeMinutes);

        // Validate: requested time must be >= earliest bookable
        // Note: Using >= (not just >) to match Cal.com behavior
        if ($requestedTime->lt($earliestBookable)) {
            Log::info('⚠️ Booking notice validation failed', [
                'requested_time' => $requestedTime->toDateTimeString(),
                'earliest_bookable' => $earliestBookable->toDateTimeString(),
                'minimum_notice_minutes' => $minimumNoticeMinutes,
                'service_id' => $service->id,
                'service_name' => $service->name,
                'minutes_short' => $earliestBookable->diffInMinutes($requestedTime),
            ]);

            return [
                'valid' => false,
                'reason' => 'too_soon',
                'minimum_notice_minutes' => $minimumNoticeMinutes,
                'earliest_bookable' => $earliestBookable,
                'minutes_until_earliest' => $now->diffInMinutes($earliestBookable),
                'minutes_short' => $earliestBookable->diffInMinutes($requestedTime),
            ];
        }

        Log::debug('✅ Booking notice validation passed', [
            'requested_time' => $requestedTime->toDateTimeString(),
            'minimum_notice_minutes' => $minimumNoticeMinutes,
            'service_id' => $service->id,
            'buffer_minutes' => $requestedTime->diffInMinutes($earliestBookable),
        ]);

        return [
            'valid' => true,
            'minimum_notice_minutes' => $minimumNoticeMinutes,
            'earliest_bookable' => $earliestBookable,
        ];
    }

    /**
     * Get minimum booking notice for a service
     *
     * Resolution order:
     * 1. Branch-specific override (if branchId provided)
     * 2. Service-specific configuration
     * 3. Global default from config
     * 4. Hardcoded fallback (15 minutes)
     *
     * @param Service $service Service to check
     * @param string|null $branchId Optional branch ID for override
     * @return int Minimum notice in minutes
     */
    public function getMinimumNoticeMinutes(Service $service, ?string $branchId = null): int
    {
        // Level 1: Branch override (highest priority)
        if ($branchId) {
            $branchPolicy = $service->branches()
                ->where('branch_id', $branchId)
                ->first()
                ?->pivot
                ?->branch_policies;

            if (isset($branchPolicy['booking_notice_minutes'])) {
                Log::debug('📍 Using branch-specific booking notice', [
                    'branch_id' => $branchId,
                    'service_id' => $service->id,
                    'notice_minutes' => $branchPolicy['booking_notice_minutes'],
                    'source' => 'branch_override',
                ]);

                return (int) $branchPolicy['booking_notice_minutes'];
            }
        }

        // Level 2: Service-specific (medium priority)
        if ($service->minimum_booking_notice !== null) {
            Log::debug('🔧 Using service-specific booking notice', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'notice_minutes' => $service->minimum_booking_notice,
                'source' => 'service_config',
            ]);

            return (int) $service->minimum_booking_notice;
        }

        // Level 3: Global default (low priority)
        $globalDefault = config('calcom.minimum_booking_notice_minutes');

        if ($globalDefault !== null) {
            Log::debug('🌍 Using global booking notice', [
                'service_id' => $service->id,
                'notice_minutes' => $globalDefault,
                'source' => 'global_config',
            ]);

            return (int) $globalDefault;
        }

        // Level 4: Hardcoded fallback (last resort)
        Log::warning('⚠️ Using hardcoded booking notice fallback', [
            'service_id' => $service->id,
            'notice_minutes' => 15,
            'source' => 'hardcoded_fallback',
            'recommendation' => 'Set CALCOM_MIN_BOOKING_NOTICE in .env',
        ]);

        return 15; // 15 minutes default
    }

    /**
     * Get earliest bookable time for a service
     *
     * @param Service $service Service to check
     * @param string|null $branchId Optional branch ID
     * @return Carbon Earliest time a booking can be made
     */
    public function getEarliestBookableTime(Service $service, ?string $branchId = null): Carbon
    {
        $minimumNoticeMinutes = $this->getMinimumNoticeMinutes($service, $branchId);
        $now = Carbon::now('Europe/Berlin');

        return $now->copy()->addMinutes($minimumNoticeMinutes);
    }

    /**
     * Suggest alternative appointment times
     *
     * When a requested time is too soon, this method suggests alternative times
     * that meet the minimum booking notice requirement.
     *
     * Strategy:
     * 1. Try to find slots today (after minimum notice)
     * 2. If no slots today, suggest tomorrow at requested time
     * 3. Round to nearest slot interval (typically 15 or 30 minutes)
     *
     * @param Carbon $requestedTime Original requested time
     * @param Service $service Service being booked
     * @param string|null $branchId Optional branch ID
     * @param int $count Number of alternatives to suggest (default: 3)
     * @return array Array of alternative times with formatted strings
     */
    public function suggestAlternatives(
        Carbon $requestedTime,
        Service $service,
        ?string $branchId = null,
        int $count = 3
    ): array {
        $earliestBookable = $this->getEarliestBookableTime($service, $branchId);
        $alternatives = [];

        // Get slot interval (default to 15 minutes if not configured)
        $slotInterval = $service->calcom_slot_interval ?? 15;

        // Round earliest bookable time to next slot interval
        $nextSlot = $earliestBookable->copy()->addMinutes(
            $slotInterval - ($earliestBookable->minute % $slotInterval)
        );

        // Strategy 1: Suggest slots starting from next available
        for ($i = 0; $i < $count; $i++) {
            $suggestionTime = $nextSlot->copy()->addMinutes($slotInterval * $i);

            $alternatives[] = [
                'datetime' => $suggestionTime,
                'date' => $suggestionTime->format('Y-m-d'),
                'time' => $suggestionTime->format('H:i'),
                'formatted_de' => $suggestionTime->locale('de')->isoFormat('dddd, D. MMMM [um] HH:mm [Uhr]'),
                'formatted_short' => $suggestionTime->locale('de')->isoFormat('dd D.MM. HH:mm'),
                'is_same_day' => $suggestionTime->isSameDay($requestedTime),
                'is_next_day' => $suggestionTime->isSameDay($requestedTime->copy()->addDay()),
            ];
        }

        Log::debug('💡 Alternative times suggested', [
            'service_id' => $service->id,
            'requested_time' => $requestedTime->toDateTimeString(),
            'earliest_bookable' => $earliestBookable->toDateTimeString(),
            'alternatives_count' => count($alternatives),
        ]);

        return $alternatives;
    }

    /**
     * Format a user-friendly error message in German
     *
     * @param array $validationResult Result from validateBookingNotice()
     * @param array|null $alternatives Optional alternative times
     * @return string Formatted German error message
     */
    public function formatErrorMessage(array $validationResult, ?array $alternatives = null): string
    {
        $minimumNotice = $validationResult['minimum_notice_minutes'];

        // Format minimum notice as human-readable string
        if ($minimumNotice >= 60) {
            $hours = floor($minimumNotice / 60);
            $minutes = $minimumNotice % 60;

            if ($minutes === 0) {
                $noticeStr = $hours === 1 ? '1 Stunde' : "{$hours} Stunden";
            } else {
                $noticeStr = "{$hours} Stunden und {$minutes} Minuten";
            }
        } else {
            $noticeStr = "{$minimumNotice} Minuten";
        }

        // Base message
        $message = "Dieser Termin liegt leider zu kurzfristig. " .
                   "Termine können frühestens {$noticeStr} im Voraus gebucht werden.";

        // Add alternative if provided
        if ($alternatives && count($alternatives) > 0) {
            $firstAlt = $alternatives[0];
            $message .= " Der nächste verfügbare Termin ist {$firstAlt['formatted_de']}.";
        }

        return $message;
    }
}
