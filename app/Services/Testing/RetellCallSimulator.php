<?php

namespace App\Services\Testing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RetellCallSimulator
{
    private $webhookSecret;
    private $functionSecret;
    private $baseUrl;
    private $currentCallId;
    private $callData = [];

    public function __construct()
    {
        $this->webhookSecret = config('services.retellai.webhook_secret');
        $this->functionSecret = config('services.retellai.function_secret');
        $this->baseUrl = config('app.url');
        $this->currentCallId = 'test_call_' . uniqid();
    }

    /**
     * Simulate a complete call flow with appointment booking
     */
    public function simulateCompleteCallFlow(array $options = [])
    {
        $fromNumber = $options['from_number'] ?? '+491510' . rand(1000000, 9999999);
        $toNumber = $options['to_number'] ?? '+493083793369';
        $customerName = $options['customer_name'] ?? 'Test Kunde ' . rand(100, 999);
        $service = $options['service'] ?? 'Herrenhaarschnitt';
        $date = $options['date'] ?? Carbon::tomorrow()->format('d.m.Y');
        $time = $options['time'] ?? '14:00';

        $results = [];

        // Step 1: Call Started
        Log::info('🧪 Test: Starting simulated call', ['call_id' => $this->currentCallId]);
        $results['call_started'] = $this->sendCallStartedWebhook($fromNumber, $toNumber);

        // Step 2: Check Customer
        Log::info('🧪 Test: Checking customer', ['phone' => $fromNumber]);
        $results['customer_check'] = $this->sendFunctionCall('check_customer', [
            'phone_number' => $fromNumber
        ]);

        // Step 3: Check Availability
        Log::info('🧪 Test: Checking availability', ['date' => $date, 'service' => $service]);
        $results['availability'] = $this->sendFunctionCall('check_availability', [
            'service' => $service,
            'date' => Carbon::createFromFormat('d.m.Y', $date)->format('Y-m-d'),
            'time' => $time
        ]);

        // Step 4: Collect Appointment Data
        Log::info('🧪 Test: Collecting appointment data');
        $results['collect'] = $this->sendFunctionCall('collect_appointment', [
            'service' => $service,
            'customer_phone' => $fromNumber,
            'customer_name' => $customerName,
            'datum' => $date,
            'uhrzeit' => $time
        ]);

        // Step 5: Book Appointment
        if ($results['collect']['success'] ?? false) {
            Log::info('🧪 Test: Booking appointment');
            $results['booking'] = $this->sendFunctionCall('book_appointment', [
                'call_id' => $this->currentCallId,
                'customer_name' => $customerName,
                'customer_phone' => $fromNumber,
                'service' => $service,
                'date' => $date,
                'time' => $time,
                'notes' => 'Automatischer Test-Termin'
            ]);
        }

        // Step 6: Call Ended
        sleep(2); // Simulate call duration
        Log::info('🧪 Test: Ending call');
        $results['call_ended'] = $this->sendCallEndedWebhook();

        return $results;
    }

    /**
     * Send a call started webhook
     */
    public function sendCallStartedWebhook($fromNumber, $toNumber)
    {
        $payload = [
            'event' => 'call.started',
            'data' => [
                'call_id' => $this->currentCallId,
                'from_number' => $fromNumber,
                'to_number' => $toNumber,
                'direction' => 'inbound',
                'call_type' => 'phone_call',
                'agent_id' => config('services.retellai.agent_id') ?? 'agent_9a8202a740cd3120d96fcfda1e',
                'start_timestamp' => Carbon::now()->timestamp * 1000
            ]
        ];

        $this->callData['start_timestamp'] = $payload['data']['start_timestamp'];

        return $this->sendWebhook($payload);
    }

    /**
     * Send a call ended webhook
     */
    public function sendCallEndedWebhook($appointmentMade = false)
    {
        $endTimestamp = Carbon::now()->timestamp * 1000;
        $duration = $endTimestamp - ($this->callData['start_timestamp'] ?? $endTimestamp);

        $payload = [
            'event' => 'call.ended',
            'data' => [
                'call_id' => $this->currentCallId,
                'call_status' => 'ended',
                'end_timestamp' => $endTimestamp,
                'start_timestamp' => $this->callData['start_timestamp'] ?? $endTimestamp,
                'duration_ms' => $duration,
                'transcript' => $this->generateTranscript($appointmentMade),
                'summary' => $appointmentMade ?
                    'Kunde hat erfolgreich einen Termin gebucht.' :
                    'Kunde hat sich über Services informiert.',
                'metadata' => [
                    'appointment_made' => $appointmentMade,
                    'test_call' => true
                ]
            ]
        ];

        return $this->sendWebhook($payload);
    }

