<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * PhoneNumberNormalizer Service
 *
 * Normalizes phone numbers to E.164 format for consistent storage and lookup.
 * Handles international phone numbers with libphonenumber for robust parsing.
 *
 * @package App\Services
 * @version 2.0.0 - Production-Ready with libphonenumber
 * @author Claude Code Architecture Team
 */
class PhoneNumberNormalizer
{
    /**
     * Default country code for phone number parsing
     */
    private const DEFAULT_COUNTRY = 'DE';

    /**
     * Fallback countries to try if primary parsing fails
     */
    private const FALLBACK_COUNTRIES = ['AT', 'CH', 'FR', 'NL', 'BE'];

    /**
     * PhoneNumberUtil instance (cached for performance)
     */
    private static ?PhoneNumberUtil $phoneUtil = null;

    /**
     * Get or initialize PhoneNumberUtil instance
     *
     * @return PhoneNumberUtil
     */
    private static function getPhoneUtil(): PhoneNumberUtil
    {
        if (self::$phoneUtil === null) {
            self::$phoneUtil = PhoneNumberUtil::getInstance();
        }
        return self::$phoneUtil;
    }

    /**
     * Normalize a phone number to E.164 format
     *
     * E.164 format: +[country code][subscriber number]
     * Example: +493083793369
     *
     * @param string|null $phoneNumber Raw phone number (may contain spaces, dashes, parentheses)
     * @param string $defaultCountry ISO 3166-1 alpha-2 country code (default: DE)
     * @return string|null Normalized phone number in E.164 format, or null if invalid
     */
    public static function normalize(?string $phoneNumber, string $defaultCountry = self::DEFAULT_COUNTRY): ?string
    {
        // Handle null, empty, or anonymous input
        if (empty($phoneNumber) || in_array(strtolower($phoneNumber), ['anonymous', 'unknown'])) {
            return null;
        }

        // Remove whitespace from input
        $phoneNumber = trim($phoneNumber);

        // Try primary country first
        $normalized = self::tryNormalizeWithCountry($phoneNumber, $defaultCountry);
        if ($normalized !== null) {
            return $normalized;
        }

        // Try fallback countries if primary failed
        foreach (self::FALLBACK_COUNTRIES as $fallbackCountry) {
            $normalized = self::tryNormalizeWithCountry($phoneNumber, $fallbackCountry);
            if ($normalized !== null) {
                Log::info('Phone number normalized with fallback country', [
                    'original' => $phoneNumber,
                    'normalized' => $normalized,
                    'fallback_country' => $fallbackCountry,
                ]);
                return $normalized;
            }
        }

        // Final fallback: basic cleaning (backward compatibility with old implementation)
        $cleaned = self::basicClean($phoneNumber);

        Log::warning('Phone number normalization failed, using basic cleaning', [
            'original' => $phoneNumber,
            'cleaned' => $cleaned,
        ]);

        return $cleaned;
    }

