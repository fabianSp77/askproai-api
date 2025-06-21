<?php

namespace App\Http\Controllers;

use App\Services\CalcomV2Service;
use App\Models\Company;
use App\Services\Booking\UniversalBookingOrchestrator;
use App\Services\PhoneNumberResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\WebhookProcessor;

class RetellRealtimeController extends Controller
{
    /**
     * Handle real-time function calls from Retell.ai during active calls
     * This is called when the agent uses collect_appointment_data with verfuegbarkeit_pruefen=true
     */
    public function handleFunctionCall(Request $request)
    {
        // DEBUG: Log all incoming function calls
        Log::warning('RETELL FUNCTION CALL RECEIVED', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'raw_body' => substr($request->getContent(), 0, 1000)
        ]);
        
        // Verify signature for real-time calls
        try {
            $webhookProcessor = app(WebhookProcessor::class);
            
            // Create proper headers array for verification
            $headers = [
                'x-retell-signature' => [$request->header('x-retell-signature')],
                'X-Retell-Signature' => [$request->header('X-Retell-Signature')],
                'x-retell-timestamp' => [$request->header('x-retell-timestamp')],
                'X-Retell-Timestamp' => [$request->header('X-Retell-Timestamp')],
            ];
            
            // Use reflection to access the protected verifyRetellSignature method
            $reflection = new \ReflectionClass($webhookProcessor);
            $method = $reflection->getMethod('verifyRetellSignature');
            $method->setAccessible(true);
            
            $isValid = $method->invoke($webhookProcessor, $request->all(), $headers);
            
            if (!$isValid) {
                Log::warning('Invalid Retell signature for function call', [
                    'headers' => $request->headers->all(),
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'error' => 'Invalid signature'
                ], 401);
            }
        } catch (\Exception $e) {
            Log::error('Signature verification failed', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Signature verification failed'
            ], 401);
        }
        
        try {
            $functionName = $request->input('function_name');
            $parameters = $request->input('parameters', []);
            $callData = $request->input('call', []);
            
            Log::info('Retell function call received', [
                'function' => $functionName,
                'parameters' => $parameters
            ]);
            
            if ($functionName === 'collect_appointment_data' && 
                isset($parameters['verfuegbarkeit_pruefen']) && 
                $parameters['verfuegbarkeit_pruefen'] === true) {
                
                return $this->checkAvailabilityBeforeBooking($parameters, $callData);
            }
            
            // Default response - just acknowledge
            return response()->json([
                'success' => true,
                'message' => 'Function processed'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in function call handler', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Fehler bei der Verarbeitung'
            ]);
        }
    }
    
    /**
     * Check availability before confirming the booking
     */
    private function checkAvailabilityBeforeBooking(array $parameters, array $callData)
    {
        // Resolve context using PhoneNumberResolver
        $phoneResolver = app(PhoneNumberResolver::class);
        $context = $phoneResolver->resolveFromWebhook([
            'to' => $callData['to_number'] ?? null,
            'from' => $callData['from_number'] ?? null,
            'agent_id' => $callData['agent_id'] ?? null,
            'metadata' => $callData['metadata'] ?? []
        ]);
        
        if (!$context['company_id']) {
            Log::error('Could not resolve company from call data', $callData);
            return response()->json([
                'success' => false,
                'message' => 'Konnte Unternehmen nicht ermitteln'
            ]);
        }
        
        // Parse appointment data
        $datum = $parameters['datum'] ?? '';
        $uhrzeit = $parameters['uhrzeit'] ?? '';
        $dienstleistung = $parameters['dienstleistung'] ?? '';
        $kundenpraeferenzen = $parameters['kundenpraeferenzen'] ?? '';
        
        // Parse date and time
        try {
            if (strpos($datum, '.') !== false) {
                $date = Carbon::createFromFormat('d.m.Y', $datum);
            } else {
                $date = Carbon::parse($datum);
            }
            
            // Remove "Uhr" and parse time
            $timeStr = str_replace(' Uhr', '', $uhrzeit);
            list($hour, $minute) = explode(':', $timeStr . ':00');
            $requestedTime = sprintf('%02d:%02d', $hour, $minute);
            $requestedDateTime = $date->copy()->setTime((int)$hour, (int)$minute);
            
        } catch (\Exception $e) {
            Log::warning('Failed to parse date/time in realtime check', [
                'datum' => $datum,
                'uhrzeit' => $uhrzeit
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Datum oder Uhrzeit konnte nicht verarbeitet werden'
            ]);
        }
        
        // Use UniversalBookingOrchestrator to check availability
        $orchestrator = app(UniversalBookingOrchestrator::class);
        
        // Build booking request for availability check
        $bookingRequest = [
            'company_id' => $context['company_id'],
            'branch_id' => $context['branch_id'],
            'service_name' => $dienstleistung,
            'date' => $date->format('Y-m-d'),
            'time' => $requestedDateTime->format('H:i'),
            'customer_preferences' => $kundenpraeferenzen,
            'check_only' => true // Just checking, not booking yet
        ];
        
        // Get availability options
        $availabilityResult = $this->checkAvailabilityAcrossBranches($bookingRequest, $context);
        
        $response = [
            'success' => true,
            'verfuegbar' => $availabilityResult['available'],
            'datum_geprueft' => $date->format('d.m.Y'),
            'uhrzeit_geprueft' => $requestedTime
        ];
        
        // If not available at requested time/branch, provide alternatives
        if (!$availabilityResult['available'] && 
            isset($parameters['alternative_termine_gewuenscht']) && 
            $parameters['alternative_termine_gewuenscht']) {
            
            if (!empty($availabilityResult['alternatives'])) {
                $response['alternative_termine'] = $this->formatAlternativesForVoice($availabilityResult['alternatives']);
                $response['alternative_anzahl'] = count($availabilityResult['alternatives']);
            } else {
                $response['alternative_termine'] = 'Keine passenden Alternativen gefunden';
                $response['alternative_anzahl'] = 0;
            }
        }
        
        // Add a message for the agent to use
        if ($availabilityResult['available']) {
            $response['nachricht'] = "Der Termin am {$date->format('d.m.Y')} um {$requestedTime} Uhr ist verfügbar.";
            if (!empty($availabilityResult['branch_name'])) {
                $response['nachricht'] .= " in unserer Filiale {$availabilityResult['branch_name']}.";
            }
        } else {
            if (isset($response['alternative_termine']) && $response['alternative_anzahl'] > 0) {
                $response['nachricht'] = "Der gewünschte Termin ist leider nicht verfügbar. Ich hätte folgende Alternativen: {$response['alternative_termine']}";
            } else {
                $response['nachricht'] = "Der gewünschte Termin ist leider nicht verfügbar.";
            }
        }
        
        Log::info('Availability check result', $response);
        
        return response()->json($response);
    }
    
    /**
     * Find alternative appointments (simplified version)
     */
    private function findAlternatives($calcomService, $eventTypeId, Carbon $requestedDate, $preferences, $requestedTime)
    {
        $alternatives = [];
        
        // Check next 7 days
        for ($i = 0; $i <= 7 && count($alternatives) < 2; $i++) {
            $checkDate = $requestedDate->copy()->addDays($i);
            $availability = $calcomService->checkAvailability($eventTypeId, $checkDate->format('Y-m-d'));
            
            if ($availability['success'] && !empty($availability['data']['slots'])) {
                // Take first 2 available slots
                foreach (array_slice($availability['data']['slots'], 0, 2) as $slot) {
                    if (count($alternatives) < 2) {
                        $slotTime = Carbon::parse($slot);
                        
                        // Skip if it's the originally requested time
                        if ($i === 0 && $slotTime->format('H:i') === $requestedTime) {
                            continue;
                        }
                        
                        $alternatives[] = [
                            'datetime' => $slot,
                            'formatted' => $this->formatDateTimeGerman($slotTime)
                        ];
                    }
                }
            }
        }
        
        if (!empty($alternatives)) {
            $formatted = array_map(function($alt) { return $alt['formatted']; }, $alternatives);
            return [
                'slots' => $alternatives,
                'formatted' => implode(' oder ', $formatted)
            ];
        }
        
        return [];
    }
    
    /**
     * Check availability across branches
     */
    private function checkAvailabilityAcrossBranches(array $bookingRequest, array $context): array
    {
        try {
            // Get unified availability service
            $availabilityService = app(\App\Services\Booking\UnifiedAvailabilityService::class);
            
            // If specific branch is requested, check only that branch
            if (!empty($context['branch_id'])) {
                $branch = \App\Models\Branch::find($context['branch_id']);
                if (!$branch || !$branch->active) {
                    return [
                        'available' => false,
                        'alternatives' => []
                    ];
                }
                
                // Find eligible staff
                $staffMatcher = app(\App\Services\Booking\StaffServiceMatcher::class);
                $eligibleStaff = $staffMatcher->findEligibleStaff($branch, [
                    'service_name' => $bookingRequest['service_name']
                ]);
                
                // Check each staff member
                foreach ($eligibleStaff as $staff) {
                    $slots = $availabilityService->getStaffAvailability(
                        $staff,
                        [
                            'start' => Carbon::parse($bookingRequest['date'] . ' ' . $bookingRequest['time']),
                            'end' => Carbon::parse($bookingRequest['date'] . ' ' . $bookingRequest['time'])->addHour()
                        ],
                        30 // Default duration
                    );
                    
                    if (!empty($slots)) {
                        return [
                            'available' => true,
                            'branch_name' => $branch->name,
                            'staff_name' => $staff->name,
                            'alternatives' => []
                        ];
                    }
                }
            } else {
                // Check all branches
                $branches = \App\Models\Branch::where('company_id', $context['company_id'])
                    ->where('active', true)
                    ->get();
                    
                $multibranchSlots = $availabilityService->getMultiBranchAvailability(
                    $branches->all(),
                    ['service_name' => $bookingRequest['service_name']],
                    [
                        'start' => Carbon::parse($bookingRequest['date'])->startOfDay(),
                        'end' => Carbon::parse($bookingRequest['date'])->endOfDay()
                    ]
                );
                
                // Check if requested time is available
                $requestedTime = Carbon::parse($bookingRequest['date'] . ' ' . $bookingRequest['time']);
                foreach ($multibranchSlots as $slot) {
                    $slotTime = Carbon::parse($slot['start']);
                    if ($slotTime->format('Y-m-d H:i') === $requestedTime->format('Y-m-d H:i')) {
                        return [
                            'available' => true,
                            'branch_name' => $slot['branch_name'] ?? null,
                            'staff_name' => $slot['staff_name'] ?? null,
                            'alternatives' => []
                        ];
                    }
                }
            }
            
            // Not available at requested time - find alternatives
            $alternatives = $availabilityService->getMultiBranchAvailability(
                $branches ?? [\App\Models\Branch::find($context['branch_id'])],
                ['service_name' => $bookingRequest['service_name']],
                [
                    'start' => Carbon::parse($bookingRequest['date'])->startOfDay(),
                    'end' => Carbon::parse($bookingRequest['date'])->addDays(3)->endOfDay()
                ],
                5 // Max 5 alternatives
            );
            
            return [
                'available' => false,
                'alternatives' => $alternatives
            ];
            
        } catch (\Exception $e) {
            Log::error('Error checking availability', [
                'error' => $e->getMessage(),
                'booking_request' => $bookingRequest
            ]);
            
            return [
                'available' => false,
                'alternatives' => []
            ];
        }
    }
    
    /**
     * Format alternatives for voice response
     */
    private function formatAlternativesForVoice(array $alternatives): string
    {
        if (empty($alternatives)) {
            return 'keine verfügbaren Termine';
        }
        
        $formatted = [];
        
        foreach (array_slice($alternatives, 0, 3) as $alt) {
            $time = Carbon::parse($alt['start']);
            $formattedTime = $this->formatDateTimeGerman($time);
            
            if (!empty($alt['branch_name'])) {
                $formattedTime .= " in " . $alt['branch_name'];
            }
            
            $formatted[] = $formattedTime;
        }
        
        return implode(', oder ', $formatted);
    }
    
    /**
     * Format datetime in German
     */
    private function formatDateTimeGerman(Carbon $datetime): string
    {
        $weekdays = [
            'Monday' => 'Montag',
            'Tuesday' => 'Dienstag', 
            'Wednesday' => 'Mittwoch',
            'Thursday' => 'Donnerstag',
            'Friday' => 'Freitag',
            'Saturday' => 'Samstag',
            'Sunday' => 'Sonntag'
        ];
        
        if ($datetime->isToday()) {
            return 'heute um ' . $datetime->format('H:i') . ' Uhr';
        } elseif ($datetime->isTomorrow()) {
            return 'morgen um ' . $datetime->format('H:i') . ' Uhr';
        } else {
            $weekday = $weekdays[$datetime->format('l')] ?? $datetime->format('l');
            return $weekday . ' um ' . $datetime->format('H:i') . ' Uhr';
        }
    }
}