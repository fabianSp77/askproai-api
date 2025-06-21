# Event Type Import Wizard - Quick Fixes

## Immediate Fixes to Implement

### 1. Fix Name Display (5 minutes)
The SmartEventTypeNameParser results are being calculated but not shown in the UI.

**In blade template (line 86-96)**, replace:
```blade
@if($preview['parsed']['success'])
    <div class="text-sm">
        <div>Filiale: <span class="font-medium">{{ $preview['parsed']['branch_name'] }}</span></div>
        <div>Service: <span class="font-medium">{{ $preview['parsed']['service_name'] }}</span></div>
    </div>
@else
    <div class="text-sm text-red-600 dark:text-red-400">
        {{ $preview['parsed']['error'] }}
    </div>
@endif
```

**With:**
```blade
<div class="text-sm space-y-1">
    @if(isset($preview['suggested_name']))
        <div class="font-medium text-primary-600">
            Vorschlag: {{ $preview['suggested_name'] }}
        </div>
    @endif
    
    <div class="text-gray-600">
        Service: <span class="font-medium">{{ $preview['extracted_service'] ?? $preview['parsed']['service_name'] ?? 'Unbekannt' }}</span>
    </div>
    
    @if(isset($preview['name_options']) && count($preview['name_options']) > 1)
        <details class="cursor-pointer">
            <summary class="text-xs text-gray-500">Weitere Optionen anzeigen</summary>
            <div class="mt-1 space-y-1">
                @foreach($preview['name_options'] as $format => $name)
                    <div class="text-xs pl-2">{{ ucfirst($format) }}: {{ $name }}</div>
                @endforeach
            </div>
        </details>
    @endif
</div>
```

### 2. Fix Default Selection Logic (10 minutes)

**In EventTypeImportWizard.php (line 393)**, replace:
```php
'suggested_action' => 'import' // Default to import with better names
```

**With:**
```php
'suggested_action' => $this->determineDefaultAction($smartResult, $branch)
```

**Add this method:**
```php
private function determineDefaultAction($smartResult, $branch): string
{
    $originalName = strtolower($smartResult['original_name']);
    
    // Skip test/demo events
    if (str_contains($originalName, 'test') || 
        str_contains($originalName, 'demo') || 
        str_contains($originalName, 'example')) {
        return 'skip';
    }
    
    // Check if it contains the branch name
    $branchName = strtolower($branch->name);
    if (str_contains($originalName, $branchName)) {
        return 'import';
    }
    
    // Check if service name is generic
    $genericTerms = ['termin', 'appointment', 'meeting', 'beratung'];
    $isGeneric = false;
    foreach ($genericTerms as $term) {
        if (str_contains($originalName, $term)) {
            $isGeneric = true;
            break;
        }
    }
    
    return $isGeneric ? 'import' : 'manual';
}
```

### 3. Add Quick Search (15 minutes)

**Add to EventTypeImportWizard.php class properties:**
```php
public string $searchTerm = '';
public string $teamFilter = '';
public array $teams = [];
```

**Add computed property:**
```php
#[Computed]
public function filteredEventTypes(): array
{
    if (empty($this->searchTerm) && empty($this->teamFilter)) {
        return $this->eventTypesPreview;
    }
    
    return array_filter($this->eventTypesPreview, function($preview) {
        $matchesSearch = empty($this->searchTerm) || 
            stripos($preview['original_name'], $this->searchTerm) !== false ||
            stripos($preview['extracted_service'] ?? '', $this->searchTerm) !== false;
            
        $matchesTeam = empty($this->teamFilter) || 
            ($preview['original']['teamId'] ?? '') == $this->teamFilter;
            
        return $matchesSearch && $matchesTeam;
    });
}
```

**Update loadEventTypesPreview() to collect teams:**
```php
// After line 394, add:
// Collect unique teams
$this->teams = [];
foreach ($eventTypes as $eventType) {
    if (isset($eventType['team']['id']) && isset($eventType['team']['name'])) {
        $this->teams[$eventType['team']['id']] = $eventType['team']['name'];
    }
}
```

