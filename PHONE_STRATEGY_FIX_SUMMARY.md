# Phone Strategy Selection Fix Summary

## Issue
When selecting "Direkte Durchwahl pro Filiale" in the Quick Setup Wizard V2, no phone number input fields were appearing.

## Root Cause
The branch phone numbers repeater was only initialized when editing an existing company with branches. For new companies, there were no branches yet, so no fields were displayed.

## Solution Implemented

### 1. **Default Initialization for New Companies**
Modified the repeater's `default()` function to create an initial entry for new companies:
```php
// For new companies, create entry for the branch being created
if (!$this->editMode) {
    return [[
        'branch_id' => 'new',
        'branch_name' => $this->data['branch_name'] ?? 'Hauptfiliale',
        'number' => '',
        'is_primary' => true,
        'sms_enabled' => false,
        'whatsapp_enabled' => false,
    ]];
}
```

### 2. **Reactive Phone Strategy Selection**
Enhanced the phone strategy toggle buttons to initialize phone fields when selected:
```php
->afterStateUpdated(function ($state, $set, $get) {
    if ($state === 'direct' || $state === 'mixed') {
        // Initialize phone numbers if not already set
        if (empty($get('branch_phone_numbers'))) {
            $set('branch_phone_numbers', [[
                'branch_id' => 'new',
                'branch_name' => $get('branch_name') ?? 'Hauptfiliale',
                'number' => '',
                // ... other fields
            ]]);
        }
    }
})
```

### 3. **Branch Name Synchronization**
Made the branch name field reactive to update the phone repeater:
```php
TextInput::make('branch_name')
    ->reactive()
    ->afterStateUpdated(function ($state, $set, $get) {
        // Update branch name in phone numbers repeater
        $phoneNumbers = $get('branch_phone_numbers') ?? [];
        if (!empty($phoneNumbers) && isset($phoneNumbers[0])) {
            $phoneNumbers[0]['branch_name'] = $state;
            $set('branch_phone_numbers', $phoneNumbers);
        }
    })
```

## Changes Made

### Files Modified:
1. `/app/Filament/Admin/Pages/QuickSetupWizardV2.php`
   - Lines 305-318: Made branch_name reactive
   - Lines 444-475: Enhanced phone_strategy afterStateUpdated
   - Lines 546-574: Added default initialization for new companies
   - Line 507: Made branch_phone_numbers repeater reactive

## How It Works Now

### For New Companies:
1. User fills in company and branch details in Step 1
2. In Step 2, when selecting "Direkte Durchwahl pro Filiale":
   - One phone field appears for the branch being created
   - The branch name is synchronized from Step 1
   - User can enter the phone number directly

### For Existing Companies:
1. All existing branches are loaded
2. Each branch gets its own phone number field
3. Existing phone numbers are pre-filled if available

## Testing
1. Navigate to: https://api.askproai.de/admin/quick-setup-wizard-v2
2. Create a new company setup
3. In Step 2, select "Direkte Durchwahl pro Filiale"
4. Phone number fields should now appear immediately

## Related GitHub Issues
- https://github.com/fabianSp77/askproai-api/issues/251