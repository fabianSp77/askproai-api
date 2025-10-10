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
            // WICHTIG: Agent sendet NUR call_id!
            $callId = $request->input('call_id');

            Log::info('🔍 Checking customer', [
                'call_id' => $callId
            ]);

            // Get phone number from call record
            $phoneNumber = null;
            $customerName = null;

            if ($callId) {
                $call = Call::where('retell_call_id', $callId)->first();
                if ($call && $call->from_number) {
                    $phoneNumber = $call->from_number;
                }
            }

            // Search for customer by phone number
            $customer = null;
            if ($phoneNumber) {
                // Normalize phone number (remove spaces, dashes, etc.)
                $normalizedPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);
                $customer = Customer::where('phone', 'LIKE', '%' . substr($normalizedPhone, -8) . '%')
                    ->first();
            }

            // If not found by phone, try by name
            if (!$customer && $customerName) {
                $customer = Customer::where('name', 'LIKE', '%' . $customerName . '%')
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
                'success' => false,
                'status' => 'not_found',
                'message' => 'Neuer Kunde',
                'customer_exists' => false,
                'suggested_action' => 'collect_customer_data'
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
            $callId = $request->input('call_id');
            $date = $request->input('date');
            $time = $request->input('time');
            $duration = $request->input('duration', 60);
            $serviceId = $request->input('service_id');

            Log::info('📅 Checking availability', [
                'call_id' => $callId,
                'date' => $date,
                'time' => $time,
                'duration' => $duration
            ]);

            // Parse date and time
            $appointmentDate = $this->parseDateTime($date, $time);

            // Get service configuration
            $service = $serviceId ? Service::find($serviceId) : Service::whereNotNull('calcom_event_type_id')->first();

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

            return response()->json([
                'success' => false,
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
                $call = Call::where('retell_call_id', $callId)->first();
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
                    'cancellation_reason' => $reason
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
                $call = Call::where('retell_call_id', $callId)->first();
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
            if (!$booking && $callId && $oldDate) {
                $parsedOldDate = $this->parseDateTime($oldDate, null);

                Log::info('🔍 Fallback search by call_id in metadata', [
                    'call_id' => $callId,
                    'old_date' => $parsedOldDate->toDateString()
                ]);

                // Use 'where' for scalar JSON values, not 'whereJsonContains' (which is for arrays)
                $booking = Appointment::where('metadata->call_id', $callId)
                    ->whereDate('starts_at', $parsedOldDate->toDateString())
                    ->where('starts_at', '>=', now())
                    ->whereIn('status', ['scheduled', 'confirmed', 'booked'])
                    // Allow appointments with or without booking IDs
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($booking) {
                    Log::info('✅ Found appointment via call_id metadata search', [
                        'booking_id' => $booking->id,
                        'starts_at' => $booking->starts_at,
                        'search_strategy' => 'call_id_metadata'
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
                    ])
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
            // FIX 2025-10-10: Use DateTimeParser service for German dates (heute, morgen, etc.)
            $dateTimeParser = app(\App\Services\Retell\DateTimeParser::class);

            // Try DateTimeParser first (handles German: heute, morgen, DD.MM.YYYY, etc.)
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
                    // Last resort: Try ISO format
                    $parsedDate = Carbon::parse($date);
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
}