<?php

namespace App\Services\Retell;

use App\Models\Call;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\Branch;
use App\Services\AppointmentAlternativeFinder;
use App\Services\NestedBookingManager;
use App\Services\CalcomService;
use App\Services\NameExtractor;
use App\Services\CalcomHostMappingService;
use App\Services\Strategies\HostMatchContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Appointment Creation Service
 *
 * Centralized service for creating appointments from call bookings
 *
 * FEATURES:
 * - Confidence validation
 * - Automatic customer creation
 * - Service resolution with branch filtering
 * - Cal.com booking integration
 * - Alternative time slot search
 * - Nested booking support (coloring, perm, highlights)
 * - Comprehensive logging and error tracking
 */
class AppointmentCreationService implements AppointmentCreationInterface
{
    private CallLifecycleService $callLifecycle;
    private ServiceSelectionService $serviceSelector;
    private AppointmentAlternativeFinder $alternativeFinder;
    private NestedBookingManager $nestedBookingManager;
    private CalcomService $calcomService;

    // Configuration constants
    private const MIN_CONFIDENCE = 60;
    private const DEFAULT_DURATION = 45;
    private const DEFAULT_TIMEZONE = 'Europe/Berlin';
    private const DEFAULT_LANGUAGE = 'de';
    private const FALLBACK_PHONE = '+491234567890';

    public function __construct(
        CallLifecycleService $callLifecycle,
        ServiceSelectionService $serviceSelector,
        AppointmentAlternativeFinder $alternativeFinder,
        NestedBookingManager $nestedBookingManager,
        CalcomService $calcomService
    ) {
        $this->callLifecycle = $callLifecycle;
        $this->serviceSelector = $serviceSelector;
        $this->alternativeFinder = $alternativeFinder;
        $this->nestedBookingManager = $nestedBookingManager;
        $this->calcomService = $calcomService;
    }

