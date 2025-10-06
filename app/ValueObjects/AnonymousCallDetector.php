<?php

namespace App\ValueObjects;

use App\Models\Call;

/**
 * AnonymousCallDetector Value Object
 *
 * Centralized logic for detecting anonymous calls
 * Eliminates duplicate conditional checks across the codebase
 *
 * Usage:
 *   AnonymousCallDetector::isAnonymous($call)
 *   AnonymousCallDetector::fromNumber($fromNumber)
 */
class AnonymousCallDetector
{
    /**
     * Anonymous call indicators
     */
    private const ANONYMOUS_INDICATORS = [
        'anonymous',
        'unknown',
        'blocked',
        'private',
        'withheld',
        'unavailable',
        null,
        '',
    ];

    /**
     * Check if a call is anonymous
     *
     * @param Call $call
     * @return bool
     */
    public static function isAnonymous(Call $call): bool
    {
        return self::fromNumber($call->from_number);
    }

    /**
     * Check if a phone number is anonymous
     *
     * @param string|null $fromNumber
     * @return bool
     */
    public static function fromNumber(?string $fromNumber): bool
    {
        if ($fromNumber === null || trim($fromNumber) === '') {
            return true;
        }

        $normalized = strtolower(trim($fromNumber));

        return in_array($normalized, self::ANONYMOUS_INDICATORS, true);
    }

    /**
     * Check if a call has a valid phone number
     *
     * @param Call $call
     * @return bool
     */
    public static function hasValidNumber(Call $call): bool
    {
        return !self::isAnonymous($call);
    }

    /**
     * Get the anonymity reason
     *
     * @param Call $call
     * @return string|null
     */
    public static function getReason(Call $call): ?string
    {
        if (!self::isAnonymous($call)) {
            return null;
        }

        if ($call->from_number === null || trim($call->from_number) === '') {
            return 'no_number_provided';
        }

        $normalized = strtolower(trim($call->from_number));

        return match ($normalized) {
            'anonymous' => 'caller_id_blocked',
            'unknown' => 'number_unknown',
            'blocked' => 'caller_id_blocked',
            'private' => 'private_number',
            'withheld' => 'number_withheld',
            'unavailable' => 'number_unavailable',
            default => 'other',
        };
    }

    /**
     * Get linkability score (0-100)
     * How likely we can link this call to a customer
     *
     * @param Call $call
     * @return float
     */
    public static function getLinkabilityScore(Call $call): float
    {
        if (self::hasValidNumber($call)) {
            // Valid phone number = 100% linkable via phone match
            return 100.0;
        }

        if ($call->customer_name) {
            // Has name but no phone = 70% linkable via name match
            return 70.0;
        }

        if ($call->transcript) {
            // Has transcript, might extract name = 40% linkable
            return 40.0;
        }

        // Completely anonymous = 0% linkable
        return 0.0;
    }

    /**
     * Should we attempt customer linking for this call?
     *
     * @param Call $call
     * @return bool
     */
    public static function shouldAttemptLinking(Call $call): bool
    {
        // If has valid number, always try
        if (self::hasValidNumber($call)) {
            return true;
        }

        // If has customer name, try fuzzy matching
        if ($call->customer_name) {
            return true;
        }

        // If has transcript, try name extraction
        if ($call->transcript && strlen($call->transcript) > 50) {
            return true;
        }

        // Too anonymous to link
        return false;
    }
}
