<?php

namespace App\Services;

use App\Models\UnifiedEventType;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CalcomImportService
{
    private $apiKey;
    private $baseUrl = 'https://api.cal.com/v1';

    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey ?: config('services.calcom.api_key', 'cal_live_bd7aedbdf12085c5312c79ba73585920');
    }

    public function importEventTypes()
    {
        $results = [
            'imported' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'skipped' => 0,
            'details' => []
        ];

        try {
            // Event Types von Cal.com abrufen
            $response = Http::get($this->baseUrl . '/event-types', [
                'apiKey' => $this->apiKey
            ]);

            if (!$response->successful()) {
                Log::error('Cal.com API Error', ['response' => $response->body()]);
                throw new \Exception('Cal.com API Error: ' . $response->status());
            }

            $responseData = $response->json();
            
            // Debug-Logging um die Struktur zu sehen
            Log::info('Cal.com API Response Structure', [
                'type' => gettype($responseData),
                'keys' => is_array($responseData) ? array_keys($responseData) : 'not an array',
                'sample' => is_array($responseData) ? array_slice($responseData, 0, 1) : $responseData
            ]);

            // Prüfe ob die Antwort ein Wrapper-Objekt ist
            $eventTypes = [];
            if (isset($responseData['event_types'])) {
                $eventTypes = $responseData['event_types'];
            } elseif (isset($responseData['data'])) {
                $eventTypes = $responseData['data'];
            } elseif (is_array($responseData) && !empty($responseData)) {
                // Prüfe ob es ein Array von Event Types ist
                $firstElement = reset($responseData);
                if (is_array($firstElement)) {
                    $eventTypes = $responseData;
                }
            }

            if (empty($eventTypes)) {
                Log::warning('No event types found in Cal.com response', ['response' => $responseData]);
                return $results;
            }

            foreach ($eventTypes as $calcomEventType) {
                // Sicherstellen dass wir ein Array haben
                if (!is_array($calcomEventType)) {
                    Log::warning('Invalid event type data', ['data' => $calcomEventType]);
                    continue;
                }

                $result = $this->processEventType($calcomEventType);

                switch ($result['status']) {
                    case 'imported':
                        $results['imported']++;
                        break;
                    case 'duplicate':
                        $results['duplicates']++;
                        break;
                    case 'error':
                        $results['errors']++;
                        break;
                    case 'skipped':
                        $results['skipped']++;
                        break;
                }

                $results['details'][] = $result;
            }

        } catch (\Exception $e) {
            Log::error('Import Error', ['error' => $e->getMessage()]);
            $results['errors']++;
            $results['details'][] = [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }

        return $results;
    }

    private function processEventType($calcomData)
    {
        try {
            // Prüfe ob ID vorhanden ist
            if (!isset($calcomData['id'])) {
                Log::error('Event Type has no ID', ['data' => $calcomData]);
                return [
                    'status' => 'error',
                    'name' => $calcomData['title'] ?? 'Unknown',
                    'error' => 'Event Type has no ID field'
                ];
            }

            // Prüfe ob Event Type bereits existiert (basierend auf external_id)
            $existing = UnifiedEventType::where('external_id', $calcomData['id'])
                                       ->where('provider', 'calcom')
                                       ->first();

            if ($existing) {
                // Duplikat gefunden - Vergleiche Daten
                $differences = $this->compareEventTypes($existing, $calcomData);

                if (!empty($differences)) {
                    // Es gibt Unterschiede - markiere als Duplikat zur Überprüfung
                    $existing->update([
                        'import_status' => 'duplicate',
                        'conflict_data' => [
                            'calcom_data' => $calcomData,
                            'differences' => $differences,
                            'detected_at' => now()->toDateTimeString()
                        ]
                    ]);

                    return [
                        'status' => 'duplicate',
                        'event_type_id' => $existing->id,
                        'name' => $calcomData['title'] ?? 'Unknown',
                        'differences' => $differences
                    ];
                }

                // Keine Unterschiede - alles ok
                return [
                    'status' => 'skipped',
                    'event_type_id' => $existing->id,
                    'name' => $calcomData['title'] ?? 'Unknown',
                    'message' => 'Already imported, no changes'
                ];
            }

            // Neuer Event Type - importieren
            $eventType = UnifiedEventType::create([
                'provider' => 'calcom',
                'external_id' => $calcomData['id'],
                'name' => $calcomData['title'] ?? 'Unnamed Event',
                'slug' => $calcomData['slug'] ?? Str::slug($calcomData['title'] ?? 'unnamed'),
                'duration_minutes' => $calcomData['length'] ?? 30,
                'price' => $calcomData['price'] ?? 0,
                'description' => $calcomData['description'] ?? '',
                'assignment_status' => 'unassigned',
                'import_status' => 'success',
                'imported_at' => now(),
                'provider_data' => $calcomData,
                'is_active' => isset($calcomData['hidden']) && $calcomData['hidden'] ? false : true
            ]);

            return [
                'status' => 'imported',
                'event_type_id' => $eventType->id,
                'name' => $eventType->name
            ];

        } catch (\Exception $e) {
            Log::error('Event Type Processing Error', [
                'calcom_id' => isset($calcomData['id']) ? $calcomData['id'] : 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'status' => 'error',
                'name' => $calcomData['title'] ?? 'Unknown',
                'error' => $e->getMessage()
            ];
        }
    }

    private function compareEventTypes($existing, $calcomData)
    {
        $differences = [];

        // Vergleiche wichtige Felder
        $fieldsToCompare = [
            'name' => 'title',
            'duration_minutes' => 'length',
            'price' => 'price',
            'description' => 'description',
            'slug' => 'slug'
        ];

        foreach ($fieldsToCompare as $localField => $calcomField) {
            $localValue = $existing->$localField;
            $calcomValue = $calcomData[$calcomField] ?? null;

            // Spezielle Behandlung für Preis (Dezimal vs Integer)
            if ($localField === 'price') {
                $localValue = floatval($localValue);
                $calcomValue = floatval($calcomValue ?? 0);
            }

            if ($localValue != $calcomValue) {
                $differences[$localField] = [
                    'local' => $localValue,
                    'calcom' => $calcomValue
                ];
            }
        }

        return $differences;
    }

    public function resolveDuplicate($eventTypeId, $action = 'keep_local')
    {
        $eventType = UnifiedEventType::find($eventTypeId);

        if (!$eventType || $eventType->import_status !== 'duplicate') {
            return false;
        }

        switch ($action) {
            case 'keep_local':
                // Behalte lokale Daten, markiere als resolved
                $eventType->update([
                    'import_status' => 'success',
                    'conflict_data' => null
                ]);
                break;

            case 'use_calcom':
                // Übernehme Cal.com Daten
                if ($eventType->conflict_data && isset($eventType->conflict_data['calcom_data'])) {
                    $calcomData = $eventType->conflict_data['calcom_data'];
                    $eventType->update([
                        'name' => $calcomData['title'] ?? $eventType->name,
                        'slug' => $calcomData['slug'] ?? $eventType->slug,
                        'duration_minutes' => $calcomData['length'] ?? $eventType->duration_minutes,
                        'price' => $calcomData['price'] ?? $eventType->price,
                        'description' => $calcomData['description'] ?? $eventType->description,
                        'provider_data' => $calcomData,
                        'import_status' => 'success',
                        'conflict_data' => null
                    ]);
                }
                break;
        }

        return true;
    }
}
