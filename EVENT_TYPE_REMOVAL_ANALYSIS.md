# Event Type Removal/Management Analysis

## Executive Summary
This document analyzes how Event Types can be removed/managed in the AskProAI system and the implications of such actions.

## 1. Current State

### Database Structure
- **Table**: `calcom_event_types`
- **NO Soft Deletes**: The table does NOT have a `deleted_at` column
- **Hard Deletes**: Currently uses hard deletion (permanent removal)
- **Status Field**: Has an `is_active` boolean field for deactivation

### Key Relationships
1. **Services** → Event Types (via `calcom_event_type_id`)
   - No foreign key constraint
   - Services can exist without Event Types
   
2. **Appointments** → Event Types (via `calcom_event_type_id`)
   - No foreign key constraint  
   - Historical appointments remain intact

3. **Staff** → Event Types (via `staff_event_types` pivot table)
   - Many-to-many relationship
   - Cascade delete on pivot table

## 2. Cal.com Sync Behavior

### Current Implementation Status
- The sync command (`SyncCalendarEventTypes`) is **BROKEN**
- References removed `UnifiedEventType` model
- Needs migration to `CalcomEventType` model

### Intended Behavior (from commented code)
```php
// Alle existierenden Event Types für diesen Provider als inaktiv markieren
UnifiedEventType::where('branch_id', $branch->id)
    ->where('provider', $provider)
    ->update(['is_active' => false]);

// Event Types die nicht mehr existieren, als gelöscht markieren
```

This shows the system was designed to:
- Mark missing Event Types as inactive (`is_active = false`)
- NOT delete them permanently
- Preserve historical data

## 3. UI Options

### Admin Panel Features

#### A. CalcomEventTypeResource
- **Location**: `/admin/calcom-event-types`
- **Features**:
  - ✅ List all Event Types
  - ✅ Edit Event Types
  - ✅ **Delete Action** available in Edit page
  - ✅ Bulk delete available
  - ✅ Filter by active/inactive status
  - ✅ Sync all Event Types action

#### B. EventTypeImportWizard
- **Location**: `/admin/event-type-import-wizard`
- **Features**:
  - Import Event Types from Cal.com
  - Select which Event Types to import
  - Map Event Types to Services
  - NO removal functionality

#### C. QuickSetupWizardV2
- **Location**: `/admin/quick-setup-wizard`
- **Features**:
  - Initial setup and configuration
  - NO Event Type removal functionality

## 4. Service Dependencies

### Current Behavior
When an Event Type is removed:

1. **Services**:
   - Keep their `calcom_event_type_id` reference
   - Become "orphaned" but still functional
   - Fall back to company-level Event Type if configured
   - Method `getEffectiveEventTypeId()` handles fallback

2. **Appointments**:
   - Remain unchanged
   - Historical data preserved
   - May cause issues if trying to modify/reschedule

3. **Staff Assignments**:
   - Pivot table entries are cascade deleted
   - Staff lose connection to deleted Event Type

## 5. Best Practices & Recommendations

### Recommended Approach: Soft Deactivation

Instead of deleting Event Types, use the existing `is_active` field:

```php
// Deactivate instead of delete
$eventType->update(['is_active' => false]);
```

### Benefits:
1. **Data Integrity**: Historical appointments remain valid
2. **Reversibility**: Can reactivate if needed
3. **Audit Trail**: Know what Event Types existed
4. **Service Continuity**: Services can detect inactive Event Types

### Implementation Steps for Proper Management:

1. **Add Soft Delete Support** (Optional but recommended):
```php
// Migration
Schema::table('calcom_event_types', function (Blueprint $table) {
    $table->softDeletes();
});

// Model
use SoftDeletes;
```

2. **Update Sync Logic**:
```php
// In sync service
$existingIds = $eventTypesFromCalcom->pluck('id');
CalcomEventType::whereNotIn('calcom_event_type_id', $existingIds)
    ->update(['is_active' => false]);
```

3. **Add UI Controls**:
- "Deactivate" button instead of "Delete"
- Show inactive Event Types grayed out
- Allow filtering active/inactive

4. **Handle Orphaned Services**:
```php
// Check if Event Type is active before booking
if (!$service->calcomEventType?->is_active) {
    throw new Exception('Event Type is no longer available');
}
```

## 6. Current Issues & Risks

### Issues:
1. **Broken Sync**: Cal.com sync command needs fixing
2. **No Soft Delete**: Hard deletion loses historical context
3. **No Cascade Handling**: Services/Appointments become orphaned
4. **No UI Warnings**: Users can delete without understanding impact

### Risks:
1. **Data Loss**: Permanently losing Event Type configuration
2. **Booking Failures**: Services trying to use deleted Event Types
3. **Sync Conflicts**: Re-importing previously deleted Event Types
4. **User Confusion**: Why appointments show non-existent Event Types

## 7. Recommended Actions

### Immediate (Safe to implement now):
1. **Use Deactivation**: Set `is_active = false` instead of deleting
2. **Update UI Labels**: Change "Delete" to "Deactivate" in admin panel
3. **Add Filters**: Show/hide inactive Event Types

### Short-term (Requires development):
1. **Fix Sync Command**: Update to use `CalcomEventType` model
2. **Add Soft Deletes**: Implement proper soft deletion
3. **Add Validation**: Prevent deletion if active appointments exist
4. **Add Warnings**: Show impact analysis before deletion

### Long-term (Strategic improvements):
1. **Archive System**: Move old Event Types to archive table
2. **Dependency Manager**: Track all dependencies before actions
3. **Sync Strategy**: Implement proper Cal.com sync with conflict resolution
4. **Audit Logging**: Track who deleted/deactivated Event Types and when

## 8. Code Examples

### Safe Deactivation Method:
```php
public function deactivateEventType($eventTypeId)
{
    $eventType = CalcomEventType::findOrFail($eventTypeId);
    
    // Check dependencies
    $activeAppointments = Appointment::where('calcom_event_type_id', $eventTypeId)
        ->where('starts_at', '>', now())
        ->count();
        
    if ($activeAppointments > 0) {
        throw new \Exception("Cannot deactivate: {$activeAppointments} future appointments exist");
    }
    
    // Deactivate
    $eventType->update(['is_active' => false]);
    
    // Notify affected services
    Service::where('calcom_event_type_id', $eventTypeId)
        ->update(['requires_review' => true]);
        
    return true;
}
```

### UI Implementation:
```php
// In CalcomEventTypeResource
Tables\Actions\Action::make('deactivate')
    ->label('Deaktivieren')
    ->icon('heroicon-o-x-circle')
    ->color('warning')
    ->requiresConfirmation()
    ->visible(fn ($record) => $record->is_active)
    ->action(fn ($record) => $record->update(['is_active' => false])),

Tables\Actions\Action::make('activate')
    ->label('Aktivieren')
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->visible(fn ($record) => !$record->is_active)
    ->action(fn ($record) => $record->update(['is_active' => true])),
```

## Conclusion

The system currently allows hard deletion of Event Types, which can cause data integrity issues. The recommended approach is to use the existing `is_active` field for soft deactivation, preserving historical data while preventing new bookings. This requires minimal code changes and provides a safer user experience.