<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalcomDebugService
{
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key');
        $this->baseUrl = 'https://api.cal.com/v1';
    }

    public function debugEventTypeHosts()
    {
        echo "=== DEBUGGING CAL.COM EVENT-TYPE HOSTS ===\n";
        
        // Alle Event-Types abrufen und Hosts prüfen
        $eventTypes = \App\Models\CalcomEventType::whereNotNull('calcom_numeric_event_type_id')
            ->take(3) // Nur erste 3 zum Testen
            ->get();

        foreach ($eventTypes as $eventType) {
            echo "\n--- Event: {$eventType->name} (ID: {$eventType->calcom_numeric_event_type_id}) ---\n";
            
            try {
                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->get($this->baseUrl . '/event-types/' . $eventType->calcom_numeric_event_type_id, [
                    'apiKey' => $this->apiKey
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Event-Type Details anzeigen
                    if (isset($data['event_type'])) {
                        $eventTypeData = $data['event_type'];
                        echo "Titel: " . ($eventTypeData['title'] ?? 'N/A') . "\n";
                        echo "Dauer: " . ($eventTypeData['length'] ?? 'N/A') . " Minuten\n";
                        
                        // Hosts prüfen
                        if (isset($eventTypeData['hosts']) && is_array($eventTypeData['hosts'])) {
                            echo "Hosts gefunden: " . count($eventTypeData['hosts']) . "\n";
                            foreach ($eventTypeData['hosts'] as $host) {
                                echo "  - Host ID: " . ($host['userId'] ?? 'N/A') . "\n";
                                echo "    Name: " . ($host['name'] ?? 'N/A') . "\n";
                                echo "    Email: " . ($host['email'] ?? 'N/A') . "\n";
                            }
                        } else {
                            echo "Keine Hosts gefunden oder falsches Format\n";
                        }
                        
                        // Users prüfen (alternative Struktur)
                        if (isset($eventTypeData['users']) && is_array($eventTypeData['users'])) {
                            echo "Users gefunden: " . count($eventTypeData['users']) . "\n";
                            foreach ($eventTypeData['users'] as $user) {
                                echo "  - User ID: " . ($user['id'] ?? 'N/A') . "\n";
                                echo "    Name: " . ($user['name'] ?? 'N/A') . "\n";
                                echo "    Email: " . ($user['email'] ?? 'N/A') . "\n";
                            }
                        }
                    }
                } else {
                    echo "API Fehler: " . $response->status() . " - " . $response->body() . "\n";
                }
                
            } catch (\Exception $e) {
                echo "Exception: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n=== TEAM MEMBERS PRÜFEN ===\n";
        $this->debugTeamMembers();
    }

    public function debugTeamMembers()
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/teams/askproai/members', [
                'apiKey' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                echo "Team Members Response:\n";
                echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "Team API Fehler: " . $response->status() . " - " . $response->body() . "\n";
            }
        } catch (\Exception $e) {
            echo "Team Exception: " . $e->getMessage() . "\n";
        }
    }
}
