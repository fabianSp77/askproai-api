<?php

namespace App\Services\Retell;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Call Lifecycle Service
 *
 * Centralized call state management for Retell.ai webhooks and function calls
 *
 * FEATURES:
 * - Request-scoped caching (3-4 queries saved per request)
 * - State machine validation for call transitions
 * - Comprehensive logging for audit trail
 * - Temporary call matching for call_inbound → call_started flow
 *
 * STATE MACHINE:
 * inbound → ongoing → completed → analyzed
 */
class CallLifecycleService implements CallLifecycleInterface
{
    /**
     * Request-scoped cache for call lookups
     *
     * Avoids duplicate database queries within same request
     * Key: retell_call_id, Value: Call instance
     *
     * @var array<string, Call>
     */
    private array $callCache = [];

    /**
     * Valid state transitions
     *
     * @var array<string, array<string>>
     */
    private const VALID_TRANSITIONS = [
        'inbound' => ['ongoing', 'completed'], // Can skip to completed if call ends immediately
        'ongoing' => ['completed'],
        'completed' => ['analyzed'],
    ];

    /**
     * Valid call statuses
     *
     * @var array<string>
     */
    private const VALID_STATUSES = [
        'inbound',
        'ongoing',
        'completed',
        'analyzed',
        'ended', // Legacy status
        'active', // Legacy status
        'in-progress', // Legacy status
    ];

