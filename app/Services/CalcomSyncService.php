<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CalcomEventType;
use App\Models\Staff;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CalcomSyncService
{
    private $baseUrl = 'https://api.cal.com/v2';
    private $baseUrlV1 = 'https://api.cal.com/v1';
    
    /**
     * Synchronisiere Event-Types für ein Unternehmen
     * @deprecated Use EventTypeImportWizard instead for branch-specific imports
     */
    public function syncEventTypesForCompany($companyId)
    {
        $company = Company::findOrFail($companyId);
        
        if (!$company->calcom_api_key) {
            throw new \Exception('No Cal.com API key configured for company');
        }
        
        Log::warning('syncEventTypesForCompany is deprecated. Use EventTypeImportWizard for branch-specific imports.');
        
        try {
            DB::beginTransaction();
            
            // Hole alle Event-Types von Cal.com
            $eventTypes = $this->fetchEventTypesFromCalcom($company->calcom_api_key);
            
            if (!$eventTypes) {
                throw new \Exception('Failed to fetch event types from Cal.com');
            }
            
            $syncedCount = 0;
            $errors = [];
            
            foreach ($eventTypes as $eventTypeData) {
                try {
                    $this->syncEventType($company, $eventTypeData);
                    $syncedCount++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'event_type_id' => $eventTypeData['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    Log::error('Failed to sync event type', [
                        'company_id' => $companyId,
                        'event_type' => $eventTypeData,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            DB::commit();
            
            Log::info('Event types sync completed', [
                'company_id' => $companyId,
                'synced_count' => $syncedCount,
                'errors_count' => count($errors)
            ]);
            
            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'total_count' => count($eventTypes),
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Event types sync failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Synchronisiere Team-Mitglieder für ein Unternehmen
     */
    public function syncTeamMembers($companyId)
    {
        $company = Company::findOrFail($companyId);
        
        if (!$company->calcom_api_key) {
            throw new \Exception('No Cal.com API key configured for company');
        }
        
        try {
            DB::beginTransaction();
            
            // Hole Team-Mitglieder von Cal.com
            $teamMembers = $this->fetchTeamMembersFromCalcom($company->calcom_api_key);
            
            if (!$teamMembers) {
                throw new \Exception('Failed to fetch team members from Cal.com');
            }
            
            $matchedCount = 0;
            $notFoundCount = 0;
            
            foreach ($teamMembers as $member) {
                // Versuche Mitarbeiter über Email oder Name zu matchen
                $staff = Staff::where('company_id', $companyId)
                    ->where(function($query) use ($member) {
                        $query->where('email', $member['email'])
                              ->orWhere('name', 'LIKE', '%' . $member['name'] . '%');
                    })
                    ->first();
                
                if ($staff) {
                    $staff->update([
                        'calcom_user_id' => $member['id'],
                        'calcom_username' => $member['username'] ?? null
                    ]);
                    $matchedCount++;
                } else {
                    $notFoundCount++;
                    Log::warning('No matching staff found for Cal.com member', [
                        'company_id' => $companyId,
                        'cal_member' => $member
                    ]);
                }
            }
            
            DB::commit();
            
            return [
                'success' => true,
                'matched_count' => $matchedCount,
                'not_found_count' => $notFoundCount,
                'total_count' => count($teamMembers)
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Team members sync failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Prüfe Verfügbarkeit für einen Event-Type
     */
    public function checkAvailability($eventTypeId, $dateFrom, $dateTo, $staffId = null)
    {
        // Cache-Key generieren
        $cacheKey = $this->getAvailabilityCacheKey($eventTypeId, $dateFrom, $dateTo, $staffId);
        
        // Prüfe Cache
        if (Cache::has($cacheKey)) {
            Log::debug('Availability cache hit', ['cache_key' => $cacheKey]);
            return Cache::get($cacheKey);
        }
        
        $eventType = CalcomEventType::findOrFail($eventTypeId);
        $company = Company::findOrFail($eventType->company_id);
        
        if (!$company->calcom_api_key) {
            throw new \Exception('No Cal.com API key configured');
        }
        
        // Wenn ein spezifischer Mitarbeiter angefragt wurde
        if ($staffId) {
            $staff = Staff::findOrFail($staffId);
            
            // Prüfe ob Mitarbeiter diesem Event-Type zugeordnet ist
            $isAssigned = DB::table('staff_event_types')
                ->where('staff_id', $staffId)
                ->where('event_type_id', $eventTypeId)
                ->exists();
            
            if (!$isAssigned) {
                $result = [
                    'available' => false,
                    'slots' => [],
                    'message' => 'Staff member is not assigned to this service'
                ];
                
                // Cache negative result für 5 Minuten
                Cache::put($cacheKey, $result, now()->addMinutes(5));
                return $result;
            }
            
            // Prüfe Verfügbarkeit für spezifischen Mitarbeiter
            $result = $this->checkStaffAvailability($eventType, $staff, $dateFrom, $dateTo, $company->calcom_api_key);
            
        } else {
            // Prüfe Verfügbarkeit für alle zugeordneten Mitarbeiter
            $result = $this->checkTeamAvailability($eventType, $dateFrom, $dateTo, $company->calcom_api_key);
        }
        
        // Cache Result für 15 Minuten
        Cache::put($cacheKey, $result, now()->addMinutes(15));
        
        return $result;
    }
    
    /**
     * Private Helper Methods
     */
    
    private function fetchEventTypesFromCalcom($apiKey)
    {
        try {
            // Try v2 API first
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/event-types');
            
            if ($response->successful()) {
                $data = $response->json();
                $eventTypes = [];
                
                // Extract event types from v2 response structure
                if (isset($data['data']['eventTypeGroups'])) {
                    foreach ($data['data']['eventTypeGroups'] as $group) {
                        if (isset($group['eventTypes'])) {
                            $eventTypes = array_merge($eventTypes, $group['eventTypes']);
                        }
                    }
                }
                
                return $eventTypes;
            }
            
            // Fallback to v1 API if v2 fails
            Log::warning('Cal.com v2 API failed, trying v1', ['status' => $response->status()]);
            
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrlV1 . '/event-types', [
                'apiKey' => $apiKey
            ]);
            
            if ($response->successful()) {
                return $response->json()['event_types'] ?? [];
            }
            
            Log::error('Failed to fetch event types from Cal.com', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Cal.com API error', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    private function fetchTeamMembersFromCalcom($apiKey)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/teams', [
                'apiKey' => $apiKey
            ]);
            
            if ($response->successful()) {
                $teams = $response->json()['teams'] ?? [];
                $allMembers = [];
                
                // Sammle alle Mitglieder aus allen Teams
                foreach ($teams as $team) {
                    if (isset($team['members'])) {
                        $allMembers = array_merge($allMembers, $team['members']);
                    }
                }
                
                return $allMembers;
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Cal.com teams API error', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    private function syncEventType($company, $eventTypeData)
    {
        $eventType = CalcomEventType::updateOrCreate(
            [
                'company_id' => $company->id,
                'calcom_event_type_id' => $eventTypeData['id']
            ],
            [
                'name' => $eventTypeData['title'],
                'slug' => $eventTypeData['slug'],
                'description' => $eventTypeData['description'] ?? null,
                'duration_minutes' => $eventTypeData['length'],
                'calcom_numeric_event_type_id' => $eventTypeData['id'],
                'is_team_event' => $eventTypeData['schedulingType'] === 'COLLECTIVE',
                'requires_confirmation' => $eventTypeData['requiresConfirmation'] ?? false,
                'booking_limits' => $eventTypeData['bookingLimits'] ?? null,
                'metadata' => [
                    'position' => $eventTypeData['position'] ?? 0,
                    'hidden' => $eventTypeData['hidden'] ?? false,
                    'hosts' => $eventTypeData['hosts'] ?? [],
                    'users' => $eventTypeData['users'] ?? []
                ],
                'is_active' => !($eventTypeData['hidden'] ?? false),
                'last_synced_at' => now()
            ]
        );
        
        // Wenn es ein Team-Event ist, verknüpfe automatisch alle Team-Mitglieder
        if ($eventType->is_team_event && isset($eventTypeData['users'])) {
            $this->syncEventTypeUsers($eventType, $eventTypeData['users'], $company->id);
        }
        
        return $eventType;
    }
    
    private function syncEventTypeUsers($eventType, $users, $companyId)
    {
        foreach ($users as $user) {
            // Finde Mitarbeiter über Cal.com User ID oder Email
            $staff = Staff::where('company_id', $companyId)
                ->where(function($query) use ($user) {
                    $query->where('calcom_user_id', $user['id'])
                          ->orWhere('email', $user['email']);
                })
                ->first();
            
            if ($staff) {
                // Erstelle oder aktualisiere die Verknüpfung
                DB::table('staff_event_types')->updateOrInsert(
                    [
                        'staff_id' => $staff->id,
                        'event_type_id' => $eventType->id
                    ],
                    [
                        'calcom_user_id' => $user['id'],
                        'is_primary' => false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            }
        }
    }
    
    private function checkStaffAvailability($eventType, $staff, $dateFrom, $dateTo, $apiKey)
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->get($this->baseUrl . '/availability', [
                'apiKey' => $apiKey,
                'eventTypeId' => $eventType->calcom_numeric_event_type_id,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'username' => $staff->calcom_username ?? $staff->email
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'available' => !empty($data['slots']),
                    'slots' => $this->formatAvailabilitySlots($data['slots'] ?? [], $staff),
                    'staff_id' => $staff->id,
                    'staff_name' => $staff->name
                ];
            }
            
            return [
                'available' => false,
                'slots' => [],
                'error' => 'Failed to check availability'
            ];
            
        } catch (\Exception $e) {
            Log::error('Availability check failed', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'available' => false,
                'slots' => [],
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkTeamAvailability($eventType, $dateFrom, $dateTo, $apiKey)
    {
        // Hole alle zugeordneten Mitarbeiter
        $assignedStaff = DB::table('staff_event_types')
            ->join('staff', 'staff.id', '=', 'staff_event_types.staff_id')
            ->where('staff_event_types.event_type_id', $eventType->id)
            ->where('staff.active', true)
            ->where('staff.is_bookable', true)
            ->select('staff.*', 'staff_event_types.calcom_user_id')
            ->get();
        
        $allSlots = [];
        
        foreach ($assignedStaff as $staff) {
            $availability = $this->checkStaffAvailability(
                $eventType, 
                (object) $staff, 
                $dateFrom, 
                $dateTo, 
                $apiKey
            );
            
            if (!empty($availability['slots'])) {
                $allSlots = array_merge($allSlots, $availability['slots']);
            }
        }
        
        // Sortiere Slots nach Zeit
        usort($allSlots, function($a, $b) {
            return strtotime($a['start']) - strtotime($b['start']);
        });
        
        // Entferne Duplikate (gleiche Zeitslots von verschiedenen Mitarbeitern)
        $uniqueSlots = [];
        $seenTimes = [];
        
        foreach ($allSlots as $slot) {
            $timeKey = $slot['start'] . '-' . $slot['end'];
            if (!in_array($timeKey, $seenTimes)) {
                $seenTimes[] = $timeKey;
                $uniqueSlots[] = $slot;
            }
        }
        
        return [
            'available' => !empty($uniqueSlots),
            'slots' => $uniqueSlots,
            'available_staff_count' => $assignedStaff->count()
        ];
    }
    
    private function formatAvailabilitySlots($slots, $staff = null)
    {
        return array_map(function($slot) use ($staff) {
            $formatted = [
                'start' => $slot['time'],
                'end' => Carbon::parse($slot['time'])->addMinutes(30)->toIso8601String(), // Default 30 min
                'available' => true
            ];
            
            if ($staff) {
                $formatted['staff_id'] = $staff->id;
                $formatted['staff_name'] = $staff->name;
            }
            
            return $formatted;
        }, $slots);
    }
    
    /**
     * Generiere Cache-Key für Verfügbarkeitsabfragen
     */
    private function getAvailabilityCacheKey($eventTypeId, $dateFrom, $dateTo, $staffId = null)
    {
        $key = "availability.{$eventTypeId}." . md5($dateFrom . $dateTo);
        
        if ($staffId) {
            $key .= ".staff.{$staffId}";
        }
        
        return $key;
    }
    
    /**
     * Cache für einen Event-Type invalidieren
     */
    public function invalidateEventTypeCache($eventTypeId)
    {
        // Lösche alle Cache-Einträge für diesen Event-Type
        $pattern = "availability.{$eventTypeId}.*";
        
        // Laravel unterstützt kein Pattern-basiertes Löschen,
        // daher müssen wir Tags verwenden oder manuell verwalten
        Cache::tags(['availability', "event-type-{$eventTypeId}"])->flush();
        
        Log::info('Event type cache invalidated', ['event_type_id' => $eventTypeId]);
    }
    
    /**
     * Cache für alle Event-Types einer Company invalidieren
     */
    public function invalidateCompanyCache($companyId)
    {
        Cache::tags(['availability', "company-{$companyId}"])->flush();
        
        Log::info('Company cache invalidated', ['company_id' => $companyId]);
    }
}