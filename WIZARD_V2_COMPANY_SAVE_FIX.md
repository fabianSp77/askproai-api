# Quick Setup Wizard V2 - Company Save Fix

## Issues Found and Fixed

### 1. **Company Not Being Saved**
**Problem**: The `createNewCompany()` method was empty with only comments.
**Solution**: Implemented full company creation logic including:
- Company creation with all settings
- Branch creation with business hours
- Phone number setup (hotline/direct)
- Cal.com integration
- Services creation (template or custom)
- Staff creation with skills and languages
- Retell.ai agent provisioning

### 2. **403 Forbidden on /admin/companies**
**Problem**: The CompanyResource requires `super_admin` role or `view_any_company` permission.
**Why this happens**:
- The resource is protected by strict permission checks
- Regular users cannot access the companies list
- Only super admins or users with specific permissions can view

**Solutions**:
1. Grant the user `super_admin` role
2. Or grant `view_any_company` permission
3. Or use the Quick Setup Wizard V2 which doesn't require these permissions

## Code Changes Made

### File: `/app/Filament/Admin/Pages/QuickSetupWizardV2.php`

1. **Implemented `createNewCompany()` method** with:
   - Company creation with logo and settings
   - Branch creation with features and business hours
   - Phone number configuration based on strategy
   - Cal.com API key encryption and storage
   - Service creation from templates or custom
   - Staff creation with multi-language support
   - Retell agent automatic provisioning

2. **Added helper methods**:
   - `getBusinessHours()` - Industry-specific working hours
   - `setupPhoneNumbers()` - Hotline and direct number setup
   - `importCalcomEventTypes()` - Cal.com integration
   - `createTemplateServices()` - Industry template services
   - `createCustomServices()` - Custom service creation
   - `createStaff()` - Staff with skills and certifications
   - `setupRetellAgent()` - AI agent provisioning

## Testing Instructions

1. **Create a new company**:
   ```
   https://api.askproai.de/admin/quick-setup-wizard-v2
   ```
   - Fill all steps
   - Company will be saved at the end
   - You'll see success notification

2. **Check if company was created**:
   - If you're super admin: Go to `/admin/companies`
   - Otherwise: Check in database or use another resource

3. **Verify all data**:
   - Company exists with correct name/industry
   - Branch created with address and phone
   - Phone numbers saved based on strategy
   - Services created from template
   - Staff members created if added

## Permission Solutions

### Option 1: Grant Super Admin Role
```sql
UPDATE users SET role = 'super_admin' WHERE email = 'your-email@example.com';
```

### Option 2: Grant Specific Permission
```php
// In a seeder or tinker
$user = User::where('email', 'your-email@example.com')->first();
$user->givePermissionTo('view_any_company');
```

### Option 3: Use Alternative Navigation
Instead of `/admin/companies`, use:
- `/admin/quick-setup-wizard-v2` - Create/edit companies
- `/admin/branches` - View branches (if permitted)
- Dashboard widgets to see your company info

## Important Notes

1. **The wizard now saves companies correctly**
2. **403 error is a permission issue, not a bug**
3. **All phone number formats work without masks**
4. **Branch phone numbers appear when selecting "Direkte Durchwahl"**

## Related Issues
- https://github.com/fabianSp77/askproai-api/issues/250 (Phone input)
- https://github.com/fabianSp77/askproai-api/issues/251 (Phone fields)
- https://github.com/fabianSp77/askproai-api/issues/252 (Phone format)