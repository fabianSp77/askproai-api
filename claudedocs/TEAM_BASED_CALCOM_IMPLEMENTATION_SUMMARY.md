# Team-Based Cal.com Integration Implementation Summary

## Date: 2025-09-29

## Overview
Implemented a comprehensive team-based cal.com integration system that ensures companies are assigned to specific cal.com teams, and only that team's event types are imported as services.

## Key Changes Implemented

### 1. Database Schema Updates
- **Added team fields to companies table:**
  - `calcom_team_name` - Stores the team name
  - `team_sync_status` - Tracks sync status (pending/syncing/synced/error)
  - `last_team_sync` - Timestamp of last sync
  - `team_sync_error` - Error message if sync fails
  - `team_member_count` - Number of team members
  - `team_event_type_count` - Number of event types

- **Created new tables:**
  - `team_event_type_mappings` - Maps team event types to companies
  - `calcom_team_members` - Stores team member information

### 2. New Services and Jobs

#### CalcomV2Service (`app/Services/CalcomV2Service.php`)
- Enhanced cal.com API client for team operations
- Methods:
  - `fetchTeams()` - Get all accessible teams
  - `fetchTeamEventTypes($teamId)` - Get event types for a team
  - `fetchTeamMembers($teamId)` - Get team members
  - `validateTeamAccess($teamId, $eventTypeId)` - Validate event type ownership
  - `importTeamEventTypes($company)` - Import all team event types as services
  - `syncTeamMembers($company)` - Sync team members

#### ImportTeamEventTypesJob (`app/Jobs/ImportTeamEventTypesJob.php`)
- Background job for importing team event types
- Includes retry logic and error handling
- Updates company sync status

### 3. Model Updates

#### Company Model
- Added team relationships:
  - `teamEventTypeMappings()`
  - `teamMembers()`
- Added helper methods:
  - `hasTeam()` - Check if company has team
  - `teamSyncIsDue()` - Check if sync is needed
  - `syncTeamEventTypes()` - Trigger sync job
  - `ownsService($eventTypeId)` - Validate service ownership
- Added accessors for UI display

#### New Models
- `TeamEventTypeMapping` - Manages team event type mappings
- `CalcomTeamMember` - Manages team member records

### 4. UI/UX Updates

#### CompanyResource
- Enhanced Cal.com Integration section with:
  - Team ID and name fields
  - Team sync status display
  - Last sync timestamp
  - Member/event type counts
  - Sync error display
- Added "Sync Team Event Types" action button

#### BranchResource ServicesRelationManager
- Updated service attachment to filter by team
- Only shows services with cal.com event types when company has team
- Dynamic modal descriptions based on team assignment

### 5. Phone Middleware Integration

#### RetellWebhookController Updates
- Added team validation in `handleBookingCreate()`
- Validates service belongs to branch's company team
- Returns 403 error if service not in team
- Enhanced appointment creation to check team ownership

### 6. Validation Chain

The complete validation flow:
```
Phone Number → Branch → Company → Team → Event Type → Service → Booking
```

Each step validates:
1. Phone number exists and has branch
2. Branch belongs to company
3. Company has team assigned
4. Service event type belongs to team
5. Booking uses valid team service

## Configuration Required

### Setting Up a Company with Team

1. **In Admin Panel:**
   - Edit company
   - Set `calcom_team_id` to the cal.com team ID
   - Click "Sync Team Event Types" action

2. **Via Database:**
   ```sql
   UPDATE companies
   SET calcom_team_id = YOUR_TEAM_ID
   WHERE id = COMPANY_ID;
   ```

3. **Via API/Job:**
   ```php
   $company = Company::find($id);
   $company->calcom_team_id = $teamId;
   $company->save();
   $company->syncTeamEventTypes();
   ```

## Benefits of Team-Based Approach

1. **Clear Ownership:** Each event type belongs to exactly one company/team
2. **No Conflicts:** Prevents multiple companies claiming same event type
3. **Automatic Sync:** Team changes automatically reflected in system
4. **Validation:** Phone calls validated against correct team services
5. **Scalability:** Easy to add new teams/companies

## Testing

Created test script: `/var/www/api-gateway/scripts/test-team-import.php`

Tests:
- Team details fetching
- Event type import
- Team member sync
- Service ownership validation
- Branch assignments

## Known Limitations

1. **API Version:** Currently using cal.com v1 API endpoints. V2 requires different authentication (OAuth).
2. **Team Assignment:** Companies can only belong to one team
3. **Manual Team ID:** Team ID must be manually set (no UI team selector yet)

## Future Enhancements

1. **Team Selector UI:** Dropdown to select team from available teams
2. **Auto-discovery:** Automatically discover team ID from API key
3. **V2 API Migration:** Migrate to cal.com v2 API when OAuth implemented
4. **Team Switching:** Allow companies to switch teams with proper cleanup
5. **Multi-team Support:** Allow companies to access multiple teams

## Files Modified/Created

### Created:
- `/app/Services/CalcomV2Service.php`
- `/app/Jobs/ImportTeamEventTypesJob.php`
- `/app/Models/TeamEventTypeMapping.php`
- `/app/Models/CalcomTeamMember.php`
- `/database/migrations/2025_09_29_093306_add_team_fields_to_companies_table.php`
- `/database/migrations/2025_09_29_093351_create_team_event_type_mappings_table.php`
- `/database/migrations/2025_09_29_093403_create_calcom_team_members_table.php`
- `/scripts/test-team-import.php`

### Modified:
- `/app/Models/Company.php`
- `/app/Filament/Resources/CompanyResource.php`
- `/app/Filament/Resources/BranchResource/RelationManagers/ServicesRelationManager.php`
- `/app/Http/Controllers/RetellWebhookController.php`

## Summary

The team-based cal.com integration ensures clean separation of companies via teams, prevents event type conflicts, and maintains full traceability from phone calls to cal.com bookings. The implementation provides a solid foundation for multi-tenant cal.com integration with proper validation at every level.