    /**
     * Send a function call request
     */
    public function sendFunctionCall($function, array $arguments)
    {
        $url = match($function) {
            'check_customer' => '/api/retell/check-customer',
            'check_availability' => '/api/retell/check-availability',
            'collect_appointment' => '/api/retell/collect-appointment',
            'book_appointment' => '/api/retell/book-appointment',
            default => throw new \Exception("Unknown function: $function")
        };

        $payload = array_merge(['call_id' => $this->currentCallId], $arguments);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->functionSecret,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . $url, $payload);

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'response' => $response->json(),
            'function' => $function,
            'arguments' => $arguments
        ];
    }

    /**
     * Send a webhook request
     */
    private function sendWebhook(array $payload)
    {
        $jsonPayload = json_encode($payload);
        $signature = hash_hmac('sha256', $jsonPayload, $this->webhookSecret);

        $response = Http::withHeaders([
            'X-Retell-Signature' => $signature,
            'Content-Type' => 'application/json'
        ])->post($this->baseUrl . '/api/webhooks/retell', $payload);

        return [
            'success' => $response->successful(),
            'status_code' => $response->status(),
            'response' => $response->json(),
            'event' => $payload['event'] ?? 'unknown'
        ];
    }

    /**
     * Generate realistic transcript
     */
    private function generateTranscript($appointmentMade = false)
    {
        if ($appointmentMade) {
            return "Agent: Guten Tag, wie kann ich Ihnen helfen?\n" .
                   "Kunde: Ich möchte gerne einen Termin für einen Herrenhaarschnitt buchen.\n" .
                   "Agent: Sehr gerne. Wann würde es Ihnen passen?\n" .
                   "Kunde: Morgen um 14 Uhr wäre gut.\n" .
                   "Agent: Perfekt, ich habe den Termin für Sie gebucht.\n" .
                   "Kunde: Vielen Dank!\n" .
                   "Agent: Gerne, bis morgen!";
        }

        return "Agent: Guten Tag, wie kann ich Ihnen helfen?\n" .
               "Kunde: Ich wollte mich nur über Ihre Services informieren.\n" .
               "Agent: Sehr gerne, was möchten Sie wissen?\n" .
               "Kunde: Welche Dienstleistungen bieten Sie an?\n" .
               "Agent: Wir bieten Herrenhaarschnitte, Damenhaarschnitte und Färben an.\n" .
               "Kunde: Danke für die Information.\n" .
               "Agent: Gerne, rufen Sie an wenn Sie einen Termin möchten.";
    }

    /**
     * Validate call was processed correctly
     */
    public function validateCallProcessing()
    {
        // Check database for call record
        $call = \App\Models\Call::where('retell_call_id', $this->currentCallId)->first();

        if (!$call) {
            return [
                'success' => false,
                'message' => 'Call not found in database',
                'call_id' => $this->currentCallId
            ];
        }

        // Check timezone
        $berlinTime = Carbon::now('Europe/Berlin');
        $callTime = Carbon::parse($call->created_at);
        $timeDiff = abs($berlinTime->timestamp - $callTime->timestamp);

        $validations = [
            'call_exists' => true,
            'call_id_matches' => $call->retell_call_id === $this->currentCallId,
            'status_is_ended' => $call->status === 'ended' || $call->status === 'completed',
            'timezone_correct' => $timeDiff < 60, // Within 1 minute
            'has_transcript' => !empty($call->transcript),
            'duration_recorded' => $call->duration_ms > 0
        ];

        // Check appointment if one was made
        if ($call->appointment_made) {
            $appointment = \App\Models\Appointment::where('call_id', $call->id)->first();
            $validations['appointment_created'] = !is_null($appointment);

            if ($appointment) {
                $validations['appointment_has_customer'] = !is_null($appointment->customer_id);
                $validations['appointment_has_service'] = !is_null($appointment->service_id);
                $validations['appointment_has_datetime'] = !is_null($appointment->scheduled_at);
            }
        }

        return [
            'success' => !in_array(false, $validations, true),
            'call_id' => $this->currentCallId,
            'database_id' => $call->id,
            'validations' => $validations,
            'call_data' => [
                'from' => $call->from_number,
                'to' => $call->to_number,
                'duration' => $call->duration_ms,
                'appointment_made' => $call->appointment_made
            ]
        ];
    }

    /**
     * Clean up test data
     */
    public function cleanup()
    {
        if ($this->currentCallId) {
            $call = \App\Models\Call::where('retell_call_id', $this->currentCallId)->first();

            if ($call) {
                // Delete related appointment
                \App\Models\Appointment::where('call_id', $call->id)->delete();

                // Delete the call
                $call->delete();

                Log::info('🧪 Test: Cleaned up test data', ['call_id' => $this->currentCallId]);
            }
        }
    }
}