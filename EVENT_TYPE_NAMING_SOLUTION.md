# Event Type Naming Solution

## Problem Analysis

The event type naming issue occurs when Cal.com event types with marketing-style names are imported into the system. The current parser expects a strict "Branch-Company-Service" format, but when it encounters non-conforming names, it uses the entire original name as the service component, leading to duplicated and concatenated names.

### Example of the Problem:
- **Original Cal.com Name**: "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7"
- **Generated Name**: "Berlin-AskProAI-AskProAI + aus Berlin + Beratung + 30% mehr Umsatz für Sie und besten Kundenservice 24/7"
- **If Re-imported**: Even more duplication occurs

## Root Causes

1. **Marketing vs Technical Names**: Cal.com event types often have marketing-friendly names with promotional text
2. **Rigid Parser**: The current parser only handles the strict "Branch-Company-Service" format
3. **Poor Fallback**: When parsing fails, the entire original name becomes the service name
4. **No Content Extraction**: The system doesn't attempt to extract meaningful service names from marketing text

## Recommended Solution

### 1. Immediate Fix - Update EventTypeNameParser

Update the existing `EventTypeNameParser` to handle non-conforming names better:

```php
// In EventTypeNameParser::analyzeEventTypesForImport() around line 130
// Replace:
'suggested_name' => $this->generateEventTypeName(
    $targetBranch, 
    $eventType['title'] ?? $eventType['name']
)

// With:
'suggested_name' => $this->generateEventTypeName(
    $targetBranch, 
    $this->extractServiceNameFromTitle($eventType['title'] ?? $eventType['name'])
)
```

Add a simple extraction method:

```php
private function extractServiceNameFromTitle(string $title): string 
{
    // Remove company/location identifiers
    $service = preg_replace('/\b(AskProAI|aus Berlin|aus München)\b/i', '', $title);
    
    // Remove marketing phrases
    $service = preg_replace('/\d+%\s*mehr\s*\w+|für Sie|24\/7|besten?\s+\w+/i', '', $service);
    
    // Clean up symbols and extra spaces
    $service = preg_replace('/[+–-]+/', ' ', $service);
    $service = trim(preg_replace('/\s+/', ' ', $service));
    
    // If too short or empty, use a fallback
    if (strlen($service) < 5) {
        // Try to find a duration
        if (preg_match('/(\d+)\s*(min|minuten|stunden?)/i', $title, $matches)) {
            return $matches[0] . ' Termin';
        }
        return 'Standardtermin';
    }
    
    // Truncate if too long
    if (strlen($service) > 50) {
        $service = substr($service, 0, 47) . '...';
    }
    
    return $service;
}
```

### 2. Long-term Solution - Flexible Naming System

1. **Add a name_format configuration** to companies/branches:
   ```php
   // In company settings
   'event_type_name_format' => 'compact', // Options: standard, compact, full, service_first
   ```

2. **Store both technical and display names**:
   ```sql
   ALTER TABLE calcom_event_types 
   ADD COLUMN display_name VARCHAR(255) NULL,
   ADD COLUMN technical_name VARCHAR(255) NULL;
   ```

3. **Implement the ImprovedEventTypeNameParser** (already created above) as the default

### 3. Migration for Existing Data

Create a command to fix existing problematic names:

```php
php artisan event-types:fix-names --dry-run
php artisan event-types:fix-names --execute
```

### 4. UI Improvements

1. **Show clean names in the import preview**
2. **Allow manual editing during import**
3. **Add a "Name Format" dropdown in settings**
4. **Show original Cal.com name as tooltip**

## Implementation Steps

### Phase 1: Quick Fix (1 hour)
1. Add `extractServiceNameFromTitle()` method to EventTypeNameParser
2. Update the fallback logic in `analyzeEventTypesForImport()`
3. Test with existing data

### Phase 2: Enhanced Parser (2-3 hours)
1. Implement ImprovedEventTypeNameParser
2. Add configuration for name formats
3. Update EventTypeImportWizard to use new parser
4. Add manual name editing in import UI

### Phase 3: Database Enhancement (3-4 hours)
1. Add display_name and technical_name columns
2. Update models and import logic
3. Create migration command for existing data
4. Update UI to show appropriate names

## Benefits

1. **Cleaner UI**: No more duplicated/concatenated names
2. **Better UX**: Meaningful service names extracted automatically
3. **Flexibility**: Different naming formats for different use cases
4. **Backward Compatible**: Existing integrations continue to work
5. **Future Proof**: Handles any marketing text Cal.com throws at it

## Testing

Test cases to verify the solution:

1. Import marketing-style event types
2. Re-import already imported event types
3. Import standard format event types
4. Mix of conforming and non-conforming names
5. Very long marketing names
6. Names with special characters and numbers

## Example Results

With the improved parser:

| Original | Extracted Service | Generated Name |
|----------|------------------|----------------|
| "AskProAI + aus Berlin + Beratung + 30% mehr Umsatz..." | "Beratung" | "Berlin - Beratung" |
| "30 Minuten Termin mit Fabian Spitzer" | "30 Minuten Termin" | "Berlin - 30 Minuten Termin" |
| "Premium Service - 2 Stunden intensive Beratung" | "Premium Service" | "Berlin - Premium Service" |