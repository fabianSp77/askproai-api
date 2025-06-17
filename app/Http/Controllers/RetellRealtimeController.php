<?php

namespace App\Http\Controllers;

use App\Services\CalcomV2Service;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RetellRealtimeController extends Controller
{
    /**
     * Handle real-time function calls from Retell.ai during active calls
     * This is called when the agent uses collect_appointment_data with verfuegbarkeit_pruefen=true
     */
    public function handleFunctionCall(Request $request)
    {
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
        // Get company from call data
        $toNumber = $callData['to_number'] ?? null;
        $company = Company::where('phone_number', $toNumber)->first() ?? Company::first();
        
        // Parse appointment data
        $datum = $parameters['datum'] ?? '';
        $uhrzeit = $parameters['uhrzeit'] ?? '';
        $kundenpraeferenzen = $parameters['kundenpraeferenzen'] ?? '';
        $eventTypeId = 1; // Default, could be determined from dienstleistung
        
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
        
        // Check availability
        $calcomService = new CalcomV2Service($company->calcom_api_key);
        $availability = $calcomService->checkAvailability($eventTypeId, $date->format('Y-m-d'));
        
        // Check if requested time is available
        $isAvailable = false;
        if ($availability['success'] && !empty($availability['data']['slots'])) {
            foreach ($availability['data']['slots'] as $slot) {
                $slotTime = Carbon::parse($slot);
                if ($slotTime->format('H:i') === $requestedTime) {
                    $isAvailable = true;
                    break;
                }
            }
        }
        
        $response = [
            'success' => true,
            'verfuegbar' => $isAvailable,
            'datum_geprueft' => $date->format('d.m.Y'),
            'uhrzeit_geprueft' => $requestedTime
        ];
        
        // If not available and alternatives requested, find them
        if (!$isAvailable && isset($parameters['alternative_termine_gewuenscht']) && $parameters['alternative_termine_gewuenscht']) {
            $alternatives = $this->findAlternatives($calcomService, $eventTypeId, $date, $kundenpraeferenzen, $requestedTime);
            
            if (!empty($alternatives)) {
                $response['alternative_termine'] = $alternatives['formatted'];
                $response['alternative_anzahl'] = count($alternatives['slots']);
            } else {
                $response['alternative_termine'] = 'Keine passenden Alternativen gefunden';
                $response['alternative_anzahl'] = 0;
            }
        }
        
        // Add a message for the agent to use
        if ($isAvailable) {
            $response['nachricht'] = "Der Termin am {$date->format('d.m.Y')} um {$requestedTime} Uhr ist verfügbar.";
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