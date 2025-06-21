# Event Type Import Wizard - Comprehensive Improvement Plan

## Current Issues Identified

### 1. **Naming Improvements Not Visible**
- The SmartEventTypeNameParser is being used but its improvements are not fully utilized
- Line 389: `'suggested_name'` is set but not displayed in the UI
- Line 392: `'name_options'` contains multiple format options but UI doesn't show them
- The blade template only shows the original name and parsed service name

### 2. **All Event Types Selected by Default**
- Line 400: Selection logic is based on `suggested_action === 'import'`
- Line 393: All event types get `'suggested_action' => 'import'` by default
- No intelligent filtering based on relevance or existing event types

### 3. **Limited Information Display**
- Current UI only shows: Original Name, Duration, Branch/Service analysis, Status
- Missing: Team info, pricing, locations, confirmation requirements, buffers, etc.

### 4. **No Search/Filter Capabilities**
- No search box to filter event types by name
- No filters for team vs individual events
- No filters by duration, price, or other attributes

### 5. **Teams/Groups Not Handled**
- Line 342-346: Event type groups are extracted but not preserved
- No indication which events belong to which team/group
- No grouping in the UI display

## Comprehensive Improvements

### 1. Enhanced Name Display & Selection

```php
// In loadEventTypesPreview() method, enhance the preview data:
$this->eventTypesPreview[] = [
    'original' => $eventType,
    'original_name' => $originalName,
    'extracted_service' => $cleanService,
    'suggested_names' => $formats,
    'selected_name_format' => 'compact', // Allow user to choose format
    'recommended_name' => $formats['compact'],
    'name_preview' => $formats['compact'],
    'suggested_action' => $this->determineSmartAction($eventType, $existingEventTypes),
    'confidence_score' => $this->calculateConfidenceScore($eventType, $branch),
    'team_info' => [
        'id' => $eventType['teamId'] ?? null,
        'name' => $eventType['team']['name'] ?? null,
        'slug' => $eventType['team']['slug'] ?? null,
    ],
    'additional_info' => [
        'locations' => $eventType['locations'] ?? [],
        'price' => $eventType['price'] ?? 0,
        'currency' => $eventType['currency'] ?? 'EUR',
        'requiresConfirmation' => $eventType['requiresConfirmation'] ?? false,
        'minimumBookingNotice' => $eventType['minimumBookingNotice'] ?? 0,
        'buffers' => [
            'before' => $eventType['beforeEventBuffer'] ?? 0,
            'after' => $eventType['afterEventBuffer'] ?? 0,
        ],
        'seatsPerTimeSlot' => $eventType['seatsPerTimeSlot'] ?? null,
        'schedulingType' => $eventType['schedulingType'] ?? null,
    ]
];
```

### 2. Smart Selection Logic

```php
private function determineSmartAction($eventType, $existingEventTypes): string
{
    // Check if similar event type already exists
    foreach ($existingEventTypes as $existing) {
        if ($this->isSimilarEventType($eventType, $existing)) {
            return 'skip'; // Already exists
        }
    }
    
    // Check if it's a test/demo event
    if ($this->isTestEventType($eventType)) {
        return 'skip';
    }
    
    // Check relevance to branch
    if (!$this->isRelevantToBranch($eventType)) {
        return 'manual'; // Requires user decision
    }
    
    return 'import'; // Good to import
}
```

### 3. Enhanced UI Components

#### Step 2 Blade Template Improvements:

