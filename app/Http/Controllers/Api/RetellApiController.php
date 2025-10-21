<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Call;
use App\Models\PhoneNumber;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\AppointmentModification;
use App\Services\CalcomService;
use App\Services\AppointmentAlternativeFinder;
use App\Services\Policies\AppointmentPolicyEngine;
use App\Services\CustomerIdentification\PhoneticMatcher;
use App\Events\Appointments\AppointmentCancellationRequested;
use App\Events\Appointments\AppointmentRescheduled;
use App\Helpers\LogSanitizer;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

/**
 * API Controller for Retell AI Function Calls
 * Handles all API endpoints defined in Retell Agent configuration
 */
class RetellApiController extends Controller
{
    private CalcomService $calcomService;
    private AppointmentAlternativeFinder $alternativeFinder;
    private AppointmentPolicyEngine $policyEngine;
    private PhoneticMatcher $phoneticMatcher;

    public function __construct(AppointmentPolicyEngine $policyEngine)
    {
        $this->calcomService = new CalcomService();
        $this->alternativeFinder = new AppointmentAlternativeFinder();
        $this->policyEngine = $policyEngine;
        $this->phoneticMatcher = new PhoneticMatcher();
    }

    /**
     * Check if customer exists in database
     * POST /api/retell/check-customer
     */
    public function checkCustomer(Request $request)
    {
        try {
            // 🔧 FIX 2025-10-13: Retell sendet Parameter im "args" Objekt, nicht als direkte POST-Parameter
            // Same issue as in cancelAppointment() and rescheduleAppointment()
            $args = $request->input('args', []);
            $callId = $args['call_id'] ?? $request->input('call_id');  // Fallback for backward compatibility

            Log::info('🔍 Checking customer', [
                'call_id' => $callId,
                'extracted_from' => isset($args['call_id']) ? 'args_object' : 'direct_input'
            ]);

            // Get phone number from call record
            $phoneNumber = null;
            $customerName = null;
            $companyId = null;

            if ($callId) {
                // Phase 4: Eager load relationships to prevent N+1 queries
                $call = Call::with(['customer', 'company', 'branch', 'phoneNumber'])
                    ->where('retell_call_id', $callId)
                    ->first();
                if ($call && $call->from_number) {
                    $phoneNumber = $call->from_number;
                    $companyId = $call->company_id;  // 🔧 FIX 2025-10-11: Get company_id for tenant isolation
                }
            }

            // Search for customer by phone number
            $customer = null;
            if ($phoneNumber && $phoneNumber !== 'anonymous') {
                // Normalize phone number (remove spaces, dashes, etc.)
                $normalizedPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);

                // Phase 4: Cache customer lookups for 5 minutes to reduce database load
                $cacheKey = "customer:phone:" . md5($normalizedPhone) . ":company:{$companyId}";
                $customer = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function() use ($normalizedPhone, $companyId) {
                    // 🔧 FIX 2025-10-11: MULTI-TENANCY - Filter by company_id!
                    // Prevents finding wrong customer from different company
                    $query = Customer::where(function($q) use ($normalizedPhone) {
                        $q->where('phone', $normalizedPhone)
                          ->orWhere('phone', 'LIKE', '%' . substr($normalizedPhone, -8) . '%');
                    });

                    // SECURITY: Tenant isolation - only search within same company
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }

                    return $query->first();
                });

