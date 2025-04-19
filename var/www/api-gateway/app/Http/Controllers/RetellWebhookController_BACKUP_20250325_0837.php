<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Call;
use App\Models\Kunde;
use App\Models\Customer;
use App\Models\PhoneNumber;
use App\Models\Appointment;
use App\Models\Service;
use App\Services\CalcomService;

class RetellWebhookController extends Controller
{
    public function processWebhook(Request $request)
    {
        try {
            // Start logging
            Log::info('Retell Webhook empfangen', [
                'data' => $request->all()
            ]);

            $data = $request->all();

            // Basic validation
            if (empty($data['call_id'])) {
                Log::error('Webhook: Fehlende call_id', $data);
                return response()->json(['error' => 'Missing call_id'], 400);
            }

            // Create or update call
            $call = Call::updateOrCreate(
                ['call_id' => $data['call_id']],
                [
                    'call_status' => $data['status'] ?? null,
                    'user_sentiment' => $data['user_sentiment'] ?? null,
                    'successful' => $data['call_successful'] ?? false,
                    'call_duration' => $data['duration'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'name' => $data['_name'] ?? null,
                    'email' => $data['_email'] ?? null,
                    'summary' => $data['summary'] ?? null,
                    'transcript' => $data['transcript'] ?? null,
                    'disconnect_reason' => $data['disconnect_reason'] ?? null,
                    'raw_data' => json_encode($data)
                ]
            );

            // Find tenant/customer by phone number
            $tenant = null;
            if (!empty($data['phone_number'])) {
                $phoneRecord = PhoneNumber::where('phone_number', $data['phone_number'])->first();
                if ($phoneRecord && $phoneRecord->customer) {
                    $tenant = $phoneRecord->customer;
                    Log::info('Tenant identified', [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name
                    ]);
                }
            }

            // Associate customer with call
            $this->associateCustomer($call, $data);

            // Process appointment if date and time are provided
            if (isset($data['_datum__termin']) && isset($data['_uhrzeit__termin'])) {
                $appointment = $this->processAppointment($call, $data, $tenant);
                
                if ($appointment) {
                    Log::info('Appointment created', [
                        'appointment_id' => $appointment->id,
                        'start_time' => $appointment->start_time
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'call_id' => $call->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing webhook: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to process webhook'
            ], 500);
        }
    }

    private function associateCustomer($call, $data)
    {
        // Try to find existing customer by phone or create a new one
        if (!empty($data['phone_number'])) {
            $kunde = Kunde::firstOrCreate(
                ['telefonnummer' => $data['phone_number']],
                [
                    'name' => $data['_name'] ?? 'Unbekannt', 
                    'email' => $data['_email'] ?? null
                ]
            );

            $call->kunde_id = $kunde->id;
            $call->save();

            return $kunde;
        }

        return null;
    }

    private function processAppointment($call, $data, $tenant = null)
    {
        try {
            // Format date and time
            $dateStr = $data['_datum__termin'];
            $timeStr = $data['_uhrzeit__termin'];
            
            $startDateTime = date('Y-m-d H:i:s', strtotime("$dateStr $timeStr"));
            
            // Find service if matching
            $serviceName = $data['_dienstleistung'] ?? 'Unbekannte Dienstleistung';
            $service = null;
            
            if ($tenant) {
                $service = Service::where('customer_id', $tenant->id)
                    ->where('name', 'like', "%$serviceName%")
                    ->first();
            }
            
            // Calculate duration and end time
            $duration = $service ? $service->duration : 30; // Default 30 min
            $endDateTime = date('Y-m-d H:i:s', strtotime("$startDateTime +$duration minutes"));
            
            // Create appointment
            $appointment = new Appointment();
            $appointment->kunde_id = $call->kunde_id;
            $appointment->call_id = $call->id;
            $appointment->start_time = $startDateTime;
            $appointment->end_time = $endDateTime;
            $appointment->service = $serviceName;
            $appointment->service_id = $service ? $service->id : null;
            $appointment->notes = $data['_zusammenfassung'] ?? '';
            $appointment->status = 'scheduled';
            
            // If we have tenant and service, try to book in external calendar
            if ($tenant && $service && !empty($service->external_id)) {
                $calcomService = new CalcomService(
                    $tenant->api_key ?? env('CAL_COM_API_KEY'),
                    $tenant->default_user_id ?? env('CAL_COM_USER_ID')
                );
                
                $customerData = [
                    'name' => $data['_name'] ?? 'Unbekannt',
                    'email' => $data['_email'] ?? 'kunde@example.com',
                    'phone' => $data['phone_number'] ?? ''
                ];
                
                $externalBooking = $calcomService->createBooking(
                    $service->external_id,
                    $startDateTime,
                    $endDateTime,
                    $customerData['name'],
                    $customerData['email'],
                    $appointment->notes
                );
                
                if ($externalBooking && isset($externalBooking['id'])) {
                    $appointment->external_id = $externalBooking['id'];
                    $appointment->external_system = 'calcom';
                }
            }
            
            $appointment->save();
            return $appointment;
            
        } catch (\Exception $e) {
            Log::error('Error processing appointment: ' . $e->getMessage(), [
                'call_id' => $call->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
}
