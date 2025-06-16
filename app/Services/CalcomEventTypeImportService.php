<?php

namespace App\Services;

use App\Models\UnifiedEventType;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalcomEventTypeImportService
{
    private $apiKey;
    private $baseUrl = 'https://api.cal.com/v1';
    
    public function __construct()
    {
        $this->apiKey = config('services.calcom.api_key');
    }
    
    public function importEventTypes(): array
    {
        $stats = [
            'imported' => 0,
            'updated' => 0,
            'duplicates' => 0,
            'errors' => 0
        ];
        
        try {
            $response = Http::get($this->baseUrl . '/event-types', [
                'apiKey' => $this->apiKey
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Cal.com API request failed: ' . $response->body());
            }
            
            $eventTypes = $response->json();
            
            if (!isset($eventTypes['event_types']) || !is_array($eventTypes['event_types'])) {
                Log::warning('No event types found in API response', ['response' => $eventTypes]);
                return $stats;
            }
            
            foreach ($eventTypes['event_types'] as $eventType) {
                try {
                    $this->processEventType($eventType, $stats);
                } catch (\Exception $e) {
                    Log::error('Error processing event type', [
                        'event_type_id' => $eventType['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    $stats['errors']++;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Cal.com import failed', ['error' => $e->getMessage()]);
            throw $e;
        }
        
        return $stats;
    }
    
    public function getEventTypeById($eventTypeId): ?array
    {
        try {
            $response = Http::get($this->baseUrl . '/event-types', [
                'apiKey' => $this->apiKey
            ]);
            
            if (!$response->successful()) {
                throw new \Exception('Cal.com API request failed');
            }
            
            $eventTypes = $response->json();
            
            if (!isset($eventTypes['event_types']) || !is_array($eventTypes['event_types'])) {
                return null;
            }
            
            foreach ($eventTypes['event_types'] as $eventType) {
                if (isset($eventType['id']) && $eventType['id'] == $eventTypeId) {
                    return $eventType;
                }
            }
            
            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching event type by ID', [
                'event_type_id' => $eventTypeId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    private function processEventType(array $eventType, array &$stats): void
    {
        // Extract event type ID safely
        $externalId = $eventType['id'] ?? null;
        if (!$externalId) {
            Log::warning('Event type without ID skipped', ['data' => $eventType]);
            return;
        }
        
        // Check for existing event type
        $existingEventType = UnifiedEventType::where('external_id', $externalId)->first();
        
        if ($existingEventType) {
            // Check if data has changed
            $hasChanges = $this->hasDataChanged($existingEventType, $eventType);
            
            if ($hasChanges) {
                $existingEventType->update(['has_duplicate' => true]);
                $stats['duplicates']++;
                Log::info('Duplicate found for event type', [
                    'external_id' => $externalId,
                    'title' => $eventType['title'] ?? 'Unknown'
                ]);
            } else {
                $stats['updated']++;
            }
        } else {
            // Create new event type
            $this->createEventType($eventType);
            $stats['imported']++;
        }
    }
    
    private function hasDataChanged(UnifiedEventType $existingEventType, array $calcomData): bool
    {
        $changes = [];
        
        // Compare title
        if ($existingEventType->title !== ($calcomData['title'] ?? '')) {
            $changes[] = 'title';
        }
        
        // Compare slug
        if ($existingEventType->slug !== ($calcomData['slug'] ?? '')) {
            $changes[] = 'slug';
        }
        
        // Compare duration
        if ($existingEventType->duration != ($calcomData['length'] ?? 0)) {
            $changes[] = 'duration';
        }
        
        // Compare price
        if ($existingEventType->price != ($calcomData['price'] ?? 0)) {
            $changes[] = 'price';
        }
        
        // Compare active status
        $calcomIsActive = !($calcomData['hidden'] ?? false);
        if ($existingEventType->is_active !== $calcomIsActive) {
            $changes[] = 'is_active';
        }
        
        if (!empty($changes)) {
            Log::info('Data changes detected', [
                'external_id' => $existingEventType->external_id,
                'changes' => $changes
            ]);
        }
        
        return !empty($changes);
    }
    
    private function createEventType(array $eventType): UnifiedEventType
    {
        // Get or create default company
        $company = Company::firstOrCreate(
            ['name' => 'Default Company'],
            [
                'email' => 'info@example.com',
                'phone' => '0000000000',
                'api_provider' => 'calcom',
                'api_key' => $this->apiKey
            ]
        );
        
        return UnifiedEventType::create([
            'company_id' => $company->id,
            'external_id' => $eventType['id'],
            'title' => $eventType['title'] ?? 'Unnamed Event',
            'slug' => $eventType['slug'] ?? 'unnamed-' . uniqid(),
            'description' => $eventType['description'] ?? null,
            'duration' => $eventType['length'] ?? 30,
            'price' => $eventType['price'] ?? 0,
            'is_active' => !($eventType['hidden'] ?? false),
            'has_duplicate' => false,
        ]);
    }
}
