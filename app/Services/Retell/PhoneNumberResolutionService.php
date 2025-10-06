<?php

namespace App\Services\Retell;

use App\Models\PhoneNumber;
use App\Services\PhoneNumberNormalizer;
use Illuminate\Support\Facades\Log;

/**
 * Phone Number Resolution Service
 *
 * Normalizes and validates phone numbers, resolves to company/branch context
 *
 * SECURITY: Protects against VULN-003 - Tenant isolation breach
 * - Validates phone numbers are registered before processing
 * - Prevents unauthorized access to other companies' data
 * - Caches lookups per request for performance
 */
class PhoneNumberResolutionService implements PhoneNumberResolutionInterface
{
    /**
     * Request-scoped cache for phone number lookups
     * Prevents repeated database queries within a single request
     */
    private array $requestCache = [];

    /**
     * Resolve phone number to company and branch context
     *
     * @param string $phoneNumber Raw phone number from webhook
     * @return array|null ['company_id' => int, 'branch_id' => int|null, 'phone_number_id' => int, 'agent_id' => int|null, 'retell_agent_id' => string|null]
     */
    public function resolve(string $phoneNumber): ?array
    {
        // Request-scoped cache check
        $cacheKey = "phone_context_{$phoneNumber}";
        if (isset($this->requestCache[$cacheKey])) {
            Log::debug('Phone resolution cache hit', ['phone' => $phoneNumber]);
            return $this->requestCache[$cacheKey];
        }

        // Normalize phone number
        $normalized = $this->normalize($phoneNumber);
        if (!$normalized) {
            Log::error('Phone normalization failed', [
                'raw_phone' => $phoneNumber,
                'ip' => request()->ip(),
            ]);
            return null;
        }

        // Lookup in database
        $phoneRecord = PhoneNumber::where('number_normalized', $normalized)
            ->with(['company', 'branch'])
            ->first();

        if (!$phoneRecord) {
            Log::error('Phone number not registered', [
                'raw_phone' => $phoneNumber,
                'normalized' => $normalized,
                'ip' => request()->ip(),
            ]);
            return null;
        }

        // Build context array
        $context = [
            'company_id' => $phoneRecord->company_id,
            'branch_id' => $phoneRecord->branch_id,
            'phone_number_id' => $phoneRecord->id,
            'agent_id' => $phoneRecord->agent_id,
            'retell_agent_id' => $phoneRecord->retell_agent_id,
        ];

        // Cache for request lifecycle
        $this->requestCache[$cacheKey] = $context;

        Log::info('Phone number resolved', [
            'phone_number_id' => $phoneRecord->id,
            'company_id' => $context['company_id'],
            'branch_id' => $context['branch_id'],
            'normalized' => $normalized,
        ]);

        return $context;
    }

    /**
     * Normalize phone number to E.164 format
     *
     * @param string $phoneNumber Raw phone number
     * @return string|null Normalized phone number or null if invalid
     */
    public function normalize(string $phoneNumber): ?string
    {
        return PhoneNumberNormalizer::normalize($phoneNumber);
    }

    /**
     * Validate that a phone number is registered and active
     *
     * @param string $phoneNumber Phone number to validate
     * @return bool True if registered, false otherwise
     */
    public function isRegistered(string $phoneNumber): bool
    {
        return $this->resolve($phoneNumber) !== null;
    }

    /**
     * Get company ID from phone number
     *
     * @param string $phoneNumber Phone number
     * @return int|null Company ID or null if not found
     */
    public function getCompanyId(string $phoneNumber): ?int
    {
        $context = $this->resolve($phoneNumber);
        return $context['company_id'] ?? null;
    }

    /**
     * Get branch ID from phone number
     *
     * @param string $phoneNumber Phone number
     * @return int|null Branch ID or null if not found
     */
    public function getBranchId(string $phoneNumber): ?int
    {
        $context = $this->resolve($phoneNumber);
        return $context['branch_id'] ?? null;
    }

    /**
     * Clear request cache (for testing)
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->requestCache = [];
    }
}