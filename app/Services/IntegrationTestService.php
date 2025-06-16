<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class IntegrationTestService
{
    /**
     * Create a test booking for a branch
     *
     * @param Branch $branch
     * @return array
     */
    public function createTestBooking(Branch $branch): array
    {
        try {
            // Create test customer
            $customer = Customer::create([
                'name' => 'Test Kunde',
                'email' => 'test@askproai.de',
                'phone' => '+49 170 1234567',
                'company_id' => $branch->company_id,
                'branch_id' => $branch->id,
            ]);

            // Get calendar configuration
            $calcomConfig = $branch->getEffectiveCalcomConfig();
            
            if (!$calcomConfig) {
                return [
                    'success' => false,
                    'message' => 'Keine Kalender-Konfiguration gefunden'
                ];
            }

            // Create appointment for tomorrow 10:00
            $appointmentTime = Carbon::tomorrow()->setHour(10)->setMinute(0);
            
            $appointment = Appointment::create([
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'staff_id' => $branch->staff()->first()?->id,
                'start_time' => $appointmentTime,
                'end_time' => $appointmentTime->copy()->addMinutes(30),
                'status' => 'confirmed',
                'notes' => 'Test-Buchung erstellt über Integration Test',
            ]);

            // TODO: Actually book in Cal.com using CalcomService
            
            return [
                'success' => true,
                'message' => "Test-Buchung erfolgreich erstellt für {$appointmentTime->format('d.m.Y H:i')} Uhr",
                'appointment_id' => $appointment->id
            ];
            
        } catch (\Exception $e) {
            Log::error('Test booking failed', [
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Fehler: ' . $e->getMessage()
            ];
        }
    }
/**
     * Test Cal.com connection
     */
    public function testCalcomConnection($apiKey)
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->get('https://api.cal.com/v1/event-types', [
                    'apiKey' => $apiKey
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Verbindung erfolgreich',
                    'details' => 'Event-Typen gefunden: ' . count($data['event_types'] ?? [])
                ];
            }

            return [
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen',
                'details' => 'Status: ' . $response->status() . ' - ' . $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Cal.com test failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Test Retell.ai connection
     */
    public function testRetellConnection($apiKey, $agentId = null)
    {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->get('https://api.retellai.com/v1/agents');

            if ($response->successful()) {
                $data = $response->json();
                $agentCount = count($data['agents'] ?? []);
                
                // Check if specific agent exists
                $agentFound = false;
                if ($agentId && isset($data['agents'])) {
                    foreach ($data['agents'] as $agent) {
                        if ($agent['agent_id'] === $agentId) {
                            $agentFound = true;
                            break;
                        }
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Verbindung erfolgreich',
                    'details' => $agentId && !$agentFound 
                        ? "Agent ID nicht gefunden (Insgesamt $agentCount Agents vorhanden)"
                        : "Agents gefunden: $agentCount"
                ];
            }

            return [
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen',
                'details' => 'Status: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('Retell.ai test failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen',
                'details' => $e->getMessage()
            ];
        }
    }
}
