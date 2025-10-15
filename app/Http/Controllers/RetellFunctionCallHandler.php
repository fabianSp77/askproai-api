<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\AppointmentAlternativeFinder;
use App\Services\CalcomService;
use App\Services\Retell\ServiceSelectionService;
use App\Services\Retell\WebhookResponseService;
use App\Services\Retell\CallLifecycleService;
use App\Services\Retell\AppointmentCreationService;
use App\Services\Retell\CustomerDataValidator;
use App\Services\Retell\AppointmentCustomerResolver;
use App\Services\Retell\DateTimeParser;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Carbon\Carbon;
use App\Helpers\LogSanitizer;
use App\Http\Requests\CollectAppointmentRequest;

/**
 * Handles real-time function calls from Retell AI during active calls
 * Enables the AI to check availability and offer alternatives in real-time
 */
class RetellFunctionCallHandler extends Controller
{
    private AppointmentAlternativeFinder $alternativeFinder;
    private CalcomService $calcomService;
    private ServiceSelectionService $serviceSelector;
    private WebhookResponseService $responseFormatter;
    private CallLifecycleService $callLifecycle;
    private CustomerDataValidator $dataValidator;
    private AppointmentCustomerResolver $customerResolver;
    private DateTimeParser $dateTimeParser;
    private array $callContextCache = []; // DEPRECATED: Use CallLifecycleService caching instead

    public function __construct(
        ServiceSelectionService $serviceSelector,
        WebhookResponseService $responseFormatter,
        CallLifecycleService $callLifecycle,
        CustomerDataValidator $dataValidator,
        AppointmentCustomerResolver $customerResolver,
        DateTimeParser $dateTimeParser
    ) {
        $this->serviceSelector = $serviceSelector;
        $this->responseFormatter = $responseFormatter;
        $this->callLifecycle = $callLifecycle;
        $this->dataValidator = $dataValidator;
        $this->customerResolver = $customerResolver;
        $this->dateTimeParser = $dateTimeParser;
        $this->alternativeFinder = new AppointmentAlternativeFinder();
        $this->calcomService = new CalcomService();
    }

    /**
     * Get call context (company_id, branch_id) from call ID
     *
     * Loads the Call record with related PhoneNumber to determine
     * which company and branch this call belongs to for proper
     * multi-tenant isolation.
     *
     * Uses CallLifecycleService for request-scoped caching.
     *
     * @param string|null $callId Retell call ID
     * @return array|null ['company_id' => int, 'branch_id' => int|null, 'phone_number_id' => int]
     */
    private function getCallContext(?string $callId): ?array
    {
        if (!$callId) {
            Log::warning('Cannot get call context: callId is null');
            return null;
        }

        $call = $this->callLifecycle->getCallContext($callId);

        if (!$call) {
            return null;
        }

        return [
            'company_id' => $call->phoneNumber->company_id,
            'branch_id' => $call->phoneNumber->branch_id,
            'phone_number_id' => $call->phoneNumber->id,
            'call_id' => $call->id,
        ];
    }

    /**
     * Main handler for Retell function calls during active conversations
     */
    public function handleFunctionCall(Request $request)
    {
        $data = $request->all();

        // ENHANCED MONITORING FOR TEST CALL
        Log::info('📞 ===== RETELL WEBHOOK RECEIVED =====', [
            'timestamp' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'method' => $request->method(),
            'path' => $request->path(),
            'headers' => LogSanitizer::sanitizeHeaders($request->headers->all()),
            'raw_body' => LogSanitizer::sanitize($request->getContent()),
            'parsed_data' => LogSanitizer::sanitize($data),
            'function_name' => $data['name'] ?? $data['function_name'] ?? 'NONE',  // Bug #4 Fix: Retell sends 'name' not 'function_name'
            'parameters' => LogSanitizer::sanitize($data['args'] ?? $data['parameters'] ?? []),
            'call_id' => $data['call_id'] ?? null,
            'agent_id' => $data['agent_id'] ?? null,
            'session_id' => $data['session_id'] ?? null
        ]);

        Log::info('🔧 Function call received from Retell', [
            'function' => $data['name'] ?? $data['function_name'] ?? 'unknown',  // Bug #4 Fix
            'parameters' => $data['args'] ?? $data['parameters'] ?? [],  // Bug #4 Fix
            'call_id' => $data['call_id'] ?? null
        ]);

        // Bug #4 Fix (Call 777): Retell sends 'name' and 'args', not 'function_name' and 'parameters'
        $functionName = $data['name'] ?? $data['function_name'] ?? '';
        $parameters = $data['args'] ?? $data['parameters'] ?? [];
        // Bug #6 Fix (Call 778): call_id is inside parameters/args, not at top level - CHECK PARAMETERS FIRST!
        $callId = $parameters['call_id'] ?? $data['call_id'] ?? null;

        // Route to appropriate function handler
        return match($functionName) {
            'check_availability' => $this->checkAvailability($parameters, $callId),
            'book_appointment' => $this->bookAppointment($parameters, $callId),
            'query_appointment' => $this->queryAppointment($parameters, $callId),
            'get_alternatives' => $this->getAlternatives($parameters, $callId),
            'list_services' => $this->listServices($parameters, $callId),
            'cancel_appointment' => $this->handleCancellationAttempt($parameters, $callId),
            'reschedule_appointment' => $this->handleRescheduleAttempt($parameters, $callId),
            'request_callback' => $this->handleCallbackRequest($parameters, $callId),
            'find_next_available' => $this->handleFindNextAvailable($parameters, $callId),
            default => $this->handleUnknownFunction($functionName, $parameters, $callId)
        };
    }

