<?php

namespace App\Services\Retell;

use App\Models\PhoneNumber;

/**
 * Phone Number Resolution Interface
 *
 * Responsible for resolving phone numbers to company and branch context
 * SECURITY: Fixes VULN-003 - Prevents unauthorized access to other companies' data
 */
interface PhoneNumberResolutionInterface
{
    /**
     * Resolve phone number to company and branch context
     *
     * @param string $phoneNumber Raw phone number from webhook
     * @return array|null ['company_id' => int, 'branch_id' => int|null, 'phone_number_id' => int, 'agent_id' => int|null, 'retell_agent_id' => string|null]
     */
    public function resolve(string $phoneNumber): ?array;

    /**
     * Normalize phone number to E.164 format
     *
     * @param string $phoneNumber Raw phone number
     * @return string|null Normalized phone number or null if invalid
     */
    public function normalize(string $phoneNumber): ?string;

    /**
     * Validate that a phone number is registered and active
     *
     * @param string $phoneNumber Phone number to validate
     * @return bool True if registered, false otherwise
     */
    public function isRegistered(string $phoneNumber): bool;

    /**
     * Get company ID from phone number
     *
     * @param string $phoneNumber Phone number
     * @return int|null Company ID or null if not found
     */
    public function getCompanyId(string $phoneNumber): ?int;

    /**
     * Get branch ID from phone number
     *
     * @param string $phoneNumber Phone number
     * @return int|null Branch ID or null if not found
     */
    public function getBranchId(string $phoneNumber): ?int;

    /**
     * Clear request cache (for testing)
     *
     * @return void
     */
    public function clearCache(): void;
}