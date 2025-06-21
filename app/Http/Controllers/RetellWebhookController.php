<?php

namespace App\Http\Controllers;

use App\Services\WebhookProcessor;
use App\Services\CalcomV2Service;
use App\Services\Calcom\CalcomAvailabilityService;
use App\Services\RateLimiter\ApiRateLimiter;
use App\Models\Company;
use App\Models\WebhookEvent;
use App\Http\Requests\Webhook\RetellWebhookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RetellWebhookController extends Controller
{
    protected WebhookProcessor $webhookProcessor;
    protected ApiRateLimiter $rateLimiter;
    
    public function __construct(WebhookProcessor $webhookProcessor, ApiRateLimiter $rateLimiter)
    {
        $this->webhookProcessor = $webhookProcessor;
        $this->rateLimiter = $rateLimiter;
    }
    
    /**
     * Process Retell webhook using the new WebhookProcessor service
     */
    public function processWebhook(RetellWebhookRequest $request)
    {
        // Check rate limit
        if (!$this->rateLimiter->checkWebhook('retell', $request->ip())) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many requests. Please slow down.'
            ], 429);
        }
        
        // DEBUG: Log all incoming webhooks
        Log::warning('RETELL WEBHOOK RECEIVED', [
            'url' => $request->fullUrl(),
            'event' => $request->input('event'),
            'has_call_data' => $request->has('call'),
            'headers' => $request->headers->all()
        ]);
        
        $correlationId = $request->input('correlation_id') ?? Str::uuid()->toString();
        $payload = $request->all();
        $headers = $request->headers->all();
        
        try {
            // Special handling for call_inbound events (synchronous)
            if ($request->input('event') === 'call_inbound') {
                return $this->handleInboundCall($request);
            }
            
            // Process webhook through the WebhookProcessor service
            $result = $this->webhookProcessor->process(
                WebhookEvent::PROVIDER_RETELL,
                $payload,
                $headers,
                $correlationId
            );
            
            // Return appropriate response based on processing result
            if ($result['duplicate']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook already processed',
                    'duplicate' => true,
                    'correlation_id' => $correlationId
                ], 200);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'correlation_id' => $correlationId
            ], 200);
            
        } catch (\App\Exceptions\WebhookSignatureException $e) {
            Log::error('Retell webhook signature verification failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId
            ]);
            
            return response()->json([
                'error' => 'Invalid signature',
                'message' => $e->getMessage()
            ], 401);
            
        } catch (\Exception $e) {
            Log::error('Failed to process Retell webhook', [
                'error' => $e->getMessage(),
                'correlation_id' => $correlationId,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Still return success to avoid webhook retries from Retell
            return response()->json([
                'success' => true,
                'message' => 'Webhook received',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 200);
        }
    }
    
    /**
     * Handle inbound calls synchronously for real-time availability checking
     */
    private function handleInboundCall(Request $request)
    {
        try {
            $callData = $request->all();
            
            Log::info('Handling inbound call for real-time response', [
                'call_id' => $callData['call_id'] ?? 'unknown',
                'from_number' => $callData['call_inbound']['from_number'] ?? null
            ]);
            
            // Get company based on the to_number
            $toNumber = $callData['call_inbound']['to_number'] ?? null;
            $company = Company::where('phone_number', $toNumber)->first();
            
            if (!$company) {
                Log::warning('No company found for phone number', ['number' => $toNumber]);
                $company = Company::first(); // Fallback to first company
            }
            
            // Initialize response with default agent
            $response = [
                'response' => [
                    'agent_id' => $company->retell_agent_id ?? config('services.retell.default_agent_id'),
                    'dynamic_variables' => [
                        'company_name' => $company->name ?? 'AskProAI',
                        'caller_number' => $callData['call_inbound']['from_number'] ?? ''
                    ]
                ]
            ];
            
            // Check if this is a request for available slots
            if (isset($callData['dynamic_variables']['check_availability']) && 
                $callData['dynamic_variables']['check_availability'] === true) {
                
                $requestedDate = $callData['dynamic_variables']['requested_date'] ?? null;
                $eventTypeId = $callData['dynamic_variables']['event_type_id'] ?? null;
                $customerPreferences = $callData['dynamic_variables']['customer_preferences'] ?? null;
                $requestedTime = $callData['dynamic_variables']['requested_time'] ?? null;
                
                if ($requestedDate && $eventTypeId) {
                    // Use the new availability service
                    $calcomService = new CalcomV2Service($company->calcom_api_key);
                    $availabilityService = new CalcomAvailabilityService($calcomService);
                    
                    // Check if requested time is available
                    if ($requestedTime) {
                        $isAvailable = $availabilityService->isTimeSlotAvailable(
                            $eventTypeId,
                            $requestedDate,
                            $requestedTime
                        );
                        
                        if ($isAvailable) {
                            $response['response']['dynamic_variables']['requested_slot_available'] = true;
                            $response['response']['dynamic_variables']['available_slots'] = $requestedTime . ' Uhr';
                            $response['response']['dynamic_variables']['slots_count'] = 1;
                        } else {
                            // Find alternative slots based on customer preferences
                            $preferences = $this->parseCustomerPreferences($customerPreferences);
                            
                            $alternatives = $availabilityService->findAlternativeSlots(
                                $eventTypeId,
                                $requestedDate,
                                $requestedTime,
                                array_merge($preferences, [
                                    'max_alternatives' => 3,
                                    'search_radius_days' => 7,
                                    'prefer_same_day' => true,
                                    'prefer_same_time' => true,
                                ])
                            );
                            
                            if ($alternatives['total_alternatives'] > 0) {
                                $formatted = $this->formatAlternativesForVoice($alternatives['alternatives']);
                                $response['response']['dynamic_variables']['requested_slot_available'] = false;
                                $response['response']['dynamic_variables']['alternative_slots'] = $formatted;
                                $response['response']['dynamic_variables']['alternative_dates'] = array_unique(
                                    array_column($alternatives['alternatives'], 'date')
                                );
                                $response['response']['dynamic_variables']['slots_count'] = $alternatives['total_alternatives'];
                                $response['response']['dynamic_variables']['preference_matched'] = true;
                            } else {
                                $response['response']['dynamic_variables']['requested_slot_available'] = false;
                                $response['response']['dynamic_variables']['alternative_slots'] = 'keine passenden Termine gefunden';
                                $response['response']['dynamic_variables']['slots_count'] = 0;
                            }
                        }
                    } else {
                        // No specific time requested, show available slots for the day
                        $dayAvailability = $availabilityService->checkAvailability($eventTypeId, $requestedDate);
                        
                        if ($dayAvailability['available']) {
                            $slots = array_slice($dayAvailability['slots'], 0, 5); // First 5 slots
                            $formatted = implode(', ', array_map(fn($s) => $s['formatted'], $slots));
                            
                            $response['response']['dynamic_variables']['available_slots'] = $formatted;
                            $response['response']['dynamic_variables']['slots_count'] = $dayAvailability['total_slots'];
                            $response['response']['dynamic_variables']['day_has_availability'] = true;
                        } else {
                            $response['response']['dynamic_variables']['available_slots'] = 'keine Termine verfügbar';
                            $response['response']['dynamic_variables']['slots_count'] = 0;
                            $response['response']['dynamic_variables']['day_has_availability'] = false;
                        }
                    }
                    
                    $response['response']['dynamic_variables']['availability_checked'] = true;
                    
                    Log::info('Availability check completed with preferences', [
                        'date' => $requestedDate,
                        'preferences' => $customerPreferences,
                        'requested_time' => $requestedTime,
                        'alternatives_found' => $response['response']['dynamic_variables']['slots_count'] ?? 0
                    ]);
                }
            }
            
            // Log the response
            Log::info('Sending real-time response to Retell.ai', [
                'response' => $response
            ]);
            
            return response()->json($response, 200);
            
        } catch (\Exception $e) {
            Log::error('Error in handleInboundCall', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return basic response even on error
            return response()->json([
                'response' => [
                    'agent_id' => config('services.retell.default_agent_id'),
                    'dynamic_variables' => [
                        'error' => 'Verfügbarkeitsprüfung fehlgeschlagen'
                    ]
                ]
            ], 200);
        }
    }
    
    /**
     * Find alternative slots based on customer preferences
     */
    private function findAlternativeSlots(
        CalcomV2Service $calcomService, 
        $eventTypeId, 
        $requestedDate, 
        $preferences,
        $requestedTime
    ): array {
        $alternatives = [];
        $preferenceType = null;
        
        // Parse customer preferences
        $timePreferences = $this->parseCustomerPreferences($preferences);
        
        // First, check the same day for different times
        $sameDaySlots = $this->findSlotsMatchingPreferences(
            $calcomService->checkAvailability($eventTypeId, $requestedDate),
            $timePreferences,
            $requestedDate
        );
        
        foreach ($sameDaySlots as $slot) {
            if (count($alternatives) < 2) {
                $alternatives[] = $slot;
            }
        }
        
        // If not enough alternatives, check next 7 days
        if (count($alternatives) < 2) {
            $startDate = Carbon::parse($requestedDate);
            
            for ($i = 1; $i <= 7 && count($alternatives) < 2; $i++) {
                $checkDate = $startDate->copy()->addDays($i);
                
                // Check if this day matches preferences (e.g., "nur donnerstags")
                if (!$this->dayMatchesPreferences($checkDate, $timePreferences)) {
                    continue;
                }
                
                $availability = $calcomService->checkAvailability(
                    $eventTypeId, 
                    $checkDate->format('Y-m-d')
                );
                
                $daySlots = $this->findSlotsMatchingPreferences(
                    $availability,
                    $timePreferences,
                    $checkDate->format('Y-m-d')
                );
                
                foreach ($daySlots as $slot) {
                    if (count($alternatives) < 2) {
                        $alternatives[] = $slot;
                    }
                }
            }
        }
        
        // Format alternatives for voice response
        if (!empty($alternatives)) {
            $formatted = [];
            $dates = [];
            
            foreach ($alternatives as $alt) {
                $time = Carbon::parse($alt['datetime']);
                $formatted[] = $this->formatDateTimeForVoice($time);
                $dates[] = $time->format('Y-m-d');
            }
            
            return [
                'slots' => $alternatives,
                'formatted' => implode(' oder ', $formatted),
                'dates' => array_unique($dates),
                'preference_matched' => true
            ];
        }
        
        return [];
    }
    
    /**
     * Parse customer preferences into structured format
     */
    private function parseCustomerPreferences($preferences): array {
        if (!$preferences) {
            return [];
        }
        
        $parsed = [
            'weekdays' => [],
            'time_ranges' => [],
            'time_preference' => null // vormittags, nachmittags, abends
        ];
        
        // Parse weekday preferences (e.g., "donnerstags", "montags und mittwochs")
        if (preg_match_all('/(montags?|dienstags?|mittwochs?|donnerstags?|freitags?|samstags?|sonntags?)/i', $preferences, $matches)) {
            $weekdayMap = [
                'montag' => 1,
                'dienstag' => 2,
                'mittwoch' => 3,
                'donnerstag' => 4,
                'freitag' => 5,
                'samstag' => 6,
                'sonntag' => 0
            ];
            
            foreach ($matches[1] as $day) {
                $day = rtrim(strtolower($day), 's');
                if (isset($weekdayMap[$day])) {
                    $parsed['weekdays'][] = $weekdayMap[$day];
                }
            }
        }
        
        // Parse time ranges (e.g., "16:00 bis 19:00", "ab 16 Uhr")
        if (preg_match('/(\d{1,2}):?(\d{2})?\s*(uhr)?\s*bis\s*(\d{1,2}):?(\d{2})?\s*(uhr)?/i', $preferences, $matches)) {
            $parsed['time_ranges'][] = [
                'from' => sprintf('%02d:%02d', $matches[1], $matches[2] ?? '00'),
                'to' => sprintf('%02d:%02d', $matches[4], $matches[5] ?? '00')
            ];
        } elseif (preg_match('/ab\s*(\d{1,2}):?(\d{2})?\s*(uhr)?/i', $preferences, $matches)) {
            $parsed['time_ranges'][] = [
                'from' => sprintf('%02d:%02d', $matches[1], $matches[2] ?? '00'),
                'to' => '20:00' // Default end time
            ];
        }
        
        // Parse general time preferences
        if (preg_match('/(vormittags?|morgens?)/i', $preferences)) {
            $parsed['time_preference'] = 'morning';
            if (empty($parsed['time_ranges'])) {
                $parsed['time_ranges'][] = ['from' => '08:00', 'to' => '12:00'];
            }
        } elseif (preg_match('/(nachmittags?)/i', $preferences)) {
            $parsed['time_preference'] = 'afternoon';
            if (empty($parsed['time_ranges'])) {
                $parsed['time_ranges'][] = ['from' => '12:00', 'to' => '17:00'];
            }
        } elseif (preg_match('/(abends?)/i', $preferences)) {
            $parsed['time_preference'] = 'evening';
            if (empty($parsed['time_ranges'])) {
                $parsed['time_ranges'][] = ['from' => '17:00', 'to' => '20:00'];
            }
        }
        
        return $parsed;
    }
    
    /**
     * Check if a day matches customer preferences
     */
    private function dayMatchesPreferences(Carbon $date, array $preferences): bool {
        if (empty($preferences['weekdays'])) {
            return true; // No weekday preference
        }
        
        return in_array($date->dayOfWeek, $preferences['weekdays']);
    }
    
    /**
     * Find slots matching time preferences
     */
    private function findSlotsMatchingPreferences($availability, array $preferences, string $date): array {
        if (!$availability['success'] || empty($availability['data']['slots'])) {
            return [];
        }
        
        $matchingSlots = [];
        
        foreach ($availability['data']['slots'] as $slot) {
            $slotTime = Carbon::parse($slot);
            
            // Check if slot matches time preferences
            $matches = false;
            
            if (!empty($preferences['time_ranges'])) {
                foreach ($preferences['time_ranges'] as $range) {
                    $from = Carbon::parse($date . ' ' . $range['from']);
                    $to = Carbon::parse($date . ' ' . $range['to']);
                    
                    if ($slotTime->between($from, $to)) {
                        $matches = true;
                        break;
                    }
                }
            } else {
                $matches = true; // No time preference
            }
            
            if ($matches) {
                $matchingSlots[] = [
                    'datetime' => $slot,
                    'date' => $date,
                    'time' => $slotTime->format('H:i')
                ];
            }
        }
        
        return $matchingSlots;
    }
    
    /**
     * Format alternatives for voice response
     */
    private function formatAlternativesForVoice(array $alternatives): string
    {
        if (empty($alternatives)) {
            return 'keine Alternativen gefunden';
        }
        
        $formatted = [];
        foreach ($alternatives as $alt) {
            $datetime = Carbon::parse($alt['datetime'] ?? $alt['date'] . ' ' . $alt['time']);
            $formatted[] = $this->formatDateTimeForVoice($datetime);
        }
        
        // Join with "oder" for natural speech
        if (count($formatted) === 1) {
            return $formatted[0];
        } elseif (count($formatted) === 2) {
            return $formatted[0] . ' oder ' . $formatted[1];
        } else {
            $last = array_pop($formatted);
            return implode(', ', $formatted) . ' oder ' . $last;
        }
    }
    
    /**
     * Format date and time for voice response in German
     */
    private function formatDateTimeForVoice(Carbon $datetime): string {
        $weekdays = [
            'Monday' => 'Montag',
            'Tuesday' => 'Dienstag',
            'Wednesday' => 'Mittwoch',
            'Thursday' => 'Donnerstag',
            'Friday' => 'Freitag',
            'Saturday' => 'Samstag',
            'Sunday' => 'Sonntag'
        ];
        
        $today = Carbon::now();
        $tomorrow = Carbon::tomorrow();
        
        if ($datetime->isSameDay($today)) {
            $dateStr = 'heute';
        } elseif ($datetime->isSameDay($tomorrow)) {
            $dateStr = 'morgen';
        } else {
            $weekday = $weekdays[$datetime->format('l')] ?? $datetime->format('l');
            $dateStr = $weekday . ', den ' . $datetime->format('j. F');
        }
        
        return $dateStr . ' um ' . $datetime->format('H:i') . ' Uhr';
    }
}