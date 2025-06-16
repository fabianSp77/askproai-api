<?php

namespace App\Services\Integrations;

use App\Models\ValidationResult;
use App\Services\CalcomService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CalcomEnhancedIntegration
{
    private CalcomService $calcomService;

    public function __construct()
    {
        $this->calcomService = app(CalcomService::class);
    }

    public function validateAndSyncConfiguration($entity, string $entityType = 'branch'): array
    {
        $results = [
            'timestamp' => now()->toIso8601String(),
            'entity' => "{$entityType}:{$entity->id}",
            'tests' => []
        ];

        try {
            // Test 1: API-Verbindung
            $results['tests']['api_connection'] = $this->testApiConnection($entity);

            // Test 2: Event-Type Validierung
            $results['tests']['event_types'] = $this->validateEventTypes($entity);

            // Test 3: Mitarbeiter/Kalender Synchronisation
            if ($entityType === 'branch') {
                $results['tests']['staff_calendars'] = $this->validateStaffCalendars($entity);
            }

            // Test 4: Verfügbarkeit prüfen
            $results['tests']['availability'] = $this->testAvailability($entity);

            // Gesamtstatus ermitteln
            $overallStatus = $this->determineOverallStatus($results);

            // Ergebnisse speichern
            ValidationResult::updateOrCreate(
                [
                    'entity_type' => $entityType,
                    'entity_id' => $entity->id,
                    'test_type' => 'full_validation'
                ],
                [
                    'status' => $overallStatus,
                    'results' => $results,
                    'tested_at' => now(),
                    'expires_at' => now()->addHours(24)
                ]
            );

        } catch (\Exception $e) {
            Log::error('Validation error', [
                'entity' => "{$entityType}:{$entity->id}",
                'error' => $e->getMessage()
            ]);
            
            $results['tests']['general'] = [
                'status' => 'error',
                'message' => 'Validation fehlgeschlagen: ' . $e->getMessage()
            ];
        }

        return $results;
    }

    private function testApiConnection($entity): array
    {
        try {
            $eventTypes = $this->calcomService->getEventTypes();
            
            return [
                'status' => $eventTypes !== null ? 'success' : 'error',
                'message' => $eventTypes !== null 
                    ? 'API-Verbindung erfolgreich' 
                    : 'API-Verbindung fehlgeschlagen',
                'details' => $eventTypes !== null 
                    ? ['event_types_count' => count($eventTypes)] 
                    : null
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'API-Verbindungsfehler',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }

    private function validateEventTypes($entity): array
    {
        try {
            $eventTypes = $this->calcomService->getEventTypes();
            if (!$eventTypes) {
                return [
                    'status' => 'error',
                    'message' => 'Keine Event-Types gefunden'
                ];
            }

            $configuredEventTypeId = $entity->calcom_event_type_id ?? null;
            if (!$configuredEventTypeId) {
                return [
                    'status' => 'warning',
                    'message' => 'Kein Event-Type konfiguriert'
                ];
            }

            $found = collect($eventTypes)->firstWhere('id', $configuredEventTypeId);
            
            return [
                'status' => $found ? 'success' : 'error',
                'message' => $found 
                    ? "Event-Type '{$found['title']}' gefunden" 
                    : 'Konfigurierter Event-Type nicht gefunden',
                'details' => $found ? [
                    'id' => $found['id'],
                    'title' => $found['title'],
                    'duration' => $found['length']
                ] : null
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Event-Type Validierung fehlgeschlagen',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }

    private function validateStaffCalendars($branch): array
    {
        $results = [
            'status' => 'success',
            'message' => 'Mitarbeiter-Kalender validiert',
            'details' => []
        ];

        try {
            $staff = $branch->staff()->where('active', true)->get();
            
            foreach ($staff as $staffMember) {
                $calendarValid = !empty($staffMember->calcom_user_id);
                
                $results['details'][$staffMember->name] = [
                    'status' => $calendarValid ? 'success' : 'warning',
                    'calendar_id' => $staffMember->calcom_user_id
                ];

                if (!$calendarValid) {
                    $results['status'] = 'warning';
                    $results['message'] = 'Einige Mitarbeiter haben keine Kalender-ID';
                }
            }
        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['message'] = 'Fehler bei Mitarbeiter-Validierung';
            $results['details']['error'] = $e->getMessage();
        }

        return $results;
    }

    private function testAvailability($entity): array
    {
        try {
            $eventTypeId = $entity->calcom_event_type_id ?? null;
            if (!$eventTypeId) {
                return [
                    'status' => 'warning',
                    'message' => 'Keine Event-Type ID für Verfügbarkeitstest'
                ];
            }

            $dateFrom = now()->addDay()->format('Y-m-d');
            $dateTo = now()->addDays(7)->format('Y-m-d');

            $availability = $this->calcomService->checkAvailability(
                $eventTypeId,
                $dateFrom,
                $dateTo
            );

            if ($availability && isset($availability['days']) && count($availability['days']) > 0) {
                $totalSlots = 0;
                foreach ($availability['days'] as $day) {
                    $totalSlots += count($day['slots'] ?? []);
                }

                return [
                    'status' => $totalSlots > 0 ? 'success' : 'warning',
                    'message' => $totalSlots > 0 
                        ? "Verfügbar: {$totalSlots} Slots in den nächsten 7 Tagen" 
                        : 'Keine verfügbaren Termine in den nächsten 7 Tagen',
                    'details' => [
                        'total_slots' => $totalSlots,
                        'days_checked' => count($availability['days'])
                    ]
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Verfügbarkeitsprüfung fehlgeschlagen'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Fehler bei Verfügbarkeitsprüfung',
                'details' => ['error' => $e->getMessage()]
            ];
        }
    }

    private function determineOverallStatus(array $results): string
    {
        $hasError = false;
        $hasWarning = false;

        foreach ($results['tests'] as $test) {
            if ($test['status'] === 'error') {
                $hasError = true;
            } elseif ($test['status'] === 'warning') {
                $hasWarning = true;
            }
        }

        if ($hasError) return ValidationResult::STATUS_ERROR;
        if ($hasWarning) return ValidationResult::STATUS_WARNING;
        return ValidationResult::STATUS_SUCCESS;
    }
}
