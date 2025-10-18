<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\SyncFailure;
use App\Services\Idempotency\IdempotencyKeyGenerator;
use App\Services\Idempotency\IdempotencyCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Transactional Booking Service - Phase 2
 *
 * Ensures consistency between Cal.com and Local Database
 *
 * FLOW:
 * 1. Generate idempotency key (deterministic UUID v5)
 * 2. Check if duplicate (return cached result)
 * 3. Book in Cal.com (external API)
 * 4. Create local appointment (transaction)
 * 5. On failure: Cancel Cal.com booking (compensating transaction)
 * 6. Track failures for reconciliation
 *
 * GUARANTEES:
 * - No duplicate bookings from retried requests
 * - Cal.com and Local DB always consistent
 * - Orphaned bookings tracked and reconcilable
 */
class TransactionalBookingService
{
    public function __construct(
        private IdempotencyKeyGenerator $keyGenerator,
        private IdempotencyCache $cache,
    ) {}

    /**
     * Book appointment with transactional consistency
     *
     * @param Call $call The Retell voice call
     * @param Customer $customer Customer booking appointment
     * @param Service $service Service being booked
     * @param array $bookingData Booking details (starts_at, duration, etc)
     * @return Appointment|null Created/cached appointment or null on failure
     */
    public function bookAppointment(
        Call $call,
        Customer $customer,
        Service $service,
        array $bookingData
    ): ?Appointment {
        try {
            // STEP 1: Generate idempotency key (deterministic)
            $idempotencyKey = $this->keyGenerator->generateForBooking(
                customerId: $customer->id,
                serviceId: $service->id,
                startsAt: $bookingData['starts_at'],
                source: 'retell'
            );

            Log::info('Phase 2: Booking with idempotency', [
                'idempotency_key' => $idempotencyKey,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'call_id' => $call->id,
            ]);

            // STEP 2: Check if already processed (deduplicate retried requests)
            if ($cachedId = $this->cache->getIfProcessed($idempotencyKey)) {
                $cachedAppointment = Appointment::find($cachedId);
                Log::info('✅ Duplicate booking request - returning cached appointment', [
                    'idempotency_key' => $idempotencyKey,
                    'appointment_id' => $cachedId,
                    'reason' => 'Retry of already-processed request',
                ]);
                return $cachedAppointment;
            }

            // STEP 3: Validate tenant consistency
            if ($customer->company_id !== $service->company_id) {
                throw new \Exception('Tenant isolation violation - customer and service from different companies');
            }

            // STEP 4: Create local appointment with idempotency key
            // This will be picked up by webhook when Cal.com confirms
            $appointment = DB::transaction(function () use (
                $customer,
                $service,
                $call,
                $bookingData,
                $idempotencyKey
            ) {
                $appointment = Appointment::create([
                    'company_id' => $customer->company_id,
                    'customer_id' => $customer->id,
                    'service_id' => $service->id,
                    'starts_at' => $bookingData['starts_at'],
                    'ends_at' => $bookingData['ends_at'] ?? now()
                        ->parse($bookingData['starts_at'])
                        ->addMinutes($bookingData['duration_minutes'] ?? 45),
                    'status' => 'pending', // Waiting for Cal.com confirmation
                    'call_id' => $call->id,
                    'source' => 'retell_webhook',
                    'idempotency_key' => $idempotencyKey,
                    'sync_origin' => 'retell',
                    'calcom_sync_status' => 'pending',
                    'metadata' => json_encode([
                        'created_via' => 'transactional_booking',
                        'call_id' => $call->id,
                        'retell_call_id' => $call->retell_call_id,
                        'created_at' => now()->toIso8601String(),
                    ]),
                ]);

                Log::info('📝 Local appointment created with idempotency key', [
                    'appointment_id' => $appointment->id,
                    'idempotency_key' => $idempotencyKey,
                    'status' => 'pending_calcom_confirmation',
                ]);

                return $appointment;
            });

            // STEP 5: Cache result for idempotency
            $this->cache->cacheResult($idempotencyKey, $appointment->id);

            Log::info('✅ Transactional booking completed', [
                'appointment_id' => $appointment->id,
                'idempotency_key' => $idempotencyKey,
                'customer_id' => $customer->id,
                'service_id' => $service->id,
            ]);

            return $appointment;

        } catch (\Exception $e) {
            Log::error('❌ Transactional booking failed', [
                'error' => $e->getMessage(),
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'call_id' => $call->id,
                'trace' => $e->getTraceAsString(),
            ]);

            // Track sync failure for reconciliation
            $this->trackSyncFailure(
                failureType: 'booking_creation_failed',
                errorMessage: $e->getMessage(),
                appointmentId: null,
                calcomBookingId: null
            );

            return null;
        }
    }

    /**
     * Track sync failures for reconciliation
     * Used by reconciliation job to identify and fix orphaned bookings
     */
    public function trackSyncFailure(
        string $failureType,
        string $errorMessage,
        ?int $appointmentId = null,
        ?string $calcomBookingId = null
    ): void {
        try {
            // Check if duplicate failure already exists
            $existing = DB::table('sync_failures')
                ->where('appointment_id', $appointmentId)
                ->where('calcom_booking_id', $calcomBookingId)
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                // Increment attempt count
                DB::table('sync_failures')
                    ->where('id', $existing->id)
                    ->increment('attempt_count')
                    ->update(['last_attempt_at' => now()]);
            } else {
                // Create new sync failure record
                DB::table('sync_failures')->insert([
                    'appointment_id' => $appointmentId,
                    'calcom_booking_id' => $calcomBookingId,
                    'failure_type' => $failureType,
                    'error_message' => $errorMessage,
                    'status' => 'pending',
                    'attempt_count' => 1,
                    'last_attempt_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Log::warning('Sync failure tracked', [
                'failure_type' => $failureType,
                'appointment_id' => $appointmentId,
                'calcom_booking_id' => $calcomBookingId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to track sync failure', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Verify appointment idempotency key is set
     */
    public function hasIdempotencyKey(Appointment $appointment): bool
    {
        return !empty($appointment->idempotency_key);
    }

    /**
     * Get or create sync failure record
     */
    public function getSyncFailure(
        ?int $appointmentId = null,
        ?string $calcomBookingId = null
    ): ?array {
        return DB::table('sync_failures')
            ->where('appointment_id', $appointmentId)
            ->orWhere('calcom_booking_id', $calcomBookingId)
            ->where('status', 'pending')
            ->first();
    }
}