    /**
     * Check availability for a specific date/time
     * Called when customer asks: "Haben Sie am Freitag um 16 Uhr noch was frei?"
     */
    private function checkAvailability(array $params, ?string $callId)
    {
        try {
            // FEATURE: Branch-aware service selection for availability checks
            // Get call context to ensure branch isolation
            $callContext = $this->getCallContext($callId);

            if (!$callContext) {
                Log::error('Cannot check availability: Call context not found', [
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            // Parse parameters
            $requestedDate = $this->dateTimeParser->parseDateTime($params);
            $duration = $params['duration'] ?? 60;
            $serviceId = $params['service_id'] ?? null;

            // Get service with branch validation using ServiceSelectionService
            if ($serviceId) {
                $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
            } else {
                $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
            }

            if (!$service || !$service->calcom_event_type_id) {
                Log::error('No active service with Cal.com event type found for branch', [
                    'service_id' => $serviceId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Service nicht verfügbar für diese Filiale');
            }

            Log::info('Using service for availability check', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $service->calcom_event_type_id,
                'call_id' => $callId
            ]);

            // Check exact availability
            $startTime = $requestedDate->copy()->startOfHour();
            $endTime = $requestedDate->copy()->endOfHour();

            $response = $this->calcomService->getAvailableSlots(
                $service->calcom_event_type_id,
                $startTime->format('Y-m-d H:i:s'),
                $endTime->format('Y-m-d H:i:s')
            );

            $slots = $response->json()['data']['slots'] ?? [];
            $isAvailable = $this->isTimeAvailable($requestedDate, $slots);

            // 🔧 FIX 2025-10-11: Check if customer already has appointment at this time
            // Bug: Customer asked for Wednesday 9:00 but system didn't detect existing appointment
            if ($isAvailable) {
                // Get customer from call context to check for existing appointments
                $call = $this->callLifecycle->findCallByRetellId($callId);

                if ($call && $call->customer_id) {
                    // Check if customer already has an appointment at or around this time
                    $existingAppointment = Appointment::where('customer_id', $call->customer_id)
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->where(function($query) use ($requestedDate, $duration) {
                            // Check for overlapping appointments (within requested time window)
                            $query->whereBetween('starts_at', [
                                $requestedDate->copy()->subMinutes($duration),
                                $requestedDate->copy()->addMinutes($duration)
                            ])
                            ->orWhere(function($q) use ($requestedDate, $duration) {
                                // Or check if requested time falls within existing appointment
                                $q->where('starts_at', '<=', $requestedDate)
                                  ->where('ends_at', '>', $requestedDate);
                            });
                        })
                        ->first();

                    if ($existingAppointment) {
                        // Customer already has an appointment at this time!
                        $appointmentTime = $existingAppointment->starts_at;
                        $germanDate = $appointmentTime->locale('de')->isoFormat('dddd, [den] D. MMMM');

                        Log::info('🚨 Customer already has appointment at requested time', [
                            'call_id' => $callId,
                            'customer_id' => $call->customer_id,
                            'requested_time' => $requestedDate->format('Y-m-d H:i'),
                            'existing_appointment_id' => $existingAppointment->id,
                            'existing_appointment_time' => $appointmentTime->format('Y-m-d H:i')
                        ]);

                        return $this->responseFormatter->success([
                            'available' => false,
                            'has_existing_appointment' => true,
                            'existing_appointment_id' => $existingAppointment->id,
                            'message' => "Sie haben bereits einen Termin am {$germanDate} um {$appointmentTime->format('H:i')} Uhr. Möchten Sie diesen Termin umbuchen oder einen weiteren Termin vereinbaren?",
                            'requested_time' => $requestedDate->format('Y-m-d H:i'),
                            'existing_appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                            'alternatives' => []
                        ]);
                    }
                }

                // No existing appointment found - slot is truly available
                return $this->responseFormatter->success([
                    'available' => true,
                    'message' => "Ja, {$requestedDate->format('H:i')} Uhr ist noch frei.",
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'alternatives' => []
                ]);
            }

            // LATENZ-OPTIMIERUNG: Alternative-Suche nur wenn Feature enabled
            // Voice-AI braucht <1s Response → Alternative-Suche (3s+) ist zu langsam!
            if (config('features.skip_alternatives_for_voice', true)) {
                return $this->responseFormatter->success([
                    'available' => false,
                    'message' => "Dieser Termin ist leider nicht verfügbar. Welche Zeit würde Ihnen alternativ passen?",
                    'requested_time' => $requestedDate->format('Y-m-d H:i'),
                    'alternatives' => [],
                    'suggest_user_alternative' => true
                ]);
            }

            // If not available, automatically find alternatives (SLOW - 3s+!)
            // SECURITY: Set tenant context for cache isolation
            // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
            $call = $call ?? $this->callLifecycle->findCallByRetellId($callId);
            $customerId = $call?->customer_id;

            $alternatives = $this->alternativeFinder
                ->setTenantContext($companyId, $branchId)
                ->findAlternatives(
                    $requestedDate,
                    $duration,
                    $service->calcom_event_type_id,
                    $customerId  // Pass customer ID to prevent offering conflicting times
                );

            return $this->responseFormatter->success([
                'available' => false,
                'message' => $alternatives['responseText'] ?? "Dieser Termin ist leider nicht verfügbar.",
                'requested_time' => $requestedDate->format('Y-m-d H:i'),
                'alternatives' => $this->formatAlternativesForRetell($alternatives['alternatives'] ?? [])
            ]);

        } catch (\Exception $e) {
            Log::error('Error checking availability', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);
            return $this->responseFormatter->error('Fehler beim Prüfen der Verfügbarkeit');
        }
    }

    /**
     * Get alternative appointments when requested time is not available
     * Called when customer says: "Wann haben Sie denn Zeit?" or "Was wäre denn möglich?"
     */
    private function getAlternatives(array $params, ?string $callId)
    {
        try {
            $requestedDate = $this->dateTimeParser->parseDateTime($params);
            $duration = $params['duration'] ?? 60;
            $serviceId = $params['service_id'] ?? null;
            $maxAlternatives = $params['max_alternatives'] ?? 3;

            // FEATURE: Branch-aware service selection for alternatives
            $callContext = $this->getCallContext($callId);

            if (!$callContext) {
                Log::error('Cannot get alternatives: Call context not found', [
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            // Get service with branch validation using ServiceSelectionService
            if ($serviceId) {
                $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
            } else {
                $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
            }

            if (!$service || !$service->calcom_event_type_id) {
                Log::error('No active service with Cal.com event type found for alternatives', [
                    'service_id' => $serviceId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Service nicht verfügbar für diese Filiale');
            }

            Log::info('Using service for alternatives', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $service->calcom_event_type_id,
                'call_id' => $callId
            ]);

            // Find alternatives using our sophisticated finder
            // SECURITY: Set tenant context for cache isolation
            // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
            $call = $this->callLifecycle->findCallByRetellId($callId);
            $customerId = $call?->customer_id;

            $alternatives = $this->alternativeFinder
                ->setTenantContext($companyId, $branchId)
                ->findAlternatives(
                    $requestedDate,
                    $duration,
                    $service->calcom_event_type_id,
                    $customerId  // Pass customer ID to prevent offering conflicting times
                );

            // Format response for natural conversation
            $responseData = [
                'found' => !empty($alternatives['alternatives']),
                'message' => $alternatives['responseText'] ?? "Ich suche nach verfügbaren Terminen...",
                'alternatives' => $this->formatAlternativesForRetell($alternatives['alternatives'] ?? []),
                'original_request' => $requestedDate->format('Y-m-d H:i')
            ];

            return $this->successResponse($responseData);

        } catch (\Exception $e) {
            Log::error('Error getting alternatives', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);
            return $this->responseFormatter->error('Fehler beim Suchen von Alternativen');
        }
    }

    /**
     * Book an appointment after customer confirms
     * Called when customer says: "Ja, 15 Uhr passt mir" or "Den nehme ich"
     */
    private function bookAppointment(array $params, ?string $callId)
    {
        try {
            // FEATURE: Branch-aware booking with strict validation
            // Get call context to ensure branch isolation
            $callContext = $this->getCallContext($callId);

            if (!$callContext) {
                Log::error('Cannot book appointment: Call context not found', [
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            $appointmentTime = $this->dateTimeParser->parseDateTime($params);
            $duration = $params['duration'] ?? 60;
            $customerName = $params['customer_name'] ?? '';
            $customerEmail = $params['customer_email'] ?? '';
            $customerPhone = $params['customer_phone'] ?? '';
            $serviceId = $params['service_id'] ?? null;
            $notes = $params['notes'] ?? '';

            // Get service with branch validation - SECURITY: No cross-branch bookings allowed
            if ($serviceId) {
                $service = $this->serviceSelector->findServiceById($serviceId, $companyId, $branchId);
            } else {
                $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
            }

            if (!$service || !$service->calcom_event_type_id) {
                Log::error('No active service with Cal.com event type found for booking', [
                    'service_id' => $serviceId,
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Service nicht verfügbar für diese Filiale');
            }

            Log::info('Using service for booking', [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'event_type_id' => $service->calcom_event_type_id,
                'call_id' => $callId
            ]);

            // Create booking via Cal.com
            $booking = $this->calcomService->createBooking([
                'eventTypeId' => $service->calcom_event_type_id,
                'start' => $appointmentTime->toIso8601String(),
                'name' => $customerName,
                'email' => $customerEmail ?: 'booking@temp.de',
                'phone' => $customerPhone,
                'notes' => $notes,
                'metadata' => [
                    'call_id' => $callId,
                    'booked_via' => 'retell_ai'
                ]
            ]);

            if ($booking->successful()) {
                $bookingData = $booking->json();
                $calcomBookingId = $bookingData['data']['id'] ?? $bookingData['id'] ?? null;

                // 🔥 PHASE 1 FIX: Create local appointment immediately after Cal.com success
                try {
                    // Get call record for customer resolution
                    $call = $this->callLifecycle->findCallByRetellId($callId);

                    if ($call) {
                        // Ensure customer exists or create from call context
                        $customer = $this->customerResolver->ensureCustomerFromCall($call, $customerName, $customerEmail);

                        // Create local appointment with full context
                        // FIX 2025-10-10: Use forceFill() because company_id/branch_id are guarded
                        $appointment = new Appointment();
                        $appointment->forceFill([
                            'calcom_v2_booking_id' => $calcomBookingId,
                            'external_id' => $calcomBookingId,
                            'customer_id' => $customer->id,
                            'company_id' => $customer->company_id,  // Use customer's company_id (guaranteed match!)
                            'branch_id' => $branchId,
                            'service_id' => $service->id,
                            'call_id' => $call->id,
                            'starts_at' => $appointmentTime,
                            'ends_at' => $appointmentTime->copy()->addMinutes($duration),
                            'status' => 'confirmed',
                            'source' => 'retell_phone',
                            'booking_type' => 'single',
                            'notes' => $notes,
                            'metadata' => json_encode([
                                'call_id' => $call->id,  // ✅ FIX 2025-10-11: For reschedule/cancel lookup
                                'retell_call_id' => $callId,  // ✅ FIX 2025-10-11: For Same-Call policy
                                'calcom_booking' => $bookingData,
                                'customer_name' => $customerName,
                                'customer_email' => $customerEmail,
                                'customer_phone' => $customerPhone,
                                'synced_at' => now()->toIso8601String(),
                                'sync_method' => 'immediate',
                                'created_at' => now()->toIso8601String()  // ✅ For Same-Call time validation
                            ]),
                            // ✅ METADATA FIX 2025-10-10: Populate tracking fields
                            'created_by' => 'customer',
                            'booking_source' => 'retell_phone',
                            'booked_by_user_id' => null  // Customer bookings have no user
                        ]);
                        $appointment->save();

                        Log::info('✅ Appointment created immediately after Cal.com booking', [
                            'appointment_id' => $appointment->id,
                            'calcom_booking_id' => $calcomBookingId,
                            'call_id' => $call->id,
                            'customer_id' => $customer->id,
                            'sync_method' => 'immediate'
                        ]);

                        return $this->responseFormatter->success([
                            'booked' => true,
                            'appointment_id' => $appointment->id,
                            'message' => "Perfekt! Ihr Termin am {$appointmentTime->format('d.m.')} um {$appointmentTime->format('H:i')} Uhr ist gebucht.",
                            'booking_id' => $calcomBookingId,
                            'appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                            'confirmation' => "Sie erhalten eine Bestätigung per SMS."
                        ]);
                    } else {
                        Log::warning('⚠️ Call not found for immediate appointment sync', [
                            'call_id' => $callId,
                            'calcom_booking_id' => $calcomBookingId
                        ]);

                        // Return partial success - Cal.com booking succeeded but no call context
                        return $this->responseFormatter->success([
                            'booked' => true,
                            'message' => "Ihr Termin am {$appointmentTime->format('d.m.')} um {$appointmentTime->format('H:i')} Uhr ist gebucht.",
                            'booking_id' => $calcomBookingId,
                            'appointment_time' => $appointmentTime->format('Y-m-d H:i'),
                            'confirmation' => "Sie erhalten eine Bestätigung per E-Mail."
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ CRITICAL: Failed to create local appointment after Cal.com success', [
                        'calcom_booking_id' => $calcomBookingId,
                        'call_id' => $callId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    // 🚨 FIX: Return error instead of success to prevent silent failures
                    // Cal.com booking succeeded but local record creation failed
                    // This requires manual intervention or webhook sync
                    return $this->responseFormatter->error(
                        'Die Terminbuchung wurde im Kalender erstellt, aber es gab ein Problem beim Speichern. ' .
                        'Bitte kontaktieren Sie uns direkt zur Bestätigung. Booking-ID: ' . $calcomBookingId
                    );
                }
            }

            return $this->responseFormatter->error('Buchung konnte nicht durchgeführt werden');

        } catch (\Exception $e) {
            Log::error('Error booking appointment', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);
            return $this->responseFormatter->error('Fehler bei der Terminbuchung');
        }
    }

    /**
     * List available services
     * Called when customer asks: "Was bieten Sie an?" or "Welche Services haben Sie?"
     */
    private function listServices(array $params, ?string $callId)
    {
        try {
            // FEATURE: Branch-specific service selection (VULN-003 fix)
            // Get call context to determine company and branch
            $callContext = $this->getCallContext($callId);

            if (!$callContext) {
                Log::error('Cannot list services: Call context not found', [
                    'call_id' => $callId
                ]);
                return $this->responseFormatter->error('Call context not available');
            }

            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];

            // Get available services using ServiceSelectionService
            $services = $this->serviceSelector->getAvailableServices($companyId, $branchId);

            Log::info('Services filtered by branch context', [
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'service_count' => $services->count(),
                'call_id' => $callId
            ]);

            if ($services->isEmpty()) {
                return $this->responseFormatter->error('Keine verfügbaren Services für diese Filiale');
            }

            // FEATURE: ASK-009 Auto-select when only one service available
            if (config('features.auto_service_select', false) && $services->count() === 1) {
                $service = $services->first();

                Log::info('Auto-selecting single available service', [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'call_id' => $callId
                ]);

                $message = "Ich buche Ihnen einen Termin für {$service->name}.";

                return $this->responseFormatter->success([
                    'auto_selected' => true,
                    'service' => [
                        'id' => $service->id,
                        'name' => $service->name,
                        'duration' => $service->duration,
                        'price' => $service->price,
                        'description' => $service->description
                    ],
                    'message' => $message,
                    'count' => 1
                ]);
            }

            // Standard behavior: List multiple services for manual selection
            $serviceList = $services->map(function($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'duration' => $service->duration,
                    'price' => $service->price,
                    'description' => $service->description
                ];
            });

            $message = "Wir bieten folgende Services an: ";
            $message .= $services->pluck('name')->join(', ');

            return $this->responseFormatter->success([
                'auto_selected' => false,
                'services' => $serviceList,
                'message' => $message,
                'count' => $services->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing services', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);
            return $this->responseFormatter->error('Fehler beim Abrufen der Services');
        }
    }

    /**
     * Parse date/time from various input formats
     *
     * @deprecated Use DateTimeParser service instead
     * @see App\Services\Retell\DateTimeParser::parseDateTime()
     */
    private function parseDateTime(array $params): Carbon
    {
        return $this->dateTimeParser->parseDateTime($params);
    }

    /**
     * Parse date string to MySQL DATE format (YYYY-MM-DD)
     * Handles: "heute", "morgen", "01.10.2025", "2025-10-01"
     *
     * @deprecated Use DateTimeParser service instead
     * @see App\Services\Retell\DateTimeParser::parseDateString()
     */
    private function parseDateString(?string $dateString): ?string
    {
        return $this->dateTimeParser->parseDateString($dateString);
    }

    /**
     * Check if a specific time is available in the slots
     */
    private function isTimeAvailable(Carbon $requestedTime, array $slots): bool
    {
        foreach ($slots as $date => $daySlots) {
            foreach ($daySlots as $slot) {
                $slotTime = Carbon::parse($slot['time']);
                if ($slotTime->format('Y-m-d H:i') === $requestedTime->format('Y-m-d H:i')) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Format alternatives for Retell AI to speak naturally
     */
    private function formatAlternativesForRetell(array $alternatives): array
    {
        return array_map(function($alt) {
            return [
                'time' => $alt['datetime']->format('Y-m-d H:i'),
                'spoken' => $alt['description'],
                'available' => $alt['available'] ?? true,
                'type' => $alt['type'] ?? 'alternative'
            ];
        }, $alternatives);
    }

    /**
     * Handle unknown function calls
     */
    private function handleUnknownFunction(string $functionName, array $params, ?string $callId)
    {
        Log::warning('Unknown function called', [
            'function' => $functionName,
            'params' => $params,
            'call_id' => $callId
        ]);

        return $this->responseFormatter->error("Function '$functionName' is not supported");
    }

    // NOTE: Old helper methods removed - now using WebhookResponseService
    // for consistent response formatting across all Retell controllers

    /**
     * Handle the collect_appointment_data function call from Retell
     * This is the specific route that Retell AI calls
     *
     * Uses CollectAppointmentRequest for automatic validation and sanitization
     */
    public function collectAppointment(CollectAppointmentRequest $request)
    {
        $data = $request->all();

        // ENHANCED MONITORING FOR TEST CALL
        Log::info('📅 ===== COLLECT APPOINTMENT WEBHOOK =====', [
            'timestamp' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'method' => $request->method(),
            'path' => $request->path(),
            'raw_body' => LogSanitizer::sanitize($request->getContent()),
            'all_headers' => LogSanitizer::sanitizeHeaders($request->headers->all())
        ]);

        Log::info('📞 Collect appointment function called', [
            'data_structure' => array_keys($data),
            'has_args' => isset($data['args']),
            'args' => $data['args'] ?? null,
            'call_id_from_args' => isset($data['args']['call_id']) ? $data['args']['call_id'] : null
        ]);

        try {
            // Use validated and sanitized data from FormRequest
            // This provides XSS protection, email validation, and length limits
            $validatedData = $request->getAppointmentData();
            $args = $data['args'] ?? $data;

            // Extract validated data
            $datum = $validatedData['datum'];
            $uhrzeit = $validatedData['uhrzeit'];
            $name = $validatedData['name'];
            $dienstleistung = $validatedData['dienstleistung'];
            $email = $validatedData['email'];

            // 🔧 BUG FIX (Call 776): Auto-fill customer name if "Unbekannt" or empty
            // If agent didn't provide name but customer exists, use database name
            $callId = $args['call_id'] ?? null;
            if (($name === 'Unbekannt' || empty($name)) && $callId) {
                $call = $this->callLifecycle->findCallByRetellId($callId);
                if ($call && $call->customer_id) {
                    $customer = \App\Models\Customer::find($call->customer_id);
                    if ($customer && !empty($customer->name)) {
                        $originalName = $name;
                        $name = $customer->name;
                        Log::info('✅ Auto-filled customer name from database', [
                            'original_name' => $originalName,
                            'auto_filled_name' => $name,
                            'customer_id' => $customer->id,
                            'call_id' => $call->id
                        ]);
                    }
                }
            }

            // Fallback: Replace Retell placeholders if not resolved
            if ($datum === '{{current_date}}' || $datum === 'current_date') {
                $originalDatum = $datum;
                $datum = Carbon::today()->format('d.m.Y');
                Log::warning('🔧 Retell placeholder not replaced, using fallback', [
                    'original' => $originalDatum,
                    'replaced' => $datum,
                    'call_id' => $callId ?? 'unknown'
                ]);
            }

            // Extract additional fields not in FormRequest validation
            $callId = $args['call_id'] ?? null;
            $confirmBooking = $args['bestaetigung'] ?? $args['confirm_booking'] ?? null;

            Log::info('📅 Collect appointment data extracted', [
                'datum' => $datum,
                'uhrzeit' => $uhrzeit,
                'name' => $name,
                'dienstleistung' => $dienstleistung,
                'call_id' => $callId,
                'bestaetigung' => $confirmBooking
            ]);

            // Create or update call record to ensure it exists
            // OPTIMIZATION: Get call record ONCE and reuse throughout function
            $call = null;
            if ($callId) {
                // First check if we have a temporary call that needs upgrading
                $phoneNumber = $args['phone'] ?? $args['customer_phone'] ?? 'unknown';

                // Try to find existing call by call_id first
                $call = $this->callLifecycle->findCallByRetellId($callId);

                // If not found, look for most recent temporary call within last 10 minutes
                if (!$call) {
                    $tempCall = $this->callLifecycle->findRecentTemporaryCall();

                    if ($tempCall) {
                        // Upgrade temp call with real call_id and preserve company/phone data
                        $call = $this->callLifecycle->upgradeTemporaryCall($tempCall, $callId, [
                            'name' => $name ?: $tempCall->name,
                            'dienstleistung' => $dienstleistung ?: $tempCall->dienstleistung,
                            'datum_termin' => $this->parseDateString($datum) ?: $tempCall->datum_termin,
                            'uhrzeit_termin' => $uhrzeit ?: $tempCall->uhrzeit_termin,
                            'appointment_requested' => true,
                            'extracted_name' => $name,
                            'extracted_date' => $datum,
                            'extracted_time' => $uhrzeit,
                            'status' => 'in_progress',
                        ]);

                        Log::info('✅ Temporary call merged with real call_id', [
                            'old_id' => 'temp_*',
                            'new_call_id' => $callId,
                            'db_id' => $call->id,
                            'company_id' => $call->company_id,
                            'phone_number_id' => $call->phone_number_id
                        ]);
                    }
                }

                if ($call) {
                    // Update existing call with latest data
                    $call->update([
                        'name' => $name ?: $call->name,
                        'customer_name' => $name ?: $call->customer_name,  // 🔧 FIX: Set customer_name for reschedule
                        'dienstleistung' => $dienstleistung ?: $call->dienstleistung,
                        'datum_termin' => $this->parseDateString($datum) ?: $call->datum_termin,
                        'uhrzeit_termin' => $uhrzeit ?: $call->uhrzeit_termin,
                        'appointment_requested' => true,
                        'extracted_name' => $name,
                        'extracted_date' => $datum,
                        'extracted_time' => $uhrzeit,
                        'updated_at' => now()
                    ]);

                    Log::info('📞 Call record updated', [
                        'call_id' => $call->id,
                        'retell_call_id' => $callId,
                        'has_company' => $call->company_id ? 'yes' : 'no',
                        'has_phone_number' => $call->phone_number_id ? 'yes' : 'no'
                    ]);
                } else {
                    // Create new call if no existing or temp call found
                    // But try to get company_id from phone number or other context
                    $companyId = null;
                    $phoneNumberId = null;

                    // CRITICAL FIX: Get to_number from the call data to lookup phone number
                    $toNumber = null;
                    if (isset($data['call']['to_number'])) {
                        $toNumber = $data['call']['to_number'];
                    }

                    // Lookup phone number if we have to_number
                    if ($toNumber) {
                        // Clean the phone number - same logic as call_inbound
                        $cleanedNumber = preg_replace('/[^0-9+]/', '', $toNumber);

                        // Try exact match first
                        $phoneRecord = \App\Models\PhoneNumber::where('number', $cleanedNumber)->first();

                        // If no exact match, try partial match (last 10 digits)
                        if (!$phoneRecord) {
                            $phoneRecord = \App\Models\PhoneNumber::where('number', 'LIKE', '%' . substr($cleanedNumber, -10))
                                ->first();
                        }

                        if ($phoneRecord) {
                            $phoneNumberId = $phoneRecord->id;
                            $companyId = $phoneRecord->company_id;

                            Log::info('✅ Phone number found in collectAppointment', [
                                'to_number' => $toNumber,
                                'phone_number_id' => $phoneNumberId,
                                'company_id' => $companyId
                            ]);
                        } else {
                            Log::warning('⚠️ Phone number not found in collectAppointment', [
                                'to_number' => $toNumber,
                                'cleaned_number' => $cleanedNumber
                            ]);
                        }
                    }

                    // Fallback: Try to find phone number record from recent calls
                    if (!$companyId) {
                        $recentCall = $this->callLifecycle->findRecentCallWithCompany(30);

                        if ($recentCall) {
                            $companyId = $recentCall->company_id;
                            $phoneNumberId = $recentCall->phone_number_id;
                        }
                    }

                    $call = $this->callLifecycle->createCall([
                        'call_id' => $callId,
                        'from_number' => $phoneNumber,
                        'to_number' => $toNumber,  // WICHTIG: to_number speichern!
                        'status' => 'in_progress',
                    ], $companyId, $phoneNumberId);

                    // Update with appointment details
                    $call->update([
                        'name' => $name,
                        'dienstleistung' => $dienstleistung,
                        'datum_termin' => $this->parseDateString($datum),
                        'uhrzeit_termin' => $uhrzeit,
                        'appointment_requested' => true,
                        'extracted_name' => $name,
                        'extracted_date' => $datum,
                        'extracted_time' => $uhrzeit,
                    ]);

                    Log::info('📞 New call record created (no temp found)', [
                        'call_id' => $call->id,
                        'retell_call_id' => $callId,
                        'company_id' => $companyId,
                        'phone_number_id' => $phoneNumberId
                    ]);
                }

                Log::info('📞 Call record final state', [
                    'call_id' => $call->id,
                    'retell_call_id' => $callId,
                    'company_id' => $call->company_id,
                    'phone_number_id' => $call->phone_number_id
                ]);
            }

            // 🔧 FIX V84 (Call 872): Name Validation - Reject placeholder names
            // Prevent bookings with "Unbekannt", "Anonym", or empty names
            $placeholderNames = ['Unbekannt', 'Anonym', 'Anonymous', 'Unknown'];
            $isPlaceholder = empty($name) || in_array(trim($name), $placeholderNames);

            if ($isPlaceholder) {
                Log::warning('⚠️ PROMPT-VIOLATION: Attempting to book without real customer name', [
                    'call_id' => $callId,
                    'name' => $name,
                    'violation_type' => 'missing_customer_name',
                    'datum' => $datum,
                    'uhrzeit' => $uhrzeit
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'missing_customer_name',
                    'message' => 'Bitte erfragen Sie zuerst den Namen des Kunden. Sagen Sie: "Darf ich Ihren Namen haben?"',
                    'prompt_violation' => true,
                    'bestaetigung_status' => 'error'
                ], 200);
            }

            // 🔧 FIX (Call 863): Required Fields Validation
            // Prevent agent from calling collect_appointment without date/time
            if (empty($datum) || empty($uhrzeit)) {
                Log::warning('⚠️ PROMPT-VIOLATION: Agent called collect_appointment without date/time', [
                    'call_id' => $callId,
                    'datum' => $datum,
                    'uhrzeit' => $uhrzeit,
                    'name' => $name
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'missing_required_fields',
                    'message' => 'Bitte fragen Sie nach Datum und Uhrzeit bevor Sie einen Termin prüfen. Sagen Sie: "Für welchen Tag und welche Uhrzeit möchten Sie den Termin?"',
                    'missing_fields' => [
                        'datum' => empty($datum),
                        'uhrzeit' => empty($uhrzeit)
                    ],
                    'bestaetigung_status' => 'error'
                ], 200);
            }

            // Parse the date and time using existing helper methods
            $appointmentDate = null;
            if ($datum && $uhrzeit) {
                // Use parseDateString() which handles relative dates (heute, morgen) AND German format
                $parsedDateStr = $this->parseDateString($datum);

                if ($parsedDateStr) {
                    $appointmentDate = Carbon::parse($parsedDateStr);

                    // Parse time (14:00 or just 14)
                    if (strpos($uhrzeit, ':') !== false) {
                        list($hour, $minute) = explode(':', $uhrzeit);
                    } else {
                        $hour = intval($uhrzeit);
                        $minute = 0;
                    }
                    $appointmentDate->setTime($hour, $minute);

                    Log::info('✅ Date parsed successfully using parseDateString()', [
                        'input_datum' => $datum,
                        'input_uhrzeit' => $uhrzeit,
                        'parsed_date' => $parsedDateStr,
                        'final_datetime' => $appointmentDate->format('Y-m-d H:i')
                    ]);

                    // 🔧 FIX (Call 863): Past-Time-Validation
                    // Reject appointments in the past
                    $now = Carbon::now('Europe/Berlin');
                    if ($appointmentDate->isPast()) {
                        $diffHours = abs($appointmentDate->diffInHours($now, false));

                        Log::critical('🚨 PAST-TIME-BOOKING-ATTEMPT', [
                            'call_id' => $callId,
                            'requested' => $appointmentDate->format('Y-m-d H:i'),
                            'current_time' => $now->format('Y-m-d H:i'),
                            'diff_hours' => $diffHours,
                            'datum_input' => $datum,
                            'uhrzeit_input' => $uhrzeit
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'past_time',
                            'message' => 'Dieser Termin liegt in der Vergangenheit. Bitte wählen Sie einen zukünftigen Zeitpunkt. Sagen Sie: "Meinen Sie heute um ' . $appointmentDate->format('H:i') . ' Uhr oder morgen?"',
                            'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                            'current_time' => $now->format('Y-m-d H:i'),
                            'bestaetigung_status' => 'error'
                        ], 200);
                    }
                }
            }

            if (!$appointmentDate) {
                Log::error('❌ Date parsing failed', [
                    'datum' => $datum,
                    'uhrzeit' => $uhrzeit
                ]);
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Entschuldigung, ich konnte das Datum nicht verstehen. Bitte nennen Sie es im Format "heute", "morgen" oder Tag.Monat.Jahr, zum Beispiel 01.10.2025',
                    'bestaetigung_status' => 'error'
                ], 200);
            }

            // Get company ID from call or phone number - now properly linked!
            // OPTIMIZATION: Reuse $call from earlier instead of fetching again
            $companyId = null;
            if ($callId && $call) {
                // First try to get company_id directly from call (should now be set)
                if ($call && $call->company_id) {
                    $companyId = $call->company_id;
                    Log::info('🎯 Got company from call record', [
                        'call_id' => $call->id,
                        'company_id' => $companyId
                    ]);
                }
                // Fallback to phone_number lookup if needed
                elseif ($call && $call->phone_number_id) {
                    $phoneNumber = \App\Models\PhoneNumber::find($call->phone_number_id);
                    $companyId = $phoneNumber ? $phoneNumber->company_id : null;
                    Log::info('🔍 Got company from phone number', [
                        'phone_number_id' => $call->phone_number_id,
                        'company_id' => $companyId
                    ]);
                }
            }

            // Dynamic service selection using ServiceSelectionService
            $service = null;

            if ($companyId) {
                $service = $this->serviceSelector->getDefaultService($companyId);

                Log::info('📋 Dynamic service selection for company', [
                    'company_id' => $companyId,
                    'service_id' => $service ? $service->id : null,
                    'service_name' => $service ? $service->name : null,
                    'event_type_id' => $service ? $service->calcom_event_type_id : null,
                    'is_default' => $service ? $service->is_default : false,
                    'priority' => $service ? $service->priority : null
                ]);
            }

            // If no service found for company, use fallback logic
            if (!$service) {
                // Default to company 15 (AskProAI) if no company detected
                $fallbackCompanyId = $companyId ?: 15;
                $service = $this->serviceSelector->getDefaultService($fallbackCompanyId);

                Log::warning('⚠️ Using fallback service selection', [
                    'original_company_id' => $companyId,
                    'fallback_company_id' => $fallbackCompanyId,
                    'service_id' => $service ? $service->id : null,
                    'service_name' => $service ? $service->name : null
                ]);
            }

            if (!$service) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service-Konfiguration fehlt'
                ], 200);
            }

            // 🔍 PRE-BOOKING DUPLICATE CHECK
            // OPTIMIZATION: Only check for duplicates when actually booking (not in "check only" mode)
            // This saves ~50-100ms per request when just checking availability
            $shouldCheckDuplicates = ($confirmBooking !== false); // Don't check if explicitly checking only

            if ($shouldCheckDuplicates && $call && $call->from_number) {
                $customer = \App\Models\Customer::where('phone', $call->from_number)
                    ->where('company_id', $companyId)
                    ->first();

                if ($customer) {
                    // Check for existing appointments at exact date/time
                    $existingAppointment = \App\Models\Appointment::where('customer_id', $customer->id)
                        ->whereDate('starts_at', $appointmentDate->format('Y-m-d'))
                        ->whereTime('starts_at', $appointmentDate->format('H:i:s'))
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->with(['service', 'staff'])
                        ->first();

                    if ($existingAppointment) {
                        Log::warning('⚠️ DUPLICATE BOOKING DETECTED - Customer already has appointment at this time', [
                            'call_id' => $call->id,
                            'customer_id' => $customer->id,
                            'customer_name' => $customer->name,
                            'existing_appointment_id' => $existingAppointment->id,
                            'requested_date' => $appointmentDate->format('Y-m-d'),
                            'requested_time' => $appointmentDate->format('H:i'),
                            'existing_starts_at' => $existingAppointment->starts_at->format('Y-m-d H:i')
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'duplicate_detected',
                            'message' => sprintf(
                                'Sie haben bereits einen Termin am %s um %s Uhr%s. Möchten Sie diesen Termin behalten, verschieben, oder einen zusätzlichen Termin buchen?',
                                $existingAppointment->starts_at->format('d.m.Y'),
                                $existingAppointment->starts_at->format('H:i'),
                                $existingAppointment->service ? ' für ' . $existingAppointment->service->name : ''
                            ),
                            'existing_appointment' => [
                                'id' => $existingAppointment->id,
                                'date' => $existingAppointment->starts_at->format('d.m.Y'),
                                'time' => $existingAppointment->starts_at->format('H:i'),
                                'datetime' => $existingAppointment->starts_at->toIso8601String(),
                                'service' => $existingAppointment->service?->name,
                                'staff' => $existingAppointment->staff?->name,
                                'status' => $existingAppointment->status
                            ],
                            'options' => [
                                'keep_existing' => 'Bestehenden Termin behalten',
                                'book_additional' => 'Zusätzlichen Termin buchen',
                                'reschedule' => 'Termin verschieben'
                            ],
                            'bestaetigung_status' => 'duplicate_confirmation_needed'
                        ], 200);
                    }

                    // Also check for appointments on same day (different time) - for context
                    $sameDayAppointments = \App\Models\Appointment::where('customer_id', $customer->id)
                        ->whereDate('starts_at', $appointmentDate->format('Y-m-d'))
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->count();

                    if ($sameDayAppointments > 0) {
                        Log::info('ℹ️ Customer has other appointments on same day', [
                            'customer_id' => $customer->id,
                            'requested_date' => $appointmentDate->format('Y-m-d'),
                            'requested_time' => $appointmentDate->format('H:i'),
                            'same_day_count' => $sameDayAppointments
                        ]);
                        // Continue with booking - just informational log
                    }
                }
            }

            // REAL Cal.com availability check
            try {
                // Use the actual date without year mapping
                $checkDate = $appointmentDate->copy();
                // Note: Removed year mapping - Cal.com should handle 2025 dates correctly

                // Get branchId from call if available
                $branchId = $call ? $call->branch_id : null;

                // STEP 1: First, check if the EXACT requested time is available in Cal.com
                // This is critical - we need to check the exact slot before searching for alternatives
                $calcomService = app(\App\Services\CalcomService::class);
                $exactTimeAvailable = false;

                try {
                    $slotsResponse = $calcomService->getAvailableSlots(
                        $service->calcom_event_type_id,
                        $appointmentDate->format('Y-m-d'),
                        $appointmentDate->format('Y-m-d')
                    );

                    if ($slotsResponse->successful()) {
                        $slotsData = $slotsResponse->json();
                        $daySlots = $slotsData['data']['slots'][$appointmentDate->format('Y-m-d')] ?? [];

                        // Check if requested time exists in available slots
                        $requestedTimeStr = $appointmentDate->format('H:i');
                        foreach ($daySlots as $slot) {
                            $slotTime = Carbon::parse($slot['time']);
                            if ($slotTime->format('H:i') === $requestedTimeStr) {
                                $exactTimeAvailable = true;
                                Log::info('✅ Exact requested time IS available in Cal.com', [
                                    'requested' => $requestedTimeStr,
                                    'slot_found' => $slot['time']
                                ]);
                                break;
                            }
                        }

                        if (!$exactTimeAvailable) {
                            Log::info('❌ Exact requested time NOT available in Cal.com', [
                                'requested' => $requestedTimeStr,
                                'total_slots' => count($daySlots),
                                'available_times' => array_map(fn($s) => Carbon::parse($s['time'])->format('H:i'), $daySlots)
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to check exact time availability', [
                        'error' => $e->getMessage(),
                        'requested_time' => $appointmentDate->format('Y-m-d H:i')
                    ]);
                }

                // STEP 2: If exact time is NOT available, search for alternatives
                // OPTIMIZATION: Cache alternatives to avoid duplicate calls
                $alternatives = [];
                $alternativesChecked = false;
                if (!$exactTimeAvailable) {
                    Log::info('🔍 Exact time not available, searching for alternatives...');

                    // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
                    // Use customer if already loaded, otherwise try to get from call
                    $customerId = ($customer ?? null)?->id ?? $call?->customer_id;

                    // SECURITY: Set tenant context for cache isolation
                    $alternatives = $this->alternativeFinder
                        ->setTenantContext($companyId, $branchId)
                        ->findAlternatives(
                            $checkDate,
                            60, // duration in minutes
                            $service->calcom_event_type_id,
                            $customerId  // Pass customer ID to prevent offering conflicting times
                        );
                    $alternativesChecked = true;
                }

                // Track nearest alternative for potential later use
                $nearestAlternative = null;
                if (!empty($alternatives['alternatives'])) {
                    $requestedMinutes = $appointmentDate->hour * 60 + $appointmentDate->minute;
                    $minDifference = PHP_INT_MAX;

                    foreach ($alternatives['alternatives'] as $alt) {
                        $altDateTime = isset($alt['datetime']) && $alt['datetime'] instanceof Carbon
                            ? $alt['datetime']
                            : (isset($alt['datetime']) ? Carbon::parse($alt['datetime']) : null);

                        if ($altDateTime) {
                            $altMinutes = $altDateTime->hour * 60 + $altDateTime->minute;
                            $difference = abs($altMinutes - $requestedMinutes);
                            if ($difference < $minDifference) {
                                $minDifference = $difference;
                                $nearestAlternative = [
                                    'time' => $altDateTime,
                                    'description' => $alt['description'] ?? $altDateTime->format('H:i'),
                                    'difference_minutes' => $difference
                                ];
                            }
                        }
                    }
                }

                // Store booking details in database
                // OPTIMIZATION: Reuse $call from earlier
                if ($callId && $call) {
                    $call->booking_details = json_encode([
                        'date' => $datum,
                        'time' => $uhrzeit,
                        'customer_name' => $name,
                        'service' => $dienstleistung,
                        'exact_time_available' => $exactTimeAvailable,
                        'alternatives_found' => count($alternatives['alternatives'] ?? []),
                        'checked_at' => now()->toIso8601String()
                    ]);
                    $call->save();
                }

                // 🔧 V84 FIX: 2-STEP ENFORCEMENT - Default to CHECK-ONLY instead of AUTO-BOOK
                // This prevents direct booking without user confirmation
                // - confirmBooking = null/not set → CHECK-ONLY (default behavior - V84 change)
                // - confirmBooking = true → BOOK (explicit confirmation required)
                // - confirmBooking = false → CHECK-ONLY (explicit check only)
                $shouldBook = $exactTimeAvailable && ($confirmBooking === true);

                // Track prompt violations for monitoring
                if ($confirmBooking === null && $exactTimeAvailable) {
                    Log::warning('⚠️ PROMPT-VIOLATION: Missing bestaetigung parameter - defaulting to CHECK-ONLY', [
                        'call_id' => $callId,
                        'defaulting_to' => 'check_only',
                        'expected' => 'bestaetigung: false for STEP 1, bestaetigung: true for STEP 2',
                        'date' => $appointmentDate->format('Y-m-d'),
                        'time' => $appointmentDate->format('H:i')
                    ]);
                }

                if ($shouldBook) {
                    // Book the exact requested time (V84: ONLY with explicit confirmation)
                    Log::info('📅 Booking exact requested time (V84: 2-step confirmation)', [
                        'requested' => $appointmentDate->format('H:i'),
                        'exact_match' => true,
                        'confirmation_received' => $confirmBooking === true,
                        'workflow' => '2-step (bestaetigung: false → user confirms → bestaetigung: true)'
                    ]);

                    Log::info('🎯 Booking attempt', [
                        'exactTimeAvailable' => $exactTimeAvailable,
                        'confirmBooking' => $confirmBooking,
                        'appointmentDate' => $appointmentDate->format('Y-m-d H:i'),
                        'checkDate' => $checkDate->format('Y-m-d H:i'),
                        'usingAlternative' => (!$exactTimeAvailable && $nearestAlternative) ? true : false
                    ]);

                    // If time is available OR this is a confirmation to book an alternative
                    // Create booking in Cal.com
                    $calcomService = app(\App\Services\CalcomService::class);

                    try {
                        // 🔧 FIX V85 (Calls 874/875): DOUBLE-CHECK availability immediately before booking
                        // Problem: 14-second gap between initial check and booking allows slot to be taken
                        // Solution: Re-check availability right before createBooking() to prevent race condition
                        Log::info('🔍 V85: Double-checking availability before booking...', [
                            'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                            'reason' => 'Prevent race condition from initial check to booking'
                        ]);

                        $stillAvailable = false;
                        try {
                            $recheckResponse = $calcomService->getAvailableSlots(
                                $service->calcom_event_type_id,
                                $appointmentDate->format('Y-m-d'),
                                $appointmentDate->format('Y-m-d')
                            );

                            if ($recheckResponse->successful()) {
                                $recheckData = $recheckResponse->json();
                                $recheckSlots = $recheckData['data']['slots'][$appointmentDate->format('Y-m-d')] ?? [];
                                $requestedTimeStr = $appointmentDate->format('H:i');

                                foreach ($recheckSlots as $slot) {
                                    $slotTime = Carbon::parse($slot['time']);
                                    if ($slotTime->format('H:i') === $requestedTimeStr) {
                                        $stillAvailable = true;
                                        Log::info('✅ V85: Slot STILL available - proceeding with booking', [
                                            'requested' => $requestedTimeStr,
                                            'verified_at' => now()->toIso8601String()
                                        ]);
                                        break;
                                    }
                                }

                                if (!$stillAvailable) {
                                    Log::warning('⚠️ V85: Slot NO LONGER available - offering alternatives', [
                                        'requested' => $requestedTimeStr,
                                        'reason' => 'Taken between initial check and booking attempt',
                                        'time_gap' => 'Race condition detected'
                                    ]);

                                    // Slot was taken in the meantime - find alternatives immediately
                                    if (!$alternativesChecked) {
                                        $customerId = $customer?->id ?? $call?->customer_id;
                                        $alternatives = $this->alternativeFinder
                                            ->setTenantContext($companyId, $branchId)
                                            ->findAlternatives(
                                                $appointmentDate,
                                                60,
                                                $service->calcom_event_type_id,
                                                $customerId
                                            );
                                        $alternativesChecked = true;
                                    }

                                    // Return alternatives instead of attempting booking
                                    $alternativesList = array_slice($alternatives['alternatives'] ?? [], 0, 2);
                                    if (!empty($alternativesList)) {
                                        return response()->json([
                                            'success' => false,
                                            'status' => 'slot_taken',
                                            'message' => "Der Termin um {$appointmentDate->format('H:i')} Uhr wurde gerade vergeben. Ich habe Alternativen gefunden:",
                                            'alternatives' => $alternativesList,
                                            'reason' => 'race_condition_detected'
                                        ], 200);
                                    } else {
                                        return response()->json([
                                            'success' => false,
                                            'status' => 'no_availability',
                                            'message' => "Der Termin um {$appointmentDate->format('H:i')} Uhr wurde gerade vergeben und leider sind keine Alternativen verfügbar.",
                                            'reason' => 'race_condition_detected'
                                        ], 200);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('V85: Double-check failed - proceeding with booking attempt', [
                                'error' => $e->getMessage(),
                                'fallback' => 'Will attempt booking and handle error if slot taken'
                            ]);
                            // Continue with booking attempt even if double-check fails
                        }

                        // Prepare booking data
                        // OPTIMIZATION: Reuse $call from earlier for email lookup
                        $currentCall = $call;

                        $args = $request->input('args', []);
                        $bookingData = [
                                'eventTypeId' => $service->calcom_event_type_id,
                                'start' => $appointmentDate->format('Y-m-d\TH:i:s'),
                                'responses' => [
                                    'name' => $name,
                                    'email' => $this->dataValidator->getValidEmail($args, $currentCall),
                                    'attendeePhoneNumber' => $this->dataValidator->getValidPhone($args, $currentCall),
                                    'notes' => "Service: {$dienstleistung}. Gebucht über KI-Telefonassistent."
                                ],
                                'metadata' => [
                                    'call_id' => $callId,
                                    'service' => $dienstleistung
                                ],
                                'language' => 'de',
                                'timeZone' => 'Europe/Berlin'
                            ];

                            $response = $calcomService->createBooking($bookingData);

                            if ($response->successful()) {
                                $booking = $response->json()['data'] ?? [];

                                // 🔧 PHASE 5.4 FIX: Create Appointment FIRST, then set booking_confirmed
                                // This ensures atomic transaction - booking_confirmed only after successful appointment creation
                                if ($callId && $call) {
                                    try {
                                        // Ensure customer exists
                                        $customer = $this->customerResolver->ensureCustomerFromCall($call, $name, $email);

                                        // Create appointment using AppointmentCreationService
                                        $appointmentService = app(AppointmentCreationService::class);

                                        $appointment = $appointmentService->createLocalRecord(
                                            customer: $customer,
                                            service: $service,
                                            bookingDetails: [
                                                'starts_at' => $appointmentDate->format('Y-m-d H:i:s'),
                                                'ends_at' => $appointmentDate->copy()->addMinutes($service->duration ?? 60)->format('Y-m-d H:i:s'),
                                                'service' => $dienstleistung,
                                                'customer_name' => $name,
                                                'date' => $datum,
                                                'time' => $uhrzeit,
                                                'duration_minutes' => $service->duration ?? 60
                                            ],
                                            calcomBookingId: $booking['uid'] ?? null,
                                            call: $call,
                                            calcomBookingData: $booking  // Pass Cal.com booking data for staff assignment
                                        );

                                        // ✅ ATOMIC TRANSACTION: Only set booking_confirmed=true AFTER appointment created successfully
                                        $call->booking_confirmed = true;
                                        $call->booking_id = $booking['uid'] ?? null;
                                        $call->booking_details = json_encode([
                                            'confirmed_at' => now()->toIso8601String(),
                                            'calcom_booking' => $booking
                                        ]);
                                        $call->appointment_id = $appointment->id;
                                        $call->appointment_made = true;
                                        $call->save();

                                        Log::info('✅ Appointment record created from Cal.com booking', [
                                            'appointment_id' => $appointment->id,
                                            'call_id' => $call->id,
                                            'booking_id' => $booking['uid'] ?? null,
                                            'customer_id' => $customer->id,
                                            'customer' => $customer->name,
                                            'service' => $service->name,
                                            'starts_at' => $appointmentDate->format('Y-m-d H:i')
                                        ]);

                                    } catch (\Exception $e) {
                                        // ❌ Appointment creation failed - DO NOT set booking_confirmed
                                        // Store booking details for manual recovery but keep booking_confirmed=false
                                        $call->booking_id = $booking['uid'] ?? null;
                                        $call->booking_details = json_encode([
                                            'confirmed_at' => now()->toIso8601String(),
                                            'calcom_booking' => $booking,
                                            'appointment_creation_failed' => true,
                                            'appointment_creation_error' => $e->getMessage()
                                        ]);
                                        $call->save();

                                        Log::error('❌ Failed to create Appointment record after Cal.com booking', [
                                            'error' => $e->getMessage(),
                                            'call_id' => $call->id,
                                            'booking_id' => $booking['uid'] ?? null,
                                            'trace' => $e->getTraceAsString()
                                        ]);

                                        // Return error to user - Cal.com booking exists but local appointment failed
                                        return response()->json([
                                            'success' => false,
                                            'status' => 'partial_booking',
                                            'message' => "Die Buchung wurde erstellt, aber es gab ein Problem bei der Speicherung. Bitte kontaktieren Sie uns direkt.",
                                            'error' => 'appointment_creation_failed'
                                        ], 500);
                                    }
                                }

                                return response()->json([
                                    'success' => true,
                                    'status' => 'booked',
                                    'message' => "Perfekt! Ihr Termin am {$datum} um {$uhrzeit} wurde erfolgreich gebucht. Sie erhalten eine Bestätigung.",
                                    'appointment_id' => $booking['uid'] ?? $booking['id'] ?? 'confirmed'
                                ], 200);
                            } else {
                                // Cal.com API returned an error - treat as unavailable and offer alternatives
                                $errorData = $response->json();
                                $errorCode = $errorData['error']['code'] ?? 'unknown';
                                $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
                                $errorDetails = $errorData['error']['details'] ?? '';

                                Log::error('Cal.com booking failed - searching for alternatives', [
                                    'status' => $response->status(),
                                    'error' => $errorData,
                                    'error_code' => $errorCode,
                                    'sent_data' => $bookingData,
                                    'call_id' => $callId
                                ]);

                                // Find alternative appointments
                                try {
                                    // OPTIMIZATION: Use cached alternatives if already checked
                                    if (!$alternativesChecked) {
                                        // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
                                        $customerId = $customer?->id ?? $call?->customer_id;

                                        // SECURITY: Set tenant context for cache isolation
                                        $alternatives = $this->alternativeFinder
                                            ->setTenantContext($companyId, $branchId)
                                            ->findAlternatives(
                                                $appointmentDate,
                                                60,
                                                $service->calcom_event_type_id,
                                                $customerId  // Pass customer ID to prevent offering conflicting times
                                            );
                                        $alternativesChecked = true;
                                    }

                                    $message = "Der Termin am {$datum} um {$uhrzeit} ist leider nicht verfügbar.";
                                    if (!empty($alternatives['responseText'])) {
                                        $message = $alternatives['responseText'];
                                    } elseif (!empty($alternatives['alternatives'])) {
                                        $message .= " Ich kann Ihnen folgende Alternativen anbieten: ";
                                        foreach ($alternatives['alternatives'] as $index => $alt) {
                                            $message .= ($index + 1) . ". " . $alt['description'] . " ";
                                        }
                                    }

                                    return response()->json([
                                        'success' => false,
                                        'status' => 'unavailable',
                                        'message' => $message,
                                        'alternatives' => array_map(function($alt) {
                                            return [
                                                'time' => $alt['datetime']->format('H:i'),
                                                'description' => $alt['description']
                                            ];
                                        }, $alternatives['alternatives'] ?? [])
                                    ], 200);
                                } catch (\Exception $e) {
                                    // Fallback if alternative search also fails
                                    Log::error('Failed to find alternatives after booking error', [
                                        'error' => $e->getMessage()
                                    ]);

                                    return response()->json([
                                        'success' => false,
                                        'status' => 'error',
                                        'message' => 'Entschuldigung, dieser Termin ist leider nicht verfügbar. Bitte nennen Sie mir einen anderen Wunschtermin.'
                                    ], 200);
                                }
                            }
                    } catch (\Exception $e) {
                        Log::error('Booking exception occurred', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'call_id' => $callId
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'error',
                            'message' => 'Es ist ein unerwarteter Fehler aufgetreten. Bitte versuchen Sie es später erneut.'
                        ], 200);
                    }
                }

                // 🔧 V84 FIX: Handle CHECK-ONLY mode (STEP 1 of 2-step process)
                // If time IS available BUT no confirmation (STEP 1), ask user for confirmation
                elseif ($exactTimeAvailable && ($confirmBooking === false || $confirmBooking === null)) {
                    Log::info('✅ V84: STEP 1 - Time available, requesting user confirmation', [
                        'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                        'bestaetigung' => $confirmBooking,
                        'next_step' => 'Wait for user confirmation, then call with bestaetigung: true'
                    ]);

                    // Format German date/time for natural language
                    $germanDate = $appointmentDate->locale('de')->translatedFormat('l, d. F');
                    $germanTime = $appointmentDate->format('H:i');

                    return response()->json([
                        'success' => true,
                        'status' => 'available',
                        'message' => "Der Termin am {$germanDate} um {$germanTime} Uhr ist noch frei. Soll ich den Termin für Sie buchen?",
                        'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                        'awaiting_confirmation' => true,
                        'next_action' => 'Wait for user "Ja", then call collect_appointment_data with bestaetigung: true'
                    ], 200);
                }

                // If time is not available OR if explicitly checking only
                elseif (!$exactTimeAvailable || $confirmBooking === false) {
                    // AlternativeFinder now handles ALL fallback logic with Cal.com verification

                    // Check if we have verified alternatives
                    if (empty($alternatives['alternatives'])) {
                        Log::warning('❌ No alternatives available after Cal.com verification', [
                            'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                            'service_id' => $service->id,
                            'event_type_id' => $service->calcom_event_type_id,
                            'company_id' => $call->company_id ?? null,
                            'call_id' => $callId
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'no_availability',
                            'message' => "Ich habe die Verfügbarkeit erfolgreich geprüft. Leider sind für Ihren Wunschtermin und auch in den nächsten 14 Tagen keine freien Termine vorhanden. Das System funktioniert einwandfrei - es sind derzeit einfach alle Termine ausgebucht. Bitte rufen Sie zu einem späteren Zeitpunkt noch einmal an oder kontaktieren Sie uns direkt."
                        ], 200);
                    }

                    // Log successful alternative generation with verification status
                    Log::info('✅ Presenting Cal.com-verified alternatives to user', [
                        'count' => count($alternatives['alternatives']),
                        'times' => collect($alternatives['alternatives'])->pluck('datetime')->map->format('Y-m-d H:i')->toArray(),
                        'all_verified' => collect($alternatives['alternatives'])->every(fn($alt) => isset($alt['source']) && str_contains($alt['source'], 'calcom')),
                        'call_id' => $callId
                    ]);

                    // Build voice-optimized German message with natural conjunction
                    $alternativeDescriptions = collect($alternatives['alternatives'])
                        ->map(fn($alt) => $alt['description'])
                        ->join(' oder ');

                    $message = "Der Termin am {$datum} um {$uhrzeit} ist leider nicht verfügbar. " .
                              "Ich kann Ihnen folgende Alternativen anbieten: " .
                              $alternativeDescriptions . ". " .
                              "Welcher Termin würde Ihnen besser passen?";

                    return response()->json([
                        'success' => false,
                        'status' => 'unavailable',
                        'message' => $message,
                        'alternatives' => array_map(function($alt) {
                            return [
                                'time' => $alt['datetime']->format('H:i'),
                                'date' => $alt['datetime']->format('d.m.Y'),
                                'description' => $alt['description'],
                                'verified' => isset($alt['source']) && str_contains($alt['source'], 'calcom')
                            ];
                        }, $alternatives['alternatives'])
                    ], 200);
                }

            } catch (\Exception $e) {
                Log::error('Error checking Cal.com availability', [
                    'error' => $e->getMessage(),
                    'date' => $appointmentDate->format('Y-m-d H:i')
                ]);

                // Fallback response
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ich kann die Verfügbarkeit momentan nicht prüfen. Bitte versuchen Sie es später noch einmal.'
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('Error in collectAppointment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Ein Fehler ist aufgetreten'
            ], 200);
        }
    }

    /**
     * Check availability for a specific date
     * Called by Retell AI to verify available appointment slots
     */
    public function handleAvailabilityCheck(Request $request)
    {
        $data = $request->all();
        $args = $data['args'] ?? $data;

        // ENHANCED MONITORING FOR TEST CALL
        Log::info('🔍 ===== AVAILABILITY CHECK WEBHOOK =====', [
            'timestamp' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'forwarded_for' => $request->header('X-Forwarded-For'),
            'method' => $request->method(),
            'path' => $request->path(),
            'raw_body' => LogSanitizer::sanitize($request->getContent()),
            'parsed_data' => LogSanitizer::sanitize($data),
            'args' => LogSanitizer::sanitize($args),
            'service' => $args['service'] ?? $args['dienstleistung'] ?? null,
            'date' => $args['date'] ?? $args['datum'] ?? null,
            'time' => $args['time'] ?? $args['uhrzeit'] ?? null,
            'all_headers' => LogSanitizer::sanitizeHeaders($request->headers->all())
        ]);

        Log::info('📅 Checking availability', [
            'args' => $args
        ]);

        try {
            $datum = $args['datum'] ?? null;
            $callId = $args['call_id'] ?? null;
            $serviceType = $args['service'] ?? $args['dienstleistung'] ?? null;

            // Parse German date format
            $checkDate = null;
            if ($datum) {
                if (preg_match('/(\d{1,2})\.(\d{1,2})\.?(\d{4})?/', $datum, $matches)) {
                    $day = intval($matches[1]);
                    $month = intval($matches[2]);
                    $year = isset($matches[3]) ? intval($matches[3]) : Carbon::now()->year;

                    // If date is in the past, assume next year
                    $checkDate = Carbon::create($year, $month, $day);
                    if ($checkDate->isPast()) {
                        $checkDate->addYear();
                    }
                } else {
                    // Try to parse as relative date (e.g., "morgen", "übermorgen")
                    $checkDate = $this->parseRelativeDate($datum);
                }
            }

            if (!$checkDate) {
                $checkDate = Carbon::now()->addDay(); // Default to tomorrow
            }

            // Get company/service info
            $companyId = 15; // Default AskProAI
            if ($callId) {
                $call = $this->callLifecycle->findCallByRetellId($callId);
                if ($call) {
                    if ($call->company_id) {
                        $companyId = $call->company_id;
                    } elseif ($call->phone_number_id) {
                        $phoneNumber = \App\Models\PhoneNumber::find($call->phone_number_id);
                        $companyId = $phoneNumber ? $phoneNumber->company_id : $companyId;
                    }
                }
            }

            // Get appropriate service using ServiceSelectionService
            $service = $this->serviceSelector->getDefaultService($companyId);

            if (!$service) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Keine Dienste verfügbar',
                    'available_slots' => []
                ], 200);
            }

            // Check Cal.com availability
            $calcom = app(\App\Services\CalcomService::class);
            $startDateTime = $checkDate->copy()->startOfDay()->toIso8601String();
            $endDateTime = $checkDate->copy()->endOfDay()->toIso8601String();

            Log::info('🔍 Querying Cal.com for availability', [
                'event_type_id' => $service->calcom_event_type_id,
                'start' => $startDateTime,
                'end' => $endDateTime
            ]);

            $response = $calcom->getAvailableSlots(
                $service->calcom_event_type_id,
                $startDateTime,
                $endDateTime
            );

            if ($response->successful()) {
                $data = $response->json();
                $slots = [];

                // Parse Cal.com response structure
                if (isset($data['data']['slots'])) {
                    foreach ($data['data']['slots'] as $date => $dateSlots) {
                        if (is_array($dateSlots)) {
                            foreach ($dateSlots as $slot) {
                                $slotTime = is_array($slot) && isset($slot['time']) ? $slot['time'] : $slot;
                                $time = Carbon::parse($slotTime)->setTimezone('Europe/Berlin');
                                $slots[] = $time->format('H:i');
                            }
                        }
                    }
                }

                // Sort slots
                sort($slots);

                if (count($slots) > 0) {
                    $slotsText = implode(' Uhr, ', array_slice($slots, 0, 5)) . ' Uhr';
                    return response()->json([
                        'success' => true,
                        'status' => 'available',
                        'message' => "Am {$checkDate->format('d.m.Y')} sind folgende Zeiten verfügbar: {$slotsText}",
                        'available_slots' => $slots,
                        'date' => $checkDate->format('Y-m-d'),
                        'formatted_date' => $checkDate->format('d.m.Y')
                    ], 200);
                } else {
                    return response()->json([
                        'success' => false,
                        'status' => 'unavailable',
                        'message' => "Am {$checkDate->format('d.m.Y')} sind leider keine Termine verfügbar. Möchten Sie einen anderen Tag probieren?",
                        'available_slots' => [],
                        'date' => $checkDate->format('Y-m-d'),
                        'formatted_date' => $checkDate->format('d.m.Y')
                    ], 200);
                }
            } else {
                Log::error('Cal.com API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Die Verfügbarkeit kann momentan nicht geprüft werden. Bitte versuchen Sie es später noch einmal.',
                    'available_slots' => []
                ], 200);
            }

        } catch (\Exception $e) {
            Log::error('Error in checkAvailability', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Ein Fehler ist aufgetreten bei der Verfügbarkeitsprüfung.',
                'available_slots' => []
            ], 200);
        }
    }

    /**
     * Parse relative German date strings
     */
    private function parseRelativeDate($dateString)
    {
        $dateString = strtolower($dateString);

        $mappings = [
            'heute' => Carbon::today(),
            'morgen' => Carbon::tomorrow(),
            'übermorgen' => Carbon::today()->addDays(2),
            'montag' => Carbon::parse('next monday'),
            'dienstag' => Carbon::parse('next tuesday'),
            'mittwoch' => Carbon::parse('next wednesday'),
            'donnerstag' => Carbon::parse('next thursday'),
            'freitag' => Carbon::parse('next friday'),
            'samstag' => Carbon::parse('next saturday'),
            'sonntag' => Carbon::parse('next sunday'),
        ];

        return $mappings[$dateString] ?? null;
    }

    /**
     * Get valid email with fallback logic
     *
     * @deprecated Use CustomerDataValidator service instead
     * @see App\Services\Retell\CustomerDataValidator::getValidEmail()
     */
    private function getValidEmail($request, $call = null)
    {
        $args = $request->input('args', []);
        return $this->dataValidator->getValidEmail($args, $call);
    }

    /**
     * Get valid phone number with fallback logic
     *
     * @deprecated Use CustomerDataValidator service instead
     * @see App\Services\Retell\CustomerDataValidator::getValidPhone()
     */
    private function getValidPhone($request, $call = null)
    {
        $args = $request->input('args', []);
        return $this->dataValidator->getValidPhone($args, $call);
    }

    /**
     * Ensure customer exists for the call
     * Helper method to find or create customer before appointment creation
     *
     * @deprecated Use AppointmentCustomerResolver service instead
     * @see App\Services\Retell\AppointmentCustomerResolver::ensureCustomerFromCall()
     */
    private function ensureCustomerFromCall(Call $call, string $name, ?string $email): Customer
    {
        return $this->customerResolver->ensureCustomerFromCall($call, $name, $email);
    }

    /**
     * Handle cancellation attempt from Retell AI
     * Called when customer says: "Ich möchte stornieren" or "Cancel my appointment"
     *
     * Security: Anonymous callers → CallbackRequest instead of direct cancellation
     */
    private function handleCancellationAttempt(array $params, ?string $callId)
    {
        try {
            // 1. Get call context
            $callContext = $this->getCallContext($callId);
            if (!$callContext) {
                Log::error('Cannot cancel: Call context not found', ['call_id' => $callId]);
                return $this->responseFormatter->error('Anruf konnte nicht gefunden werden');
            }

            // Get call by internal ID from context
            $call = Call::find($callContext['call_id']);

            // 🔒 SECURITY: Anonymous callers → CallbackRequest for verification
            if ($call && ($call->from_number === 'anonymous' || in_array(strtolower($call->from_number ?? ''), ['anonymous', 'unknown', 'withheld', 'restricted', '']))) {
                return $this->createAnonymousCallbackRequest($call, $params, 'cancellation');
            }

            // 2. Find appointment
            $appointment = $this->findAppointmentFromCall($call, $params);

            if (!$appointment) {
                $dateStr = $params['appointment_date'] ?? $params['datum'] ?? 'dem gewünschten Datum';
                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => "Ich konnte keinen Termin am {$dateStr} finden. Könnten Sie das Datum noch einmal nennen?"
                ], 200);
            }

            // 3. Check policy
            $policyEngine = app(\App\Services\Policies\AppointmentPolicyEngine::class);
            $policyResult = $policyEngine->canCancel($appointment);

            // 4. If allowed: Cancel appointment
            if ($policyResult->allowed) {
                // Cancel in database
                $appointment->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $params['reason'] ?? 'Via Telefonassistent storniert'
                ]);

                // Track modification for quota/analytics
                \App\Models\AppointmentModification::create([
                    'appointment_id' => $appointment->id,
                    'customer_id' => $appointment->customer_id,
                    'company_id' => $appointment->company_id,
                    'modification_type' => 'cancel',
                    'within_policy' => true,
                    'fee_charged' => $policyResult->fee,
                    'reason' => $params['reason'] ?? null,
                    'modified_by_type' => 'System',
                    'metadata' => [
                        'call_id' => $callId,
                        'hours_notice' => $policyResult->details['hours_notice'] ?? null,
                        'policy_required' => $policyResult->details['required_hours'] ?? null,
                        'cancelled_via' => 'retell_ai'
                    ]
                ]);

                // Fire event for listeners (notifications, stats, etc.)
                event(new \App\Events\Appointments\AppointmentCancellationRequested(
                    appointment: $appointment->fresh(),
                    reason: $params['reason'] ?? 'Via Telefonassistent storniert',
                    customer: $appointment->customer,
                    fee: $policyResult->fee,
                    withinPolicy: true
                ));

                $feeMessage = $policyResult->fee > 0
                    ? " Es fällt eine Stornogebühr von {$policyResult->fee}€ an."
                    : "";

                Log::info('✅ Appointment cancelled via Retell AI', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $callId,
                    'fee' => $policyResult->fee,
                    'within_policy' => true
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'cancelled',
                    'message' => "Ihr Termin am {$appointment->starts_at->format('d.m.Y')} um {$appointment->starts_at->format('H:i')} Uhr wurde erfolgreich storniert.{$feeMessage}",
                    'fee' => $policyResult->fee,
                    'appointment_id' => $appointment->id
                ], 200);
            }

            // 5. If denied: Explain reason
            $details = $policyResult->details;

            // Build user-friendly German message based on reason
            if (str_contains($policyResult->reason, 'hours notice')) {
                $message = sprintf(
                    "Eine Stornierung ist leider nicht mehr möglich. Sie benötigen %d Stunden Vorlauf, aber Ihr Termin ist nur noch in %.0f Stunden.",
                    $details['required_hours'] ?? 24,
                    $details['hours_notice'] ?? 0
                );
                if (isset($details['fee_if_forced']) && $details['fee_if_forced'] > 0) {
                    $message .= sprintf(" Wenn Sie trotzdem stornieren möchten, fällt eine Gebühr von %.2f€ an.", $details['fee_if_forced']);
                }
                $reasonCode = 'deadline_missed';
            } elseif (str_contains($policyResult->reason, 'quota exceeded')) {
                $message = sprintf(
                    "Sie haben Ihr monatliches Storno-Limit bereits erreicht (%d/%d verwendet).",
                    $details['quota_used'] ?? 0,
                    $details['quota_max'] ?? 3
                );
                $reasonCode = 'quota_exceeded';
            } else {
                $message = $policyResult->reason ?? "Eine Stornierung ist derzeit nicht möglich.";
                $reasonCode = 'policy_violation';
            }

            // Fire policy violation event
            event(new \App\Events\Appointments\AppointmentPolicyViolation(
                appointment: $appointment,
                policyResult: $policyResult,
                attemptedAction: 'cancel',
                source: 'retell_ai'
            ));

            Log::warning('❌ Cancellation denied by policy', [
                'appointment_id' => $appointment->id,
                'call_id' => $callId,
                'reason' => $reasonCode,
                'details' => $details
            ]);

            return response()->json([
                'success' => false,
                'status' => 'denied',
                'message' => $message,
                'reason' => $reasonCode,
                'details' => $details
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error handling cancellation attempt', [
                'error' => $e->getMessage(),
                'call_id' => $callId,
                'trace' => $e->getTraceAsString()
            ]);

            // In testing, expose the actual error
            if (app()->environment('testing')) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut oder kontaktieren Sie uns direkt.',
                    'debug_error' => $e->getMessage(),
                    'debug_trace' => $e->getTraceAsString()
                ], 200);
            }

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut oder kontaktieren Sie uns direkt.'
            ], 200);
        }
    }

    /**
     * Handle reschedule attempt from Retell AI
     * Called when customer says: "Kann ich den Termin verschieben?" or "I need to reschedule"
     *
     * Security: Anonymous callers → CallbackRequest instead of direct reschedule
     */
    private function handleRescheduleAttempt(array $params, ?string $callId)
    {
        try {
            // 1. Get call context
            $callContext = $this->getCallContext($callId);
            if (!$callContext) {
                Log::error('Cannot reschedule: Call context not found', ['call_id' => $callId]);
                return $this->responseFormatter->error('Anruf konnte nicht gefunden werden');
            }

            $call = $this->callLifecycle->findCallByRetellId($callId);

            // 🔒 SECURITY: Anonymous callers → CallbackRequest for verification
            if ($call && ($call->from_number === 'anonymous' || in_array(strtolower($call->from_number ?? ''), ['anonymous', 'unknown', 'withheld', 'restricted', '']))) {
                return $this->createAnonymousCallbackRequest($call, $params, 'reschedule');
            }

            // 2. Find current appointment
            $oldDate = $params['old_date'] ?? $params['appointment_date'] ?? $params['datum'] ?? null;
            $appointment = $this->findAppointmentFromCall($call, ['appointment_date' => $oldDate]);

            if (!$appointment) {
                // Try listing all upcoming appointments for customer
                if ($call->customer_id) {
                    $upcomingAppointments = Appointment::where('customer_id', $call->customer_id)
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->where('starts_at', '>=', now())
                        ->orderBy('starts_at', 'asc')
                        ->limit(3)
                        ->get();

                    if ($upcomingAppointments->count() > 0) {
                        $appointments_list = $upcomingAppointments->map(function($apt) {
                            return $apt->starts_at->format('d.m.Y \u\m H:i \U\h\r');
                        })->join(', ');

                        return response()->json([
                            'success' => false,
                            'status' => 'multiple_found',
                            'message' => "Ich habe mehrere Termine für Sie gefunden: {$appointments_list}. Welchen möchten Sie verschieben?",
                            'appointments' => $upcomingAppointments->map(function($apt) {
                                return [
                                    'id' => $apt->id,
                                    'date' => $apt->starts_at->format('Y-m-d'),
                                    'time' => $apt->starts_at->format('H:i'),
                                    'formatted' => $apt->starts_at->format('d.m.Y H:i')
                                ];
                            })
                        ], 200);
                    }
                }

                $dateStr = $oldDate ?? 'dem gewünschten Datum';
                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => "Ich konnte keinen Termin am {$dateStr} finden. Könnten Sie das Datum noch einmal nennen?"
                ], 200);
            }

            // 3. Parse new date FIRST (before policy check)
            $newDate = $params['new_date'] ?? null;
            $newTime = $params['new_time'] ?? null;

            if (!$newDate || !$newTime) {
                return response()->json([
                    'success' => true,
                    'status' => 'ready_to_reschedule',
                    'message' => "Wann möchten Sie den Termin verschieben?",
                    'current_appointment' => [
                        'date' => $appointment->starts_at->format('d.m.Y'),
                        'time' => $appointment->starts_at->format('H:i')
                    ]
                ], 200);
            }

            // Parse new datetime
            $newDateParsed = $this->parseDateString($newDate);
            if (!$newDateParsed) {
                return response()->json([
                    'success' => false,
                    'status' => 'invalid_date',
                    'message' => "Das Datum konnte nicht verstanden werden. Bitte nennen Sie es im Format Tag.Monat.Jahr oder als 'heute', 'morgen'."
                ], 200);
            }

            // Add time to date
            if (strpos($newTime, ':') !== false) {
                list($hour, $minute) = explode(':', $newTime);
            } else {
                $hour = intval($newTime);
                $minute = 0;
            }
            $newDateTime = $newDateParsed->setTime($hour, $minute);

            // 6. Check availability for new slot
            $companyId = $callContext['company_id'];
            $branchId = $callContext['branch_id'];
            $service = $this->serviceSelector->getDefaultService($companyId, $branchId);

            if (!$service || !$service->calcom_event_type_id) {
                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Service-Konfiguration fehlt. Bitte kontaktieren Sie uns direkt.'
                ], 200);
            }

            // Check if new time is available
            $calcomService = app(\App\Services\CalcomService::class);
            $slotsResponse = $calcomService->getAvailableSlots(
                $service->calcom_event_type_id,
                $newDateTime->format('Y-m-d'),
                $newDateTime->format('Y-m-d')
            );

            $isAvailable = false;
            if ($slotsResponse->successful()) {
                $slots = $slotsResponse->json()['data']['slots'][$newDateTime->format('Y-m-d')] ?? [];
                foreach ($slots as $slot) {
                    $slotTime = Carbon::parse($slot['time']);
                    if ($slotTime->format('H:i') === $newDateTime->format('H:i')) {
                        $isAvailable = true;
                        break;
                    }
                }
            }

            if (!$isAvailable) {
                // Find alternatives
                // 🔧 FIX 2025-10-13: Get customer_id to filter out existing appointments
                $customerId = $call?->customer_id ?? $appointment?->customer_id;

                $alternativeFinder = app(\App\Services\AppointmentAlternativeFinder::class);
                $alternatives = $alternativeFinder
                    ->setTenantContext($companyId, $branchId)
                    ->findAlternatives($newDateTime, 60, $service->calcom_event_type_id, $customerId);

                $message = "Der Termin am {$newDate} um {$newTime} Uhr ist leider nicht verfügbar.";
                if (!empty($alternatives['responseText'])) {
                    $message = $alternatives['responseText'];
                }

                return response()->json([
                    'success' => false,
                    'status' => 'unavailable',
                    'message' => $message,
                    'alternatives' => array_map(function($alt) {
                        return [
                            'time' => $alt['datetime']->format('H:i'),
                            'description' => $alt['description']
                        ];
                    }, $alternatives['alternatives'] ?? [])
                ], 200);
            }

            // 5. ONLY NOW check policy (after we know slot is available)
            $policyEngine = app(\App\Services\Policies\AppointmentPolicyEngine::class);
            $policyResult = $policyEngine->canReschedule($appointment);

            if (!$policyResult->allowed) {
                $details = $policyResult->details;

                if (str_contains($policyResult->reason, 'hours notice')) {
                    $message = sprintf(
                        "Eine Umbuchung ist leider nicht mehr möglich. Sie benötigen %d Stunden Vorlauf, aber Ihr Termin ist nur noch in %.0f Stunden.",
                        $details['required_hours'] ?? 24,
                        $details['hours_notice'] ?? 0
                    );
                    $reasonCode = 'deadline_missed';
                } elseif (str_contains($policyResult->reason, 'rescheduled')) {
                    $message = sprintf(
                        "Dieser Termin wurde bereits %d Mal umgebucht (Maximum: %d). Eine weitere Umbuchung ist nicht möglich.",
                        $details['reschedule_count'] ?? 0,
                        $details['max_allowed'] ?? 2
                    );
                    $reasonCode = 'max_reschedules_reached';
                } else {
                    $message = $policyResult->reason ?? "Eine Umbuchung ist derzeit nicht möglich.";
                    $reasonCode = 'policy_violation';
                }

                // Fire policy violation event
                event(new \App\Events\Appointments\AppointmentPolicyViolation(
                    appointment: $appointment,
                    policyResult: $policyResult,
                    attemptedAction: 'reschedule',
                    source: 'retell_ai'
                ));

                Log::warning('❌ Reschedule denied by policy', [
                    'appointment_id' => $appointment->id,
                    'call_id' => $callId,
                    'reason' => $reasonCode,
                    'details' => $details
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'denied',
                    'message' => $message,
                    'reason' => $reasonCode,
                    'details' => $details
                ], 200);
            }

            // 6. Perform reschedule
            $oldStartsAt = $appointment->starts_at->copy();

            // Update appointment
            $appointment->update([
                'starts_at' => $newDateTime,
                'ends_at' => $newDateTime->copy()->addMinutes($service->duration ?? 60),
                'updated_at' => now()
            ]);

            // Track modification
            \App\Models\AppointmentModification::create([
                'appointment_id' => $appointment->id,
                'customer_id' => $appointment->customer_id,
                'company_id' => $appointment->company_id,
                'modification_type' => 'reschedule',
                'within_policy' => true,
                'fee_charged' => $policyResult->fee,
                'reason' => $params['reason'] ?? null,
                'modified_by_type' => 'System',
                'metadata' => [
                    'call_id' => $callId,
                    'hours_notice' => $policyResult->details['hours_notice'] ?? null,
                    'original_time' => $oldStartsAt->toIso8601String(),
                    'new_time' => $newDateTime->toIso8601String(),
                    'rescheduled_via' => 'retell_ai'
                ]
            ]);

            // Fire event for listeners (notifications, stats, etc.)
            event(new \App\Events\Appointments\AppointmentRescheduled(
                appointment: $appointment->fresh(),
                oldStartTime: $oldStartsAt,
                newStartTime: $newDateTime,
                reason: $params['reason'] ?? null,
                fee: $policyResult->fee,
                withinPolicy: true
            ));

            $feeMessage = $policyResult->fee > 0
                ? " Es fällt eine Umbuchungsgebühr von {$policyResult->fee}€ an."
                : "";

            Log::info('✅ Appointment rescheduled via Retell AI', [
                'appointment_id' => $appointment->id,
                'call_id' => $callId,
                'old_time' => $oldStartsAt->toIso8601String(),
                'new_time' => $newDateTime->toIso8601String(),
                'fee' => $policyResult->fee
            ]);

            return response()->json([
                'success' => true,
                'status' => 'rescheduled',
                'message' => "Ihr Termin wurde erfolgreich umgebucht auf {$newDateTime->format('d.m.Y')} um {$newDateTime->format('H:i')} Uhr.{$feeMessage}",
                'fee' => $policyResult->fee,
                'appointment_id' => $appointment->id,
                'old_time' => $oldStartsAt->format('Y-m-d H:i'),
                'new_time' => $newDateTime->format('Y-m-d H:i')
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error handling reschedule attempt', [
                'error' => $e->getMessage(),
                'call_id' => $callId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut oder kontaktieren Sie uns direkt.'
            ], 200);
        }
    }

    /**
     * Find appointment from call and date information
     *
     * FIX 2025-10-10: Use DateTimeParser service instead of deprecated parseDateString()
     * Root cause: parseDateString() cannot parse German relative dates (heute, morgen)
     * Impact: reschedule_appointment and cancel_appointment failed to find appointments
     */
    private function findAppointmentFromCall(Call $call, array $data): ?Appointment
    {
        // Parse date
        $dateString = $data['appointment_date'] ?? $data['datum'] ?? null;

        // Strategy 0: SAME-CALL Detection (<5 minutes old)
        // If user just booked and wants to reschedule immediately without specifying date
        if (!$dateString || $dateString === 'heute' || $dateString === 'today') {
            $recentAppointment = Appointment::where('call_id', $call->id)
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->where('created_at', '>=', now()->subMinutes(5))  // Last 5 minutes
                ->orderBy('created_at', 'desc')
                ->first();

            if ($recentAppointment) {
                Log::info('✅ Found SAME-CALL appointment (booked <5min ago)', [
                    'appointment_id' => $recentAppointment->id,
                    'created_at' => $recentAppointment->created_at->toIso8601String(),
                    'age_seconds' => $recentAppointment->created_at->diffInSeconds(now())
                ]);
                return $recentAppointment;
            }
        }

        if (!$dateString) {
            Log::warning('findAppointmentFromCall: No date provided', ['call_id' => $call->id]);
            return null;
        }

        // FIX: Use DateTimeParser service for German relative dates support
        $parsedDate = $this->dateTimeParser->parseDateString($dateString);
        if (!$parsedDate) {
            Log::warning('findAppointmentFromCall: Could not parse date', [
                'call_id' => $call->id,
                'date_string' => $dateString
            ]);
            return null;
        }

        $date = Carbon::parse($parsedDate);  // parseDateString returns YYYY-MM-DD format

        Log::info('🔍 Finding appointment', [
            'call_id' => $call->id,
            'customer_id' => $call->customer_id,
            'company_id' => $call->company_id,
            'date' => $date->toDateString(),
        ]);

        // Strategy 1: Try call_id first (same call booking)
        $appointment = Appointment::where('call_id', $call->id)
            ->whereDate('starts_at', $date)
            ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
            ->first();

        if ($appointment) {
            Log::info('✅ Found appointment via call_id', ['appointment_id' => $appointment->id]);
            return $appointment;
        }

        // Strategy 2: Try customer_id (cross-call, same customer)
        if ($call->customer_id) {
            $appointment = Appointment::where('customer_id', $call->customer_id)
                ->whereDate('starts_at', $date)
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($appointment) {
                Log::info('✅ Found appointment via customer_id', [
                    'appointment_id' => $appointment->id,
                    'customer_id' => $call->customer_id
                ]);
                return $appointment;
            }
        }

        // Strategy 3: Try phone number (if customer not linked yet)
        if ($call->from_number && !in_array($call->from_number, ['unknown', 'anonymous', null, ''])) {
            $customer = Customer::where('phone', $call->from_number)
                ->where('company_id', $call->company_id ?? 1)
                ->first();

            if ($customer) {
                $appointment = Appointment::where('customer_id', $customer->id)
                    ->whereDate('starts_at', $date)
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($appointment) {
                    Log::info('✅ Found appointment via phone number', [
                        'appointment_id' => $appointment->id,
                        'customer_id' => $customer->id,
                        'phone' => $call->from_number
                    ]);

                    // Auto-link customer to call for future lookups
                    $call->update(['customer_id' => $customer->id]);

                    return $appointment;
                }
            }
        }

        // 🔥 NEW Strategy 4: Try customer name (for anonymous callers)
        $customerName = $data['customer_name'] ?? $data['name'] ?? $data['kundename'] ?? $call->customer_name ?? null;
        if ($customerName && $call->company_id) {
            Log::info('🔍 Searching appointment by customer name (anonymous caller)', [
                'customer_name' => $customerName,
                'company_id' => $call->company_id,
                'date' => $date->toDateString()
            ]);

            // Find customer by name + company (fuzzy match)
            $customer = Customer::where('company_id', $call->company_id)
                ->where(function($query) use ($customerName) {
                    $query->where('name', 'LIKE', '%' . $customerName . '%')
                          ->orWhere('name', $customerName);
                })
                ->first();

            if ($customer) {
                $appointment = Appointment::where('customer_id', $customer->id)
                    ->whereDate('starts_at', $date)
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($appointment) {
                    Log::info('✅ Found appointment via customer name', [
                        'appointment_id' => $appointment->id,
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'matched_with' => $customerName
                    ]);

                    // Auto-link customer to call for future lookups
                    if (!$call->customer_id) {
                        $call->update(['customer_id' => $customer->id]);
                    }

                    return $appointment;
                }
            }
        }

        // Strategy 5: FALLBACK - List ALL upcoming appointments for customer
        if ($call->customer_id) {
            $customerAppointments = Appointment::where('customer_id', $call->customer_id)
                ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                ->where('starts_at', '>=', now())  // Only future appointments
                ->orderBy('starts_at', 'asc')
                ->get();

            if ($customerAppointments->count() === 1) {
                // Only 1 appointment → automatically use it
                Log::info('✅ Found single upcoming appointment for customer (FALLBACK)', [
                    'appointment_id' => $customerAppointments->first()->id,
                    'customer_id' => $call->customer_id,
                    'starts_at' => $customerAppointments->first()->starts_at->toIso8601String()
                ]);
                return $customerAppointments->first();
            } elseif ($customerAppointments->count() > 1) {
                // Multiple appointments → need clarification (handled in handleRescheduleAttempt)
                Log::info('⚠️ Multiple appointments found, need clarification (FALLBACK)', [
                    'count' => $customerAppointments->count(),
                    'customer_id' => $call->customer_id,
                    'dates' => $customerAppointments->pluck('starts_at')->map(fn($dt) => $dt->format('Y-m-d H:i'))->toArray()
                ]);
                return null;  // Will be handled in handleRescheduleAttempt with appointment list
            }
        }

        Log::warning('❌ No appointment found', [
            'call_id' => $call->id,
            'customer_id' => $call->customer_id,
            'company_id' => $call->company_id,
            'from_number' => $call->from_number,
            'date' => $date->toDateString(),
        ]);

        return null;
    }

    /**
     * Create callback request for anonymous caller modifications
     *
     * Security: Anonymous callers cannot directly cancel/reschedule.
     * Instead, create a CallbackRequest for staff to handle within business hours.
     *
     * @param Call $call Current call
     * @param array $params Request parameters
     * @param string $action 'cancellation' or 'reschedule'
     * @return \Illuminate\Http\JsonResponse
     */
    private function createAnonymousCallbackRequest(Call $call, array $params, string $action): \Illuminate\Http\JsonResponse
    {
        try {
            $callbackRequest = \App\Models\CallbackRequest::create([
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'phone_number' => 'anonymous_' . time(),
                'customer_name' => $params['customer_name'] ?? $params['name'] ?? 'Anonymer Anrufer',
                'priority' => 'high',
                'status' => 'pending',
                'notes' => sprintf(
                    'Anonymer Anrufer möchte Termin %s. Datum: %s',
                    $action === 'cancellation' ? 'stornieren' : 'verschieben',
                    $params['old_date'] ?? $params['appointment_date'] ?? $params['datum'] ?? 'unbekannt'
                ),
                'metadata' => [
                    'call_id' => $call->retell_call_id,
                    'action_requested' => $action,
                    'appointment_date' => $params['old_date'] ?? $params['appointment_date'] ?? $params['datum'] ?? null,
                    'new_date' => $params['new_date'] ?? null,
                    'new_time' => $params['new_time'] ?? null,
                    'customer_name_provided' => $params['customer_name'] ?? $params['name'] ?? null,
                    'from_number' => 'anonymous',
                    'created_via' => 'retell_webhook_anonymous'
                ],
                'expires_at' => now()->addHours(24)
            ]);

            Log::info('📋 Anonymous caller callback request created', [
                'callback_request_id' => $callbackRequest->id,
                'action' => $action,
                'call_id' => $call->id,
                'retell_call_id' => $call->retell_call_id
            ]);

            $actionText = $action === 'cancellation' ? 'Stornierung' : 'Umbuchung';

            return response()->json([
                'success' => true,
                'status' => 'callback_queued',
                'message' => sprintf(
                    'Aus Sicherheitsgründen können wir %s nur mit übertragener Rufnummer durchführen. Wir haben Ihre Anfrage notiert und rufen Sie innerhalb der nächsten 2 Stunden zurück, um die %s zu bestätigen. Alternativ können Sie während unserer Geschäftszeiten direkt anrufen.',
                    $actionText,
                    $actionText
                ),
                'callback_request_id' => $callbackRequest->id,
                'estimated_callback_time' => '2 Stunden'
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Failed to create callback request for anonymous caller', [
                'error' => $e->getMessage(),
                'call_id' => $call->id ?? null,
                'action' => $action
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Es ist ein Fehler aufgetreten. Bitte rufen Sie direkt während unserer Geschäftszeiten an.'
            ], 200);
        }
    }

    /**
     * Handle callback request from customer
     *
     * Called when customer wants a callback instead of immediate booking
     *
     * @param array $params
     * @param string|null $callId
     * @return array
     */
    private function handleCallbackRequest(array $params, ?string $callId): array
    {
        try {
            Log::info('📞 Processing callback request', [
                'call_id' => $callId,
                'params' => $params,
            ]);

            // Get call context
            $call = $this->getCallRecord($callId);
            if (!$call) {
                return [
                    'success' => false,
                    'message' => 'Anrufkontext nicht gefunden'
                ];
            }

            // Prepare callback data
            $callbackData = [
                'customer_id' => $call->customer_id,
                'branch_id' => $call->company->branches()->first()?->id,
                'phone_number' => $params['phone_number'] ?? $call->customer?->phone ?? 'unknown',
                'customer_name' => $params['customer_name'] ?? $call->customer?->name ?? 'Unknown',
                'preferred_time_window' => $params['preferred_time_window'] ?? null,
                'priority' => $params['priority'] ?? 'normal',
                'notes' => $params['reason'] ?? $params['notes'] ?? null,
                'metadata' => [
                    'call_id' => $callId,
                    'retell_call_id' => $call->retell_call_id,
                    'requested_at' => now()->toIso8601String(),
                ],
            ];

            // Add service if specified
            if (!empty($params['service_name'])) {
                $service = Service::where('company_id', $call->company_id)
                    ->where('name', 'like', '%' . $params['service_name'] . '%')
                    ->first();

                if ($service) {
                    $callbackData['service_id'] = $service->id;
                }
            }

            // Create callback request via service
            $callbackService = app(\App\Services\Appointments\CallbackManagementService::class);
            $callback = $callbackService->createRequest($callbackData);

            Log::info('✅ Callback request created', [
                'callback_id' => $callback->id,
                'customer_name' => $callback->customer_name,
                'phone' => $callback->phone_number,
                'assigned_to' => $callback->assigned_to,
            ]);

            return [
                'success' => true,
                'callback_id' => $callback->id,
                'status' => $callback->status,
                'assigned_to' => $callback->assignedTo?->name ?? 'Wird zugewiesen',
                'priority' => $callback->priority,
                'message' => sprintf(
                    'Rückruf-Anfrage erfolgreich erstellt. %s',
                    $callback->assignedTo
                        ? "Zugewiesen an {$callback->assignedTo->name}."
                        : 'Wird automatisch zugewiesen.'
                ),
            ];

        } catch (\Exception $e) {
            Log::error('❌ Failed to create callback request', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'callback_creation_failed',
                'message' => 'Entschuldigung, Rückruf-Anfrage konnte nicht erstellt werden.',
            ];
        }
    }

    /**
     * Find next available appointment slot
     *
     * Called when customer asks for next available time
     *
     * @param array $params
     * @param string|null $callId
     * @return array
     */
    private function handleFindNextAvailable(array $params, ?string $callId): array
    {
        try {
            Log::info('🔍 Finding next available slot', [
                'call_id' => $callId,
                'params' => $params,
            ]);

            // Get call context
            $call = $this->getCallRecord($callId);
            if (!$call) {
                return [
                    'success' => false,
                    'message' => 'Anrufkontext nicht gefunden'
                ];
            }

            // Find service
            $service = null;
            if (!empty($params['service_name'])) {
                $service = Service::where('company_id', $call->company_id)
                    ->where('name', 'like', '%' . $params['service_name'] . '%')
                    ->first();
            } elseif (!empty($params['service_id'])) {
                $service = Service::where('company_id', $call->company_id)
                    ->where('id', $params['service_id'])
                    ->first();
            }

            if (!$service) {
                return [
                    'success' => false,
                    'message' => 'Service nicht gefunden'
                ];
            }

            // Parse start time
            $after = null;
            if (!empty($params['after'])) {
                try {
                    $after = Carbon::parse($params['after']);
                } catch (\Exception $e) {
                    Log::warning('⚠️ Invalid after date', [
                        'after' => $params['after'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Search for next available slot
            $finder = new \App\Services\Appointments\SmartAppointmentFinder($call->company);
            $searchDays = $params['search_days'] ?? 14;

            $nextSlot = $finder->findNextAvailable($service, $after, $searchDays);

            if (!$nextSlot) {
                Log::info('📅 No available slots found', [
                    'service_id' => $service->id,
                    'search_days' => $searchDays,
                ]);

                return [
                    'success' => false,
                    'message' => sprintf(
                        'Keine freien Termine für %s in den nächsten %d Tagen gefunden.',
                        $service->name,
                        $searchDays
                    ),
                ];
            }

            Log::info('✅ Found next available slot', [
                'service_id' => $service->id,
                'next_slot' => $nextSlot->toIso8601String(),
            ]);

            return [
                'success' => true,
                'service' => $service->name,
                'next_available' => $nextSlot->toIso8601String(),
                'formatted_date' => $nextSlot->locale('de')->isoFormat('dddd, D. MMMM YYYY'),
                'formatted_time' => $nextSlot->format('H:i'),
                'message' => sprintf(
                    'Der nächste freie Termin für %s ist am %s um %s Uhr.',
                    $service->name,
                    $nextSlot->locale('de')->isoFormat('dddd, D. MMMM'),
                    $nextSlot->format('H:i')
                ),
            ];

        } catch (\Exception $e) {
            Log::error('❌ Failed to find next available slot', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'finder_error',
                'message' => 'Entschuldigung, Verfügbarkeitssuche fehlgeschlagen.',
            ];
        }
    }

    /**
     * Query existing appointments for caller
     *
     * Security: Requires phone number verification
     * Anonymous callers are rejected for security reasons
     *
     * @param array $params Query parameters (date, service, etc.)
     * @param string|null $callId Retell call ID
     * @return array Response with appointment info or error
     */
    private function queryAppointment(array $params, ?string $callId)
    {
        try {
            Log::info('🔍 Query appointment function called', [
                'call_id' => $callId,
                'parameters' => $params
            ]);

            // Get call context
            $call = $this->callLifecycle->findCallByRetellId($callId);

            if (!$call) {
                Log::error('❌ Call not found for query', [
                    'retell_call_id' => $callId
                ]);

                return [
                    'success' => false,
                    'error' => 'call_not_found',
                    'message' => 'Anruf konnte nicht gefunden werden.'
                ];
            }

            // Use query service for secure appointment lookup
            $queryService = app(\App\Services\Retell\AppointmentQueryService::class);

            $criteria = [
                'appointment_date' => $params['appointment_date'] ?? $params['datum'] ?? null,
                'service_name' => $params['service_name'] ?? $params['dienstleistung'] ?? null
            ];

            $result = $queryService->findAppointments($call, $criteria);

            Log::info('✅ Query appointment completed', [
                'call_id' => $callId,
                'success' => $result['success'],
                'appointment_count' => $result['appointment_count'] ?? 0
            ]);

            return response()->json($result, 200);

        } catch (\Exception $e) {
            Log::error('❌ Query appointment failed', [
                'call_id' => $callId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'query_error',
                'message' => 'Entschuldigung, ich konnte Ihren Termin nicht finden. Bitte versuchen Sie es erneut.'
            ], 200);
        }
    }
}