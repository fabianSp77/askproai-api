<?php

namespace App\Services\Retell;

use App\Models\Call;
use Illuminate\Support\Facades\Log;

/**
 * CustomerDataValidator
 *
 * Validates and normalizes customer contact information (email, phone)
 * with intelligent fallback logic for appointment booking.
 *
 * Phase 3: Extracted from RetellFunctionCallHandler to eliminate duplication
 */
class CustomerDataValidator
{
    /**
     * Get valid email with fallback logic
     *
     * Priority:
     * 1. Request parameters (validated)
     * 2. Call customer database record
     * 3. Environment fallback
     *
     * @param array $params Request parameters
     * @param Call|null $call Call record for customer lookup
     * @return string Valid email address
     */
    public function getValidEmail(array $params, ?Call $call = null): string
    {
        // 1. Try from request parameters
        $email = $params['email']
              ?? $params['customer_email']
              ?? null;

        // 2. Validate email format
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Log::info('📧 Using customer email for appointment', [
                'email' => $email,
                'source' => 'request'
            ]);
            return $email;
        }

        // 3. Try from call customer data if available
        if ($call && $call->customer) {
            $customerEmail = $call->customer->email ?? null;
            if ($customerEmail && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                Log::info('📧 Using customer email from database', [
                    'email' => $customerEmail,
                    'source' => 'database',
                    'customer_id' => $call->customer->id
                ]);
                return $customerEmail;
            }
        }

        // 4. Use fallback email from environment
        $fallbackEmail = env('DEFAULT_APPOINTMENT_EMAIL', 'termin@askproai.de');

        Log::info('📧 Using fallback email for appointment (no valid customer email)', [
            'fallback_email' => $fallbackEmail,
            'original_email' => $email,
            'call_id' => $call?->id,
            'warning' => 'Customer email not provided or invalid'
        ]);

        return $fallbackEmail;
    }

    /**
     * Get valid phone number with fallback logic
     *
     * Priority:
     * 1. Request parameters (validated and normalized)
     * 2. Call customer database record
     * 3. Call from_number
     * 4. Environment fallback
     *
     * @param array $params Request parameters
     * @param Call|null $call Call record for customer lookup
     * @return string Valid phone number in international format
     */
    public function getValidPhone(array $params, ?Call $call = null): string
    {
        // 1. Try from request parameters
        $phone = $params['phone']
              ?? $params['customer_phone']
              ?? $params['telefonnummer']
              ?? null;

        // 2. Validate and format phone number
        if ($phone) {
            $normalized = $this->normalizePhoneNumber($phone);
            if ($normalized) {
                Log::info('📞 Using customer phone number', [
                    'phone' => $normalized,
                    'original' => $phone,
                    'source' => 'request_args'
                ]);
                return $normalized;
            }
        }

        // 3. Try from database if call exists
        if ($call && $call->customer) {
            $customerPhone = $call->customer->phone ?? null;
            if ($customerPhone) {
                Log::info('📞 Using customer phone from database', [
                    'phone' => $customerPhone,
                    'source' => 'database',
                    'customer_id' => $call->customer->id
                ]);
                return $customerPhone;
            }
        }

        // 4. Try from call from_number
        if ($call && $call->from_number && !in_array(strtolower($call->from_number), ['anonymous', 'unknown', 'blocked'])) {
            $normalized = $this->normalizePhoneNumber($call->from_number);
            if ($normalized) {
                Log::info('📞 Using phone from call record', [
                    'phone' => $normalized,
                    'source' => 'call_from_number',
                    'call_id' => $call->id
                ]);
                return $normalized;
            }
        }

        // 5. Use fallback phone from environment
        $fallbackPhone = env('DEFAULT_APPOINTMENT_PHONE', '+491234567890');

        Log::warning('📞 Using fallback phone for appointment (no valid customer phone)', [
            'fallback_phone' => $fallbackPhone,
            'original_phone' => $phone,
            'call_id' => $call?->id,
            'warning' => 'Customer phone not provided or invalid'
        ]);

        return $fallbackPhone;
    }

    /**
     * Normalize phone number to international format (+49...)
     *
     * @param string $phone Raw phone number
     * @return string|null Normalized phone or null if invalid
     */
    private function normalizePhoneNumber(string $phone): ?string
    {
        // Remove spaces and special characters for validation
        $cleanPhone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Check if it's a valid phone number format
        if (!preg_match('/^(\+49|0049|0)?[1-9]\d{5,14}$/', $cleanPhone)) {
            return null;
        }

        // Format to international format
        if (strpos($cleanPhone, '+49') !== 0) {
            // Remove leading zeros or country code variants
            $cleanPhone = preg_replace('/^(0049|0)/', '', $cleanPhone);
            $cleanPhone = '+49' . $cleanPhone;
        }

        return $cleanPhone;
    }

    /**
     * Validate customer name
     *
     * @param string|null $name Customer name
     * @return string|null Validated name or null
     */
    public function getValidName(?string $name): ?string
    {
        if (!$name || strlen(trim($name)) < 2) {
            return null;
        }

        $name = trim($name);

        // Check if name contains at least one letter
        if (!preg_match('/[a-zA-ZäöüÄÖÜß]/', $name)) {
            return null;
        }

        // Reasonable length check
        if (strlen($name) > 100) {
            return null;
        }

        return $name;
    }
}
