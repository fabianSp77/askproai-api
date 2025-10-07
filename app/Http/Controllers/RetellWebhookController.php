<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use App\Models\PhoneNumber;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Call;
use App\Models\Appointment;
use App\Services\RetellApiClient;
use App\Services\CostCalculator;
use App\Helpers\LogSanitizer;
use App\Services\NameExtractor;
use App\Services\PlatformCostService;
use App\Services\ExchangeRateService;
use App\Services\Retell\PhoneNumberResolutionService;
use App\Services\Retell\ServiceSelectionService;
use App\Services\Retell\WebhookResponseService;
use App\Services\Retell\CallLifecycleService;
use App\Services\Retell\AppointmentCreationService;
use App\Services\Retell\BookingDetailsExtractor;
use App\Traits\LogsWebhookEvents;
use Carbon\Carbon;

class RetellWebhookController extends Controller
{
    use LogsWebhookEvents;

    private PhoneNumberResolutionService $phoneResolver;
    private ServiceSelectionService $serviceSelector;
    private WebhookResponseService $responseFormatter;
    private CallLifecycleService $callLifecycle;
    private AppointmentCreationService $appointmentCreator;
    private BookingDetailsExtractor $bookingExtractor;

    public function __construct(
        PhoneNumberResolutionService $phoneResolver,
        ServiceSelectionService $serviceSelector,
        WebhookResponseService $responseFormatter,
        CallLifecycleService $callLifecycle,
        AppointmentCreationService $appointmentCreator,
        BookingDetailsExtractor $bookingExtractor
    ) {
        $this->phoneResolver = $phoneResolver;
        $this->serviceSelector = $serviceSelector;
        $this->responseFormatter = $responseFormatter;
        $this->callLifecycle = $callLifecycle;
        $this->appointmentCreator = $appointmentCreator;
        $this->bookingExtractor = $bookingExtractor;
    }

