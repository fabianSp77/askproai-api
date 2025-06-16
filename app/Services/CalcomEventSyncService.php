<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\CalcomEventType;
use App\Models\Staff;

class CalcomEventSyncService
{
    private $apiKey;
    private $baseUrl;
    private $teamSlug;

    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key');
        $this->baseUrl = 'https://api.cal.com/v1';
        $this->teamSlug = 'askproai';
    }

    /**
     * Synchronisiert alle Event-Types aus Cal.com
     */
    public function syncAllEventTypes()
    {
        try {
            // 1. Event-Types von Cal.com abrufen
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/event-types', [
                'apiKey' => $this->apiKey
            ]);

            if (!$response->successful()) {
                Log::error('Cal.com API Fehler beim Abrufen der Event-Types', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }

            $eventTypes = $response->json()['event_types'] ?? [];
            
            foreach ($eventTypes as $eventType) {
                $this->syncSingleEventType($eventType);
            }

            // 2. Mitarbeiter-Zuordnungen synchronisieren
            $this->syncEventTypeUsers();

            Log::info('Event-Type Synchronisation abgeschlossen', [
                'synced_count' => count($eventTypes)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Fehler bei Event-Type Synchronisation', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Synchronisiert einen einzelnen Event-Type
     */
    private function syncSingleEventType($eventTypeData)
    {
        CalcomEventType::updateOrCreate(
            [
                'calcom_numeric_event_type_id' => $eventTypeData['id']
            ],
            [
                'name' => $eventTypeData['title'],
                'slug' => $eventTypeData['slug'],
                'duration_minutes' => $eventTypeData['length'],
                'description' => $eventTypeData['description'] ?? '',
                'price' => $eventTypeData['price'] ?? 0,
                'is_active' => true,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'metadata' => json_encode($eventTypeData)
            ]
        );
    }

    /**
     * Synchronisiert Mitarbeiter-Zuordnungen zu Event-Types
     */
    private function syncEventTypeUsers()
    {
        try {
            // Team-Mitglieder von Cal.com abrufen
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/teams/' . $this->teamSlug . '/members', [
                'apiKey' => $this->apiKey
            ]);

            if ($response->successful()) {
                $members = $response->json()['members'] ?? [];
                
                foreach ($members as $member) {
                    $this->syncStaffMember($member);
                }
            }

        } catch (\Exception $e) {
            Log::error('Fehler bei Mitarbeiter-Synchronisation', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Synchronisiert einen Mitarbeiter mit Cal.com
     */
    private function syncStaffMember($memberData)
    {
        Staff::updateOrCreate(
            [
                'calcom_user_id' => $memberData['id']
            ],
            [
                'name' => $memberData['name'],
                'email' => $memberData['email'],
                'active' => true,
                'is_bookable' => true
            ]
        );
    }

    /**
     * Verknüpft Mitarbeiter mit Event-Types basierend auf Cal.com Daten
     */
    public function linkStaffToEventTypes()
    {
        try {
            // Alle Event-Types durchgehen und Mitarbeiter-Zuordnungen prüfen
            $eventTypes = CalcomEventType::where('is_active', true)->get();
            
            foreach ($eventTypes as $eventType) {
                $this->getEventTypeHosts($eventType);
            }

        } catch (\Exception $e) {
            Log::error('Fehler bei Staff-Event-Type Verknüpfung', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Ruft die Hosts eines Event-Types ab und verknüpft sie
     */
    private function getEventTypeHosts($eventType)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/event-types/' . $eventType->calcom_numeric_event_type_id, [
                'apiKey' => $this->apiKey
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $hosts = $data['event_type']['hosts'] ?? [];
                
                foreach ($hosts as $host) {
                    // Mitarbeiter in lokaler DB finden
                    $staff = Staff::where('calcom_user_id', $host['userId'])->first();
                    
                    if ($staff) {
                        // Verknüpfung erstellen falls noch nicht vorhanden
                        DB::table('staff_event_types')->updateOrInsert(
                            [
                                'staff_id' => $staff->id,
                                'event_type_id' => $eventType->id
                            ],
                            [
                                'created_at' => now(),
                                'updated_at' => now()
                            ]
                        );
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Fehler beim Abrufen der Event-Type Hosts', [
                'event_type_id' => $eventType->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