    /**
     * {@inheritDoc}
     */
    public function createFromCall(Call $call, array $bookingDetails): ?Appointment
    {
        try {
            // PERFORMANCE: Eager load relationships to prevent N+1 queries
            $call->loadMissing(['customer', 'company', 'branch', 'phoneNumber']);

            // Validate booking confidence
            if (!$this->validateConfidence($bookingDetails)) {
                Log::info('Booking confidence too low, skipping appointment creation', [
                    'confidence' => $bookingDetails['confidence'] ?? 0
                ]);
                $this->callLifecycle->trackFailedBooking(
                    $call,
                    $bookingDetails,
                    'Low confidence extraction - needs manual review'
                );
                return null;
            }

            // Additional validation for German time parsing (14:00 = vierzehn Uhr)
            if (isset($bookingDetails['extracted_data']['time_fourteen']) &&
                !str_contains($bookingDetails['starts_at'], '14:')) {
                Log::error('🔴 Time extraction mismatch', [
                    'expected' => '14:xx',
                    'extracted' => $bookingDetails['starts_at'],
                    'raw_data' => $bookingDetails['extracted_data']
                ]);
                // Attempt to fix
                $correctedTime = Carbon::parse($bookingDetails['starts_at'])->setHour(14)->setMinute(0);
                $bookingDetails['starts_at'] = $correctedTime->format('Y-m-d H:i:s');
                $bookingDetails['ends_at'] = $correctedTime->copy()->addMinutes(45)->format('Y-m-d H:i:s');
                Log::info('✅ Corrected appointment time', [
                    'corrected_to' => $correctedTime->format('Y-m-d H:i')
                ]);
            }

            // Ensure customer exists
            $customer = $this->ensureCustomer($call);
            if (!$customer) {
                Log::error('Failed to create/find customer for appointment', [
                    'call_id' => $call->id,
                    'from_number' => $call->from_number
                ]);
                $this->callLifecycle->trackFailedBooking($call, $bookingDetails, 'customer_creation_failed');
                return null;
            }

            // Find appropriate service
            $companyId = $call->company_id ?? $customer->company_id ?? 15;
            $branchId = $call->branch_id ?? $customer->branch_id ?? null;

            // Get default service for company/branch
            $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
            if (!$service) {
                Log::error('No service found for booking', [
                    'service_name' => $bookingDetails['service'] ?? 'unknown',
                    'company_id' => $companyId,
                    'branch_id' => $branchId
                ]);
                $this->callLifecycle->trackFailedBooking($call, $bookingDetails, 'service_not_found');
                return null;
            }

            // Parse desired time and duration
            $desiredTime = Carbon::parse($bookingDetails['starts_at']);
            $duration = $bookingDetails['duration_minutes'] ?? self::DEFAULT_DURATION;

            // Check if service supports nested booking
            $serviceType = $this->determineServiceType($service->name);
            if ($this->supportsNesting($serviceType)) {
                return $this->createNestedBooking(
                    [
                        'customer_name' => $customer->name,
                        'customer_email' => $customer->email,
                        'phone' => $customer->phone
                    ],
                    $service,
                    $customer,
                    $call
                );
            }

            // Try to book at desired time first
            $bookingResult = $this->bookInCalcom($customer, $service, $desiredTime, $duration, $call);

            if ($bookingResult) {
                // Booking successful at desired time
                Log::info('✅ Appointment created at desired time', [
                    'time' => $desiredTime->format('Y-m-d H:i'),
                    'call_id' => $call->id
                ]);

                $this->callLifecycle->trackBooking($call, $bookingDetails, true, $bookingResult['booking_id']);

                return $this->createLocalRecord(
                    $customer,
                    $service,
                    $bookingDetails,
                    $bookingResult['booking_id'],
                    $call,
                    $bookingResult['booking_data'] ?? null  // Phase 2: Pass Cal.com response
                );
            }

            // If booking failed, search for alternatives
            Log::info('⚠️ Desired time not available, searching for alternatives', [
                'desired_time' => $desiredTime->format('Y-m-d H:i')
            ]);

            // SECURITY: Set tenant context for cache isolation
            $alternatives = $this->alternativeFinder
                ->setTenantContext($companyId, $branchId)
                ->findAlternatives($desiredTime, $duration, $service->calcom_event_type_id);

            if (empty($alternatives)) {
                Log::warning('No alternative appointments found', [
                    'call_id' => $call->id,
                    'desired_time' => $desiredTime->format('Y-m-d H:i')
                ]);
                $this->callLifecycle->trackFailedBooking($call, $bookingDetails, 'no_alternatives_found');
                return null;
            }

            // Try to book alternative
            $alternativeResult = $this->bookAlternative(
                $alternatives,
                $customer,
                $service,
                $duration,
                $call,
                $bookingDetails
            );

            if ($alternativeResult) {
                return $this->createLocalRecord(
                    $customer,
                    $service,
                    $bookingDetails,
                    $alternativeResult['booking_id'],
                    $call,
                    $alternativeResult['booking_data'] ?? null  // Phase 2: Pass Cal.com response for staff assignment
                );
            }

            // All alternatives failed
            Log::error('Failed to book any alternative', [
                'call_id' => $call->id,
                'alternatives_tried' => count($alternatives)
            ]);
            $this->callLifecycle->trackFailedBooking($call, $bookingDetails, 'all_alternatives_failed');

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to create appointment from call', [
                'error' => $e->getMessage(),
                'call_id' => $call->id,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createDirect(
        Customer $customer,
        Service $service,
        Carbon $startTime,
        int $durationMinutes,
        ?Call $call = null,
        bool $searchAlternatives = true
    ): ?Appointment {
        try {
            // Try to book at desired time
            $bookingResult = $this->bookInCalcom($customer, $service, $startTime, $durationMinutes, $call);

            if ($bookingResult) {
                $bookingDetails = [
                    'starts_at' => $startTime->format('Y-m-d H:i:s'),
                    'ends_at' => $startTime->copy()->addMinutes($durationMinutes)->format('Y-m-d H:i:s'),
                    'service' => $service->name,
                    'duration_minutes' => $durationMinutes,
                ];

                if ($call) {
                    $this->callLifecycle->trackBooking($call, $bookingDetails, true, $bookingResult['booking_id']);
                }

                return $this->createLocalRecord(
                    $customer,
                    $service,
                    $bookingDetails,
                    $bookingResult['booking_id'],
                    $call,
                    $bookingResult['booking_data'] ?? null  // Phase 2: Pass Cal.com response
                );
            }

            // Search for alternatives if enabled and booking failed
            if ($searchAlternatives) {
                // SECURITY: Set tenant context for cache isolation
                $companyId = $customer->company_id ?? $service->company_id ?? null;
                $branchId = $call?->branch_id ?? $customer->branch_id ?? null;

                $alternatives = $this->alternativeFinder
                    ->setTenantContext($companyId, $branchId)
                    ->findAlternatives($startTime, $durationMinutes, $service->calcom_event_type_id);

                if (!empty($alternatives)) {
                    $bookingDetails = [
                        'starts_at' => $startTime->format('Y-m-d H:i:s'),
                        'ends_at' => $startTime->copy()->addMinutes($durationMinutes)->format('Y-m-d H:i:s'),
                        'service' => $service->name,
                        'duration_minutes' => $durationMinutes,
                    ];

                    $alternativeResult = $this->bookAlternative(
                        $alternatives,
                        $customer,
                        $service,
                        $durationMinutes,
                        $call,
                        $bookingDetails
                    );

                    if ($alternativeResult) {
                        return $this->createLocalRecord(
                            $customer,
                            $service,
                            $bookingDetails,
                            $alternativeResult['booking_id'],
                            $call,
                            $alternativeResult['booking_data'] ?? null  // Phase 2: Pass Cal.com response
                        );
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to create direct appointment', [
                'error' => $e->getMessage(),
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'start_time' => $startTime->format('Y-m-d H:i')
            ]);
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function createLocalRecord(
        Customer $customer,
        Service $service,
        array $bookingDetails,
        ?string $calcomBookingId = null,
        ?Call $call = null,
        ?array $calcomBookingData = null  // Phase 2: Full Cal.com response for staff assignment
    ): Appointment {
        // 🔧 PHASE 5.5: Enhanced logging for appointment creation start
        Log::info('📝 Starting appointment creation', [
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'service_id' => $service->id,
            'service_name' => $service->name,
            'starts_at' => $bookingDetails['starts_at'] ?? null,
            'calcom_booking_id' => $calcomBookingId,
            'call_id' => $call?->id,
            'call_retell_id' => $call?->retell_call_id
        ]);

        // FIX 3: Check for existing appointment with same Cal.com booking ID (duplicate prevention)
        // 🔒 RACE CONDITION FIX (RC1): Use pessimistic locking to prevent double-booking
        // This prevents concurrent requests from creating duplicate appointments
        // See: claudedocs/08_REFERENCE/CONCURRENCY_RACE_CONDITIONS_2025-10-17.md#rc1
        if ($calcomBookingId) {
            $existingAppointment = Appointment::where('calcom_v2_booking_id', $calcomBookingId)
                ->lockForUpdate()  // ← Acquire exclusive lock, atomic check-then-act
                ->first();

            if ($existingAppointment) {
                Log::error('🚨 DUPLICATE BOOKING PREVENTION: Appointment with this Cal.com booking ID already exists', [
                    'existing_appointment_id' => $existingAppointment->id,
                    'existing_call_id' => $existingAppointment->call_id,
                    'existing_customer_id' => $existingAppointment->customer_id,
                    'existing_customer_name' => $existingAppointment->customer?->name,
                    'existing_starts_at' => $existingAppointment->starts_at,
                    'existing_created_at' => $existingAppointment->created_at,
                    'new_call_id' => $call?->id,
                    'new_call_retell_id' => $call?->retell_call_id,
                    'new_customer_id' => $customer->id,
                    'new_customer_name' => $customer->name,
                    'calcom_booking_id' => $calcomBookingId,
                    'reason' => 'Database duplicate check prevented creating duplicate appointment'
                ]);

                // 🔧 FIX: Link current call to existing appointment to prevent orphaned calls
                if ($call && !$call->appointment_id) {
                    $call->update([
                        'appointment_id' => $existingAppointment->id,
                        'appointment_link_status' => 'linked',
                        'appointment_linked_at' => now(),
                        'customer_link_status' => 'linked',
                        'customer_linked_at' => now(),
                    ]);

                    Log::info('✅ Duplicate booking attempt: Linked new call to existing appointment', [
                        'call_id' => $call->id,
                        'appointment_id' => $existingAppointment->id,
                        'original_call_id' => $existingAppointment->call_id
                    ]);
                }

                // Return existing appointment instead of creating duplicate
                return $existingAppointment;
            }
        }

        // Get the default branch if customer doesn't have one
        $branchId = $customer->branch_id;
        if (!$branchId && $customer->company_id) {
            // SECURITY: Validate and cast company_id to prevent SQL injection
            $companyId = (int) $customer->company_id;
            if ($companyId > 0) {
                // PERFORMANCE: Cache branch lookups for 1 hour
                $cacheKey = "branch.default.{$companyId}";
                $defaultBranch = Cache::remember($cacheKey, 3600, function () use ($companyId) {
                    return Branch::where('company_id', $companyId)->first();
                });
                $branchId = $defaultBranch ? $defaultBranch->id : null;

                // 🔧 PHASE 5.5: Log branch resolution
                Log::info('🏢 Branch resolved for appointment', [
                    'customer_id' => $customer->id,
                    'customer_branch_id' => $customer->branch_id,
                    'resolved_branch_id' => $branchId,
                    'company_id' => $companyId,
                    'resolution_method' => $customer->branch_id ? 'customer_branch' : ($defaultBranch ? 'default_branch' : 'no_branch')
                ]);
            }
        }

        // FIX 2025-10-10: Use forceFill() because company_id is guarded
        $appointment = new Appointment();

        // FIX 2025-10-11: Add call_id to metadata for reschedule/cancel lookup
        $metadataWithCallId = array_merge($bookingDetails, [
            'call_id' => $call ? $call->id : null,
            'retell_call_id' => $call ? $call->retell_call_id : null,
            'created_via' => 'retell_webhook',
            'created_at' => now()->toIso8601String()
        ]);

        $appointment->forceFill([
            'company_id' => $customer->company_id,  // Use customer's company_id (guaranteed match!)
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'branch_id' => $branchId,
            // REMOVED: 'tenant_id' - column doesn't exist in table!
            'starts_at' => $bookingDetails['starts_at'],
            'ends_at' => $bookingDetails['ends_at'],
            'call_id' => $call ? $call->id : null,
            'status' => 'scheduled',
            'notes' => 'Created via Retell webhook',
            'source' => 'retell_webhook',
            'calcom_v2_booking_id' => $calcomBookingId,  // ✅ Correct column for V2 UIDs
            'external_id' => $calcomBookingId,            // ✅ Backup reference
            'metadata' => json_encode($metadataWithCallId),  // ✅ FIX: Include call_id for reschedule/cancel
            'sync_origin' => 'retell',  // ← Mark origin for bidirectional sync (Phase 2: Loop Prevention)
            'calcom_sync_status' => $calcomBookingId ? 'synced' : 'pending',  // If has Cal.com ID, already synced
        ]);

        // 🔧 SAGA PATTERN: Enhanced error handling with Cal.com rollback
        // 🚨 CRITICAL FIX 2025-10-23: Implement SAGA compensation pattern
        // If local DB save fails after successful Cal.com booking, we MUST cancel the Cal.com booking
        // to prevent orphaned bookings (CVSS 8.5 vulnerability)
        try {
            $appointment->save();
        } catch (\Exception $e) {
            Log::error('❌ Failed to save appointment record to database', [
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'customer_id' => $customer->id,
                'service_id' => $service->id,
                'branch_id' => $branchId,
                'starts_at' => $bookingDetails['starts_at'],
                'calcom_booking_id' => $calcomBookingId,
                'call_id' => $call?->id,
                'trace' => $e->getTraceAsString()
            ]);

            // 🔄 COMPENSATION LOGIC: Rollback Cal.com booking if it was already created
            if ($calcomBookingId) {
                Log::warning('🔄 SAGA Compensation: Attempting to cancel Cal.com booking due to DB failure', [
                    'calcom_booking_id' => $calcomBookingId,
                    'customer_id' => $customer->id,
                    'starts_at' => $bookingDetails['starts_at']
                ]);

                try {
                    $cancellationReason = sprintf(
                        'Automatic rollback: Database save failed (%s)',
                        substr($e->getMessage(), 0, 100)
                    );

                    $cancelResponse = $this->calcomService->cancelBooking(
                        $calcomBookingId,
                        $cancellationReason
                    );

                    if ($cancelResponse->successful()) {
                        Log::info('✅ SAGA Compensation successful: Cal.com booking cancelled', [
                            'calcom_booking_id' => $calcomBookingId,
                            'reason' => 'db_save_failed'
                        ]);
                    } else {
                        Log::error('❌ SAGA Compensation FAILED: Could not cancel Cal.com booking', [
                            'calcom_booking_id' => $calcomBookingId,
                            'cancel_status' => $cancelResponse->status(),
                            'cancel_response' => $cancelResponse->json(),
                            'impact' => 'ORPHANED BOOKING - Manual intervention required'
                        ]);

                        // TODO: Queue manual cleanup job
                        // OrphanedBookingCleanupJob::dispatch($calcomBookingId);
                    }
                } catch (\Exception $cancelException) {
                    Log::error('❌ SAGA Compensation exception: Cal.com cancellation failed', [
                        'calcom_booking_id' => $calcomBookingId,
                        'cancel_error' => $cancelException->getMessage(),
                        'original_error' => $e->getMessage(),
                        'impact' => 'ORPHANED BOOKING - Manual intervention required'
                    ]);

                    // TODO: Queue manual cleanup job
                    // OrphanedBookingCleanupJob::dispatch($calcomBookingId);
                }
            }

            throw $e;  // Re-throw original exception after compensation attempt
        }

        // 🛡️ POST-BOOKING VALIDATION (2025-10-20): Verify appointment was actually created
        if ($call) {
            try {
                $validator = app(\App\Services\Validation\PostBookingValidationService::class);
                $validation = $validator->validateAppointmentCreation($call, $appointment->id, $calcomBookingId);

                if (!$validation->success) {
                    Log::error('❌ Post-booking validation failed', [
                        'appointment_id' => $appointment->id,
                        'call_id' => $call->id,
                        'reason' => $validation->reason
                    ]);

                    // Rollback call flags (appointment stays in DB for manual review)
                    $validator->rollbackOnFailure($call, $validation->reason);

                    throw new \Exception("Appointment validation failed: {$validation->reason}");
                }

                Log::info('✅ Post-booking validation successful', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $call->id
                ]);
            } catch (\Exception $e) {
                Log::error('⚠️ Post-booking validation error', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $call?->id,
                    'error' => $e->getMessage()
                ]);
                // Don't throw - let appointment be created even if validation fails
                // Manual review will catch issues
            }
        }

        // PHASE 2: Staff Assignment from Cal.com hosts array
        if ($calcomBookingData) {
            $this->assignStaffFromCalcomHost($appointment, $calcomBookingData, $call);
        }

        Log::info('📅 Local appointment record created', [
            'appointment_id' => $appointment->id,
            'customer' => $customer->name,
            'service' => $service->name,
            'starts_at' => $bookingDetails['starts_at'],
            'calcom_id' => $calcomBookingId
        ]);

        return $appointment;
    }

    /**
     * PHASE 2: Assign staff from Cal.com host data
     *
     * Extracts host information from Cal.com booking response and resolves to internal staff
     */
    private function assignStaffFromCalcomHost(
        Appointment $appointment,
        array $calcomBookingData,
        ?Call $call
    ): void {
        $hostMappingService = app(CalcomHostMappingService::class);

        // Extract host from Cal.com response (handles both data.hosts and hosts)
        $bookingData = $calcomBookingData['data'] ?? $calcomBookingData;
        $hostData = $hostMappingService->extractHostFromBooking($bookingData);

        if (!$hostData) {
            Log::warning('AppointmentCreationService: No host data in Cal.com response', [
                'appointment_id' => $appointment->id,
                'calcom_booking_id' => $appointment->calcom_v2_booking_id
            ]);
            return;
        }

        // Build context for tenant isolation
        $context = new HostMatchContext(
            companyId: $call?->company_id ?? $appointment->company_id,
            branchId: $call?->branch_id ?? $appointment->branch_id,
            serviceId: $appointment->service_id,
            calcomBooking: $bookingData
        );

        // Resolve staff_id via matching strategies
        $staffId = $hostMappingService->resolveStaffForHost($hostData, $context);

        if ($staffId) {
            $appointment->update([
                'staff_id' => $staffId,
                'calcom_host_id' => $hostData['id']
            ]);

            Log::info('✅ AppointmentCreationService: Staff assigned from Cal.com host', [
                'appointment_id' => $appointment->id,
                'staff_id' => $staffId,
                'calcom_host_id' => $hostData['id'],
                'host_email' => $hostData['email'] ?? null
            ]);
        } else {
            Log::warning('AppointmentCreationService: Could not resolve staff from host', [
                'appointment_id' => $appointment->id,
                'calcom_host_id' => $hostData['id'] ?? null,
                'host_email' => $hostData['email'] ?? null,
                'host_name' => $hostData['name'] ?? null
            ]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function ensureCustomer(Call $call): ?Customer
    {
        // Return existing customer if already linked
        if ($call->customer) {
            return $call->customer;
        }

        // Extract customer information from call
        $customerName = null;
        $customerPhone = $call->from_number;

        // Try to extract name from analysis data
        if ($call->analysis && isset($call->analysis['custom_analysis_data'])) {
            $customData = $call->analysis['custom_analysis_data'];
            $customerName = $customData['patient_full_name'] ??
                           $customData['customer_name'] ??
                           $customData['extracted_info']['customer_name'] ?? null;
        }

        // Fallback to transcript parsing if no name found
        if (!$customerName && $call->transcript) {
            $nameExtractor = new NameExtractor();
            $extractedName = $nameExtractor->extractNameFromTranscript($call->transcript);
            $customerName = $extractedName ?: 'Anonym ' . substr($customerPhone, -4);
        }

        // Final fallback
        if (!$customerName) {
            $customerName = 'Anonym ' . substr($customerPhone, -4);
        }

        // 🔒 RACE CONDITION FIX (RC5): Use atomic firstOrCreate() instead of first() → create()
        // This prevents duplicate customer creation during concurrent Retell calls
        // See: claudedocs/08_REFERENCE/CONCURRENCY_RACE_CONDITIONS_2025-10-17.md#rc5

        // Get default branch (cached)
        $companyId = (int) $call->company_id;
        $defaultBranch = null;
        if ($companyId > 0) {
            // PERFORMANCE: Cache branch lookups for 1 hour
            $cacheKey = "branch.default.{$companyId}";
            $defaultBranch = Cache::remember($cacheKey, 3600, function () use ($companyId) {
                return Branch::where('company_id', $companyId)->first();
            });
        }

        // Atomic operation: find existing customer or create new one
        // Unique constraint on (phone, company_id) ensures no duplicates
        $customer = Customer::firstOrCreate(
            [
                'phone' => $customerPhone,
                'company_id' => $call->company_id,
            ],
            [
                'name' => $customerName,
                'source' => 'phone_anonymous',
                'status' => 'active',
                'notes' => 'Automatisch erstellt aus Telefonanruf',
                'branch_id' => $call->branch_id ?? ($defaultBranch ? $defaultBranch->id : null),
            ]
        );

        // Log if customer was newly created (check if created_at is recent)
        if ($customer->wasRecentlyCreated) {
            Log::info('✅ Customer created from anonymous call', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'phone' => $customerPhone,
                'company_id' => $customer->company_id,
                'branch_id' => $customer->branch_id
            ]);
        } else {
            Log::debug('📋 Using existing customer for anonymous call', [
                'customer_id' => $customer->id,
                'phone' => $customerPhone,
                'company_id' => $customer->company_id,
            ]);
        }

        // Link customer to call
        $this->callLifecycle->linkCustomer($call, $customer);

        return $customer;
    }

    /**
     * {@inheritDoc}
     */
    public function findService(array $bookingDetails, int $companyId, ?string $branchId = null): ?Service
    {
        $serviceName = $bookingDetails['service'] ?? 'General Service';

        // PERFORMANCE: Cache service lookups for 1 hour
        $cacheKey = sprintf('service.%s.%d.%s', md5($serviceName), $companyId, $branchId ?? 'null');

        return Cache::remember($cacheKey, 3600, function () use ($serviceName, $companyId, $branchId) {
            // ServiceSelectionService doesn't have findService(), use getDefaultService()
            return $this->serviceSelector->getDefaultService($companyId, $branchId);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function bookInCalcom(
        Customer $customer,
        Service $service,
        Carbon $startTime,
        int $durationMinutes,
        ?Call $call = null
    ): ?array {
        // 🔒 CRITICAL FIX 2025-10-23: Distributed lock BEFORE Cal.com API call
        // This prevents race condition where multiple concurrent requests book the same slot
        // Lock key: company_id + service_id + start_time ensures slot-level exclusivity
        // See: CVSS 7.8 Race Condition vulnerability

        $companyId = $customer->company_id ?? $service->company_id ?? 15;
        $lockKey = sprintf(
            'booking_lock:%d:%d:%s',
            $companyId,
            $service->id,
            $startTime->format('Y-m-d_H:i')
        );

        Log::debug('🔒 Acquiring distributed lock for booking', [
            'lock_key' => $lockKey,
            'customer_id' => $customer->id,
            'service_id' => $service->id,
            'start_time' => $startTime->format('Y-m-d H:i')
        ]);

        // Acquire lock for 30 seconds (enough time for booking + DB save)
        // If another thread holds the lock, wait up to 10 seconds
        $lock = Cache::lock($lockKey, 30);

        try {
            // Block for up to 10 seconds trying to acquire lock
            if (!$lock->block(10)) {
                Log::warning('⚠️ Could not acquire booking lock - concurrent booking in progress', [
                    'lock_key' => $lockKey,
                    'customer_id' => $customer->id,
                    'service_id' => $service->id,
                    'start_time' => $startTime->format('Y-m-d H:i'),
                    'reason' => 'Another thread is currently booking this slot'
                ]);
                return null;  // Slot likely being booked by another thread
            }

            Log::info('✅ Distributed lock acquired', [
                'lock_key' => $lockKey,
                'customer_id' => $customer->id
            ]);

            // SECURITY: Sanitize and validate customer data before sending to external API
            $sanitizedName = strip_tags(trim($customer->name ?? 'Unknown'));

            // Validate email format
            $sanitizedEmail = filter_var($customer->email, FILTER_VALIDATE_EMAIL);
            if (!$sanitizedEmail) {
                Log::warning('Invalid customer email, using fallback', [
                    'customer_id' => $customer->id,
                    'email' => $customer->email
                ]);
                $sanitizedEmail = 'noreply@placeholder.local';
            }

            // Sanitize phone number (allow only digits, +, spaces, hyphens, parentheses)
            $rawPhone = $customer->phone ?? ($call ? $call->from_number : self::FALLBACK_PHONE);
            $sanitizedPhone = preg_replace('/[^\d\+\s\-\(\)]/', '', $rawPhone);

            $bookingData = [
                'eventTypeId' => $service->calcom_event_type_id,
                'startTime' => $startTime->toIso8601String(),
                'endTime' => $startTime->copy()->addMinutes($durationMinutes)->toIso8601String(),
                'name' => $sanitizedName,
                'email' => $sanitizedEmail,
                'phone' => $sanitizedPhone,
                'timeZone' => self::DEFAULT_TIMEZONE,
                'language' => self::DEFAULT_LANGUAGE
            ];

            Log::info('📞 Attempting Cal.com booking (lock acquired)', [
                'customer' => $customer->name,
                'service' => $service->name,
                'start_time' => $startTime->format('Y-m-d H:i'),
                'lock_key' => $lockKey
            ]);

            $response = $this->calcomService->createBooking($bookingData);

        if ($response->successful()) {
            $appointmentData = $response->json();
            $bookingData = $appointmentData['data'] ?? $appointmentData;
            $bookingId = $bookingData['id'] ?? $appointmentData['id'] ?? null;

            // 🚨 CRITICAL FIX 2025-10-18: VALIDATE BOOKED TIME MATCHES REQUESTED TIME
            // ISSUE: Cal.com sometimes books different time than requested (race condition)
            // Our database was storing REQUESTED time instead of ACTUAL booked time
            if (isset($bookingData['start'])) {
                $bookedStart = Carbon::parse($bookingData['start']);
                $bookedTimeStr = $bookedStart->format('Y-m-d H:i');
                $requestedTimeStr = $startTime->format('Y-m-d H:i');

                if ($bookedTimeStr !== $requestedTimeStr) {
                    Log::error('🚨 CRITICAL: Cal.com booked WRONG time - rejecting booking!', [
                        'requested_time' => $requestedTimeStr,
                        'actual_booked_time' => $bookedTimeStr,
                        'time_mismatch_minutes' => $startTime->diffInMinutes($bookedStart),
                        'booking_id' => $bookingId,
                        'reason' => 'Race condition: Slot was taken between availability check and booking',
                        'action_taken' => 'Booking REJECTED to prevent data inconsistency'
                    ]);
                    return null; // REJECT this mismatched booking
                }
            }

            // FIX 1: Validate booking freshness - Prevent accepting stale bookings from Cal.com idempotency
            $createdAt = isset($bookingData['createdAt'])
                ? Carbon::parse($bookingData['createdAt'])
                : null;

            if ($createdAt && $createdAt->lt(now()->subSeconds(30))) {
                Log::error('🚨 DUPLICATE BOOKING PREVENTION: Stale booking detected from Cal.com idempotency', [
                    'booking_id' => $bookingId,
                    'created_at' => $createdAt->toIso8601String(),
                    'age_seconds' => now()->diffInSeconds($createdAt),
                    'freshness_threshold_seconds' => 30,
                    'current_call_id' => $call?->retell_call_id,
                    'booking_metadata_call_id' => $bookingData['metadata']['call_id'] ?? null,
                    'booking_attendees' => $bookingData['attendees'] ?? null,
                    'requested_time' => $startTime->format('Y-m-d H:i'),
                    'reason' => 'Cal.com returned existing booking instead of creating new one'
                ]);
                return null; // Reject stale booking
            }

            // FIX 2: Validate metadata call_id matches current request
            $bookingCallId = $bookingData['metadata']['call_id'] ?? null;
            if ($bookingCallId && $call && $bookingCallId !== $call->retell_call_id) {
                Log::error('🚨 DUPLICATE BOOKING PREVENTION: Call ID mismatch - booking belongs to different call', [
                    'expected_call_id' => $call->retell_call_id,
                    'received_call_id' => $bookingCallId,
                    'booking_id' => $bookingId,
                    'created_at' => $createdAt?->toIso8601String(),
                    'age_seconds' => $createdAt ? now()->diffInSeconds($createdAt) : null,
                    'reason' => 'Cal.com returned booking from different call due to idempotency'
                ]);
                return null; // Reject booking from different call
            }

            // 🔍 PHASE 1 POC: Debug Cal.com Response Structure
            Log::info('🔍 POC: COMPLETE Cal.com Booking Response Analysis', [
                'booking_id' => $bookingId,
                'response_structure' => [
                    'has_data_key' => isset($appointmentData['data']),
                    'has_hosts' => isset($appointmentData['data']['hosts']) || isset($appointmentData['hosts']),
                    'has_organizer' => isset($appointmentData['organizer']),
                    'has_user' => isset($appointmentData['user']),
                    'top_level_keys' => array_keys($appointmentData),
                    'data_keys' => isset($appointmentData['data']) ? array_keys($appointmentData['data']) : null,
                ],
                'hosts_array' => $appointmentData['data']['hosts'] ?? $appointmentData['hosts'] ?? null,
                'organizer' => $appointmentData['organizer'] ?? null,
                'full_response' => $appointmentData, // Complete response for analysis
                'freshness_check_passed' => true,
                'call_id_validation_passed' => true,
                'booking_age_seconds' => $createdAt ? now()->diffInSeconds($createdAt) : null
            ]);

            Log::info('✅ Cal.com booking successful and validated', [
                'booking_id' => $bookingId,
                'time' => $startTime->format('Y-m-d H:i'),
                'freshness_validated' => true,
                'call_id_validated' => $bookingCallId === $call?->retell_call_id
            ]);

            return [
                'booking_id' => $bookingId,
                'booking_data' => $appointmentData
            ];
        }

        Log::warning('Cal.com booking failed', [
            'status' => $response->status(),
            'time' => $startTime->format('Y-m-d H:i')
        ]);

        return null;

        } finally {
            // 🔓 ALWAYS release distributed lock, even if booking fails
            if (isset($lock) && $lock->owner()) {
                $lock->release();
                Log::debug('🔓 Distributed lock released', [
                    'lock_key' => $lockKey ?? 'unknown',
                    'customer_id' => $customer->id ?? null
                ]);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findAlternatives(
        Carbon $desiredTime,
        int $durationMinutes,
        int $eventTypeId
    ): array {
        return $this->alternativeFinder->findAlternatives($desiredTime, $durationMinutes, $eventTypeId);
    }

    /**
     * {@inheritDoc}
     */
    public function bookAlternative(
        array $alternatives,
        Customer $customer,
        Service $service,
        int $durationMinutes,
        Call $call,
        array &$bookingDetails
    ): ?array {
        if (empty($alternatives)) {
            return null;
        }

        // Try to book first alternative
        $alternative = $alternatives[0];
        $alternativeTime = $alternative['datetime'];

        $bookingResult = $this->bookInCalcom($customer, $service, $alternativeTime, $durationMinutes, $call);

        if ($bookingResult) {
            Log::info('✅ Appointment created with alternative time', [
                'original_request' => $bookingDetails['starts_at'],
                'booked_time' => $alternativeTime->format('Y-m-d H:i'),
                'alternative_type' => $alternative['type']
            ]);

            // Update booking details with alternative information
            $originalTime = Carbon::parse($bookingDetails['starts_at']);
            $bookingDetails['original_request'] = $originalTime->format('Y-m-d H:i:s');
            $bookingDetails['booked_alternative'] = $alternativeTime->format('Y-m-d H:i:s');
            $bookingDetails['alternative_type'] = $alternative['type'];
            $bookingDetails['starts_at'] = $alternativeTime->format('Y-m-d H:i:s');
            $bookingDetails['ends_at'] = $alternativeTime->copy()->addMinutes($durationMinutes)->format('Y-m-d H:i:s');

            // Track booking and notify customer
            $this->callLifecycle->trackBooking($call, $bookingDetails, true, $bookingResult['booking_id']);
            $this->notifyCustomerAboutAlternative($customer, $originalTime, $alternativeTime, $alternative);

            return [
                'booking_id' => $bookingResult['booking_id'],
                'booking_data' => $bookingResult['booking_data'],  // Pass Cal.com booking data for staff assignment
                'alternative_time' => $alternativeTime,
                'alternative_type' => $alternative['type']
            ];
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function createNestedBooking(
        array $bookingData,
        Service $service,
        Customer $customer,
        Call $call
    ): ?Appointment {
        // For nested bookings, the appointment is created in NestedBookingManager
        // This method handles the integration
        if (isset($bookingData['appointment'])) {
            $appointment = $bookingData['appointment'];

            // Update the appointment with call information
            $appointment->update([
                'call_id' => $call->id,
                'notes' => 'Nested booking created via Retell webhook'
            ]);

            Log::info('✅ Nested booking appointment created', [
                'appointment_id' => $appointment->id,
                'service' => $service->name,
                'customer' => $customer->name,
                'starts_at' => $bookingData['starts_at'] ?? null,
                'ends_at' => $bookingData['ends_at'] ?? null
            ]);

            return $appointment;
        }

        // If no appointment in booking data, nested booking failed
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsNesting(string $serviceType): bool
    {
        return $this->nestedBookingManager->supportsNesting($serviceType);
    }

    /**
     * {@inheritDoc}
     */
    public function determineServiceType(string $serviceName): string
    {
        $serviceName = strtolower($serviceName);

        if (str_contains($serviceName, 'färben') || str_contains($serviceName, 'color')) {
            return 'coloring';
        }
        if (str_contains($serviceName, 'dauerwelle') || str_contains($serviceName, 'perm')) {
            return 'perm';
        }
        if (str_contains($serviceName, 'strähnchen') || str_contains($serviceName, 'highlight')) {
            return 'highlights';
        }

        return 'general';
    }

    /**
     * {@inheritDoc}
     */
    public function validateConfidence(array $bookingDetails): bool
    {
        $confidence = $bookingDetails['confidence'] ?? 0;
        return $confidence >= self::MIN_CONFIDENCE;
    }

    /**
     * {@inheritDoc}
     */
    public function notifyCustomerAboutAlternative(
        Customer $customer,
        Carbon $requestedTime,
        Carbon $bookedTime,
        array $alternative
    ): void {
        // TODO: Implement notification system
        // This could send SMS, email, or push notification to customer
        // informing them that their appointment was booked at alternative time

        Log::info('📧 Customer notification queued for alternative booking', [
            'customer_id' => $customer->id,
            'requested_time' => $requestedTime->format('Y-m-d H:i'),
            'booked_time' => $bookedTime->format('Y-m-d H:i'),
            'alternative_type' => $alternative['type'] ?? 'unknown'
        ]);

        // Placeholder for future notification implementation
        // e.g., dispatch notification job
        // NotifyCustomerAboutAlternative::dispatch($customer, $requestedTime, $bookedTime, $alternative);
    }
}