    /**
     * Try to normalize phone number with a specific country code
     *
     * @param string $phoneNumber
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return string|null Normalized phone number or null if parsing failed
     */
    private static function tryNormalizeWithCountry(string $phoneNumber, string $countryCode): ?string
    {
        try {
            $phoneUtil = self::getPhoneUtil();
            $parsed = $phoneUtil->parse($phoneNumber, $countryCode);

            // Validate the parsed number
            if (!$phoneUtil->isValidNumber($parsed)) {
                return null;
            }

            // Format to E.164
            return $phoneUtil->format($parsed, PhoneNumberFormat::E164);

        } catch (NumberParseException $e) {
            // Parsing failed for this country, return null to try next
            return null;
        } catch (\Exception $e) {
            Log::error('Unexpected error during phone number parsing', [
                'phone_number' => $phoneNumber,
                'country_code' => $countryCode,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Basic cleaning: remove all characters except digits and leading +
     *
     * This is a fallback when libphonenumber parsing fails.
     * Maintains backward compatibility with old regex-based implementation.
     *
     * @param string $phoneNumber
     * @return string|null Cleaned phone number or null if no digits remain
     */
    private static function basicClean(string $phoneNumber): ?string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phoneNumber);

        if (empty($phone)) {
            return null;
        }

        // Handle German number formats (backward compatibility)
        if (preg_match('/^0049/', $phone)) {
            // Convert 0049 to +49
            $phone = '+49' . substr($phone, 4);
        } elseif (preg_match('/^049/', $phone)) {
            // Convert 049 to +49
            $phone = '+49' . substr($phone, 3);
        } elseif (preg_match('/^49/', $phone)) {
            // Add + prefix
            $phone = '+' . $phone;
        } elseif (preg_match('/^0[1-9]/', $phone)) {
            // German national format (e.g., 030xxx)
            $phone = '+49' . substr($phone, 1);
        } elseif (!preg_match('/^\+/', $phone)) {
            // If no country code and not starting with 0, assume German
            if (preg_match('/^[1-9]/', $phone) && strlen($phone) >= 10) {
                $phone = '+49' . $phone;
            }
        }

        return $phone ?: null;
    }

    /**
     * Normalize multiple phone numbers in batch
     *
     * Useful for bulk operations like data migrations.
     *
     * @param array<string|null> $phoneNumbers
     * @param string $defaultCountry
     * @return array<int, string|null> Array of normalized phone numbers (same order as input)
     */
    public static function normalizeBatch(array $phoneNumbers, string $defaultCountry = self::DEFAULT_COUNTRY): array
    {
        return array_map(
            fn($number) => self::normalize($number, $defaultCountry),
            $phoneNumbers
        );
    }

    /**
     * Validate if a phone number string is already in E.164 format
     *
     * E.164 format requirements:
     * - Starts with +
     * - Contains only digits after +
     * - Length between 8 and 15 characters (including +)
     *
     * @param string|null $phoneNumber
     * @return bool
     */
    public static function isE164Format(?string $phoneNumber): bool
    {
        if (empty($phoneNumber)) {
            return false;
        }

        // E.164 format: + followed by 7-14 digits
        return preg_match('/^\+[1-9]\d{6,14}$/', $phoneNumber) === 1;
    }

    /**
     * Get country code from a normalized E.164 phone number
     *
     * @param string $e164Number Phone number in E.164 format
     * @return int|null Country calling code (e.g., 49 for Germany, 1 for USA)
     */
    public static function getCountryCode(string $e164Number): ?int
    {
        try {
            $phoneUtil = self::getPhoneUtil();
            $parsed = $phoneUtil->parse($e164Number, null);
            return $parsed->getCountryCode();
        } catch (\Exception $e) {
            Log::warning('Failed to extract country code', [
                'phone_number' => $e164Number,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get region code (ISO 3166-1 alpha-2) from a normalized E.164 phone number
     *
     * @param string $e164Number Phone number in E.164 format
     * @return string|null Region code (e.g., 'DE' for Germany, 'US' for USA)
     */
    public static function getRegionCode(string $e164Number): ?string
    {
        try {
            $phoneUtil = self::getPhoneUtil();
            $parsed = $phoneUtil->parse($e164Number, null);
            return $phoneUtil->getRegionCodeForNumber($parsed) ?: null;
        } catch (\Exception $e) {
            Log::warning('Failed to extract region code', [
                'phone_number' => $e164Number,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Format a phone number for display (international format)
     *
     * Converts E.164 to human-readable international format.
     * Example: +493083793369 → +49 30 83793369
     *
     * @param string|null $e164Number Phone number in E.164 format
     * @return string|null Formatted phone number or original if formatting fails
     */
    public static function formatForDisplay(?string $e164Number): ?string
    {
        if (empty($e164Number)) {
            return null;
        }

        try {
            $phoneUtil = self::getPhoneUtil();
            $parsed = $phoneUtil->parse($e164Number, null);
            return $phoneUtil->format($parsed, PhoneNumberFormat::INTERNATIONAL);
        } catch (\Exception $e) {
            Log::warning('Failed to format phone number for display', [
                'phone_number' => $e164Number,
                'error' => $e->getMessage(),
            ]);
            return $e164Number; // Return original if formatting fails
        }
    }

    /**
     * Compare two phone numbers for equality (after normalization)
     *
     * Useful for matching phone numbers in different formats.
     * Example: "+49 30 83793369" equals "+493083793369"
     *
     * @param string|null $phone1
     * @param string|null $phone2
     * @param string $defaultCountry
     * @return bool True if both numbers normalize to the same E.164 format
     */
    public static function areEqual(?string $phone1, ?string $phone2, string $defaultCountry = self::DEFAULT_COUNTRY): bool
    {
        $normalized1 = self::normalize($phone1, $defaultCountry);
        $normalized2 = self::normalize($phone2, $defaultCountry);

        // Both must be successfully normalized and match
        return $normalized1 !== null
            && $normalized2 !== null
            && $normalized1 === $normalized2;
    }

    /**
     * Legacy method for backward compatibility
     *
     * @deprecated Use areEqual() instead
     */
    public static function matches(string $phone1, string $phone2): bool
    {
        return self::areEqual($phone1, $phone2);
    }

    /**
     * Generate phone number variants for matching
     *
     * @deprecated This method is no longer needed with proper E.164 normalization
     *            but kept for backward compatibility
     */
    public static function generateVariants(string $phone): array
    {
        $normalized = self::normalize($phone);
        if (!$normalized) {
            return [];
        }

        $variants = [$normalized];

        // For German numbers, add display variants
        if (str_starts_with($normalized, '+49')) {
            $nationalNumber = substr($normalized, 3);
            $variants[] = self::formatForDisplay($normalized);
        }

        return array_unique(array_filter($variants));
    }
}