```blade
<!-- Add search and filters -->
<div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="text-sm font-medium">Suche</label>
        <input type="text" 
               wire:model.live="searchTerm" 
               placeholder="Event-Type suchen..."
               class="w-full rounded-md border-gray-300">
    </div>
    
    <div>
        <label class="text-sm font-medium">Team Filter</label>
        <select wire:model.live="teamFilter" class="w-full rounded-md border-gray-300">
            <option value="">Alle Teams</option>
            @foreach($teams as $teamId => $teamName)
                <option value="{{ $teamId }}">{{ $teamName }}</option>
            @endforeach
        </select>
    </div>
    
    <div>
        <label class="text-sm font-medium">Status Filter</label>
        <select wire:model.live="statusFilter" class="w-full rounded-md border-gray-300">
            <option value="">Alle</option>
            <option value="import">Empfohlen</option>
            <option value="manual">Überprüfung nötig</option>
            <option value="skip">Überspringen</option>
        </select>
    </div>
</div>

<!-- Enhanced table with more information -->
<table class="w-full divide-y divide-gray-200">
    <thead>
        <tr>
            <th><!-- Checkbox --></th>
            <th>Event Type Details</th>
            <th>Namensvorschläge</th>
            <th>Zusätzliche Infos</th>
            <th>Empfehlung</th>
        </tr>
    </thead>
    <tbody>
        @foreach($filteredEventTypes as $index => $preview)
            <tr class="hover:bg-gray-50">
                <td class="p-4">
                    <input type="checkbox" wire:model.live="importSelections.{{ $index }}">
                </td>
                
                <td class="p-4">
                    <div class="space-y-1">
                        <div class="font-medium">{{ $preview['original_name'] }}</div>
                        <div class="text-sm text-gray-500">
                            <span class="inline-flex items-center">
                                <x-heroicon-o-clock class="w-4 h-4 mr-1"/>
                                {{ $preview['original']['length'] }} Min
                            </span>
                            @if($preview['team_info']['name'])
                                <span class="ml-3 inline-flex items-center">
                                    <x-heroicon-o-user-group class="w-4 h-4 mr-1"/>
                                    {{ $preview['team_info']['name'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                </td>
                
                <td class="p-4">
                    <select wire:model.live="nameFormats.{{ $index }}" 
                            class="w-full text-sm rounded border-gray-300">
                        @foreach($preview['suggested_names'] as $format => $name)
                            <option value="{{ $format }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <div class="mt-1 text-xs text-gray-500">
                        Service: {{ $preview['extracted_service'] }}
                    </div>
                </td>
                
                <td class="p-4 text-sm">
                    <div class="space-y-1">
                        @if($preview['additional_info']['price'] > 0)
                            <div>
                                <x-heroicon-o-currency-euro class="w-4 h-4 inline mr-1"/>
                                {{ number_format($preview['additional_info']['price'] / 100, 2) }} 
                                {{ $preview['additional_info']['currency'] }}
                            </div>
                        @endif
                        
                        @if($preview['additional_info']['requiresConfirmation'])
                            <div class="text-amber-600">
                                <x-heroicon-o-exclamation-triangle class="w-4 h-4 inline mr-1"/>
                                Bestätigung erforderlich
                            </div>
                        @endif
                        
                        @if($preview['additional_info']['locations'])
                            <div>
                                <x-heroicon-o-map-pin class="w-4 h-4 inline mr-1"/>
                                {{ count($preview['additional_info']['locations']) }} Location(s)
                            </div>
                        @endif
                    </div>
                </td>
                
                <td class="p-4">
                    <div class="flex items-center space-x-2">
                        @if($preview['confidence_score'] >= 80)
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="text-sm text-green-600">Empfohlen</span>
                        @elseif($preview['confidence_score'] >= 50)
                            <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                            <span class="text-sm text-yellow-600">Prüfen</span>
                        @else
                            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                            <span class="text-sm text-red-600">Überspringen</span>
                        @endif
                        <span class="text-xs text-gray-500">({{ $preview['confidence_score'] }}%)</span>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

### 4. Additional Features to Implement

#### A. Bulk Actions
```php
public function bulkSelectByTeam($teamId): void
{
    foreach ($this->eventTypesPreview as $index => $preview) {
        if ($preview['team_info']['id'] == $teamId) {
            $this->importSelections[$index] = true;
        }
    }
}

