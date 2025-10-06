<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class DeterministicCustomerMatcher
{
    /**
     * Match a customer using deterministic rules only
     * Returns: matched customer, confidence score, or null
     */
    public static function matchCustomer(
        ?string $fromNumber,
        ?string $toNumber,
        ?array $callData = null
    ): array {
        $result = [
            'customer' => null,
            'confidence' => 0,
            'match_method' => null,
            'company_id' => null,
            'is_unknown' => false,
            'unknown_reason' => null,
        ];

        // Step 1: Check if we have a from number
        if (!$fromNumber) {
            $result['is_unknown'] = true;
            $result['unknown_reason'] = 'no_phone_number';
            return $result;
        }

        // Step 2: Normalize phone numbers
        $normalizedFrom = PhoneNumberNormalizer::normalize($fromNumber);
        $normalizedTo = PhoneNumberNormalizer::normalize($toNumber);

        if (!$normalizedFrom) {
            $result['is_unknown'] = true;
            $result['unknown_reason'] = 'invalid_phone_number';
            return $result;
        }

        // Step 3: Determine target company from called number
        $targetCompany = null;
        if ($normalizedTo) {
            $targetPhone = PhoneNumber::where('number', $normalizedTo)
                ->orWhere('number', $toNumber)
                ->first();

            if ($targetPhone) {
                $targetCompany = $targetPhone->company_id;
                $result['company_id'] = $targetCompany;
                Log::info('Target company determined', [
                    'to_number' => $normalizedTo,
                    'company_id' => $targetCompany
                ]);
            }
        }

        // Step 4: EXACT phone number match within company
        if ($targetCompany) {
            $customer = Customer::where('company_id', $targetCompany)
                ->where('phone', $normalizedFrom)
                ->first();

            if ($customer) {
                $result['customer'] = $customer;
                $result['confidence'] = 100; // Perfect match
                $result['match_method'] = 'exact_phone_in_company';
                return $result;
            }
        }

        // Step 5: Check phone variants within company
        if ($targetCompany) {
            $phoneVariants = PhoneNumberNormalizer::generateVariants($normalizedFrom);
            
            $customer = Customer::where('company_id', $targetCompany)
                ->where(function($query) use ($phoneVariants, $normalizedFrom) {
                    $query->whereIn('phone', $phoneVariants);
                    foreach ($phoneVariants as $variant) {
                        $query->orWhereJsonContains('phone_variants', $variant);
                    }
                })
                ->first();

            if ($customer) {
                $result['customer'] = $customer;
                $result['confidence'] = 95; // Very high confidence
                $result['match_method'] = 'phone_variant_in_company';
                return $result;
            }
        }

        // Step 6: Cross-company search (lower confidence)
        $customer = Customer::where('phone', $normalizedFrom)->first();
        if ($customer) {
            $result['customer'] = $customer;
            $result['confidence'] = 70; // Medium confidence - wrong company context
            $result['match_method'] = 'exact_phone_cross_company';
            Log::warning('Customer found but in different company', [
                'customer_company' => $customer->company_id,
                'call_company' => $targetCompany
            ]);
            return $result;
        }

        // Step 7: Mark as unknown - legitimate unknown customer
        $result['is_unknown'] = true;
        $result['unknown_reason'] = 'no_match_found';
        $result['confidence'] = 0;
        
        // Check if it's a suspicious number
        if (in_array($fromNumber, ['anonymous', 'unknown', 'private', 'blocked'])) {
            $result['unknown_reason'] = 'anonymous_caller';
        } elseif (!preg_match('/^\+\d{10,15}$/', $normalizedFrom)) {
            $result['unknown_reason'] = 'invalid_format';
        }

        return $result;
    }

    /**
     * Handle unknown customers - create placeholder or track
     */
    public static function handleUnknownCustomer(
        string $fromNumber,
        ?int $companyId = null,
        array $callData = []
    ): ?Customer {
        $normalizedPhone = PhoneNumberNormalizer::normalize($fromNumber);
        
        // Don't create customers for anonymous calls
        if (!$normalizedPhone || in_array($fromNumber, ['anonymous', 'unknown', 'private'])) {
            return null;
        }

        // Check if we already have an unknown customer placeholder for this number
        $unknownCustomer = Customer::where('phone', $normalizedPhone)
            ->where('customer_type', 'unknown')
            ->first();

        if ($unknownCustomer) {
            // Update call count
            $unknownCustomer->increment('call_count');
            $unknownCustomer->update([
                'last_call_at' => now(),
                'last_seen_at' => now(),
            ]);
            return $unknownCustomer;
        }

        // Create new unknown customer placeholder
        $customerName = 'Unbekannt #' . substr($normalizedPhone, -4);
        
        // Try to extract a better name from call data if available
        if (!empty($callData['call_analysis']['customer_name'])) {
            $customerName = 'Unbekannt: ' . $callData['call_analysis']['customer_name'];
        }

        // 🔧 FIX: Create customer without guarded fields first
        $unknownCustomer = Customer::create([
            'name' => $customerName,
            'phone' => $normalizedPhone,
            'phone_variants' => PhoneNumberNormalizer::generateVariants($normalizedPhone),
            'customer_type' => 'unknown',
            'status' => 'pending_verification',
            'source' => 'retell_unknown',
            'notes' => 'Unbekannter Anrufer - Verifizierung erforderlich',
            'internal_notes' => json_encode([
                'first_call' => now()->toIso8601String(),
                'from_number' => $fromNumber,
                'needs_verification' => true,
            ]),
            'call_count' => 1,
            'last_call_at' => now(),
            'last_seen_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Then set guarded fields directly (bypass mass assignment protection)
        $unknownCustomer->company_id = $companyId ?? 1;
        $unknownCustomer->save();

        Log::info('Created unknown customer placeholder', [
            'customer_id' => $unknownCustomer->id,
            'phone' => $normalizedPhone,
            'company_id' => $companyId
        ]);

        return $unknownCustomer;
    }

    /**
     * Verify and upgrade unknown customer to real customer
     */
    public static function verifyUnknownCustomer(
        Customer $unknownCustomer,
        array $verifiedData
    ): Customer {
        if ($unknownCustomer->customer_type !== 'unknown') {
            return $unknownCustomer;
        }

        $updateData = [
            'customer_type' => 'verified',
            'status' => 'active',
            'matching_confidence' => 100,
        ];

        if (!empty($verifiedData['name'])) {
            $updateData['name'] = $verifiedData['name'];
        }
        if (!empty($verifiedData['email'])) {
            $updateData['email'] = $verifiedData['email'];
        }
        if (!empty($verifiedData['company_name'])) {
            $updateData['company_name'] = $verifiedData['company_name'];
        }

        $unknownCustomer->update($updateData);

        Log::info('Unknown customer verified and upgraded', [
            'customer_id' => $unknownCustomer->id,
            'verified_name' => $verifiedData['name'] ?? null
        ]);

        return $unknownCustomer->fresh();
    }

    /**
     * Get statistics about unknown customers
     */
    public static function getUnknownCustomerStats(): array
    {
        $stats = [
            'total_unknown' => Customer::where('customer_type', 'unknown')->count(),
            'unknown_with_multiple_calls' => Customer::where('customer_type', 'unknown')
                ->where('call_count', '>', 1)
                ->count(),
            'unknown_last_24h' => Customer::where('customer_type', 'unknown')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'pending_verification' => Customer::where('status', 'pending_verification')->count(),
        ];

        return $stats;
    }
}