**Add search UI to blade (before the table):**
```blade
<div class="mb-4 flex gap-4">
    <div class="flex-1">
        <input type="text" 
               wire:model.live.debounce.300ms="searchTerm" 
               placeholder="Event-Types durchsuchen..."
               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800">
    </div>
    
    @if(count($teams) > 0)
    <div class="w-64">
        <select wire:model.live="teamFilter" 
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800">
            <option value="">Alle Teams</option>
            @foreach($teams as $teamId => $teamName)
                <option value="{{ $teamId }}">{{ $teamName }}</option>
            @endforeach
        </select>
    </div>
    @endif
</div>
```

**Update the @foreach in blade to use filtered results:**
```blade
@foreach($this->filteredEventTypes as $index => $preview)
```

### 4. Show Additional Cal.com Data (10 minutes)

**Update the blade template table to show more info:**

Add a new column header after "Status":
```blade
<th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
    Details
</th>
```

Add corresponding cell:
```blade
<td class="px-4 py-3 text-xs">
    <div class="space-y-1">
        @if(($preview['original']['price'] ?? 0) > 0)
            <div class="flex items-center">
                <x-heroicon-o-currency-euro class="w-3 h-3 mr-1 text-green-600"/>
                <span>{{ number_format(($preview['original']['price'] ?? 0) / 100, 2) }} €</span>
            </div>
        @endif
        
        @if($preview['original']['requiresConfirmation'] ?? false)
            <div class="flex items-center text-amber-600">
                <x-heroicon-o-clock class="w-3 h-3 mr-1"/>
                <span>Bestätigung nötig</span>
            </div>
        @endif
        
        @if(isset($preview['original']['team']['name']))
            <div class="flex items-center text-blue-600">
                <x-heroicon-o-user-group class="w-3 h-3 mr-1"/>
                <span>{{ $preview['original']['team']['name'] }}</span>
            </div>
        @endif
        
        @if(($preview['original']['minimumBookingNotice'] ?? 0) > 0)
            <div class="flex items-center text-gray-600">
                <x-heroicon-o-calendar class="w-3 h-3 mr-1"/>
                <span>{{ $preview['original']['minimumBookingNotice'] }}h Vorlauf</span>
            </div>
        @endif
    </div>
</td>
```

### 5. Improve Selection UX (5 minutes)

**Add smart selection buttons after search bar:**
```blade
<div class="mb-4 flex gap-2">
    <x-filament::button 
        size="sm"
        color="gray"
        wire:click="selectRecommended">
        Empfohlene auswählen
    </x-filament::button>
    
    <x-filament::button 
        size="sm"
        color="gray"
        wire:click="deselectAll">
        Alle abwählen
    </x-filament::button>
    
    <x-filament::button 
        size="sm"
        color="gray"
        wire:click="invertSelection">
        Auswahl umkehren
    </x-filament::button>
</div>
```

**Add methods to EventTypeImportWizard.php:**
```php
public function selectRecommended(): void
{
    foreach ($this->eventTypesPreview as $index => $preview) {
        $this->importSelections[$index] = $preview['suggested_action'] === 'import';
    }
}

public function deselectAll(): void
{
    foreach ($this->eventTypesPreview as $index => $preview) {
        $this->importSelections[$index] = false;
    }
}

public function invertSelection(): void
{
    foreach ($this->eventTypesPreview as $index => $preview) {
        if ($preview['suggested_action'] !== 'skip') {
            $this->importSelections[$index] = !$this->importSelections[$index];
        }
    }
}
```

## Testing Checklist

1. ✅ Smart name suggestions are visible
2. ✅ Default selections are intelligent (not all selected)
3. ✅ Search functionality works
4. ✅ Team filter works (if teams exist)
5. ✅ Additional Cal.com data is displayed
6. ✅ Selection helper buttons work
7. ✅ Import still functions correctly

## Total Implementation Time: ~45 minutes

These fixes will significantly improve the UX without requiring major architectural changes.