public function bulkSelectByDuration($minDuration, $maxDuration): void
{
    foreach ($this->eventTypesPreview as $index => $preview) {
        $duration = $preview['original']['length'];
        if ($duration >= $minDuration && $duration <= $maxDuration) {
            $this->importSelections[$index] = true;
        }
    }
}
```

#### B. Import History & Duplicate Detection
```php
private function loadImportHistory(): void
{
    $this->importHistory = DB::table('event_type_import_logs')
        ->where('branch_id', $this->branch_id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
}

private function detectDuplicates(): void
{
    $existingEventTypes = CalcomEventType::where('branch_id', $this->branch_id)
        ->pluck('calcom_numeric_event_type_id')
        ->toArray();
    
    foreach ($this->eventTypesPreview as &$preview) {
        $preview['is_duplicate'] = in_array($preview['original']['id'], $existingEventTypes);
    }
}
```

#### C. Advanced Filtering
```php
public function applyFilters(): void
{
    $this->filteredEventTypes = collect($this->eventTypesPreview);
    
    // Search filter
    if ($this->searchTerm) {
        $this->filteredEventTypes = $this->filteredEventTypes->filter(function ($item) {
            return str_contains(strtolower($item['original_name']), strtolower($this->searchTerm));
        });
    }
    
    // Team filter
    if ($this->teamFilter) {
        $this->filteredEventTypes = $this->filteredEventTypes->filter(function ($item) {
            return $item['team_info']['id'] == $this->teamFilter;
        });
    }
    
    // Status filter
    if ($this->statusFilter) {
        $this->filteredEventTypes = $this->filteredEventTypes->filter(function ($item) {
            return $item['suggested_action'] == $this->statusFilter;
        });
    }
}
```

### 5. Summary Dashboard (Step 4)

```blade
<!-- Enhanced summary with statistics -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-blue-50 rounded-lg p-4">
        <div class="text-2xl font-bold text-blue-600">{{ $importSummary['total_found'] }}</div>
        <div class="text-sm text-gray-600">Gefunden</div>
    </div>
    
    <div class="bg-green-50 rounded-lg p-4">
        <div class="text-2xl font-bold text-green-600">{{ $importSummary['total_selected'] }}</div>
        <div class="text-sm text-gray-600">Ausgewählt</div>
    </div>
    
    <div class="bg-purple-50 rounded-lg p-4">
        <div class="text-2xl font-bold text-purple-600">{{ $importSummary['teams_count'] }}</div>
        <div class="text-sm text-gray-600">Teams</div>
    </div>
    
    <div class="bg-amber-50 rounded-lg p-4">
        <div class="text-2xl font-bold text-amber-600">{{ $importSummary['total_duration'] }} Min</div>
        <div class="text-sm text-gray-600">Gesamtdauer</div>
    </div>
</div>

<!-- Group by teams -->
@foreach($importSummary['by_team'] as $teamName => $teamEvents)
    <div class="mb-4">
        <h4 class="font-medium text-gray-700 mb-2">{{ $teamName }}</h4>
        <div class="bg-gray-50 rounded-lg p-3 space-y-2">
            @foreach($teamEvents as $event)
                <div class="flex justify-between items-center">
                    <span>{{ $event['name'] }}</span>
                    <span class="text-sm text-gray-500">{{ $event['duration'] }} Min</span>
                </div>
            @endforeach
        </div>
    </div>
@endforeach
```

## Implementation Priority

1. **High Priority**
   - Fix name display to show SmartEventTypeNameParser suggestions
   - Implement intelligent default selection
   - Add search functionality
   
2. **Medium Priority**
   - Display additional Cal.com data (teams, pricing, etc.)
   - Add bulk selection features
   - Implement duplicate detection
   
3. **Low Priority**
   - Advanced filters
   - Import history
   - Statistics dashboard

## Migration Notes

- Preserve backward compatibility with existing imports
- Add feature flags for gradual rollout
- Test thoroughly with different Cal.com account types (individual, team, enterprise)