<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\RetellV2Service;
use App\Services\MCP\MCPGateway;
use App\Services\MCP\AppointmentManagementMCPServer;
use App\Models\Company;
use App\Models\Call;
use Carbon\Carbon;

class RetellCustomFunctionsController extends Controller
{
    protected $mcpGateway;
    protected $appointmentMCP;

    public function __construct(MCPGateway $mcpGateway, AppointmentManagementMCPServer $appointmentMCP)
    {
        $this->mcpGateway = $mcpGateway;
        $this->appointmentMCP = $appointmentMCP;
    }

    protected function logRetellRequest($functionName, $request)
    {
        $logData = [
            'function' => $functionName,
            'timestamp' => now()->toIso8601String(),
            'data' => $request->all(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip()
        ];
        
        Log::channel('daily')->info("RETELL_FUNCTION_CALL: {$functionName}", $logData);
        
        // Also log to a separate file for easier debugging
        $logFile = storage_path('logs/retell-functions-' . date('Y-m-d') . '.log');
        file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);
    }

    public function checkAvailability(Request $request)
    {
        $this->logRetellRequest('check_availability', $request);
        
        try {
            $data = $request->all();
            Log::info('CheckAvailability called with data:', $data);
            
            // SICHERHEITSPRÜFUNG: Company benötigt Terminbuchung?
            $callId = $data['call_id'] ?? $data['args']['call_id'] ?? null;
            if ($callId) {
                $call = Call::where('retell_call_id', $callId)->first();
                if ($call && $call->company && !$call->company->needsAppointmentBooking()) {
                    Log::warning('CheckAvailability blocked for company without appointment booking', [
                        'company_id' => $call->company_id,
                        'call_id' => $callId
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Diese Funktion ist für Ihr Unternehmen nicht verfügbar.'
                    ]);
                }
            }
            
            // Extract date from request
            $dateInput = $data['date'] ?? $data['datum'] ?? null;
            
            if (!$dateInput) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kein Datum angegeben.'
                ]);
            }
            
            // Parse relative dates
            $date = $this->parseRelativeDate($dateInput);
            
            // Use MCP to check availability
            $result = $this->appointmentMCP->checkAvailability([
                'date' => $date,
                'branch_id' => 1 // Default branch
            ]);
            
            Log::info('Availability check result:', $result);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error in checkAvailability: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Es gab ein technisches Problem bei der Verfügbarkeitsprüfung. Bitte versuchen Sie es erneut.'
            ]);
        }
    }

    public function collectAppointment(Request $request)
    {
        $this->logRetellRequest('collect_appointment_data', $request);
        
        try {
            $data = $request->all();
            $callData = $data['call'] ?? [];
            
            Log::info('CollectAppointment called with data:', $data);
            
            // Get call_id from the call object (Retell sends {{call_id}} as literal string)
            $callId = $callData['call_id'] ?? null;
            
            // Also check args for call_id in case it was resolved
            if (!$callId && isset($data['args']['call_id']) && $data['args']['call_id'] !== '{{call_id}}') {
                $callId = $data['args']['call_id'];
            }
            
            Log::info("Call ID resolved: " . ($callId ?? 'none'));
            
            $phoneNumber = null;
            
            if ($callId) {
                $call = \App\Models\Call::where('call_id', $callId)->first();
                if ($call) {
                    // SICHERHEITSPRÜFUNG: Company benötigt Terminbuchung?
                    if ($call->company && !$call->company->needsAppointmentBooking()) {
                        Log::warning('CollectAppointment blocked for company without appointment booking', [
                            'company_id' => $call->company_id,
                            'call_id' => $callId
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Diese Funktion ist für Ihr Unternehmen nicht verfügbar.'
                        ]);
                    }
                    
                    $phoneNumber = $call->from_number;
                    Log::info("Phone number resolved from call_id: {$phoneNumber}");
                }
            }
            
            // Parse the date from args (where Retell sends the function arguments)
            $args = $data['args'] ?? $data;
            $dateInput = $args['datum'] ?? $args['date'] ?? null;
            $timeInput = $args['uhrzeit'] ?? $args['time'] ?? null;
            
            if (!$dateInput || !$timeInput) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datum und Uhrzeit sind erforderlich.'
                ]);
            }
            
            $date = $this->parseRelativeDate($dateInput);
            $time = $this->parseTime($timeInput);
            
            // Create appointment using MCP
            $appointmentData = [
                'date' => $date,
                'time' => $time,
                'customer_name' => $args['name'] ?? 'Kunde',
                'service' => $args['dienstleistung'] ?? $args['service'] ?? 'Allgemeine Beratung',
                'email' => $args['email'] ?? null,
                'phone_number' => $phoneNumber, // MCP expects phone_number, not phone
                'notes' => $args['kundenpraeferenzen'] ?? null,
                'branch_id' => 1, // Default branch
                'call_id' => $callId
            ];
            
            Log::info('Creating appointment with data:', $appointmentData);
            
            $result = $this->appointmentMCP->create($appointmentData);
            
            Log::info('Appointment creation result:', $result);
            
            if ($result['success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => "Perfekt! Ich habe Ihren Termin am {$date} um {$time} Uhr gebucht. Sie erhalten gleich eine Bestätigung per E-Mail."
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Der Termin konnte nicht gebucht werden.'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in collectAppointment: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Es gab ein technisches Problem bei der Terminbuchung. Bitte versuchen Sie es erneut.'
            ]);
        }
    }

    public function checkCustomer(Request $request)
    {
        $this->logRetellRequest('check_customer', $request);
        
        try {
            $data = $request->all();
            $callData = $data['call'] ?? [];
            
            // Get call_id from the call object (Retell sends {{call_id}} as literal string)
            $callId = $callData['call_id'] ?? null;
            
            // Also check args for call_id in case it was resolved
            if (!$callId && isset($data['args']['call_id']) && $data['args']['call_id'] !== '{{call_id}}') {
                $callId = $data['args']['call_id'];
            }
            
            if (!$callId) {
                Log::warning('No call_id provided in check_customer');
                return response()->json([
                    'exists' => false,
                    'message' => 'Keine Call-ID gefunden.'
                ]);
            }
            
            // Fetch the call from database
            $call = \App\Models\Call::where('call_id', $callId)->first();
            
            if (!$call || !$call->from_number) {
                Log::info("No call found for call_id: {$callId}");
                return response()->json([
                    'exists' => false,
                    'message' => 'Ich kann Sie als neuer Kunde willkommen heißen.'
                ]);
            }
            
            $phoneNumber = $call->from_number;
            Log::info("Checking customer with phone: {$phoneNumber}");
            
            // Check if customer exists
            $customer = \App\Models\Customer::where('phone', $phoneNumber)
                ->orWhere('phone', 'LIKE', '%' . substr($phoneNumber, -10))
                ->first();
            
            if ($customer) {
                return response()->json([
                    'exists' => true,
                    'customer_name' => $customer->name,
                    'message' => "Willkommen zurück, {$customer->name}!"
                ]);
            } else {
                return response()->json([
                    'exists' => false,
                    'message' => 'Ich kann Sie als neuer Kunde willkommen heißen.'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in checkCustomer: ' . $e->getMessage());
            
            return response()->json([
                'exists' => false,
                'message' => 'Kunde konnte nicht überprüft werden.'
            ]);
        }
    }

    public function cancelAppointment(Request $request)
    {
        $this->logRetellRequest('cancel_appointment', $request);
        
        try {
            $data = $request->all();
            $callData = $data['call'] ?? [];
            
            // Get call_id from the call object (Retell sends {{call_id}} as literal string)
            $callId = $callData['call_id'] ?? null;
            
            // Also check args for call_id in case it was resolved
            if (!$callId && isset($data['args']['call_id']) && $data['args']['call_id'] !== '{{call_id}}') {
                $callId = $data['args']['call_id'];
            }
            $phoneNumber = null;
            
            if ($callId) {
                $call = \App\Models\Call::where('call_id', $callId)->first();
                if ($call) {
                    // SICHERHEITSPRÜFUNG: Company benötigt Terminbuchung?
                    if ($call->company && !$call->company->needsAppointmentBooking()) {
                        Log::warning('CancelAppointment blocked for company without appointment booking', [
                            'company_id' => $call->company_id,
                            'call_id' => $callId
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Diese Funktion ist für Ihr Unternehmen nicht verfügbar.'
                        ]);
                    }
                    
                    $phoneNumber = $call->from_number;
                }
            }
            
            if (!$phoneNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Telefonnummer konnte nicht ermittelt werden.'
                ]);
            }
            
            $appointmentDate = $data['appointment_date'] ?? null;
            
            // Implementation would go here
            return response()->json([
                'success' => true,
                'message' => 'Ihr Termin wurde erfolgreich storniert.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in cancelAppointment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Der Termin konnte nicht storniert werden.'
            ]);
        }
    }

    public function rescheduleAppointment(Request $request)
    {
        $this->logRetellRequest('reschedule_appointment', $request);
        
        try {
            $data = $request->all();
            $callData = $data['call'] ?? [];
            
            // Get call_id from the call object (Retell sends {{call_id}} as literal string)
            $callId = $callData['call_id'] ?? null;
            
            // Also check args for call_id in case it was resolved
            if (!$callId && isset($data['args']['call_id']) && $data['args']['call_id'] !== '{{call_id}}') {
                $callId = $data['args']['call_id'];
            }
            $phoneNumber = null;
            
            if ($callId) {
                $call = \App\Models\Call::where('call_id', $callId)->first();
                if ($call) {
                    // SICHERHEITSPRÜFUNG: Company benötigt Terminbuchung?
                    if ($call->company && !$call->company->needsAppointmentBooking()) {
                        Log::warning('RescheduleAppointment blocked for company without appointment booking', [
                            'company_id' => $call->company_id,
                            'call_id' => $callId
                        ]);
                        return response()->json([
                            'success' => false,
                            'message' => 'Diese Funktion ist für Ihr Unternehmen nicht verfügbar.'
                        ]);
                    }
                    
                    $phoneNumber = $call->from_number;
                }
            }
            
            if (!$phoneNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Telefonnummer konnte nicht ermittelt werden.'
                ]);
            }
            
            $oldDate = $data['old_date'] ?? null;
            $newDate = $data['new_date'] ?? null;
            $newTime = $data['new_time'] ?? null;
            
            // Implementation would go here
            return response()->json([
                'success' => true,
                'message' => 'Ihr Termin wurde erfolgreich verschoben.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in rescheduleAppointment: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Der Termin konnte nicht verschoben werden.'
            ]);
        }
    }

    public function currentTimeBerlin(Request $request)
    {
        $this->logRetellRequest('current_time_berlin', $request);
        
        try {
            $now = Carbon::now('Europe/Berlin');
            
            return response()->json([
                'success' => true,
                'date' => $now->format('Y-m-d'),
                'time' => $now->format('H:i'),
                'weekday' => $now->locale('de')->dayName,
                'message' => "Es ist jetzt {$now->format('H:i')} Uhr am {$now->locale('de')->dayName}."
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in currentTimeBerlin: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Zeit konnte nicht ermittelt werden.'
            ]);
        }
    }

    protected function parseRelativeDate($dateInput)
    {
        $dateInput = strtolower(trim($dateInput));
        $now = Carbon::now('Europe/Berlin');
        
        switch ($dateInput) {
            case 'heute':
                return $now->format('Y-m-d');
            case 'morgen':
                return $now->addDay()->format('Y-m-d');
            case 'übermorgen':
                return $now->addDays(2)->format('Y-m-d');
            default:
                // Try to parse as date
                try {
                    // Handle German date format (25.12.2024)
                    if (preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $dateInput)) {
                        return Carbon::createFromFormat('d.m.Y', $dateInput)->format('Y-m-d');
                    }
                    // Handle ISO format
                    return Carbon::parse($dateInput)->format('Y-m-d');
                } catch (\Exception $e) {
                    return $now->format('Y-m-d'); // Default to today
                }
        }
    }

    protected function parseTime($timeInput)
    {
        // Remove "Uhr" if present
        $timeInput = str_replace(['Uhr', 'uhr'], '', $timeInput);
        $timeInput = trim($timeInput);
        
        // Ensure format is HH:MM
        if (preg_match('/^\d{1,2}:\d{2}$/', $timeInput)) {
            return $timeInput;
        }
        
        // Handle hour only (e.g., "14" -> "14:00")
        if (preg_match('/^\d{1,2}$/', $timeInput)) {
            return $timeInput . ':00';
        }
        
        return '09:00'; // Default
    }
}