    public function __invoke(Request $request): Response
    {
        $data = $request->json()->all();

        $webhookEvent = null;
        $shouldLogWebhooks = filter_var(config('services.retellai.log_webhooks', true), FILTER_VALIDATE_BOOL);

        if ($shouldLogWebhooks && Schema::hasTable('webhook_events')) {
            try {
                $webhookEvent = $this->logWebhookEvent($request, 'retell', $data);
            } catch (\Throwable $exception) {
                Log::warning('Failed to persist Retell webhook event', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        // Log incoming webhook for debugging (GDPR-compliant)
        Log::info('🔔 Retell Webhook received', [
            // 'webhook_event_id' => $webhookEvent->id,
            'headers' => LogSanitizer::sanitizeHeaders($request->headers->all()),
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        // 🔍 ENHANCED DEBUG: Log full payload in debug mode
        if (config('app.debug') || config('services.retellai.debug_webhooks', false)) {
            Log::debug('🔍 FULL Retell Webhook Payload', [
                'event' => $data['event'] ?? $data['event_type'] ?? 'unknown',
                'call_id' => ($data['call']['call_id'] ?? $data['call_inbound']['call_id'] ?? null),
                'full_payload' => $data,  // Complete payload for debugging
            ]);
        }

        // Log payload structure for debugging
        Log::info('Retell Webhook payload', [
            'payload_keys' => array_keys($data),
            'has_intent' => isset($data['payload']['intent']),
            'intent' => $data['payload']['intent'] ?? 'none',
        ]);

        $intent = $data['payload']['intent'] ?? null;
        $slotsData = $data['payload']['slots'] ?? [];

        // 1. Telefonnummer aus Webhook-Payload extrahieren (je nach Struktur!)
        $incomingNumber = $slotsData['to_number'] ?? $slotsData['callee'] ?? null; // Passe ggf. an, je nach Payload!

        // Check for Retell call events (call_started, call_ended, call_analyzed)
        $event = $data['event'] ?? $data['event_type'] ?? null;

        // For call_inbound, the data is in the call_inbound field
        if ($event === 'call_inbound' && isset($data['call_inbound'])) {
            $callData = $data['call_inbound'];
        } else {
            $callData = $data['call'] ?? $data;
        }

        // Log the full payload for debugging call_inbound
        if ($event === 'call_inbound') {
            Log::info('📞 Full call_inbound payload', [
                'full_data' => $data,
                'call_data' => $callData
            ]);
        }

        Log::info('🔔 Retell Call Event received', [
            'event' => $event,
            'call_id' => $callData['call_id'] ?? null,
        ]);

        // Handle different Retell events
        // Handle call_inbound event (initial webhook when call arrives)
        if ($event === 'call_inbound') {
            Log::info('🚀 Processing call_inbound event', ['event' => $event]);
            try {
                // Extract call data - check multiple possible field names
                $callId = $callData['call_id'] ?? $callData['id'] ?? null;
                $fromNumber = $callData['from_number'] ?? $callData['from'] ?? $callData['caller'] ?? null;
                $toNumber = $callData['to_number'] ?? $callData['to'] ?? $callData['callee'] ?? $incomingNumber ?? null;
                $agentId = $callData['agent_id'] ?? $callData['retell_agent_id'] ?? null;

                // Log the extracted data for debugging
                Log::info('📞 Extracted call data from webhook', [
                    'callId' => $callId,
                    'fromNumber' => $fromNumber,
                    'toNumber' => $toNumber,
                    'agentId' => $agentId,
                    'raw_call_data' => $callData
                ]);

                // SECURITY FIX (VULN-003): Resolve phone number using PhoneNumberResolutionService
                // Validates phone number is registered and resolves company/branch context

                if (!$toNumber) {
                    Log::error('Webhook rejected: Missing to_number in call_inbound', [
                        'call_id' => $callId,
                        'ip' => request()->ip(),
                    ]);
                    return $this->responseFormatter->validationError('to_number', 'Invalid webhook: to_number required');
                }

                // Resolve phone number to company/branch context
                $phoneContext = $this->phoneResolver->resolve($toNumber);

                if (!$phoneContext) {
                    return $this->responseFormatter->notFound('phone_number', 'This phone number is not configured in the system');
                }

                // Extract context
                $companyId = $phoneContext['company_id'];
                $branchId = $phoneContext['branch_id'];
                $phoneNumberId = $phoneContext['phone_number_id'];

                // Create call record even without call_id
                // If no call_id, create a temporary one based on timestamp and phone numbers
                if (!$callId && ($fromNumber || $toNumber)) {
                    $call = $this->callLifecycle->createTemporaryCall(
                        $fromNumber,
                        $toNumber,
                        $companyId,
                        $phoneNumberId,
                        $branchId,
                        $phoneContext['agent_id'] ?? $agentId
                    );

                    Log::info('✅ Call created with temporary ID (no call_id in webhook)', [
                        'call_db_id' => $call->id,
                        'temp_id' => $call->retell_call_id,
                        'from' => $fromNumber,
                        'to' => $toNumber,
                        'company_id' => $call->company_id,
                        'phone_number_id' => $call->phone_number_id,
                    ]);
                } elseif ($callId) {
                    // Normal case with call_id
                    $call = Call::firstOrCreate(
                        ['retell_call_id' => $callId],
                        [
                            'call_id' => $callId,
                            'from_number' => $fromNumber,
                            'to_number' => $toNumber,
                            'phone_number_id' => $phoneNumberId,
                            'company_id' => $companyId,
                            'branch_id' => $branchId, // NEW: Track branch for proper isolation
                            'agent_id' => $phoneContext['agent_id'],
                            'retell_agent_id' => $agentId,
                            'status' => 'inbound',
                            'direction' => 'inbound',
                            'called_at' => now(),
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );

                    Log::info('✅ Call created from inbound webhook', [
                        'call_id' => $call->id,
                        'retell_call_id' => $callId,
                        'from' => $fromNumber,
                        'to' => $toNumber,
                        'phone_number_id' => $call->phone_number_id,
                        'company_id' => $call->company_id
                    ]);
                } else {
                    Log::warning('❌ Cannot create call - no identifier available', [
                        'from' => $fromNumber,
                        'to' => $toNumber
                    ]);
                }

                return $this->responseFormatter->webhookSuccess('call_inbound');
            } catch (\Exception $e) {
                return $this->responseFormatter->serverError($e, ['call_data' => $callData]);
            }
        }

        // Handle call_started event (call has begun)
        if ($event === 'call_started') {
            return $this->handleCallStarted($data);
        }

        // Handle call_ended event (call finished but not yet analyzed)
        if ($event === 'call_ended') {
            return $this->handleCallEnded($data);
        }

        // Handle call_analyzed event (final analysis complete with transcript)
        if ($event === 'call_analyzed') {
            try {
                // Check if we already have this call
                $existingCall = Call::where('retell_call_id', $callData['call_id'] ?? null)->first();

                if ($existingCall) {
                    Log::info('Call already exists, updating with latest data', ['call_id' => $existingCall->id]);
                }

                // Sync the call data using our existing service
                $retellClient = new RetellApiClient();

                // Sync the call from Retell
                $call = $retellClient->syncCallToDatabase($callData);

                if ($call) {
                    // 🔧 BUG FIX: Only extract name from transcript if NOT already set by function call
                    // Function calls (collect_appointment_data, reschedule_appointment) provide the
                    // authoritative customer name. We should only fall back to transcript extraction
                    // when no function call provided a name.
                    if (empty($call->name) && empty($call->customer_name)) {
                        Log::info('📝 No name from function call - extracting from transcript', [
                            'call_id' => $call->retell_call_id
                        ]);

                        $nameExtractor = new NameExtractor();
                        $nameExtractor->updateCallWithExtractedName($call);
                    } else {
                        Log::info('✅ Name already set by function call - skipping transcript extraction', [
                            'call_id' => $call->retell_call_id,
                            'name' => $call->name,
                            'customer_name' => $call->customer_name
                        ]);
                    }

                    // Process insights and potential appointments
                    $this->processCallInsights($call);

                    // 🔧 EMERGENCY FIX #1: Activate customer linking service
                    if ($call->extracted_name || $call->customer_name || $call->name) {
                        try {
                            $linker = new \App\Services\DataIntegrity\CallCustomerLinkerService();
                            $match = $linker->findBestCustomerMatch($call);

                            if ($match && $match['confidence'] >= 70) {
                                Log::info('🔗 Auto-linking customer', [
                                    'call_id' => $call->id,
                                    'customer_id' => $match['customer']->id,
                                    'confidence' => $match['confidence'],
                                    'method' => $match['method']
                                ]);

                                $linker->linkCustomer(
                                    $call,
                                    $match['customer'],
                                    $match['method'],
                                    $match['confidence']
                                );
                            } elseif ($match && $match['confidence'] >= 40) {
                                Log::info('🔍 Manual review needed', [
                                    'call_id' => $call->id,
                                    'confidence' => $match['confidence']
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error('❌ Customer linking failed', [
                                'call_id' => $call->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    // 🔧 EMERGENCY FIX #2: Activate session outcome tracker
                    try {
                        $outcomeTracker = new \App\Services\DataIntegrity\SessionOutcomeTrackerService();
                        $outcomeTracker->autoDetectAndSet($call);

                        Log::info('📊 Session outcome detected', [
                            'call_id' => $call->id,
                            'outcome' => $call->session_outcome
                        ]);
                    } catch (\Exception $e) {
                        Log::error('❌ Outcome tracking failed', [
                            'call_id' => $call->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    // 🔧 EMERGENCY FIX #3: Determine call success
                    try {
                        $this->determineCallSuccess($call);
                    } catch (\Exception $e) {
                        Log::error('❌ Call success determination failed', [
                            'call_id' => $call->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    Log::info('✅ Call successfully synced via webhook', [
                        'call_id' => $call->id,
                        'retell_call_id' => $call->retell_call_id,
                        'customer_id' => $call->customer_id,
                        'notes' => $call->notes,
                    ]);

                    return $this->responseFormatter->webhookSuccess('call_analyzed', ['call_id' => $call->id]);
                }
            } catch (\Exception $e) {
                return $this->responseFormatter->serverError($e, ['call_id' => $callData['call_id'] ?? null]);
            }
        }

        // EXISTING LOGIC FOR INTENTS (booking_create, booking_cancel, etc.)
        // (keeping this for backward compatibility if Retell starts sending intents)

        switch ($intent) {
            case 'booking_create':
            case 'book':
            case 'appointment_create':
                return $this->handleBookingCreate($slotsData, $incomingNumber);

            case 'booking_cancel':
            case 'cancel':
            case 'appointment_cancel':
                return $this->handleBookingCancel($slotsData);

            case 'booking_query':
            case 'query':
            case 'appointment_query':
                return $this->handleBookingQuery($slotsData);

            default:
                // Log unknown intents for debugging
                if ($intent) {
                    Log::warning('Unknown intent received from Retell', ['intent' => $intent]);
                }

                // Return success anyway to prevent Retell from retrying
                return $this->responseFormatter->webhookSuccess('unknown_intent');
        }
    }

    /**
     * Handle call_started event - track real-time call status
     */
    private function handleCallStarted(array $data): Response
    {
        $callData = $data['call'] ?? $data;

        Log::info('📞 Call started - Real-time tracking', [
            'call_id' => $callData['call_id'] ?? null,
            'from' => $callData['from_number'] ?? null,
            'to' => $callData['to_number'] ?? null,
            'direction' => $callData['direction'] ?? null,
            'agent_id' => $callData['agent_id'] ?? null,
        ]);

        try {
            // Check if we already have this call
            $existingCall = $this->callLifecycle->findCallByRetellId($callData['call_id'] ?? 'unknown');

            // 🔥 FIX: If not found, check for recent temporary call to upgrade
            if (!$existingCall) {
                $tempCall = $this->callLifecycle->findRecentTemporaryCall();

                if ($tempCall) {
                    // Upgrade temporary call with real call_id
                    $call = $this->callLifecycle->upgradeTemporaryCall(
                        $tempCall,
                        $callData['call_id'],
                        [
                            'status' => 'ongoing',
                            'call_status' => 'ongoing',
                            'agent_id' => $callData['agent_id'] ?? null,
                            'start_timestamp' => isset($callData['start_timestamp'])
                                ? \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp'])
                                : null,
                        ]
                    );

                    Log::info('✅ Upgraded temporary call to real call_id', [
                        'call_id' => $call->id,
                        'old_retell_id' => $tempCall->retell_call_id,
                        'new_retell_id' => $call->retell_call_id,
                    ]);

                    $existingCall = $call; // Mark as existing for further processing
                }
            }

            if ($existingCall) {
                // Update existing call status (if not just upgraded)
                if ($existingCall->status !== 'ongoing') {
                    $additionalData = [];
                    if (isset($callData['start_timestamp'])) {
                        $additionalData['start_timestamp'] = \Carbon\Carbon::createFromTimestampMs($callData['start_timestamp']);
                    }

                    $call = $this->callLifecycle->updateCallStatus($existingCall, 'ongoing', $additionalData);

                    Log::info('✅ Updated existing call status to ongoing', [
                        'call_id' => $call->id,
                        'retell_call_id' => $call->retell_call_id,
                    ]);
                } else {
                    $call = $existingCall;
                }
            } else {
                // Resolve phone number using PhoneNumberResolutionService
                $phoneContext = null;
                $agentId = null;
                $companyId = 1; // Default fallback
                $phoneNumberId = null;
                $branchId = null;

                if (!empty($callData['to_number'])) {
                    $phoneContext = $this->phoneResolver->resolve($callData['to_number']);

                    if ($phoneContext) {
                        $companyId = $phoneContext['company_id'];
                        $phoneNumberId = $phoneContext['phone_number_id'];
                        $branchId = $phoneContext['branch_id'];
                        $agentId = $phoneContext['agent_id'];
                    } else {
                        Log::warning('⚠️ Phone number not found in handleCallStarted', [
                            'to_number' => $callData['to_number'],
                        ]);
                    }
                }

                // Create new call record for tracking
                $call = $this->callLifecycle->createCall(
                    $callData,
                    $companyId,
                    $phoneNumberId,
                    $branchId
                );

                Log::info('✅ Created real-time call tracking record', [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'status' => 'ongoing',
                    'phone_number_id' => $call->phone_number_id, // Verify it was set
                    'company_id' => $call->company_id,
                    'branch_id' => $call->branch_id,
                ]);
            }

            // Direkt Verfügbarkeiten mitgeben - KEIN Function Call nötig!
            $availableSlots = $this->getQuickAvailability($call->company_id ?? 1, $call->branch_id ?? null);
            $customData = [
                'verfuegbare_termine_heute' => $availableSlots['today'] ?? [],
                'verfuegbare_termine_morgen' => $availableSlots['tomorrow'] ?? [],
                'naechster_freier_termin' => $availableSlots['next'] ?? null,
            ];

            // Response mit Appointment-Daten für Retell AI
            return $this->responseFormatter->callTracking([
                'call_id' => $callData['call_id'] ?? null,
                'status' => 'ongoing',
                'response_data' => [
                    'available_appointments' => $this->formatAppointmentsForAI($availableSlots),
                    'booking_enabled' => true,
                    'calendar_status' => 'active'
                ]
            ], $customData);

        } catch (\Exception $e) {
            return $this->responseFormatter->serverError($e, ['call_id' => $callData['call_id'] ?? null]);
        }
    }

    /**
     * Handle call_ended event - update call status
     * 🔴 IMPROVED: Now syncs ALL data from Retell API (cost, latency, timing metrics, etc.)
     */
    private function handleCallEnded(array $data): Response
    {
        $callData = $data['call'] ?? $data;

        Log::info('📴 Call ended - Syncing complete data', [
            'call_id' => $callData['call_id'] ?? null,
            'duration' => $callData['duration_ms'] ?? null,
            'disconnection_reason' => $callData['disconnection_reason'] ?? null,
            'has_cost_data' => isset($callData['call_cost']),
            'has_latency_data' => isset($callData['latency']),
        ]);

        try {
            // 🟢 IMPROVED: Use full syncCallToDatabase to get ALL new fields
            // (cost, latency, timing metrics, agent_version, etc.)
            $retellClient = new RetellApiClient();
            $call = $retellClient->syncCallToDatabase($callData);

            if (!$call) {
                Log::warning('⚠️ Failed to sync call_ended data', [
                    'call_id' => $callData['call_id'] ?? null
                ]);

                // Fallback to old behavior if sync fails
                $call = $this->callLifecycle->findCallByRetellId($callData['call_id'] ?? 'unknown');

                if ($call) {
                    // Update call with end data (fallback)
                    $additionalData = [
                        'end_timestamp' => isset($callData['end_timestamp'])
                            ? \Carbon\Carbon::createFromTimestampMs($callData['end_timestamp'])
                            : now(),
                        'duration_ms' => $callData['duration_ms'] ?? null,
                        'duration_sec' => isset($callData['duration_ms']) ? round($callData['duration_ms'] / 1000) : null,
                        'disconnection_reason' => $callData['disconnection_reason'] ?? null,
                        'call_status' => 'ended',
                    ];

                    $call = $this->callLifecycle->updateCallStatus($call, 'completed', $additionalData);
                }
            } else {
                Log::info('✅ Call data fully synced via call_ended', [
                    'call_id' => $call->id,
                    'retell_call_id' => $call->retell_call_id,
                    'has_cost' => !is_null($call->cost_cents),
                    'has_timing' => !is_null($call->agent_talk_time_ms),
                    'has_latency' => !is_null($call->latency_metrics),
                ]);

                // Calculate and update costs after call ends
                try {
                    $costCalculator = new CostCalculator();
                    $costCalculator->updateCallCosts($call);

                    // Track external platform costs
                    $platformCostService = new PlatformCostService();

                    // 🔥 FIX: Use actual cost from webhook call_cost.combined_cost
                    // combined_cost includes ALL costs: Retell API + Twilio + Voice Engine + LLM + Add-ons
                    // CRITICAL: combined_cost is in CENTS, not DOLLARS! Must divide by 100!
                    if (isset($callData['call_cost']['combined_cost'])) {
                        $combinedCostCents = $callData['call_cost']['combined_cost'];
                        $retellCostUsd = $combinedCostCents / 100; // Convert CENTS to DOLLARS
                        if ($retellCostUsd > 0) {
                            Log::info('Using actual Retell cost from webhook', [
                                'call_id' => $call->id,
                                'combined_cost_cents' => $combinedCostCents,
                                'combined_cost_usd' => $retellCostUsd,
                                'source' => 'webhook.call_cost.combined_cost'
                            ]);
                            $platformCostService->trackRetellCost($call, $retellCostUsd);
                        }
                    } elseif (isset($callData['price_usd']) || isset($callData['cost_usd'])) {
                        // Backward compatibility for older webhook format
                        $retellCostUsd = $callData['price_usd'] ?? $callData['cost_usd'] ?? 0;
                        if ($retellCostUsd > 0) {
                            $platformCostService->trackRetellCost($call, $retellCostUsd);
                        }
                    } else {
                        // Fallback: Estimate Retell cost only if no actual data available
                        if ($call->duration_sec > 0) {
                            $estimatedRetellCostUsd = ($call->duration_sec / 60) * 0.10; // Updated to 0.10 USD/min (more accurate estimate)
                            Log::warning('Using estimated Retell cost (no webhook data)', [
                                'call_id' => $call->id,
                                'estimated_cost_usd' => $estimatedRetellCostUsd,
                                'duration_sec' => $call->duration_sec
                            ]);
                            $platformCostService->trackRetellCost($call, $estimatedRetellCostUsd);
                        }
                    }

                    // Track Twilio costs with intelligent estimation
                    // IMPORTANT: Retell's combined_cost does NOT include Twilio telephony costs!
                    // We need to track Twilio separately (actual from webhook OR estimated from duration)
                    if (isset($callData['call_cost']['twilio_cost']) && $callData['call_cost']['twilio_cost'] > 0) {
                        // PATH 1: Use actual Twilio cost from webhook
                        $twilioCostUsd = $callData['call_cost']['twilio_cost'];

                        Log::info('Using actual Twilio cost from webhook', [
                            'call_id' => $call->id,
                            'twilio_cost_usd' => $twilioCostUsd,
                            'source' => 'webhook.call_cost.twilio_cost'
                        ]);

                        $platformCostService->trackTwilioCost($call, $twilioCostUsd);
                    } elseif (isset($callData['twilio_cost_usd']) && $callData['twilio_cost_usd'] > 0) {
                        // PATH 1b: Alternative webhook field
                        $twilioCostUsd = $callData['twilio_cost_usd'];

                        Log::info('Using actual Twilio cost from webhook (alt field)', [
                            'call_id' => $call->id,
                            'twilio_cost_usd' => $twilioCostUsd,
                            'source' => 'webhook.twilio_cost_usd'
                        ]);

                        $platformCostService->trackTwilioCost($call, $twilioCostUsd);
                    } elseif ($this->shouldEstimateTwilioCost($call)) {
                        // PATH 2: Estimate Twilio cost based on duration
                        $estimatedTwilioCostUsd = $this->estimateTwilioCost($call);

                        if ($estimatedTwilioCostUsd > 0) {
                            $platformCostService->trackTwilioCost($call, $estimatedTwilioCostUsd);
                        }
                    } else {
                        // PATH 3: Cannot estimate (log for debugging)
                        Log::debug('Skipping Twilio cost estimation', [
                            'call_id' => $call->id,
                            'duration_sec' => $call->duration_sec,
                            'reason' => 'insufficient_duration_or_disabled'
                        ]);
                    }

                    // Update call with total external costs
                    $platformCostService->calculateCallTotalCosts($call);

                    Log::info('Call costs calculated with external platform costs', [
                        'call_id' => $call->id,
                        'base_cost' => $call->base_cost,
                        'customer_cost' => $call->customer_cost,
                        'retell_cost_usd' => $call->retell_cost_usd,
                        'twilio_cost_usd' => $call->twilio_cost_usd,
                        'total_external_cost_eur_cents' => $call->total_external_cost_eur_cents,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to calculate call costs', [
                        'call_id' => $call->id,
                        'error' => $e->getMessage()
                    ]);
                }

                Log::info('✅ Call status updated to ended', [
                    'call_id' => $call->id,
                    'duration_sec' => $call->duration_sec,
                ]);
            }

            // If we STILL don't have a call after all attempts, log warning
            if (!$call) {
                Log::warning('⚠️ No call record found or created for call_ended event', [
                    'call_id' => $callData['call_id'] ?? null,
                ]);
                return $this->responseFormatter->webhookSuccess('call_ended', ['warning' => 'no_call_record']);
            }

            // 🟢 REMOVED: Duplicate cost calculation logic (now handled by syncCallToDatabase)
            // The old code block for creating new calls was redundant and caused structural issues

            return $this->responseFormatter->webhookSuccess('call_ended');

        } catch (\Exception $e) {
            return $this->responseFormatter->serverError($e, ['call_id' => $callData['call_id'] ?? null]);
        }
    }

    private function handleBookingCreate($slotsData, $incomingNumber): Response
    {
        Log::info('Handling booking create intent', ['slots' => $slotsData, 'phone' => $incomingNumber]);

        // 2. PhoneNumber prüfen (using PhoneNumberResolutionService)
        if (!$incomingNumber) {
            return $this->responseFormatter->error('No phone number found in request');
        }

        $phoneContext = $this->phoneResolver->resolve($incomingNumber);

        if (!$phoneContext || !$phoneContext['branch_id']) {
            return $this->responseFormatter->error('Phone number not recognized');
        }

        // 3. Branch und Services laden
        $branch = Branch::with(['services', 'staffs', 'company'])->find($phoneContext['branch_id']);
        $tenant = Tenant::find($branch->tenant_id);
        $company = $branch->company;

        // 4. Slots verarbeiten
        $date = $slotsData['date'] ?? $slotsData['appointment_date'] ?? null;
        $time = $slotsData['time'] ?? $slotsData['appointment_time'] ?? null;
        $serviceName = $slotsData['service'] ?? $slotsData['service_name'] ?? null;
        $staffName = $slotsData['staff'] ?? $slotsData['staff_name'] ?? null;
        $customerName = $slotsData['customer_name'] ?? $slotsData['name'] ?? 'Walk-In Customer';
        $customerPhone = $slotsData['customer_phone'] ?? $slotsData['phone'] ?? null;

        // 5. Service und Staff zuordnen with team validation
        $service = null;
        if ($serviceName && $branch->services) {
            $service = $branch->services->first(function ($s) use ($serviceName) {
                return stripos($s->name, $serviceName) !== false;
            });
        }
        $service = $service ?: $branch->services->first();

        // 5a. Validate service belongs to company's team
        if ($service && $company && $company->hasTeam()) {
            // Validate that the service belongs to the company's team
            if (!$company->ownsService($service->calcom_event_type_id)) {
                Log::warning('Service does not belong to company team', [
                    'service_id' => $service->id,
                    'calcom_event_type_id' => $service->calcom_event_type_id,
                    'company_id' => $company->id,
                    'team_id' => $company->calcom_team_id
                ]);

                return $this->responseFormatter->error('Selected service is not available for this branch');
            }
        }

        $staff = null;
        if ($staffName && $branch->staffs) {
            $staff = $branch->staffs->first(function ($s) use ($staffName) {
                return stripos($s->name, $staffName) !== false;
            });
        }
        $staff = $staff ?: $branch->staffs->first();

        // 6. Customer anlegen oder finden
        $customer = null;
        if ($customerPhone) {
            $customer = Customer::firstOrCreate(
                ['phone' => $customerPhone],
                [
                    'name' => $customerName,
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branch->id,
                ]
            );
        }

        // 7. Appointment erstellen
        $appointmentDateTime = \Carbon\Carbon::now();
        if ($date && $time) {
            try {
                $appointmentDateTime = \Carbon\Carbon::parse("$date $time");
            } catch (\Exception $e) {
                Log::warning('Could not parse date/time', ['date' => $date, 'time' => $time]);
            }
        }

        $appointment = Appointment::create([
            'customer_id' => $customer ? $customer->id : null,
            'service_id' => $service ? $service->id : null,
            'staff_id' => $staff ? $staff->id : null,
            'branch_id' => $branch->id,
            'tenant_id' => $tenant->id,
            'appointment_datetime' => $appointmentDateTime,
            'status' => 'scheduled',
            'notes' => 'Created via Retell webhook',
            'source' => 'retell_webhook',
        ]);

        Log::info('Appointment created successfully', ['appointment_id' => $appointment->id]);

        // 8. Response für Retell
        return $this->responseFormatter->bookingConfirmed([
            'id' => $appointment->id,
            'time' => $appointmentDateTime->format('Y-m-d H:i'),
            'service' => $service ? $service->name : 'General Service',
            'staff' => $staff ? $staff->name : 'Any available staff',
            'customer' => $customerName,
        ]);
    }

    private function handleBookingCancel($slotsData): Response
    {
        Log::info('Handling booking cancel intent', ['slots' => $slotsData]);

        $appointmentId = $slotsData['appointment_id'] ?? null;
        $customerPhone = $slotsData['customer_phone'] ?? null;

        if (!$appointmentId && !$customerPhone) {
            return $this->responseFormatter->error('Need appointment ID or customer phone to cancel');
        }

        // Find appointment
        $query = Appointment::query();
        if ($appointmentId) {
            $query->where('id', $appointmentId);
        } elseif ($customerPhone) {
            $customer = Customer::where('phone', $customerPhone)->first();
            if ($customer) {
                $query->where('customer_id', $customer->id)
                      ->where('status', 'scheduled')
                      ->orderBy('appointment_datetime', 'desc');
            }
        }

        $appointment = $query->first();

        if (!$appointment) {
            return $this->responseFormatter->error('Appointment not found');
        }

        // Cancel appointment
        $appointment->update(['status' => 'cancelled']);

        return $this->responseFormatter->success(
            ['appointment_id' => $appointment->id],
            'Appointment cancelled successfully'
        );
    }

    private function handleBookingQuery($slotsData): Response
    {
        Log::info('Handling booking query intent', ['slots' => $slotsData]);

        $customerPhone = $slotsData['customer_phone'] ?? null;
        $date = $slotsData['date'] ?? null;

        if (!$customerPhone) {
            return $this->responseFormatter->error('Need customer phone to query appointments');
        }

        // Find customer
        $customer = Customer::where('phone', $customerPhone)->first();
        if (!$customer) {
            return $this->responseFormatter->success(
                ['appointments' => []],
                'No appointments found for this phone number'
            );
        }

        // Query appointments
        $query = Appointment::with(['service', 'staff'])
                            ->where('customer_id', $customer->id)
                            ->where('status', 'scheduled');

        if ($date) {
            try {
                $targetDate = \Carbon\Carbon::parse($date);
                $query->whereDate('appointment_datetime', $targetDate);
            } catch (\Exception $e) {
                Log::warning('Could not parse query date', ['date' => $date]);
            }
        }

        $appointments = $query->orderBy('appointment_datetime')->get();

        $appointmentDetails = $appointments->map(function ($apt) {
            return [
                'id' => $apt->id,
                'datetime' => $apt->appointment_datetime->format('Y-m-d H:i'),
                'service' => $apt->service ? $apt->service->name : 'General Service',
                'staff' => $apt->staff ? $apt->staff->name : 'Any staff',
            ];
        });

        return $this->responseFormatter->success(
            ['appointments' => $appointmentDetails],
            count($appointments) . ' appointment(s) found'
        );
    }

    /**
     * Process call insights and extract appointment requests
     */
    private function processCallInsights(Call $call): void
    {
        try {
            // Only process if we have a transcript
            if (empty($call->transcript)) {
                return;
            }

            // Extract insights from the transcript
            $insights = [];
            $transcript = strtolower($call->transcript);

            // FIRST: Check if Retell provided appointment data in custom_analysis_data
            $bookingDetails = null;
            $hasAppointmentRequest = false;

            // Extract booking details using BookingDetailsExtractor
            // This automatically tries Retell data first, then falls back to transcript
            if ($call->analysis && isset($call->analysis['custom_analysis_data'])) {
                $customData = $call->analysis['custom_analysis_data'];
                if (isset($customData['appointment_made']) && $customData['appointment_made'] === true) {
                    $hasAppointmentRequest = true;
                    $insights['appointment_discussed'] = true;
                }
            }

            // Check transcript for appointment keywords if no Retell flag
            if (!$hasAppointmentRequest) {
                $appointmentKeywords = ['termin', 'appointment', 'booking', 'buchen', 'vereinbaren'];
                foreach ($appointmentKeywords as $keyword) {
                    if (str_contains($transcript, $keyword)) {
                        $hasAppointmentRequest = true;
                        $insights['appointment_discussed'] = true;
                        break;
                    }
                }
            }

            // Extract booking details if appointment discussion detected
            if ($hasAppointmentRequest) {
                $bookingDetails = $this->bookingExtractor->extract($call);
            }

            if ($hasAppointmentRequest && $bookingDetails) {
                $insights['booking_details'] = $bookingDetails;

                // Only create appointment if we haven't already
                if (!$call->converted_appointment_id) {
                        Log::info('📅 Creating appointment from call transcript', [
                            'call_id' => $call->id,
                            'booking_details' => $bookingDetails
                        ]);

                        $appointment = $this->appointmentCreator->createFromCall($call, $bookingDetails);
                        if ($appointment) {
                            $insights['appointment_created'] = true;
                            $insights['appointment_id'] = $appointment->id;
                        }
                    }
            }

            // Check for specific services mentioned
            $services = ['haarschnitt', 'färben', 'tönung', 'styling', 'beratung'];
            $mentionedServices = [];
            foreach ($services as $service) {
                if (str_contains($transcript, $service)) {
                    $mentionedServices[] = $service;
                }
            }
            if (!empty($mentionedServices)) {
                $insights['services_mentioned'] = $mentionedServices;
            }

            // Update call with insights
            if (!empty($insights)) {
                $call = $this->callLifecycle->updateAnalysis($call, ['insights' => $insights]);

                Log::info('Call insights processed', [
                    'call_id' => $call->id,
                    'insights' => $insights,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to process call insights', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
        }
    }



    /**
     * Diagnostic endpoint to test webhook data flow
     */
    public function diagnostic(Request $request)
    {
        $diagnostics = [];

        // 1. Check recent calls
        $recentCalls = Call::with(['agent', 'customer', 'phoneNumber'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        $diagnostics['recent_calls'] = $recentCalls->map(function($call) {
            return [
                'id' => $call->id,
                'created' => $call->created_at?->format('Y-m-d H:i:s'),
                'from' => $call->from_number,
                'to' => $call->to_number,
                'status' => $call->status,
                'agent' => $call->agent?->name ?? 'No agent',
                'customer' => $call->customer?->name ?? 'No customer',
                'appointment_made' => $call->appointment_made,
                'duration' => $call->duration_seconds,
                'start_timestamp' => $call->start_timestamp,
                'end_timestamp' => $call->end_timestamp,
            ];
        });

        // 2. Check phone number configuration
        $phoneNumbers = \App\Models\PhoneNumber::with(['company', 'branch'])->get();
        $diagnostics['phone_numbers'] = $phoneNumbers->map(function($phone) {
            $agent = null;
            if ($phone->retell_agent_id) {
                $agent = \App\Models\RetellAgent::where('retell_agent_id', $phone->retell_agent_id)->first();
            }
            return [
                'number' => $phone->number,
                'company' => $phone->company?->name,
                'branch' => $phone->branch?->name,
                'agent_id' => $phone->agent_id,
                'retell_agent_id' => $phone->retell_agent_id,
                'linked_agent' => $agent?->name ?? 'Not found',
                'is_active' => $phone->is_active,
            ];
        });

        // 3. Test appointment alternatives
        $testDate = Carbon::tomorrow()->setTime(14, 0);
        $alternativeFinder = new AppointmentAlternativeFinder();
        try {
            $alternatives = $alternativeFinder->findAlternatives($testDate, 120, 2563193);
            $diagnostics['appointment_test'] = [
                'test_date' => $testDate->format('Y-m-d H:i'),
                'alternatives_found' => count($alternatives['alternatives'] ?? []),
                'response' => $alternatives['responseText'] ?? 'No response text',
                'alternatives' => array_map(function($alt) {
                    return [
                        'time' => $alt['datetime']->format('Y-m-d H:i'),
                        'description' => $alt['description'],
                    ];
                }, $alternatives['alternatives'] ?? []),
            ];
        } catch (\Exception $e) {
            $diagnostics['appointment_test'] = [
                'error' => $e->getMessage(),
            ];
        }

        // 4. Test Cal.com connectivity
        try {
            $calcomService = new CalcomService();
            $response = $calcomService->getAvailableSlots(
                2563193,  // eventTypeId first
                Carbon::now()->addDay()->format('Y-m-d'),
                Carbon::now()->addDays(3)->format('Y-m-d')
            );
            $responseData = $response->json();
            $slots = $responseData['data']['slots'] ?? [];
            $diagnostics['calcom_test'] = [
                'status' => 'connected',
                'slots_found' => count($slots),
                'date_range' => [
                    'from' => Carbon::now()->addDay()->format('Y-m-d'),
                    'to' => Carbon::now()->addDays(3)->format('Y-m-d'),
                ],
            ];
        } catch (\Exception $e) {
            $diagnostics['calcom_test'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        // 5. Check system settings
        $settings = \App\Models\SystemSetting::first();
        $diagnostics['system_settings'] = [
            'retell_api_configured' => !empty($settings?->retell_api_key),
            'calcom_api_configured' => !empty($settings?->calcom_api_key),
            'webhook_secret_configured' => !empty($settings?->retell_webhook_secret),
        ];

        // 6. Check ongoing calls
        $ongoingCalls = Call::whereIn('status', ['ongoing', 'in-progress', 'active'])
            ->orWhere('call_status', 'ongoing')
            ->get();
        $diagnostics['ongoing_calls'] = [
            'count' => $ongoingCalls->count(),
            'calls' => $ongoingCalls->map(function($call) {
                return [
                    'id' => $call->id,
                    'from' => $call->from_number,
                    'status' => $call->status,
                    'started' => $call->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
        ];

        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'diagnostics' => $diagnostics,
        ], 200);
    }

    /**
     * Get quick availability for immediate response to Retell
     * This avoids the need for function calls
     *
     * @param int $companyId Company ID for service selection
     * @param string|null $branchId Branch UUID for branch-specific services
     * @return array Available time slots
     */
    private function getQuickAvailability(int $companyId = 1, ?string $branchId = null): array
    {
        try {
            $service = $this->serviceSelector->getDefaultService($companyId, $branchId);
            if (!$service) {
                Log::warning('No service available for quick availability check', [
                    'company_id' => $companyId,
                    'branch_id' => $branchId
                ]);
                return [];
            }

            $today = Carbon::today();
            $tomorrow = Carbon::tomorrow();

            // Parallel API calls for 50% faster response (300-800ms vs 600-1600ms)
            $responses = Http::pool(fn ($pool) => [
                $pool->as('today')->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.calcom.api_key')
                ])->timeout(5)->acceptJson()->get($this->buildAvailabilityUrl($service, $today)),

                $pool->as('tomorrow')->withHeaders([
                    'Authorization' => 'Bearer ' . config('services.calcom.api_key')
                ])->timeout(5)->acceptJson()->get($this->buildAvailabilityUrl($service, $tomorrow)),
            ]);

            $todaySlots = $this->extractTimeSlots($responses['today']->json());
            $tomorrowSlots = $this->extractTimeSlots($responses['tomorrow']->json());

            return [
                'today' => $todaySlots,
                'tomorrow' => $tomorrowSlots,
                'next' => !empty($todaySlots) ? $todaySlots[0] : (!empty($tomorrowSlots) ? $tomorrowSlots[0] : null)
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get quick availability', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Build Cal.com availability URL with query parameters
     */
    private function buildAvailabilityUrl($service, Carbon $date): string
    {
        $query = http_build_query([
            'eventTypeId' => $service->calcom_event_type_id,
            'startTime' => $date->format('Y-m-d'),
            'endTime' => $date->format('Y-m-d'),
        ]);
        return config('services.calcom.base_url') . '/slots/available?' . $query;
    }

    /**
     * Extract time slots from Cal.com response
     */
    private function extractTimeSlots($response)
    {
        $slots = [];
        $data = $response['data']['slots'] ?? [];

        foreach ($data as $date => $daySlots) {
            foreach ($daySlots as $slot) {
                $time = Carbon::parse($slot['time']);
                $slots[] = $time->format('H:i');
            }
        }

        return $slots;
    }

    /**
     * Format appointments for AI to understand
     */
    private function formatAppointmentsForAI($availableSlots)
    {
        $formatted = [];

        if (!empty($availableSlots['today'])) {
            $formatted['heute'] = "Heute verfügbar: " . implode(', ', $availableSlots['today']) . " Uhr";
        }

        if (!empty($availableSlots['tomorrow'])) {
            $formatted['morgen'] = "Morgen verfügbar: " . implode(', ', $availableSlots['tomorrow']) . " Uhr";
        }

        if (empty($formatted)) {
            $formatted['message'] = "Momentan keine freien Termine in den nächsten 2 Tagen";
        }

        return $formatted;
    }

    /**
     * Determine if call was successful based on multiple criteria
     *
     * 🔧 EMERGENCY FIX #3: This method determines call_successful field
     * based on various signals (appointment, duration, transcript, etc.)
     *
     * @param Call $call
     * @return void
     */
    private function determineCallSuccess(\App\Models\Call $call): void
    {
        // Skip if already set (e.g., by appointment booking)
        if ($call->call_successful !== null) {
            return;
        }

        $successful = false;
        $reason = 'unknown';

        // Success criteria (in priority order)
        if ($call->appointment_made || $call->appointments()->exists()) {
            $successful = true;
            $reason = 'appointment_made';
        } elseif ($call->session_outcome === 'appointment_booked') {
            $successful = true;
            $reason = 'appointment_booked';
        } elseif ($call->session_outcome === 'information_only' && $call->duration_sec >= 30) {
            $successful = true;
            $reason = 'information_provided';
        } elseif ($call->customer_id && $call->duration_sec >= 20) {
            $successful = true;
            $reason = 'customer_interaction';
        } elseif ($call->duration_sec < 10) {
            $successful = false;
            $reason = 'too_short';
        } elseif (!$call->transcript || strlen($call->transcript) < 50) {
            $successful = false;
            $reason = 'no_meaningful_interaction';
        } else {
            // Default: if we got a transcript and >20s, consider it successful
            $successful = ($call->duration_sec >= 20 && $call->transcript);
            $reason = $successful ? 'completed_interaction' : 'unclear';
        }

        $call->call_successful = $successful;
        $call->save();

        Log::info($successful ? '✅ Call marked successful' : '❌ Call marked failed', [
            'call_id' => $call->id,
            'reason' => $reason,
            'duration' => $call->duration_sec,
            'has_transcript' => !empty($call->transcript)
        ]);
    }

    /**
     * Determine if Twilio cost estimation should be performed
     *
     * @param Call $call
     * @return bool
     */
    private function shouldEstimateTwilioCost(\App\Models\Call $call): bool
    {
        // Check if estimation is enabled in configuration
        if (!config('platform-costs.twilio.estimation.enabled', true)) {
            return false;
        }

        // Check if call has sufficient duration
        $minDuration = config('platform-costs.twilio.estimation.min_duration_sec', 1);

        return $call->duration_sec >= $minDuration;
    }

    /**
     * Estimate Twilio cost based on call duration and configured pricing
     *
     * @param Call $call
     * @return float Estimated cost in USD
     */
    private function estimateTwilioCost(\App\Models\Call $call): float
    {
        try {
            // Get pricing configuration
            $costPerMinuteUsd = config('platform-costs.twilio.pricing.inbound_per_minute_usd', 0.0085);

            // Calculate duration in minutes (use actual seconds for precision)
            $durationMinutes = $call->duration_sec / 60;

            // Calculate estimated cost
            $estimatedCostUsd = $durationMinutes * $costPerMinuteUsd;

            Log::info('Estimated Twilio cost', [
                'call_id' => $call->id,
                'duration_sec' => $call->duration_sec,
                'duration_minutes' => round($durationMinutes, 2),
                'cost_per_minute_usd' => $costPerMinuteUsd,
                'estimated_cost_usd' => round($estimatedCostUsd, 4),
                'source' => 'estimated'
            ]);

            // Sanity check: Alert if cost seems unreasonable
            if ($estimatedCostUsd < 0 || $estimatedCostUsd > 10) { // $10 = ~1000 minutes
                Log::warning('Twilio cost estimate out of expected range', [
                    'call_id' => $call->id,
                    'duration_sec' => $call->duration_sec,
                    'estimated_cost_usd' => $estimatedCostUsd,
                    'warning' => 'cost_threshold_exceeded'
                ]);
            }

            // Never return negative cost
            return max(0, $estimatedCostUsd);

        } catch (\Exception $e) {
            Log::error('Failed to estimate Twilio cost', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Fail safe: return 0 instead of breaking the webhook
            return 0;
        }
    }
}