    /**
     * {@inheritDoc}
     */
    public function createCall(
        array $callData,
        ?int $companyId = null,
        ?string $phoneNumberId = null,
        ?string $branchId = null
    ): Call {
        return DB::transaction(function () use ($callData, $companyId, $phoneNumberId, $branchId) {
            // 🔥 FIX: Auto-resolve company_id/branch_id from phone_number if not provided
            if ($phoneNumberId && (!$companyId || !$branchId)) {
                $phoneNumber = \App\Models\PhoneNumber::find($phoneNumberId);
                if ($phoneNumber) {
                    $companyId = $companyId ?? $phoneNumber->company_id;
                    $branchId = $branchId ?? $phoneNumber->branch_id;

                    Log::info('🔧 Auto-resolved company/branch from phone_number', [
                        'phone_number_id' => $phoneNumberId,
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                    ]);
                }
            }

            $createData = [
                'retell_call_id' => $callData['call_id'] ?? $callData['retell_call_id'] ?? null,
                'external_id' => $callData['call_id'] ?? null,
                'from_number' => $callData['from_number'] ?? 'unknown',
                'to_number' => $callData['to_number'] ?? null,
                'direction' => $callData['direction'] ?? 'inbound',
                'status' => $callData['status'] ?? 'ongoing',
                'call_status' => $callData['call_status'] ?? 'ongoing',
                'agent_id' => $callData['agent_id'] ?? null,
                // phone_number_id is GUARDED - will be set manually after create
                // company_id and branch_id are GUARDED - will be set manually after create
            ];

            // Add timestamps if available
            if (isset($callData['start_timestamp'])) {
                $createData['start_timestamp'] = Carbon::createFromTimestampMs($callData['start_timestamp']);
            }

            // 🔧 FIX 2025-12-13: Use updateOrCreate to handle race conditions properly
            // BACKGROUND: ensureCallRecordExists() in RetellFunctionCallHandler may have already
            // created the Call record before this webhook is processed (race condition).
            //
            // WHY updateOrCreate() instead of firstOrCreate():
            // - ensureCallRecordExists() only sets: from_number, to_number, status, direction
            // - Webhook provides ADDITIONAL critical fields: agent_id, external_id, start_timestamp
            // - firstOrCreate() would NOT update agent_id if Call already exists
            // - updateOrCreate() ensures webhook fields are always applied
            $call = Call::updateOrCreate(
                ['retell_call_id' => $createData['retell_call_id']],
                $createData
            );

            // 🔥 FIX: Manually set company_id, branch_id, and phone_number_id to bypass $guarded protection
            // These fields are guarded to prevent mass assignment vulnerabilities,
            // but we need to set them explicitly for webhook-created calls
            $needsSave = false;

            if ($companyId !== null) {
                $call->company_id = $companyId;
                $needsSave = true;
            }
            if ($branchId !== null) {
                $call->branch_id = $branchId;
                $needsSave = true;
            }
            if ($phoneNumberId !== null) {
                $call->phone_number_id = $phoneNumberId;
                $needsSave = true;
            }

            if ($needsSave) {
                $call->save();
            }

            // Cache the call
            if ($call->retell_call_id) {
                $this->callCache[$call->retell_call_id] = $call;
            }

            // 🔧 FIX 2025-12-13: Log whether call was created or updated
            // With updateOrCreate(): created = new record, updated = existing record enriched with webhook data
            $logMessage = $call->wasRecentlyCreated ? '📞 Call created' : '📞 Call updated (enriched with webhook data)';
            Log::info($logMessage, [
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'status' => $call->status,
                'agent_id' => $call->agent_id,             // Critical field from webhook
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'phone_number_id' => $call->phone_number_id,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'was_recently_created' => $call->wasRecentlyCreated,
            ]);

            return $call;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function createTemporaryCall(
        string $fromNumber,
        string $toNumber,
        ?int $companyId = null,
        ?string $phoneNumberId = null,
        ?string $branchId = null,
        ?string $agentId = null
    ): Call {
        return DB::transaction(function () use ($fromNumber, $toNumber, $companyId, $phoneNumberId, $branchId, $agentId) {
            // Generate unique temporary ID
            $tempId = 'temp_' . now()->timestamp . '_' . substr(md5($fromNumber . $toNumber), 0, 8);

            $call = Call::create([
                'retell_call_id' => $tempId,
                'call_id' => $tempId,
                'from_number' => $fromNumber,
                'to_number' => $toNumber,
                // phone_number_id, company_id and branch_id removed from create array - will be set manually below
                'agent_id' => $agentId,
                'retell_agent_id' => $agentId,
                'status' => 'inbound',
                'direction' => 'inbound',
            ]);

            // 🔥 FIX: Manually set phone_number_id, company_id and branch_id to ensure persistence
            $needsSave = false;
            if ($phoneNumberId !== null) {
                $call->phone_number_id = $phoneNumberId;
                $needsSave = true;
            }
            if ($companyId !== null) {
                $call->company_id = $companyId;
                $needsSave = true;
            }
            if ($branchId !== null) {
                $call->branch_id = $branchId;
                $needsSave = true;
            }
            if ($needsSave) {
                $call->save();
            }

            Log::info('📞 Temporary call created', [
                'temp_id' => $tempId,
                'from' => $fromNumber,
                'to' => $toNumber,
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'phone_number_id' => $call->phone_number_id, // Log actual saved value
            ]);

            return $call;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function findCallByRetellId(string $retellCallId, bool $withRelations = false): ?Call
    {
        // Check cache first
        if (isset($this->callCache[$retellCallId])) {
            Log::debug('Call cache hit', ['retell_call_id' => $retellCallId]);
            return $this->callCache[$retellCallId];
        }

        // Query database
        $query = Call::where('retell_call_id', $retellCallId)
            ->orWhere('external_id', $retellCallId);

        if ($withRelations) {
            $query->with(['phoneNumber', 'customer', 'company', 'branch', 'appointment']);
        }

        $call = $query->first();

        // Cache if found
        if ($call) {
            $this->callCache[$retellCallId] = $call;
            Log::debug('Call found and cached', [
                'retell_call_id' => $retellCallId,
                'call_id' => $call->id
            ]);
        } else {
            Log::debug('Call not found', ['retell_call_id' => $retellCallId]);
        }

        return $call;
    }

    /**
     * {@inheritDoc}
     */
    public function findRecentTemporaryCall(): ?Call
    {
        $tempCall = Call::where('retell_call_id', 'LIKE', 'temp_%')
            ->where('created_at', '>=', now()->subMinutes(10))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($tempCall) {
            Log::info('Temporary call found for matching', [
                'temp_id' => $tempCall->retell_call_id,
                'created_at' => $tempCall->created_at,
            ]);
        }

        return $tempCall;
    }

    /**
     * {@inheritDoc}
     */
    public function upgradeTemporaryCall(
        Call $tempCall,
        string $realCallId,
        array $additionalData = []
    ): Call {
        $updateData = array_merge([
            'retell_call_id' => $realCallId,
            'external_id' => $realCallId,
        ], $additionalData);

        $tempCall->update($updateData);

        // Update cache with new call ID
        $this->callCache[$realCallId] = $tempCall;

        Log::info('✅ Temporary call upgraded', [
            'temp_id' => $tempCall->retell_call_id,
            'real_call_id' => $realCallId,
            'call_id' => $tempCall->id,
        ]);

        // PERFORMANCE: Use refresh() instead of fresh() - more efficient
        return $tempCall->refresh();
    }

    /**
     * {@inheritDoc}
     */
    public function updateCallStatus(Call $call, string $newStatus, array $additionalData = []): Call
    {
        // Validate status
        if (!in_array($newStatus, self::VALID_STATUSES)) {
            Log::warning('Invalid call status attempted', [
                'call_id' => $call->id,
                'current_status' => $call->status,
                'attempted_status' => $newStatus,
            ]);
            // Don't throw exception, just log and continue with update
        }

        // Validate state transition
        $currentStatus = $call->status;
        if (isset(self::VALID_TRANSITIONS[$currentStatus])) {
            if (!in_array($newStatus, self::VALID_TRANSITIONS[$currentStatus])) {
                Log::warning('Invalid state transition attempted', [
                    'call_id' => $call->id,
                    'from_status' => $currentStatus,
                    'to_status' => $newStatus,
                    'valid_transitions' => self::VALID_TRANSITIONS[$currentStatus],
                ]);
            }
        }

        $updateData = array_merge([
            'status' => $newStatus,
            'call_status' => $newStatus,
        ], $additionalData);

        $call->update($updateData);

        // Update cache
        if ($call->retell_call_id) {
            $this->callCache[$call->retell_call_id] = $call;
        }

        Log::info('📊 Call status updated', [
            'call_id' => $call->id,
            'from_status' => $currentStatus,
            'to_status' => $newStatus,
        ]);

        // PERFORMANCE: Use refresh() instead of fresh() - more efficient
        return $call->refresh();
    }

    /**
     * {@inheritDoc}
     */
    public function linkCustomer(Call $call, Customer $customer): Call
    {
        $call->update(['customer_id' => $customer->id]);

        // Update cache
        if ($call->retell_call_id) {
            $this->callCache[$call->retell_call_id] = $call;
        }

        Log::info('👤 Customer linked to call', [
            'call_id' => $call->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
        ]);

        // PERFORMANCE: Use refresh() instead of fresh() - more efficient
        return $call->refresh();
    }

    /**
     * {@inheritDoc}
     */
    public function linkAppointment(Call $call, Appointment $appointment): Call
    {
        $call->update(['converted_appointment_id' => $appointment->id]);

        // Update cache
        if ($call->retell_call_id) {
            $this->callCache[$call->retell_call_id] = $call;
        }

        Log::info('📅 Appointment linked to call (conversion)', [
            'call_id' => $call->id,
            'appointment_id' => $appointment->id,
            'appointment_time' => $appointment->appointment_datetime,
        ]);

        // PERFORMANCE: Use refresh() instead of fresh() - more efficient
        return $call->refresh();
    }

    /**
     * {@inheritDoc}
     */
    public function trackBooking(
        Call $call,
        array $bookingDetails,
        bool $confirmed = false,
        ?string $bookingId = null
    ): Call {
        $updateData = [
            'booking_details' => json_encode($bookingDetails),
        ];

        if ($confirmed) {
            $updateData['booking_confirmed'] = true;
            $updateData['call_successful'] = true;
            $updateData['appointment_made'] = true;
        }

        if ($bookingId) {
            $updateData['booking_id'] = $bookingId;
        }

        $call->update($updateData);

        // Update cache
        if ($call->retell_call_id) {
            $this->callCache[$call->retell_call_id] = $call;
        }

        Log::info('📋 Booking details tracked', [
            'call_id' => $call->id,
            'confirmed' => $confirmed,
            'booking_id' => $bookingId,
            'service' => $bookingDetails['service'] ?? null,
            'date' => $bookingDetails['date'] ?? null,
        ]);

        // PERFORMANCE: Use refresh() instead of fresh() - more efficient
        return $call->refresh();
    }

    /**
     * {@inheritDoc}
     */
    public function trackFailedBooking(
        Call $call,
        array $bookingDetails,
        string $failureReason
    ): Call {
        $call->update([
            'booking_failed' => true,
            'booking_failure_reason' => $failureReason,
            'booking_details' => json_encode($bookingDetails),
            'requires_manual_processing' => true,
            'call_successful' => false,
        ]);

        // Update cache
        if ($call->retell_call_id) {
            $this->callCache[$call->retell_call_id] = $call;
        }

        Log::warning('❌ Booking failed - stored for manual review', [
            'call_id' => $call->id,
            'failure_reason' => $failureReason,
            'customer_name' => $bookingDetails['customer_name'] ?? null,
        ]);

        // PERFORMANCE: Use refresh() instead of fresh() - more efficient
        return $call->refresh();
    }

    /**
     * {@inheritDoc}
     */
    public function updateAnalysis(Call $call, array $analysisData): Call
    {
        // Merge with existing analysis
        $existingAnalysis = $call->analysis ?? [];
        $mergedAnalysis = array_merge($existingAnalysis, $analysisData);

        $call->update(['analysis' => $mergedAnalysis]);

        // Update cache
        if ($call->retell_call_id) {
            $this->callCache[$call->retell_call_id] = $call;
        }

        Log::info('🔍 Call analysis updated', [
            'call_id' => $call->id,
            'analysis_keys' => array_keys($analysisData),
        ]);

        // PERFORMANCE: Use refresh() instead of fresh() - more efficient
        return $call->refresh();
    }

    /**
     * {@inheritDoc}
     */
    public function getCallContext(string $retellCallId): ?Call
    {
        // Check cache first (request-scoped, saves 3-4 DB queries per request)
        if (isset($this->callCache[$retellCallId])) {
            Log::debug('Call context cache hit', ['retell_call_id' => $retellCallId]);
            return $this->callCache[$retellCallId];
        }

        // Load from database with relationships (optimized with select for performance)
        $call = Call::where('retell_call_id', $retellCallId)
            ->with([
                'phoneNumber:id,company_id,branch_id,phone_number',
                'company:id,name',
                'branch:id,name',
                'customer' => function ($query) {
                    $query->select('id', 'name', 'phone', 'email')
                        ->with(['appointments' => function ($q) {
                            $q->where('starts_at', '>=', now())
                                ->select('id', 'customer_id', 'starts_at', 'ends_at', 'service_id', 'status')
                                ->orderBy('starts_at')
                                ->limit(5);
                        }]);
                }
            ])
            ->first();

        if ($call) {
            // 🔧 FIX 2025-10-24: If phone_number_id is NULL, use to_number to find correct PhoneNumber
            // For inbound calls, from_number might not be in our database (or is anonymous),
            // but to_number (the number that was called) MUST be in our database
            if (!$call->phoneNumber) {
                // First, try to_number lookup (most accurate for inbound calls)
                if ($call->to_number) {
                    $phoneNumber = \App\Models\PhoneNumber::where('number', $call->to_number)->first();

                    if ($phoneNumber) {
                        // Set the relationship manually for this request
                        $call->setRelation('phoneNumber', $phoneNumber);

                        // Also set/update company_id and branch_id from the phone number
                        $needsSave = false;
                        if (!$call->company_id || $call->company_id != $phoneNumber->company_id) {
                            $call->company_id = $phoneNumber->company_id;
                            $needsSave = true;
                        }
                        if (!$call->branch_id || $call->branch_id != $phoneNumber->branch_id) {
                            $call->branch_id = $phoneNumber->branch_id;
                            $needsSave = true;
                        }
                        if (!$call->phone_number_id) {
                            $call->phone_number_id = $phoneNumber->id;
                            $needsSave = true;
                        }

                        if ($needsSave) {
                            $call->save();
                        }

                        Log::info('✅ Phone number resolved from to_number', [
                            'call_id' => $call->id,
                            'retell_call_id' => $retellCallId,
                            'to_number' => $call->to_number,
                            'phone_number_id' => $phoneNumber->id,
                            'company_id' => $phoneNumber->company_id,
                            'branch_id' => $phoneNumber->branch_id,
                        ]);
                    } else {
                        Log::error('❌ to_number not found in PhoneNumber table', [
                            'call_id' => $call->id,
                            'retell_call_id' => $retellCallId,
                            'to_number' => $call->to_number,
                        ]);

                        // Don't return null yet, check if we have company_id/branch_id set
                        if (!$call->company_id || !$call->branch_id) {
                            return null;
                        }
                        // If we have company_id/branch_id, continue (might be set by webhook)
                    }
                } elseif ($call->company_id && $call->branch_id) {
                    // Fallback: Look up phone number by company/branch (legacy behavior)
                    Log::warning('⚠️ No to_number, attempting company/branch fallback', [
                        'call_id' => $call->id,
                        'retell_call_id' => $retellCallId,
                        'company_id' => $call->company_id,
                        'branch_id' => $call->branch_id,
                    ]);

                    $phoneNumber = \App\Models\PhoneNumber::where('company_id', $call->company_id)
                        ->where('branch_id', $call->branch_id)
                        ->first();

                    if ($phoneNumber) {
                        $call->setRelation('phoneNumber', $phoneNumber);
                        if (!$call->phone_number_id) {
                            $call->phone_number_id = $phoneNumber->id;
                            $call->save();
                        }

                        Log::info('✅ Phone number resolved from company/branch fallback', [
                            'call_id' => $call->id,
                            'phone_number_id' => $phoneNumber->id,
                        ]);
                    }
                } else {
                    // No to_number AND no company_id/branch_id
                    Log::error('❌ Cannot resolve call context: No to_number and no company/branch', [
                        'call_id' => $call->id,
                        'retell_call_id' => $retellCallId,
                        'from_number' => $call->from_number,
                        'to_number' => $call->to_number,
                        'company_id' => $call->company_id,
                        'branch_id' => $call->branch_id,
                    ]);
                    return null;
                }
            }

            // Cache for future lookups in this request
            $this->callCache[$retellCallId] = $call;

            Log::debug('Call context loaded and cached', [
                'call_id' => $call->id,
                'company_id' => $call->company_id,
                'phone_number_id' => $call->phone_number_id,
            ]);

            return $call;
        }

        Log::warning('Call context not found', ['retell_call_id' => $retellCallId]);
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function findRecentCallWithCompany(int $minutesBack = 30): ?Call
    {
        $recentCall = Call::whereNotNull('company_id')
            ->where('created_at', '>=', now()->subMinutes($minutesBack))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentCall) {
            Log::info('Recent call found for company context', [
                'call_id' => $recentCall->id,
                'company_id' => $recentCall->company_id,
                'created_at' => $recentCall->created_at,
            ]);
        } else {
            Log::debug('No recent call with company_id found', [
                'minutes_back' => $minutesBack,
            ]);
        }

        return $recentCall;
    }

    /**
     * {@inheritDoc}
     */
    public function clearCache(): void
    {
        $cacheCount = count($this->callCache);
        $this->callCache = [];

        Log::debug('Call cache cleared', ['cached_calls' => $cacheCount]);
    }
}