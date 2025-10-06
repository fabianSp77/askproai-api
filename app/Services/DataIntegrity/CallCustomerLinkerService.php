<?php

namespace App\Services\DataIntegrity;

use App\Models\Call;
use App\Models\Customer;
use App\Events\CustomerLinkedEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CallCustomerLinkerService
{
    /**
     * Confidence thresholds for automated linking
     */
    const CONFIDENCE_AUTO_LINK = 70.0;      // Auto-link above 70%
    const CONFIDENCE_MANUAL_REVIEW = 40.0;  // Below 70% but above 40% = manual review
    const CONFIDENCE_REJECT = 40.0;         // Below 40% = reject match

    /**
     * Link a call to a customer with full audit trail
     *
     * @param Call $call
     * @param Customer $customer
     * @param string $method Method used for linking (phone_match, name_match, manual_link, etc.)
     * @param float $confidence Confidence score 0-100
     * @param int|null $userId User ID if manually linked
     * @return bool
     */
    public function linkCustomer(
        Call $call,
        Customer $customer,
        string $method,
        float $confidence = 100.0,
        ?int $userId = null
    ): bool {
        try {
            DB::beginTransaction();

            // Verify multi-tenant isolation
            if ($call->company_id !== $customer->company_id) {
                Log::error('Attempted to link call to customer from different company', [
                    'call_id' => $call->id,
                    'call_company_id' => $call->company_id,
                    'customer_id' => $customer->id,
                    'customer_company_id' => $customer->company_id,
                ]);
                return false;
            }

            // Determine link status based on confidence
            $linkStatus = $this->determineLinkStatus($confidence, $method);

            // Update call with linking information
            $call->update([
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_link_status' => $linkStatus,
                'customer_link_method' => $method,
                'customer_link_confidence' => $confidence,
                'customer_linked_at' => now(),
                'linked_by_user_id' => $userId,
                'linking_metadata' => [
                    'customer_phone' => $customer->phone,
                    'customer_email' => $customer->email,
                    'linked_at' => now()->toIso8601String(),
                    'method' => $method,
                    'confidence' => $confidence,
                    'user_id' => $userId,
                ],
            ]);

            DB::commit();

            // Dispatch event for listeners
            event(new CustomerLinkedEvent($call, $customer, $method, $confidence));

            Log::info('Call linked to customer', [
                'call_id' => $call->id,
                'customer_id' => $customer->id,
                'method' => $method,
                'confidence' => $confidence,
                'link_status' => $linkStatus,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to link call to customer', [
                'call_id' => $call->id,
                'customer_id' => $customer->id,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Find the best customer match for a call
     *
     * @param Call $call
     * @return array|null ['customer' => Customer, 'confidence' => float, 'method' => string]
     */
    public function findBestCustomerMatch(Call $call): ?array
    {
        $candidates = [];

        // Strategy 1: Exact phone match (100% confidence)
        if ($call->from_number && $call->from_number !== 'anonymous') {
            $phoneMatch = Customer::where('company_id', $call->company_id)
                ->where('phone', $call->from_number)
                ->first();

            if ($phoneMatch) {
                $candidates[] = [
                    'customer' => $phoneMatch,
                    'confidence' => 100.0,
                    'method' => 'phone_match',
                    'reason' => 'Exact phone number match',
                ];
            }
        }

        // Strategy 2: Name + Company fuzzy match
        if ($call->customer_name) {
            $nameMatches = $this->findNameMatches($call);
            $candidates = array_merge($candidates, $nameMatches);
        }

        // Strategy 3: Appointment-based linking
        if ($call->appointment_id) {
            $appointmentMatch = $this->findAppointmentMatch($call);
            if ($appointmentMatch) {
                $candidates[] = $appointmentMatch;
            }
        }

        // Return best candidate if any
        if (empty($candidates)) {
            return null;
        }

        // Sort by confidence descending
        usort($candidates, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return $candidates[0];
    }

    /**
     * Unlink a customer from a call
     *
     * @param Call $call
     * @param string $reason
     * @param int|null $userId
     * @return bool
     */
    public function unlinkCustomer(Call $call, string $reason, ?int $userId = null): bool
    {
        try {
            DB::beginTransaction();

            $previousCustomerId = $call->customer_id;

            $call->update([
                'customer_id' => null,
                'customer_link_status' => 'unlinked',
                'customer_link_method' => null,
                'customer_link_confidence' => null,
                'linked_by_user_id' => $userId,
                'linking_metadata' => array_merge($call->linking_metadata ?? [], [
                    'unlinked_at' => now()->toIso8601String(),
                    'unlink_reason' => $reason,
                    'unlinked_by_user_id' => $userId,
                    'previous_customer_id' => $previousCustomerId,
                ]),
            ]);

            DB::commit();

            Log::info('Call unlinked from customer', [
                'call_id' => $call->id,
                'previous_customer_id' => $previousCustomerId,
                'reason' => $reason,
                'user_id' => $userId,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to unlink call from customer', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a new customer from call data
     *
     * @param Call $call
     * @return Customer|null
     */
    public function createCustomerFromCall(Call $call): ?Customer
    {
        if (!$call->customer_name) {
            Log::warning('Cannot create customer from call without name', [
                'call_id' => $call->id,
            ]);
            return null;
        }

        try {
            DB::beginTransaction();

            $customer = Customer::create([
                'company_id' => $call->company_id,
                'name' => $call->customer_name,
                'phone' => $call->from_number !== 'anonymous' ? $call->from_number : null,
                'email' => null,
                'created_from_call_id' => $call->id,
            ]);

            // Link the call to the newly created customer
            $this->linkCustomer($call, $customer, 'auto_created', 100.0);

            DB::commit();

            Log::info('Created customer from call', [
                'call_id' => $call->id,
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
            ]);

            return $customer;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create customer from call', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Determine link status based on confidence and method
     */
    private function determineLinkStatus(float $confidence, string $method): string
    {
        if ($method === 'manual_link') {
            return 'linked';
        }

        if ($confidence >= self::CONFIDENCE_AUTO_LINK) {
            return 'linked';
        }

        if ($confidence >= self::CONFIDENCE_MANUAL_REVIEW) {
            return 'pending_review';
        }

        return 'failed';
    }

    /**
     * Find customer matches by name with fuzzy matching
     */
    private function findNameMatches(Call $call): array
    {
        $candidates = [];
        $searchName = strtolower(trim($call->customer_name));

        // Exact name match
        $exactMatches = Customer::where('company_id', $call->company_id)
            ->whereRaw('LOWER(name) = ?', [$searchName])
            ->get();

        foreach ($exactMatches as $customer) {
            $candidates[] = [
                'customer' => $customer,
                'confidence' => 85.0,
                'method' => 'name_match',
                'reason' => 'Exact name match within company',
            ];
        }

        // Fuzzy name match (contains)
        if (count($candidates) === 0 && strlen($searchName) > 3) {
            $fuzzyMatches = Customer::where('company_id', $call->company_id)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . $searchName . '%'])
                ->limit(5)
                ->get();

            foreach ($fuzzyMatches as $customer) {
                $similarity = $this->calculateStringSimilarity(
                    $searchName,
                    strtolower($customer->name)
                );

                if ($similarity >= 0.6) {
                    $candidates[] = [
                        'customer' => $customer,
                        'confidence' => round($similarity * 100, 2),
                        'method' => 'name_match',
                        'reason' => 'Fuzzy name match (similarity: ' . round($similarity * 100) . '%)',
                    ];
                }
            }
        }

        return $candidates;
    }

    /**
     * Find customer match via appointment relationship
     */
    private function findAppointmentMatch(Call $call): ?array
    {
        if (!$call->appointment_id) {
            return null;
        }

        $appointment = \App\Models\Appointment::find($call->appointment_id);
        if (!$appointment || !$appointment->customer_id) {
            return null;
        }

        $customer = Customer::find($appointment->customer_id);
        if (!$customer || $customer->company_id !== $call->company_id) {
            return null;
        }

        return [
            'customer' => $customer,
            'confidence' => 95.0,
            'method' => 'appointment_link',
            'reason' => 'Linked via appointment #' . $appointment->id,
        ];
    }

    /**
     * Calculate string similarity (Levenshtein-based)
     */
    private function calculateStringSimilarity(string $str1, string $str2): float
    {
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        $maxLen = max($len1, $len2);

        if ($maxLen === 0) {
            return 1.0;
        }

        $levenshtein = levenshtein($str1, $str2);
        return 1 - ($levenshtein / $maxLen);
    }
}
