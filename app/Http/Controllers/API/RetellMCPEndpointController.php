<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MCP\RetellMCPServer;
use App\Models\Call;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RetellMCPEndpointController extends Controller
{
    protected RetellMCPServer $retellMCPServer;
    
    public function __construct(RetellMCPServer $retellMCPServer)
    {
        $this->retellMCPServer = $retellMCPServer;
    }
    
    /**
     * Handle MCP tool calls from Retell.ai
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleToolCall(Request $request)
    {
        $startTime = microtime(true);
        
        // Log incoming request for debugging
        Log::info('Retell MCP Tool Call received', [
            'tool' => $request->input('tool'),
            'arguments' => $request->input('arguments'),
            'call_id' => $request->input('call_id'),
            'timestamp' => now()->toIso8601String()
        ]);
        
        try {
            // Validate required fields
            $tool = $request->input('tool');
            $arguments = $request->input('arguments', []);
            $callId = $request->input('call_id');
            
            if (!$tool) {
                return $this->errorResponse('Tool name is required', 400);
            }
            
            // Get company context from call if available
            $company = null;
            if ($callId) {
                $call = Call::where('retell_call_id', $callId)->first();
                if ($call && $call->company_id) {
                    $company = Company::find($call->company_id);
                    
                    // Validate company has required configuration
                    if (!$this->validateCompanyConfiguration($company, $tool)) {
                        return $this->errorResponse('Company configuration incomplete for this operation', 403);
                    }
                }
            } else {
                // For tools that don't require call context, use default company or extract from arguments
                $companyId = $arguments['company_id'] ?? 1;
                $company = Company::find($companyId);
            }
            
            // Route to appropriate tool handler
            $response = match($tool) {
                'getCurrentTimeBerlin' => $this->handleGetCurrentTimeBerlin(),
                'checkAvailableSlots' => $this->handleCheckAvailableSlots($arguments, $company),
                'bookAppointment' => $this->handleBookAppointment($arguments, $callId, $company),
                'getCustomerInfo' => $this->handleGetCustomerInfo($arguments, $callId),
                'endCallSession' => $this->handleEndCallSession($callId),
                default => $this->errorResponse("Unknown tool: {$tool}", 404)
            };
            
            // Log performance metrics
            $duration = (microtime(true) - $startTime) * 1000;
            Log::info('MCP Tool Call completed', [
                'tool' => $tool,
                'duration_ms' => round($duration, 2),
                'success' => !isset($response['error'])
            ]);
            
            // Add performance header
            return response()->json($response)
                ->header('X-MCP-Duration', round($duration, 2));
                
        } catch (\Exception $e) {
            Log::error('MCP Tool Call error', [
                'tool' => $request->input('tool'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->errorResponse('Internal server error', 500);
        }
    }
    
    /**
     * Handle getCurrentTimeBerlin tool
     */
    protected function handleGetCurrentTimeBerlin(): array
    {
        try {
            $berlinTime = Carbon::now('Europe/Berlin');
            
            return [
                'success' => true,
                'data' => [
                    'current_time_berlin' => $berlinTime->format('Y-m-d H:i:s'),
                    'current_date' => $berlinTime->format('Y-m-d'),
                    'current_time' => $berlinTime->format('H:i'),
                    'weekday' => $this->getGermanWeekday($berlinTime->dayOfWeek),
                    'timestamp' => $berlinTime->timestamp,
                    'timezone' => 'Europe/Berlin'
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error getting Berlin time', ['error' => $e->getMessage()]);
            return $this->errorResponse('Failed to get current time', 500);
        }
    }
    
    /**
     * Handle checkAvailableSlots tool
     */
    protected function handleCheckAvailableSlots(array $arguments, ?Company $company): array
    {
        try {
            // Validate required arguments
            $date = $arguments['date'] ?? $arguments['datum'] ?? null;
            
            if (!$date) {
                return $this->errorResponse('Date is required', 400);
            }
            
            // Parse relative dates (morgen, übermorgen, etc.)
            $parsedDate = $this->parseRelativeDate($date);
            
            // Use cache for performance
            $cacheKey = "available_slots:{$company?->id}:{$parsedDate}";
            $slots = Cache::remember($cacheKey, 60, function() use ($parsedDate, $company) {
                return $this->retellMCPServer->getAvailableSlots([
                    'company_id' => $company?->id ?? 1,
                    'date' => $parsedDate,
                    'branch_id' => $arguments['branch_id'] ?? 1
                ]);
            });
            
            return [
                'success' => true,
                'data' => [
                    'date' => $parsedDate,
                    'slots' => $slots,
                    'message' => $this->formatSlotsMessage($slots, $parsedDate)
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Error checking available slots', [
                'arguments' => $arguments,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Failed to check availability', 500);
        }
    }
    
    /**
     * Handle bookAppointment tool
     */
    protected function handleBookAppointment(array $arguments, ?string $callId, ?Company $company): array
    {
        try {
            // Security check: Ensure company needs appointment booking
            if ($company && !$company->needsAppointmentBooking()) {
                Log::warning('Appointment booking blocked for company', [
                    'company_id' => $company->id,
                    'call_id' => $callId
                ]);
                return $this->errorResponse('Diese Funktion ist für Ihr Unternehmen nicht verfügbar.', 403);
            }
            
            // Extract and validate appointment data
            $appointmentData = [
                'name' => $arguments['name'] ?? null,
                'phone' => $arguments['telefonnummer'] ?? $arguments['phone'] ?? null,
                'email' => $arguments['email'] ?? null,
                'date' => $this->parseRelativeDate($arguments['datum'] ?? $arguments['date'] ?? null),
                'time' => $arguments['uhrzeit'] ?? $arguments['time'] ?? null,
                'service' => $arguments['dienstleistung'] ?? $arguments['service'] ?? 'Beratung',
                'notes' => $arguments['notizen'] ?? $arguments['notes'] ?? null,
                'customer_preferences' => $arguments['kundenpraeferenzen'] ?? null,
                'staff_preference' => $arguments['mitarbeiter_wunsch'] ?? null,
                'call_id' => $callId,
                'company_id' => $company?->id ?? 1,
                'branch_id' => $arguments['branch_id'] ?? 1
            ];
            
            // Validate required fields
            if (!$appointmentData['name'] || !$appointmentData['date'] || !$appointmentData['time']) {
                return $this->errorResponse('Name, Datum und Uhrzeit sind erforderlich', 400);
            }
            
            // Get phone from call if not provided
            if (!$appointmentData['phone'] && $callId) {
                $call = Call::where('retell_call_id', $callId)->first();
                if ($call) {
                    $appointmentData['phone'] = $call->from_number;
                }
            }
            
            // Book appointment using MCP server
            $result = $this->retellMCPServer->bookAppointment($appointmentData);
            
            if ($result['success'] ?? false) {
                Log::info('Appointment booked successfully via MCP', [
                    'appointment_id' => $result['appointment_id'] ?? null,
                    'call_id' => $callId
                ]);
                
                return [
                    'success' => true,
                    'data' => [
                        'appointment_id' => $result['appointment_id'],
                        'confirmation_number' => $result['confirmation_number'] ?? null,
                        'message' => "Termin erfolgreich gebucht für {$appointmentData['name']} am {$appointmentData['date']} um {$appointmentData['time']} Uhr."
                    ]
                ];
            } else {
                return $this->errorResponse($result['error'] ?? 'Booking failed', 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Error booking appointment via MCP', [
                'arguments' => $arguments,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Failed to book appointment', 500);
        }
    }
    
    /**
     * Handle getCustomerInfo tool
     */
    protected function handleGetCustomerInfo(array $arguments, ?string $callId): array
    {
        try {
            $phoneNumber = $arguments['phone'] ?? $arguments['telefonnummer'] ?? null;
            
            // Get phone from call if not provided
            if (!$phoneNumber && $callId) {
                $call = Call::where('retell_call_id', $callId)->first();
                if ($call) {
                    $phoneNumber = $call->from_number;
                }
            }
            
            if (!$phoneNumber) {
                return $this->errorResponse('Phone number is required', 400);
            }
            
            // Get customer info using MCP server
            $customerInfo = $this->retellMCPServer->getCustomerByPhone([
                'phone' => $phoneNumber
            ]);
            
            if ($customerInfo && !isset($customerInfo['error'])) {
                return [
                    'success' => true,
                    'data' => [
                        'customer' => $customerInfo,
                        'found' => true
                    ]
                ];
            } else {
                return [
                    'success' => true,
                    'data' => [
                        'customer' => null,
                        'found' => false,
                        'message' => 'Kein Kunde mit dieser Telefonnummer gefunden.'
                    ]
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Error getting customer info', [
                'arguments' => $arguments,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Failed to get customer info', 500);
        }
    }
    
    /**
     * Handle endCallSession tool
     */
    protected function handleEndCallSession(?string $callId): array
    {
        try {
            if ($callId) {
                // Update call status
                $call = Call::where('retell_call_id', $callId)->first();
                if ($call) {
                    $call->update([
                        'status' => 'ended',
                        'ended_at' => now()
                    ]);
                    
                    Log::info('Call session ended', ['call_id' => $callId]);
                }
            }
            
            return [
                'success' => true,
                'data' => [
                    'message' => 'Vielen Dank für Ihren Anruf. Auf Wiederhören!',
                    'call_ended' => true
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Error ending call session', [
                'call_id' => $callId,
                'error' => $e->getMessage()
            ]);
            // Still return success to end the call gracefully
            return [
                'success' => true,
                'data' => [
                    'message' => 'Auf Wiederhören!',
                    'call_ended' => true
                ]
            ];
        }
    }
    
    /**
     * Parse relative date strings
     */
    protected function parseRelativeDate(string $dateInput): string
    {
        $dateInput = strtolower(trim($dateInput));
        $berlinTime = Carbon::now('Europe/Berlin');
        
        return match($dateInput) {
            'heute' => $berlinTime->format('Y-m-d'),
            'morgen' => $berlinTime->addDay()->format('Y-m-d'),
            'übermorgen' => $berlinTime->addDays(2)->format('Y-m-d'),
            'montag' => $berlinTime->next(Carbon::MONDAY)->format('Y-m-d'),
            'dienstag' => $berlinTime->next(Carbon::TUESDAY)->format('Y-m-d'),
            'mittwoch' => $berlinTime->next(Carbon::WEDNESDAY)->format('Y-m-d'),
            'donnerstag' => $berlinTime->next(Carbon::THURSDAY)->format('Y-m-d'),
            'freitag' => $berlinTime->next(Carbon::FRIDAY)->format('Y-m-d'),
            'samstag' => $berlinTime->next(Carbon::SATURDAY)->format('Y-m-d'),
            'sonntag' => $berlinTime->next(Carbon::SUNDAY)->format('Y-m-d'),
            default => $dateInput // Assume it's already a date
        };
    }
    
    /**
     * Get German weekday name
     */
    protected function getGermanWeekday(int $dayOfWeek): string
    {
        return match($dayOfWeek) {
            0 => 'Sonntag',
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag',
            default => ''
        };
    }
    
    /**
     * Format available slots message
     */
    protected function formatSlotsMessage(array $slots, string $date): string
    {
        if (empty($slots)) {
            return "Leider sind am {$date} keine Termine verfügbar.";
        }
        
        $slotCount = count($slots);
        $firstSlots = array_slice($slots, 0, 3);
        $slotTimes = array_map(fn($slot) => $slot['time'] ?? $slot, $firstSlots);
        
        return "Am {$date} sind {$slotCount} Termine verfügbar, zum Beispiel um " . 
               implode(', ', $slotTimes) . " Uhr.";
    }
    
    /**
     * Return error response
     */
    protected function errorResponse(string $message, int $statusCode = 400): array
    {
        return [
            'success' => false,
            'error' => $message,
            'status_code' => $statusCode
        ];
    }
    
    /**
     * Validate company configuration for specific tool
     */
    protected function validateCompanyConfiguration(?Company $company, string $tool): bool
    {
        if (!$company) {
            return false;
        }
        
        return match($tool) {
            'bookAppointment' => $company->needsAppointmentBooking() && 
                                !empty($company->calcom_api_key) && 
                                !empty($company->calcom_event_type_id),
            'checkAvailableSlots' => !empty($company->calcom_api_key),
            'getCustomerInfo' => true, // Always allowed
            'getCurrentTimeBerlin' => true, // Always allowed
            'endCallSession' => true, // Always allowed
            default => false
        };
    }
}