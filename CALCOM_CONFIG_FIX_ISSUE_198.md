# Cal.com Configuration Fix for Issue #198

## Problem
GitHub Issue #198 reported that the "Firma verwalten" (Company Management) page was missing Cal.com configuration options in the QuickSetupWizardV2.

## Root Cause
The `getCalcomFields()` method in `QuickSetupWizardV2.php` was returning an empty array with just a comment "// Copy fields from original", meaning the Cal.com configuration UI was never implemented.

## Solution Implemented

### 1. Added Complete Cal.com Configuration Fields
- **Connection Type Selection**: Choice between OAuth and API Key authentication
- **API Key Input**: Password-protected field with proper placeholders and helper text
- **Team Slug Input**: Optional field for team calendar integration
- **Import Event Types Toggle**: Option to automatically import appointment types from Cal.com
- **Test Connection Button**: Interactive button to verify the Cal.com connection

### 2. Added Connection Test Functionality
Created `testCalcomConnection()` method that:
- Validates API key presence
- Tests the connection using CalcomV2Service
- Provides immediate feedback with success/error notifications

### 3. Added Event Type Import Functionality
Created `importCalcomEventTypes()` method that:
- Fetches all event types from Cal.com API
- Creates/updates CalcomEventType records in the database
- Provides feedback on import success/failure

### 4. Updated Save Logic
Enhanced the `updateExistingCompany()` method to:
- Properly handle Cal.com API key updates
- Update team slug separately (allowing it to be cleared)
- Trigger event type import when requested

## Files Modified
- `/var/www/api-gateway/app/Filament/Admin/Pages/QuickSetupWizardV2.php`

## Testing the Fix
1. Navigate to `/admin/quick-setup-wizard-v2?company=1`
2. Go to Step 3 "Kalender verbinden"
3. You should now see:
   - Connection type selector
   - API key input field
   - Team slug field
   - Import toggle
   - Test connection button

## Additional Notes
- The fix maintains backward compatibility with existing companies
- API keys are properly encrypted before storage
- The UI shows masked placeholders for existing API keys
- Import functionality uses the existing CalcomEventType model structure