                if ($customer) {
                    Log::info('🔒 SECURITY: Tenant-isolated customer search', [
                        'company_id' => $companyId,
                        'phone_last_8' => substr($normalizedPhone, -8),
                        'cache_key' => $cacheKey
                    ]);
                }
            }

            // If not found by phone, try by name
            if (!$customer && $customerName && $companyId) {
                // 🔧 FIX 2025-10-11: Also filter by company_id for name search
                $customer = Customer::where('company_id', $companyId)
                    ->where('name', 'LIKE', '%' . $customerName . '%')
                    ->first();
            }

            if ($customer) {
                // Update call record with customer_id
                if ($callId) {
                    Call::where('retell_call_id', $callId)
                        ->update(['customer_id' => $customer->id]);
                }

                return response()->json([
                    'success' => true,
                    'status' => 'found',
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'phone' => $customer->phone,
                        'email' => $customer->email,
                        'last_visit' => $customer->last_appointment_at?->format('d.m.Y'),
                        'notes' => $customer->notes
                    ],
                    'message' => "Willkommen zurück, {$customer->name}!"
                ], 200);
            }

            return response()->json([
                'success' => true,  // ✅ NOT an error - just a new customer scenario
                'status' => 'new_customer',
                'message' => 'Dies ist ein neuer Kunde. Bitte fragen Sie nach Name und E-Mail-Adresse.',
                'customer_exists' => false,
                'customer_name' => null,
                'next_steps' => 'ask_for_customer_details',
                'suggested_prompt' => 'Kein Problem! Darf ich Ihren Namen und Ihre E-Mail-Adresse haben?'
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error checking customer', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Fehler beim Prüfen der Kundendaten'
            ], 200);
        }
    }

    /**
     * Check availability for appointment
     * POST /api/retell/check-availability
     */
    public function checkAvailability(Request $request)
    {
        try {
            Log::info('🔍 CHECKPOINT 1: checkAvailability called');

            // FIX 2025-10-20: Support BOTH flat parameters AND nested 'args' format
            // from different Retell agent versions
            $args = $request->input('args', []);

            Log::info('🔍 CHECKPOINT 2: Args extracted', ['args' => $args]);

            $callId = $args['call_id'] ?? $request->input('call_id');
            $date = $args['date'] ?? $request->input('date');
            $time = $args['time'] ?? $request->input('time');
            $duration = $args['duration'] ?? $request->input('duration', 60);
            $serviceId = $args['service_id'] ?? $request->input('service_id');

            Log::info('🔍 CHECKPOINT 3: Parameters extracted', [
                'call_id' => $callId,
                'date' => $date,
                'time' => $time
            ]);

            Log::info('📅 Checking availability', [
                'call_id' => $callId,
                'date' => $date,
                'time' => $time,
                'duration' => $duration,
                'parameter_source' => isset($args['call_id']) ? 'nested_args' : 'flat_params'
            ]);

            Log::info('🔍 CHECKPOINT 4: Before parseDateTime');

            // Parse date and time
            $appointmentDate = $this->parseDateTime($date, $time);

            Log::info('🔍 CHECKPOINT 5: After parseDateTime', [
                'parsed' => $appointmentDate->format('Y-m-d H:i')
            ]);

            // 🔧 FIX 2025-10-20: Get company_id from call context for proper service selection
            $companyId = 15; // Default to AskProAI
            if ($callId) {
                $call = Call::where('retell_call_id', $callId)->first();
                if ($call && $call->company_id) {
                    $companyId = $call->company_id;
                }
            }

            Log::info('🔍 CHECKPOINT 6: Company context', [
                'company_id' => $companyId,
                'call_id' => $callId
            ]);

            // Get service configuration
            // 🔧 FIX 2025-10-20: Use ACTIVE services only, filtered by company
            // Respects is_active flag and multi-tenant isolation
            $service = $serviceId
                ? Service::find($serviceId)
                : Service::where('company_id', $companyId)
                         ->where('is_active', 1)
                         ->whereNotNull('calcom_event_type_id')
                         ->latest()  // Newest active service
                         ->first();

            if (!$service || !$service->calcom_event_type_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service nicht konfiguriert'
                ], 200);
            }

            // Use the actual date without year mapping
            $checkDate = $appointmentDate->copy();
            // Note: Removed year mapping - Cal.com should handle 2025 dates correctly

            // Check exact availability
            $startTime = $checkDate->copy()->startOfHour();
            $endTime = $checkDate->copy()->endOfHour()->addHour();

            $response = $this->calcomService->getAvailableSlots(
                $service->calcom_event_type_id,
                $startTime->format('Y-m-d H:i:s'),
                $endTime->format('Y-m-d H:i:s')
            );

            $slots = $response->json()['data']['slots'] ?? [];
            $isAvailable = $this->isTimeAvailable($checkDate, $slots);

            if ($isAvailable) {
                return response()->json([
                    'success' => true,
                    'status' => 'available',
                    'message' => "Ja, um {$appointmentDate->format('H:i')} Uhr ist noch frei.",
                    'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                    'available' => true,
                    'available_slots' => [$appointmentDate->format('Y-m-d H:i')]
                ], 200);
            }

            // Find alternatives if not available
            $alternatives = $this->alternativeFinder->findAlternatives(
                $checkDate,
                $duration,
                $service->calcom_event_type_id
            );

            // 💾 NEW PHASE: Save appointment wish for follow-up tracking
            $this->saveAppointmentWish(
                $callId,
                $appointmentDate,
                $duration,
                $service,
                $alternatives,
                'not_available'
            );

            return response()->json([
                'success' => true,  // ✅ NOT an error - just unavailable slot (valid business scenario)
                'status' => 'unavailable',
                'message' => $alternatives['responseText'] ?? "Dieser Termin ist leider nicht verfügbar.",
                'requested_time' => $appointmentDate->format('Y-m-d H:i'),
                'available' => false,
                'alternatives' => $this->formatAlternatives($alternatives['alternatives'] ?? []),
                'available_slots' => array_map(function($alt) {
                    return $alt['datetime']->format('Y-m-d H:i');
                }, $alternatives['alternatives'] ?? [])
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error checking availability', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Fehler beim Prüfen der Verfügbarkeit'
            ], 200);
        }
    }

    /**
     * Collect appointment data
     * POST /api/retell/collect-appointment
     * Redirects to existing handler
     */
    public function collectAppointment(\App\Http\Requests\CollectAppointmentRequest $request)
    {
        // Forward to existing handler
        $handler = app(\App\Http\Controllers\RetellFunctionCallHandler::class);
        return $handler->collectAppointment($request);
    }

    /**
     * Book appointment
     * POST /api/retell/book-appointment
     */
    public function bookAppointment(Request $request)
    {
        try {
            // Agent sendet: appointment_date, appointment_time, service_type, customer_name, call_id
            $callId = $request->input('call_id');
            $date = $request->input('appointment_date'); // Agent sendet appointment_date!
            $time = $request->input('appointment_time'); // Agent sendet appointment_time!
            $customerName = $request->input('customer_name');
            $serviceType = $request->input('service_type'); // Agent sendet service_type!
            $customerPhone = $request->input('customer_phone');
            $customerEmail = $request->input('customer_email');
            $notes = $request->input('notes', '');
            $duration = $request->input('duration', 60);

            Log::info('📘 Booking appointment', [
                'call_id' => $callId,
                'date' => $date,
                'time' => $time,
                'customer' => $customerName
            ]);

            // Parse date and time
            $appointmentDate = $this->parseDateTime($date, $time);

            // Get phone number from call if not provided
            if (!$customerPhone && $callId) {
                $call = Call::where('retell_call_id', $callId)->first();
                if ($call) {
                    $customerPhone = $call->from_number;
                }
            }

            // Get service by type or use default
            $service = null;
            if ($serviceType) {
                $service = Service::where('name', 'LIKE', '%' . $serviceType . '%')
                    ->whereNotNull('calcom_event_type_id')
                    ->first();
            }
            if (!$service) {
                $service = Service::whereNotNull('calcom_event_type_id')->first();
            }

            if (!$service || !$service->calcom_event_type_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Service nicht konfiguriert'
                ], 200);
            }

            // Use the actual date without year mapping
            $bookingDate = $appointmentDate->copy();
            // Note: Removed year mapping - Cal.com should handle 2025 dates correctly

            // Get company_id from service or call
            $companyId = $service->company_id ?? $call->company_id ?? \App\Models\Company::first()?->id ?? 1;

            // Create or find customer WITH company_id
            $customer = $this->findOrCreateCustomer($customerName, $customerPhone, $customerEmail, $companyId);

            // Create booking via Cal.com with exception handling
            try {
                $booking = $this->calcomService->createBooking([
                    'eventTypeId' => $service->calcom_event_type_id,
                    'start' => $bookingDate->toIso8601String(),
                    'end' => $bookingDate->copy()->addMinutes($duration)->toIso8601String(),
                    'name' => $customerName ?: 'Kunde',
                    'email' => $customerEmail ?: 'booking@askpro.ai',
                    'phone' => $customerPhone ?: '',
                    'notes' => $notes,
                    'metadata' => [
                        'call_id' => $callId,
                        'booked_via' => 'retell_ai',
                        'original_date' => $appointmentDate->format('Y-m-d')
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Cal.com booking exception', [
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'call_id' => $callId,
                    'service_id' => $service->id,
                    'booking_date' => $bookingDate->toIso8601String()
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Der Termin konnte nicht gebucht werden. Bitte versuchen Sie es später erneut.'
                ], 200);
            }

            if ($booking->successful()) {
                $bookingData = $booking->json()['data'] ?? [];

                // Cal.com V2 API returns 'uid' not 'id'
                $bookingId = $bookingData['uid'] ?? $bookingData['id'] ?? null;

                Log::info('📅 Cal.com booking response received', [
                    'booking_data_keys' => array_keys($bookingData),
                    'booking_id' => $bookingId,
                    'has_uid' => isset($bookingData['uid']),
                    'has_id' => isset($bookingData['id']),
                    'call_id' => $callId
                ]);

                // Store booking in database
                if ($bookingId) {
                    $appointment = Appointment::create([
                        'calcom_v2_booking_id' => $bookingId,  // ✅ Correct column for V2 UIDs
                        'external_id' => $bookingId,            // ✅ Backup reference
                        'calcom_event_type_id' => $service->calcom_event_type_id,
                        'customer_id' => $customer->id ?? null,
                        'service_id' => $service->id,
                        'branch_id' => $service->branch_id,
                        'company_id' => $service->company_id,
                        'starts_at' => $bookingDate,
                        'ends_at' => $bookingDate->copy()->addMinutes($duration),
                        'status' => 'confirmed',
                        'metadata' => [
                            'call_id' => $callId,
                            'booked_via' => 'retell_ai'
                        ]
                    ]);

                    Log::info('✅ Appointment persisted to database', [
                        'appointment_id' => $appointment->id,
                        'calcom_booking_id' => $bookingId,
                        'customer_id' => $customer->id ?? null,
                        'starts_at' => $bookingDate->toDateTimeString()
                    ]);
                } else {
                    Log::error('❌ Failed to extract booking ID from Cal.com response', [
                        'booking_data' => $bookingData,
                        'call_id' => $callId,
                        'response_keys' => array_keys($bookingData)
                    ]);

                    // Return error - DO NOT continue with success response
                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Buchung konnte nicht abgeschlossen werden. Bitte versuchen Sie es erneut.'
                    ], 200);
                }

                // Update call record
                if ($callId) {
                    Call::where('retell_call_id', $callId)->update([
                        'customer_id' => $customer->id ?? null,
                        'converted_appointment_id' => $bookingId,
                        'appointment_datetime' => $appointmentDate,
                        'booking_status' => 'booked'
                    ]);
                }

                $germanDate = $appointmentDate->locale('de')->isoFormat('dddd, [den] D. MMMM');
                $germanTime = $appointmentDate->format('H:i');

                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'message' => "Perfekt! Ihr Termin am {$germanDate} um {$germanTime} Uhr ist gebucht.",
                    'appointment_id' => $bookingId, // Agent erwartet appointment_id
                    'booking' => [
                        'id' => $bookingId,
                        'date' => $appointmentDate->format('Y-m-d'),
                        'time' => $appointmentDate->format('H:i'),
                        'service' => $service->name,
                        'duration' => $duration,
                        'customer' => $customerName
                    ],
                    'confirmation' => 'Sie erhalten eine Bestätigung per SMS.'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Buchung konnte nicht durchgeführt werden. Bitte versuchen Sie es später noch einmal.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error booking appointment', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Fehler bei der Terminbuchung'
            ], 200);
        }
    }

    /**
     * Cancel appointment
     * POST /api/retell/cancel-appointment
     */
    public function cancelAppointment(Request $request)
    {
        try {
            // 🔧 BUG #7c FIX: Retell sendet Parameter im "args" Objekt, nicht als direkte POST-Parameter
            $args = $request->input('args', []);

            // Agent sendet: appointment_date, call_id, customer_name
            $callId = $args['call_id'] ?? null;
            $appointmentDate = $args['appointment_date'] ?? null; // Agent sendet appointment_date!
            $customerName = $args['customer_name'] ?? null; // WICHTIG für anonyme Anrufer!
            $reason = $args['reason'] ?? 'Vom Kunden storniert';

            Log::info('🚫 Cancelling appointment', [
                'call_id' => $callId,
                'appointment_date' => $appointmentDate,
                'customer_name' => $customerName
            ]);

            // Find booking by date and customer (via call_id)
            $booking = null;
            $customer = null;

            // Get customer from call
            $call = null;
            if ($callId) {
                // Phase 4: Eager load relationships to prevent N+1 queries
                $call = Call::with(['customer', 'company', 'branch', 'phoneNumber'])
                    ->where('retell_call_id', $callId)
                    ->first();

                // FIX 2025-10-11: PRIORITY search for anonymous callers - check THIS call's appointments FIRST
                if ($call && $call->from_number === 'anonymous' && $appointmentDate) {
                    $parsedDate = $this->parseDateTime($appointmentDate, null);

                    Log::info('🔒 SECURITY: Anonymous caller cancellation - checking THIS call appointments FIRST', [
                        'call_id' => $callId,
                        'appointment_date' => $parsedDate->toDateString(),
                        'reason' => 'Same-call policy for anonymous'
                    ]);

                    // Check appointments from THIS call only (last 30 minutes for security)
                    $booking = Appointment::where(function($q) use ($callId, $call) {
                            $q->where('metadata->retell_call_id', $callId)
                              ->orWhere('call_id', $call->id);
                        })
                        ->whereDate('starts_at', $parsedDate->toDateString())
                        ->where('starts_at', '>=', now())
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        ->where('created_at', '>=', now()->subMinutes(30))  // Only recent (same call)
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($booking) {
                        Log::info('✅ Found same-call appointment for anonymous cancellation', [
                            'appointment_id' => $booking->id,
                            'starts_at' => $booking->starts_at,
                            'created_minutes_ago' => now()->diffInMinutes($booking->created_at),
                            'policy' => 'same_call_allowed'
                        ]);

                        // Skip customer search strategies - we found the appointment via call_id
                        goto process_cancellation;
                    } else {
                        Log::warning('⚠️ No same-call appointment found for anonymous caller', [
                            'call_id' => $callId,
                            'date' => $parsedDate->toDateString(),
                            'reason' => 'Anonymous callers can only modify appointments from current call'
                        ]);

                        return response()->json([
                            'success' => false,
                            'status' => 'not_found',
                            'message' => 'Entschuldigung, ich kann diesen Termin nicht finden. Bei unterdrückter Rufnummer kann ich nur Termine aus dem aktuellen Gespräch ändern. Für ältere Termine rufen Sie bitte direkt an.'
                        ], 200);
                    }
                }

                // Continue with regular customer search strategies for non-anonymous...
                if ($call) {
                    // Strategy 1: Customer already linked to call
                    if ($call->customer_id) {
                        $customer = Customer::find($call->customer_id);
                        Log::info('✅ Found customer via call->customer_id', ['customer_id' => $customer?->id]);
                    }

                    // Strategy 2: Search by phone number (if not anonymous)
                    // ENHANCED: Phone = strong auth, name verification optional
                    if (!$customer && $call->from_number && $call->from_number !== 'anonymous') {
                        $normalizedPhone = preg_replace('/[^0-9+]/', '', $call->from_number);

                        // RATE LIMITING: Prevent brute force authentication attempts
                        // Max 3 failed attempts per hour per phone+company combination
                        $rateLimitKey = 'phone_auth:' . $normalizedPhone . ':' . $call->company_id;
                        $maxAttempts = config('features.phonetic_matching_rate_limit', 3);

                        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
                            $availableIn = RateLimiter::availableIn($rateLimitKey);

                            Log::warning('⚠️ Rate limit exceeded for phone authentication', [
                                'phone_masked' => substr($normalizedPhone, 0, 6) . '****',
                                'company_id' => $call->company_id,
                                'available_in_seconds' => $availableIn,
                                'max_attempts' => $maxAttempts
                            ]);

                            return response()->json([
                                'success' => false,
                                'status' => 'rate_limit_exceeded',
                                'message' => 'Zu viele Authentifizierungsversuche. Bitte versuchen Sie es in ' . ceil($availableIn / 60) . ' Minuten erneut.',
                                'retry_after_seconds' => $availableIn
                            ], 429);
                        }

                        // Company-scoped phone search ONLY (strict tenant isolation)
                        // SECURITY: No cross-tenant search to prevent data leakage between companies
                        $customer = Customer::where('company_id', $call->company_id)
                            ->where(function($q) use ($normalizedPhone) {
                                $q->where('phone', $normalizedPhone)
                                  ->orWhere('phone', 'LIKE', '%' . substr($normalizedPhone, -8) . '%');
                            })
                            ->first();

                        if ($customer) {
                            Log::info('✅ Found customer via phone - STRONG AUTH', LogSanitizer::sanitize([
                                'customer_id' => $customer->id,
                                'auth_method' => 'phone_number',
                                'security_level' => 'high',
                                'name_matching' => 'not_required'
                            ]));

                            // Optional: Verify name similarity for logging (nicht blockierend!)
                            if ($customerName && $customer->name !== $customerName) {
                                $phoneticEnabled = config('features.phonetic_matching_enabled', false);

                                if ($phoneticEnabled) {
                                    $similarity = $this->phoneticMatcher->similarity($customer->name, $customerName);
                                    $phoneticMatch = $this->phoneticMatcher->matches($customer->name, $customerName);

                                    Log::info('📊 Name mismatch detected (phone auth active, phonetic matching enabled)', LogSanitizer::sanitize([
                                        'db_name' => $customer->name,
                                        'spoken_name' => $customerName,
                                        'similarity' => round($similarity, 4),
                                        'phonetic_match' => $phoneticMatch,
                                        'action' => 'proceeding_with_phone_auth'
                                    ]));
                                } else {
                                    similar_text($customer->name, $customerName, $percent);
                                    Log::info('📊 Name mismatch detected (phone auth active, phonetic matching disabled)', LogSanitizer::sanitize([
                                        'db_name' => $customer->name,
                                        'spoken_name' => $customerName,
                                        'similarity' => round($percent, 2) . '%',
                                        'action' => 'proceeding_with_phone_auth'
                                    ]));
                                }
                            }

                            // Link customer to call
                            $call->update(['customer_id' => $customer->id]);

                            // Success → clear rate limit for this phone+company
                            RateLimiter::clear($rateLimitKey);
                        } else {
                            // Failed authentication → increment rate limit counter
                            RateLimiter::hit($rateLimitKey, 3600); // 1 hour decay

                            Log::info('❌ Phone authentication failed - no customer found', [
                                'phone_masked' => substr($normalizedPhone, 0, 6) . '****',
                                'company_id' => $call->company_id,
                                'attempts_remaining' => $maxAttempts - RateLimiter::attempts($rateLimitKey)
                            ]);
                        }
                    }

                    // Strategy 3: Search by customer_name (ONLY for anonymous callers)
                    // SECURITY: Require 100% exact match - no fuzzy matching without phone
                    if (!$customer && $customerName && $call->from_number === 'anonymous' && $call->company_id) {
                        Log::info('📞 Anonymous caller - EXACT name match required', LogSanitizer::sanitize([
                            'customer_name' => $customerName,
                            'company_id' => $call->company_id,
                            'security_policy' => 'exact_match_only',
                            'reason' => 'no_phone_verification'
                        ]));

                        // Only exact match allowed - no LIKE queries for security
                        $customer = Customer::where('company_id', $call->company_id)
                            ->where('name', $customerName)
                            ->first();

                        if ($customer) {
                            Log::info('✅ Found customer via EXACT name match', LogSanitizer::sanitize([
                                'customer_id' => $customer->id,
                                'auth_method' => 'name_only',
                                'security_level' => 'low',
                                'match_type' => 'exact'
                            ]));
                            $call->update(['customer_id' => $customer->id]);
                        } else {
                            Log::warning('❌ No customer found - exact name match required', LogSanitizer::sanitize([
                                'search_name' => $customerName,
                                'reason' => 'Anonymous caller requires exact name match for security'
                            ]));
                        }
                    }

                    // Strategy 4: Use customer_name from call record (extracted from transcript)
                    // SECURITY: Require 100% exact match - no fuzzy matching
                    if (!$customer && !$customerName && $call->customer_name && $call->company_id) {
                        Log::info('📞 Using customer_name from call record - EXACT match only', [
                            'customer_name' => $call->customer_name,
                            'company_id' => $call->company_id,
                            'security_policy' => 'exact_match_only'
                        ]);

                        // Only exact match allowed
                        $customer = Customer::where('company_id', $call->company_id)
                            ->where('name', $call->customer_name)
                            ->first();

                        if ($customer) {
                            Log::info('✅ Found customer via call->customer_name EXACT match', [
                                'customer_id' => $customer->id,
                                'customer_name' => $customer->name,
                                'match_type' => 'exact'
                            ]);
                        }
                    }
                }
            }

            // Find booking by customer and date
            if ($customer && $appointmentDate) {
                $parsedDate = $this->parseDateTime($appointmentDate, null);
                $booking = Appointment::where('customer_id', $customer->id)
                    ->whereDate('starts_at', $parsedDate->toDateString())
                    ->where('starts_at', '>=', now())  // Only future appointments
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                    ->where(function($q) {
                        // Check for Cal.com booking ID in any of the columns
                        $q->whereNotNull('calcom_v2_booking_id')
                          ->orWhereNotNull('calcom_booking_id')
                          ->orWhereNotNull('external_id');
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            // 🔧 BUG #7e FIX: Strategy 5 - Last resort fallback (company_id + date)
            // If customer search failed (Strategies 1-4), try finding by company + date
            // This handles the timing issue where customer_name hasn't been extracted yet
            if (!$booking && $appointmentDate && $call && $call->company_id) {
                Log::info('📞 No customer found - trying company_id + date fallback', [
                    'company_id' => $call->company_id,
                    'appointment_date' => $appointmentDate
                ]);

                $parsedDate = $this->parseDateTime($appointmentDate, null);
                $booking = Appointment::where('company_id', $call->company_id)
                    ->whereDate('starts_at', $parsedDate->toDateString())
                    ->where('starts_at', '>=', now())
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                    ->where(function($q) {
                        $q->whereNotNull('calcom_v2_booking_id')
                          ->orWhereNotNull('calcom_booking_id')
                          ->orWhereNotNull('external_id');
                    })
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($booking) {
                    Log::warning('⚠️ Found appointment via company_id + date fallback (Strategy 5)', [
                        'appointment_id' => $booking->id,
                        'company_id' => $call->company_id,
                        'date' => $parsedDate->toDateString(),
                        'customer_id' => $booking->customer_id
                    ]);
                }
            }

            if (!$booking) {
                // Provide helpful message for anonymous callers who couldn't be identified
                $isAnonymous = $call && $call->from_number === 'anonymous';
                $message = $isAnonymous && !$customer
                    ? 'Entschuldigung, ich kann Ihren Termin ohne Rufnummernanzeige nicht sicher zuordnen. Bitte rufen Sie direkt während der Öffnungszeiten an, damit wir Ihnen persönlich weiterhelfen können.'
                    : 'Kein Termin gefunden für das angegebene Datum';

                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => $message
                ], 200);
            }

            // Label for goto from anonymous same-call search
            process_cancellation:

            // Check cancellation policy
            $policyResult = $this->policyEngine->canCancel($booking);

            if (!$policyResult->allowed) {
                Log::warning('⚠️ Cancellation denied by policy', [
                    'appointment_id' => $booking->id,
                    'reason' => $policyResult->reason,
                    'details' => $policyResult->details
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'policy_violation',
                    'message' => $policyResult->reason
                ], 200);
            }

            // Resolve Cal.com booking ID from all possible columns
            $calcomBookingId = $booking->calcom_v2_booking_id
                            ?? $booking->calcom_booking_id
                            ?? $booking->external_id;

            // 🔧 BUG #8d FIX: Granular error handling - Separate critical from non-critical errors
            $warnings = [];

            // CRITICAL: Cancel via Cal.com
            try {
                $response = $this->calcomService->cancelBooking($calcomBookingId, $reason);

                if (!$response->successful()) {
                    Log::error('❌ CRITICAL: Cal.com API cancellation failed', [
                        'appointment_id' => $booking->id,
                        'calcom_booking_id' => $calcomBookingId,
                        'response' => $response->body()
                    ]);

                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Termin konnte nicht bei Cal.com storniert werden'
                    ], 200);
                }
            } catch (\Exception $e) {
                Log::error('❌ CRITICAL: Cal.com API exception', [
                    'error' => $e->getMessage(),
                    'appointment_id' => $booking->id,
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Fehler beim Stornieren bei Cal.com: ' . $e->getMessage()
                ], 200);
            }

            // CRITICAL: Update database
            try {
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'cancellation_reason' => $reason,
                    // ✅ METADATA FIX 2025-10-10: Populate cancellation tracking fields
                    'cancelled_by' => 'customer',
                    'cancellation_source' => 'retell_api'
                ]);

                // Track modification for quota/analytics
                AppointmentModification::create([
                    'appointment_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'company_id' => $booking->company_id,
                    'modification_type' => 'cancel',
                    'within_policy' => true,
                    'fee_charged' => $policyResult->fee,
                    'reason' => $reason,
                    'modified_by_type' => 'System',
                    'metadata' => [
                        'call_id' => $callId,
                        'hours_notice' => $policyResult->details['hours_notice'] ?? null,
                        'policy_required' => $policyResult->details['required_hours'] ?? null,
                        'cancelled_via' => 'retell_api'
                    ]
                ]);

                // Update call record
                if ($callId) {
                    Call::where('retell_call_id', $callId)->update([
                        'booking_status' => 'cancelled'
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ CRITICAL: Database update failed after Cal.com cancellation', [
                    'error' => $e->getMessage(),
                    'appointment_id' => $booking->id,
                    'calcom_booking_id' => $calcomBookingId,
                    'trace' => $e->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'error',
                    'message' => 'Termin wurde bei Cal.com storniert, aber Datenbankaktualisierung fehlgeschlagen'
                ], 200);
            }

            // NON-CRITICAL: Fire event for listeners (notifications, stats, etc.)
            try {
                event(new AppointmentCancellationRequested(
                    appointment: $booking->fresh(),
                    reason: $reason,
                    customer: $booking->customer,
                    fee: $policyResult->fee,
                    withinPolicy: true
                ));
            } catch (\Exception $e) {
                // ⚠️ Non-critical error: Cancellation succeeded, but notifications failed
                Log::warning('⚠️ NON-CRITICAL: Event firing failed (notifications may not be sent)', [
                    'error' => $e->getMessage(),
                    'appointment_id' => $booking->id,
                    'trace' => $e->getTraceAsString()
                ]);

                $warnings[] = 'Termin wurde storniert, aber E-Mail-Benachrichtigung konnte nicht gesendet werden';
            }

            // Prepare success response
            $germanDate = Carbon::parse($booking->starts_at)->locale('de')->isoFormat('dddd, [den] D. MMMM');
            $feeMessage = $policyResult->fee > 0
                ? " Es fällt eine Stornogebühr von " . number_format($policyResult->fee, 2) . "€ an."
                : "";

            Log::info('✅ Appointment cancelled via Retell API', [
                'appointment_id' => $booking->id,
                'call_id' => $callId,
                'fee' => $policyResult->fee,
                'within_policy' => true,
                'warnings' => count($warnings) > 0 ? $warnings : null
            ]);

            $responseData = [
                'success' => true,
                'status' => 'success',
                'message' => "Ihr Termin am {$germanDate} wurde erfolgreich storniert.{$feeMessage}",
                'cancelled_booking' => [
                    'id' => $calcomBookingId,
                    'date' => $booking->starts_at->format('Y-m-d'),
                    'time' => $booking->starts_at->format('H:i'),
                    'fee' => $policyResult->fee
                ]
            ];

            // Add warnings array if there were non-critical errors
            if (count($warnings) > 0) {
                $responseData['warnings'] = $warnings;
            }

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::error('❌ Error cancelling appointment', [
                'error' => $e->getMessage(),
                'call_id' => $callId
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Fehler beim Stornieren des Termins'
            ], 200);
        }
    }

    /**
     * Reschedule appointment
     * POST /api/retell/reschedule-appointment
     */
    public function rescheduleAppointment(Request $request)
    {
        try {
            // Retell sendet Parameter im "args" Objekt, nicht als direkte POST-Parameter
            $args = $request->input('args', []);

            // Agent sendet: old_date, new_date, new_time, call_id, customer_name
            $callId = $args['call_id'] ?? null;
            $oldDate = $args['old_date'] ?? null; // Agent sendet old_date!
            $newDate = $args['new_date'] ?? null;
            $newTime = $args['new_time'] ?? null;
            $customerName = $args['customer_name'] ?? null; // WICHTIG für anonyme Anrufer!
            $reason = $args['reason'] ?? 'Vom Kunden umgebucht';

            Log::info('🔄 Rescheduling appointment', [
                'call_id' => $callId,
                'old_date' => $oldDate,
                'new_date' => $newDate,
                'new_time' => $newTime,
                'customer_name' => $customerName
            ]);

            // Find booking by old date and customer (via call_id)
            $booking = null;
            $customer = null;

            // Get customer from call
            $call = null;
            if ($callId) {
                // Phase 4: Eager load relationships to prevent N+1 queries
                $call = Call::with(['customer', 'company', 'branch', 'phoneNumber'])
                    ->where('retell_call_id', $callId)
                    ->first();
                if ($call) {
                    // Strategy 1: Customer already linked to call
                    if ($call->customer_id) {
                        $customer = Customer::find($call->customer_id);
                        Log::info('✅ Found customer via call->customer_id', ['customer_id' => $customer?->id]);
                    }

                    // Strategy 2: Search by phone number (if not anonymous)
                    // ENHANCED: Phone = strong auth, name verification optional
                    if (!$customer && $call->from_number && $call->from_number !== 'anonymous') {
                        $normalizedPhone = preg_replace('/[^0-9+]/', '', $call->from_number);

                        // RATE LIMITING: Prevent brute force authentication attempts
                        // Max 3 failed attempts per hour per phone+company combination
                        $rateLimitKey = 'phone_auth:' . $normalizedPhone . ':' . $call->company_id;
                        $maxAttempts = config('features.phonetic_matching_rate_limit', 3);

                        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
                            $availableIn = RateLimiter::availableIn($rateLimitKey);

                            Log::warning('⚠️ Rate limit exceeded for phone authentication', [
                                'phone_masked' => substr($normalizedPhone, 0, 6) . '****',
                                'company_id' => $call->company_id,
                                'available_in_seconds' => $availableIn,
                                'max_attempts' => $maxAttempts
                            ]);

                            return response()->json([
                                'success' => false,
                                'status' => 'rate_limit_exceeded',
                                'message' => 'Zu viele Authentifizierungsversuche. Bitte versuchen Sie es in ' . ceil($availableIn / 60) . ' Minuten erneut.',
                                'retry_after_seconds' => $availableIn
                            ], 429);
                        }

                        // Company-scoped phone search ONLY (strict tenant isolation)
                        // SECURITY: No cross-tenant search to prevent data leakage between companies
                        $customer = Customer::where('company_id', $call->company_id)
                            ->where(function($q) use ($normalizedPhone) {
                                $q->where('phone', $normalizedPhone)
                                  ->orWhere('phone', 'LIKE', '%' . substr($normalizedPhone, -8) . '%');
                            })
                            ->first();

                        if ($customer) {
                            Log::info('✅ Found customer via phone - STRONG AUTH', LogSanitizer::sanitize([
                                'customer_id' => $customer->id,
                                'auth_method' => 'phone_number',
                                'security_level' => 'high',
                                'name_matching' => 'not_required'
                            ]));

                            // Optional: Verify name similarity for logging (nicht blockierend!)
                            if ($customerName && $customer->name !== $customerName) {
                                $phoneticEnabled = config('features.phonetic_matching_enabled', false);

                                if ($phoneticEnabled) {
                                    $similarity = $this->phoneticMatcher->similarity($customer->name, $customerName);
                                    $phoneticMatch = $this->phoneticMatcher->matches($customer->name, $customerName);

                                    Log::info('📊 Name mismatch detected (phone auth active, phonetic matching enabled)', LogSanitizer::sanitize([
                                        'db_name' => $customer->name,
                                        'spoken_name' => $customerName,
                                        'similarity' => round($similarity, 4),
                                        'phonetic_match' => $phoneticMatch,
                                        'action' => 'proceeding_with_phone_auth'
                                    ]));
                                } else {
                                    similar_text($customer->name, $customerName, $percent);
                                    Log::info('📊 Name mismatch detected (phone auth active, phonetic matching disabled)', LogSanitizer::sanitize([
                                        'db_name' => $customer->name,
                                        'spoken_name' => $customerName,
                                        'similarity' => round($percent, 2) . '%',
                                        'action' => 'proceeding_with_phone_auth'
                                    ]));
                                }
                            }

                            // Link customer to call
                            $call->update(['customer_id' => $customer->id]);

                            // Success → clear rate limit for this phone+company
                            RateLimiter::clear($rateLimitKey);
                        } else {
                            // Failed authentication → increment rate limit counter
                            RateLimiter::hit($rateLimitKey, 3600); // 1 hour decay

                            Log::info('❌ Phone authentication failed - no customer found', [
                                'phone_masked' => substr($normalizedPhone, 0, 6) . '****',
                                'company_id' => $call->company_id,
                                'attempts_remaining' => $maxAttempts - RateLimiter::attempts($rateLimitKey)
                            ]);
                        }
                    }

                    // Strategy 3: Search by customer_name (ONLY for anonymous callers)
                    // SECURITY: Require 100% exact match - no fuzzy matching without phone
                    if (!$customer && $customerName && $call->from_number === 'anonymous' && $call->company_id) {
                        Log::info('📞 Anonymous caller - EXACT name match required', LogSanitizer::sanitize([
                            'customer_name' => $customerName,
                            'company_id' => $call->company_id,
                            'security_policy' => 'exact_match_only',
                            'reason' => 'no_phone_verification'
                        ]));

                        // Only exact match allowed - no LIKE queries for security
                        $customer = Customer::where('company_id', $call->company_id)
                            ->where('name', $customerName)
                            ->first();

                        if ($customer) {
                            Log::info('✅ Found customer via EXACT name match', LogSanitizer::sanitize([
                                'customer_id' => $customer->id,
                                'auth_method' => 'name_only',
                                'security_level' => 'low',
                                'match_type' => 'exact'
                            ]));
                            $call->update(['customer_id' => $customer->id]);
                        } else {
                            Log::warning('❌ No customer found - exact name match required', LogSanitizer::sanitize([
                                'search_name' => $customerName,
                                'reason' => 'Anonymous caller requires exact name match for security'
                            ]));
                        }
                    }
                }
            }

            // Find booking by customer and old date
            if ($customer && $oldDate) {
                $parsedOldDate = $this->parseDateTime($oldDate, null);
                $parsedNewDate = $newDate ? $this->parseDateTime($newDate, null) : null;
                $parsedNewTime = $newTime ? $this->parseDateTime($newDate ?? $oldDate, $newTime) : null;

                Log::info('🔍 Searching appointment by date and customer', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'old_date' => $parsedOldDate->toDateString(),
                    'new_date' => $parsedNewDate?->toDateString(),
                    'new_time' => $parsedNewTime?->format('H:i'),
                    'search_method' => $call && $call->from_number === 'anonymous' ? 'name_based' : 'phone_based'
                ]);

                // Get all appointments for this customer on this date
                $query = Appointment::where('customer_id', $customer->id)
                    ->whereDate('starts_at', $parsedOldDate->toDateString())
                    ->where('starts_at', '>=', now())  // Only future appointments
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked']);

                // 🔧 FIX 2.7: Only exclude appointments if rescheduling to SAME date AND time (true no-op)
                // Don't exclude if just the TIME matches but DATE is different!
                if ($parsedNewTime && $parsedNewDate) {
                    // Only exclude if we're rescheduling to the exact same date AND time
                    if ($parsedOldDate->toDateString() === $parsedNewDate->toDateString()) {
                        $newTimeString = $parsedNewTime->format('H:i:s');
                        $query->whereTime('starts_at', '!=', $newTimeString);

                        Log::info('⏰ Preventing no-op reschedule (same date, excluding same time)', [
                            'target_time' => $newTimeString,
                            'reason' => 'old_date equals new_date'
                        ]);
                    } else {
                        Log::info('✅ Different dates - allowing same time match', [
                            'old_date' => $parsedOldDate->toDateString(),
                            'new_date' => $parsedNewDate->toDateString(),
                            'new_time' => $parsedNewTime->format('H:i')
                        ]);
                    }
                }

                // Order by start time (earliest first) - most likely to be rescheduled
                $booking = $query->orderBy('starts_at', 'asc')->first();

                // Smart fallback: If old_date == new_date, agent probably meant "today"
                if (!$booking && $parsedNewDate && $parsedOldDate->toDateString() === $parsedNewDate->toDateString()) {
                    $today = now()->toDateString();

                    Log::info('🤔 old_date == new_date detected - trying TODAY as fallback', [
                        'today' => $today,
                        'provided_date' => $parsedOldDate->toDateString()
                    ]);

                    $booking = Appointment::where('customer_id', $customer->id)
                        ->whereDate('starts_at', $today)
                        ->where('starts_at', '>=', now())
                        ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                        // Allow appointments with or without booking IDs
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($booking) {
                        Log::info('✅ Found appointment using TODAY fallback', [
                            'booking_id' => $booking->id,
                            'starts_at' => $booking->starts_at
                        ]);
                        // Update parsedOldDate for reschedule logic
                        $parsedOldDate = now()->startOfDay();
                    }
                }

                if ($booking) {
                    Log::info('✅ Found appointment for rescheduling', [
                        'booking_id' => $booking->id,
                        'customer_id' => $booking->customer_id,
                        'company_id' => $booking->company_id,
                        'starts_at' => $booking->starts_at,
                        'current_time' => $booking->starts_at->format('H:i'),
                        'target_time' => $parsedNewTime?->format('H:i'),
                        'has_calcom_booking' => !empty($booking->calcom_booking_id) || !empty($booking->calcom_v2_booking_id),
                        'calcom_booking_id' => $booking->calcom_booking_id,
                        'search_strategy' => 'customer_and_date'
                    ]);

                    // Validate no-op reschedule
                    if ($parsedNewTime && $booking->starts_at->format('H:i') === $parsedNewTime->format('H:i')) {
                        Log::warning('⚠️ Appointment already at target time - no reschedule needed', [
                            'booking_id' => $booking->id,
                            'current_time' => $booking->starts_at->format('H:i'),
                            'target_time' => $parsedNewTime->format('H:i')
                        ]);

                        return response()->json([
                            'result' => 'Der Termin ist bereits zur gewünschten Uhrzeit. Keine Änderung erforderlich.'
                        ]);
                    }
                } else {
                    Log::warning('❌ No appointment found for rescheduling', [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'search_date' => $parsedOldDate->toDateString(),
                        'target_time' => $parsedNewTime?->format('H:i')
                    ]);
                }
            }

            // Strategy 4: Fallback search by call_id in metadata (for same-call reschedules)
            // FIX 2025-10-11: PRIORITY for anonymous callers - check THIS call first!
            if (!$booking && $callId && $oldDate) {
                $parsedOldDate = $this->parseDateTime($oldDate, null);

                Log::info('🔍 Fallback search by call_id in metadata (PRIORITY for anonymous)', [
                    'call_id' => $callId,
                    'old_date' => $parsedOldDate->toDateString(),
                    'is_anonymous' => $call && $call->from_number === 'anonymous'
                ]);

                // SECURITY: For anonymous callers, ONLY allow reschedule of appointments from THIS call
                // Check both metadata->retell_call_id AND direct call_id match
                $query = Appointment::where(function($q) use ($callId, $call) {
                        $q->where('metadata->retell_call_id', $callId)
                          ->orWhere('call_id', $call ? $call->id : null);
                    })
                    ->whereDate('starts_at', $parsedOldDate->toDateString())
                    ->where('starts_at', '>=', now())
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked']);

                // Additional security for anonymous: Only recent appointments (last 30 minutes)
                if ($call && $call->from_number === 'anonymous') {
                    $query->where('created_at', '>=', now()->subMinutes(30));

                    Log::info('🔒 SECURITY: Anonymous caller - restricting to appointments from THIS call only', [
                        'call_id' => $callId,
                        'time_window' => '30 minutes',
                        'reason' => 'Cannot verify identity for old appointments'
                    ]);
                }

                $booking = $query->orderBy('created_at', 'desc')->first();

                if ($booking) {
                    Log::info('✅ Found appointment via call_id metadata search', [
                        'booking_id' => $booking->id,
                        'starts_at' => $booking->starts_at,
                        'search_strategy' => 'call_id_metadata',
                        'is_same_call' => true,
                        'created_minutes_ago' => now()->diffInMinutes($booking->created_at)
                    ]);
                }
            }

            if (!$booking) {
                Log::warning('❌ No appointment found for rescheduling', [
                    'customer_found' => $customer ? true : false,
                    'customer_id' => $customer?->id,
                    'old_date' => $oldDate,
                    'call_id' => $callId,
                    'search_method' => $call && $call->from_number === 'anonymous' ? 'anonymous_caller' : 'normal',
                    'tried_strategies' => ['customer_and_date', 'call_id_metadata']
                ]);

                // Provide helpful message for anonymous callers who couldn't be identified
                $isAnonymous = $call && $call->from_number === 'anonymous';
                $message = $isAnonymous && !$customer
                    ? 'Entschuldigung, ich kann Ihren Termin ohne Rufnummernanzeige nicht sicher zuordnen. Bitte rufen Sie direkt während der Öffnungszeiten an, damit wir Ihnen persönlich weiterhelfen können.'
                    : 'Kein Termin zum Umbuchen am angegebenen Datum gefunden';

                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => $message
                ], 200);
            }

            // Check reschedule policy
            $policyResult = $this->policyEngine->canReschedule($booking);

            if (!$policyResult->allowed) {
                Log::warning('⚠️ Reschedule denied by policy', [
                    'appointment_id' => $booking->id,
                    'reason' => $policyResult->reason,
                    'details' => $policyResult->details
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'policy_violation',
                    'message' => $policyResult->reason
                ], 200);
            }

            // Parse new date and time
            $newAppointmentDate = $this->parseDateTime($newDate, $newTime);

            // Use the actual date without year mapping
            $rescheduleDate = $newAppointmentDate->copy();
            // Note: Removed year mapping - Cal.com should handle 2025 dates correctly

            // Get duration from original booking
            $duration = $booking->starts_at->diffInMinutes($booking->ends_at);

            // 🔧 FIX 2025-10-11: CHECK AVAILABILITY before rescheduling!
            // Prevents rescheduling to already occupied slots
            $service = Service::find($booking->service_id);
            if ($service && $service->calcom_event_type_id) {
                Log::info('🔍 Checking availability before reschedule', [
                    'target_datetime' => $rescheduleDate->toIso8601String(),
                    'service_id' => $service->id,
                    'event_type_id' => $service->calcom_event_type_id
                ]);

                // Check 1-hour window around target time
                $checkStart = $rescheduleDate->copy()->subMinutes(30);
                $checkEnd = $rescheduleDate->copy()->addMinutes(30);

                $availabilityResponse = $this->calcomService->getAvailableSlots(
                    $service->calcom_event_type_id,
                    $checkStart->format('Y-m-d H:i:s'),
                    $checkEnd->format('Y-m-d H:i:s')
                );

                $slots = $availabilityResponse->json()['data']['slots'] ?? [];
                $isAvailable = $this->isTimeAvailable($rescheduleDate, $slots);

                if (!$isAvailable) {
                    Log::warning('⚠️ Target time not available for reschedule', [
                        'target_time' => $rescheduleDate->format('Y-m-d H:i'),
                        'reason' => 'Slot occupied or not available'
                    ]);

                    // Find alternatives near the requested time
                    // 🔧 FIX 2025-10-13: Pass customer_id to filter out existing appointments
                    $alternatives = $this->alternativeFinder->findAlternatives(
                        $rescheduleDate,
                        $duration,
                        $service->calcom_event_type_id,
                        $booking->customer_id  // Pass customer ID to prevent offering conflicting times
                    );

                    return response()->json([
                        'success' => false,
                        'status' => 'unavailable',
                        'message' => "Der gewünschte Termin um {$rescheduleDate->format('H:i')} Uhr ist leider nicht verfügbar. " .
                                    ($alternatives['responseText'] ?? "Möchten Sie eine andere Zeit?"),
                        'requested_time' => $rescheduleDate->format('Y-m-d H:i'),
                        'alternatives' => $this->formatAlternatives($alternatives['alternatives'] ?? [])
                    ], 200);
                }

                Log::info('✅ Target time available for reschedule', [
                    'target_time' => $rescheduleDate->format('Y-m-d H:i')
                ]);
            }

            // Resolve Cal.com booking ID from all possible columns
            $calcomBookingId = $booking->calcom_v2_booking_id
                            ?? $booking->calcom_booking_id
                            ?? $booking->external_id;

            // Validate booking ID before attempting Cal.com API call
            if ($calcomBookingId && !$this->isValidCalcomBookingId($calcomBookingId)) {
                Log::warning('⚠️ Invalid Cal.com booking ID detected - skipping Cal.com sync', [
                    'appointment_id' => $booking->id,
                    'invalid_booking_id' => $calcomBookingId,
                    'reason' => 'Dummy/test value detected'
                ]);
                $calcomBookingId = null; // Force database-only update
            }

            // Get timezone from appointment or default to Europe/Berlin
            // Must be defined BEFORE transaction for variable scope
            $timezone = $booking->booking_timezone ?? 'Europe/Berlin';

            // Check if this appointment was booked via Cal.com
            $calcomSuccess = true;
            $newCalcomBookingId = null; // Track new booking ID from Cal.com
            if ($calcomBookingId) {
                // Reschedule via Cal.com with exception handling
                Log::info('📅 Rescheduling via Cal.com', [
                    'calcom_booking_id' => $calcomBookingId,
                    'column_used' => $booking->calcom_v2_booking_id ? 'v2' : ($booking->calcom_booking_id ? 'v1' : 'external'),
                    'new_datetime' => $rescheduleDate->toIso8601String()
                ]);

                try {
                    $response = $this->calcomService->rescheduleBooking(
                        $calcomBookingId,
                        $rescheduleDate->toIso8601String(),
                        $reason,
                        $timezone
                    );

                    $calcomSuccess = $response->successful();

                    if ($calcomSuccess) {
                        // CRITICAL: Cal.com creates a NEW booking when rescheduling
                        // Extract the new booking UID from response
                        $responseData = $response->json();
                        $newCalcomBookingId = $responseData['data']['uid'] ?? $responseData['data']['id'] ?? null;

                        Log::info('✅ Cal.com reschedule successful - NEW booking created', [
                            'old_booking_id' => $calcomBookingId,
                            'new_booking_id' => $newCalcomBookingId,
                            'appointment_id' => $booking->id,
                            'response' => $responseData
                        ]);
                    } else {
                        Log::warning('⚠️ Cal.com reschedule failed, updating database only', [
                            'calcom_booking_id' => $calcomBookingId,
                            'status' => $response->status()
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Cal.com reschedule exception', [
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'calcom_booking_id' => $calcomBookingId,
                        'appointment_id' => $booking->id
                    ]);

                    // Provide better error messages based on error type
                    if (str_contains($e->getMessage(), '500') || str_contains($e->getMessage(), 'not found')) {
                        return response()->json([
                            'success' => false,
                            'status' => 'error',
                            'message' => 'Dieser Termin kann leider nicht online umgebucht werden. Bitte rufen Sie uns direkt an.'
                        ], 200);
                    }

                    return response()->json([
                        'success' => false,
                        'status' => 'error',
                        'message' => 'Der Termin konnte nicht umgebucht werden. Bitte versuchen Sie es später erneut.'
                    ], 200);
                }
            } else {
                // No Cal.com booking ID - this is a manually created appointment
                Log::info('📝 No Cal.com booking ID - updating database only', [
                    'appointment_id' => $booking->id
                ]);
            }

            // Store old start time before update
            $oldStartsAt = $booking->starts_at->copy();

            // Wrap database operations in transaction for atomicity
            // Either ALL succeed or ALL rollback - no partial updates
            DB::transaction(function () use ($booking, $rescheduleDate, $duration, $timezone, $callId, $calcomBookingId, $newCalcomBookingId, $calcomSuccess, $oldStartsAt, $policyResult, $reason) {
                // Decode metadata if it's a JSON string (Laravel sometimes returns JSON as string)
                $currentMetadata = $booking->metadata;
                if (is_string($currentMetadata)) {
                    $currentMetadata = json_decode($currentMetadata, true) ?? [];
                } elseif (!is_array($currentMetadata)) {
                    $currentMetadata = [];
                }

                // Prepare update data
                $updateData = [
                    'starts_at' => $rescheduleDate,
                    'ends_at' => $rescheduleDate->copy()->addMinutes($duration),
                    'booking_timezone' => $timezone ?? 'Europe/Berlin', // Preserve timezone
                    'metadata' => array_merge($currentMetadata, [
                        'rescheduled_at' => now()->toIso8601String(),
                        'rescheduled_via' => 'retell_api',
                        'call_id' => $callId,
                        'calcom_synced' => $calcomBookingId ? $calcomSuccess : false,
                        'previous_booking_id' => $calcomBookingId // Track old booking ID
                    ]),
                    // ✅ METADATA FIX 2025-10-10: Populate reschedule tracking fields
                    'rescheduled_at' => now(),
                    'rescheduled_by' => 'customer',
                    'reschedule_source' => 'retell_api',
                    'previous_starts_at' => $oldStartsAt
                ];

                // CRITICAL: Update booking ID if Cal.com returned a new one
                if ($newCalcomBookingId && $calcomSuccess) {
                    // Determine which column to update based on which was used
                    if ($booking->calcom_v2_booking_id) {
                        $updateData['calcom_v2_booking_id'] = $newCalcomBookingId;
                        Log::info('🔄 Updating V2 booking ID', [
                            'old' => $calcomBookingId,
                            'new' => $newCalcomBookingId
                        ]);
                    } elseif ($booking->calcom_booking_id) {
                        $updateData['calcom_booking_id'] = $newCalcomBookingId;
                        Log::info('🔄 Updating V1 booking ID', [
                            'old' => $calcomBookingId,
                            'new' => $newCalcomBookingId
                        ]);
                    } else {
                        $updateData['external_id'] = $newCalcomBookingId;
                        Log::info('🔄 Updating external booking ID', [
                            'old' => $calcomBookingId,
                            'new' => $newCalcomBookingId
                        ]);
                    }
                }

                // Update database (regardless of Cal.com success)
                $booking->update($updateData);

                // Track modification for quota/analytics
                Log::info('🚀 RESCHEDULE CODE - VERSION 12:36 - BOOKING ID UPDATE FIX', [
                    'company_id' => $booking->company_id,
                    'booking_id' => $booking->id,
                    'new_calcom_id' => $newCalcomBookingId,
                    'old_calcom_id' => $calcomBookingId
                ]);

                AppointmentModification::create([
                    'appointment_id' => $booking->id,
                    'customer_id' => $booking->customer_id,
                    'company_id' => $booking->company_id,
                    'modification_type' => 'reschedule',
                    'within_policy' => true,
                    'fee_charged' => $policyResult->fee,
                    'reason' => $reason,
                    'modified_by_type' => 'System',
                    'metadata' => [
                        'call_id' => $callId,
                        'hours_notice' => $policyResult->details['hours_notice'] ?? null,
                        'original_time' => $oldStartsAt->toIso8601String(),
                        'new_time' => $rescheduleDate->toIso8601String(),
                        'rescheduled_via' => 'retell_api',
                        'calcom_synced' => $calcomBookingId ? $calcomSuccess : false
                    ]
                ]);

                // Update call record - link to rescheduled appointment
                if ($callId) {
                    Call::where('retell_call_id', $callId)->update([
                        'converted_appointment_id' => $booking->id,
                        'appointment_made' => true
                    ]);

                    Log::info('✅ Updated call record with rescheduled appointment', [
                        'call_id' => $callId,
                        'appointment_id' => $booking->id
                    ]);
                }
            }); // End DB::transaction

            // 🔧 BUG #8d FIX: Wrap event firing in non-critical try-catch
            $warnings = [];

            // NON-CRITICAL: Fire event for listeners (notifications, stats, etc.) - AFTER transaction commits
            try {
                event(new AppointmentRescheduled(
                    appointment: $booking->fresh(),
                    oldStartTime: $oldStartsAt,
                    newStartTime: $rescheduleDate,
                    reason: $reason,
                    fee: $policyResult->fee,
                    withinPolicy: true
                ));
            } catch (\Exception $e) {
                // ⚠️ Non-critical error: Reschedule succeeded, but notifications failed
                Log::warning('⚠️ NON-CRITICAL: Event firing failed (notifications may not be sent)', [
                    'error' => $e->getMessage(),
                    'appointment_id' => $booking->id,
                    'trace' => $e->getTraceAsString()
                ]);

                $warnings[] = 'Termin wurde umgebucht, aber E-Mail-Benachrichtigung konnte nicht gesendet werden';
            }

            Log::info('✅ Appointment rescheduled via Retell API', [
                'appointment_id' => $booking->id,
                'call_id' => $callId,
                'old_time' => $oldStartsAt->toIso8601String(),
                'new_time' => $rescheduleDate->toIso8601String(),
                'fee' => $policyResult->fee,
                'within_policy' => true,
                'warnings' => count($warnings) > 0 ? $warnings : null
            ]);

            $oldGermanDate = $oldStartsAt->locale('de')->isoFormat('dddd, [den] D. MMMM');
            $newGermanDate = $newAppointmentDate->locale('de')->isoFormat('dddd, [den] D. MMMM');
            $feeMessage = $policyResult->fee > 0
                ? " Es fällt eine Umbuchungsgebühr von " . number_format($policyResult->fee, 2) . "€ an."
                : "";

            $responseData = [
                'success' => true,
                'status' => 'success',
                'message' => "Ihr Termin wurde vom {$oldGermanDate} auf {$newGermanDate} um {$newAppointmentDate->format('H:i')} Uhr umgebucht.{$feeMessage}",
                'rescheduled_booking' => [
                    'id' => $booking->id,
                    'old_date' => $oldStartsAt->format('Y-m-d'),
                    'old_time' => $oldStartsAt->format('H:i'),
                    'new_date' => $newAppointmentDate->format('Y-m-d'),
                    'new_time' => $newAppointmentDate->format('H:i'),
                    'fee' => $policyResult->fee,
                    'calcom_synced' => $calcomBookingId ? $calcomSuccess : null
                ]
            ];

            // Add warnings array if there were non-critical errors
            if (count($warnings) > 0) {
                $responseData['warnings'] = $warnings;
            }

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            Log::error('❌ Error rescheduling appointment', [
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'call_id' => $callId ?? null,
                'booking_id' => $booking->id ?? null,
                'customer_name' => $customerName ?? null,
                'old_date' => $oldDate ?? null,
                'new_date' => $newDate ?? null,
                'new_time' => $newTime ?? null
            ]);

            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Fehler beim Umbuchen des Termins'
            ], 200);
        }
    }

    /**
     * Parse date and time from various formats
     */
    private function parseDateTime($date, $time)
    {
        try {
            // 🔧 FIX 2025-10-20: Handle ISO format FIRST (from Retell Agent)
            // ISO format: YYYY-MM-DD (e.g., "2025-10-20")
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $parsedDate = Carbon::parse($date);

                Log::debug('✅ Parsed ISO date directly', [
                    'input' => $date,
                    'parsed' => $parsedDate->format('Y-m-d')
                ]);
            } else {
                // Use DateTimeParser for German dates (heute, morgen, DD.MM.YYYY, etc.)
                $dateTimeParser = app(\App\Services\Retell\DateTimeParser::class);
                $parsedDateString = $dateTimeParser->parseDateString($date);

                if ($parsedDateString) {
                    $parsedDate = Carbon::parse($parsedDateString);
                } else {
                    // Fallback: Handle German date format (DD.MM.YYYY or DD.MM.)
                    if (strpos($date, '.') !== false) {
                        $dateParts = explode('.', $date);
                        $day = intval($dateParts[0]);
                        $month = intval($dateParts[1]);
                        $year = isset($dateParts[2]) ? intval($dateParts[2]) : Carbon::now()->year;

                        $parsedDate = Carbon::create($year, $month, $day);
                    } else {
                        // Last resort: Try Carbon parse
                        $parsedDate = Carbon::parse($date);
                    }
                }
            }

            // Parse time (HH:MM or just HH)
            if ($time) {
                if (strpos($time, ':') !== false) {
                    list($hour, $minute) = explode(':', $time);
                } else {
                    $hour = intval($time);
                    $minute = 0;
                }
                $parsedDate->setTime($hour, $minute);
            }

            return $parsedDate;

        } catch (\Exception $e) {
            // Log parse failure for debugging
            Log::warning('⚠️ Date/time parsing failed - using default', [
                'date_input' => $date,
                'time_input' => $time,
                'error' => $e->getMessage(),
                'default_returned' => 'tomorrow 10:00'
            ]);

            // Default to tomorrow at 10 AM if parsing fails
            return Carbon::tomorrow()->setTime(10, 0);
        }
    }

    /**
     * Check if a specific time is available in slots
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
     * Format alternatives for response
     */
    private function formatAlternatives(array $alternatives): array
    {
        return array_map(function($alt) {
            return [
                'time' => $alt['datetime']->format('Y-m-d H:i'),
                'spoken' => $alt['description'],
                'available' => true
            ];
        }, $alternatives);
    }

    /**
     * Find or create customer
     */
    private function findOrCreateCustomer($name, $phone, $email, int $companyId)
    {
        // 🔧 BUG FIX: We need at least a name to create a customer
        // Anonymous callers provide name without phone - this is valid!
        if (!$name) {
            return null;
        }

        // Try to find existing customer by phone first
        if ($phone) {
            $normalizedPhone = preg_replace('/[^0-9+]/', '', $phone);
            $customer = Customer::where('phone', 'LIKE', '%' . substr($normalizedPhone, -8) . '%')->first();
            if ($customer) {
                return $customer;
            }
        }

        // Try to find existing customer by name (for anonymous callers)
        if (!$phone && $name) {
            $customer = Customer::where('company_id', $companyId)
                ->where('name', $name)
                ->first();
            if ($customer) {
                Log::info('✅ Found existing customer by name for anonymous caller', [
                    'customer_id' => $customer->id,
                    'name' => $name
                ]);
                return $customer;
            }
        }

        // 🔧 FIX 2.5: Create customer with company_id using new instance pattern
        // We need company_id in the INSERT to satisfy NOT NULL constraint
        $customer = new Customer();
        $customer->company_id = $companyId;
        $customer->forceFill([
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'source' => 'retell_ai'
        ]);
        $customer->save();

        Log::info('✅ Created new customer', [
            'customer_id' => $customer->id,
            'name' => $name,
            'has_phone' => !empty($phone),
            'is_anonymous_booking' => empty($phone)
        ]);

        return $customer;
    }

    /**
     * Validate if Cal.com booking ID is a valid format
     *
     * Rejects obvious test/dummy values that would cause Cal.com API errors
     *
     * @param mixed $bookingId The booking ID to validate
     * @return bool True if valid, false if dummy/test value
     */
    private function isValidCalcomBookingId($bookingId): bool
    {
        // Reject null or empty values
        if (empty($bookingId)) {
            return false;
        }

        // Convert to string for comparison
        $bookingIdStr = (string) $bookingId;

        // Reject obvious test/dummy numeric values
        $dummyValues = [
            '99999999',
            '00000000',
            '11111111',
            '12345678',
            '87654321',
            '0',
            '1',
            '-1'
        ];

        if (in_array($bookingIdStr, $dummyValues, true)) {
            return false;
        }

        // Reject obvious test string values
        $dummyStrings = ['test', 'dummy', 'fake', 'example', 'sample', 'placeholder'];
        $lowerBookingId = strtolower($bookingIdStr);
        foreach ($dummyStrings as $dummy) {
            if (str_contains($lowerBookingId, $dummy)) {
                return false;
            }
        }

        // Cal.com V2 booking UIDs are typically 20+ character alphanumeric strings
        // V1 IDs are integers but not obvious dummy values (already checked above)
        // Allow anything that doesn't match dummy patterns
        return true;
    }

    /**
     * 💾 Save unfulfilled appointment wish for follow-up tracking
     *
     * Creates AppointmentWish record when:
     * - Check-availability returns no available slots
     * - Customer declines offered alternatives
     * - Booking confidence is too low
     */
    private function saveAppointmentWish(
        ?string $callId,
        Carbon $desiredDateTime,
        int $duration,
        Service $service,
        array $alternatives,
        string $rejectionReason = 'not_available'
    ): void {
        try {
            if (!$callId) {
                return; // No call context, skip wish tracking
            }

            $call = Call::where('retell_call_id', $callId)->first();
            if (!$call) {
                return; // Call not found
            }

            // 💾 Create wish record
            \App\Models\AppointmentWish::create([
                'company_id' => $call->company_id,
                'branch_id' => $call->branch_id,
                'call_id' => $call->id,
                'customer_id' => $call->customer_id,
                'desired_date' => $desiredDateTime,
                'desired_time' => $desiredDateTime->format('H:i'),
                'desired_duration' => $duration,
                'desired_service' => $service->name,
                'alternatives_offered' => collect($alternatives['alternatives'] ?? [])->map(function($alt) {
                    return [
                        'datetime' => $alt['datetime']->format('Y-m-d H:i'),
                        'type' => $alt['type'] ?? null,
                        'description' => $alt['description'] ?? null
                    ];
                })->toArray(),
                'rejection_reason' => $rejectionReason,
                'status' => 'pending',
                'metadata' => [
                    'call_retell_id' => $callId,
                    'service_id' => $service->id,
                    'source' => 'check_availability_api'
                ]
            ]);

            Log::info('💾 Appointment wish recorded', [
                'call_id' => $call->id,
                'customer_id' => $call->customer_id,
                'desired_date' => $desiredDateTime->format('Y-m-d H:i'),
                'rejection_reason' => $rejectionReason,
                'alternatives_count' => count($alternatives['alternatives'] ?? [])
            ]);

            // 📧 Trigger notification event (handled by listener in Phase 4)
            event(new \App\Events\AppointmentWishCreated(
                \App\Models\AppointmentWish::where('call_id', $call->id)->latest()->first(),
                $call
            ));

        } catch (\Exception $e) {
            Log::error('❌ Failed to save appointment wish', [
                'error' => $e->getMessage(),
                'call_id' => $callId,
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - continue with response even if wish tracking fails
        }
    }
}