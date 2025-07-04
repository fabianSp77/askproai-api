# Phone Resolution Implementation

## Overview
We've implemented a robust phone number resolution system to handle the critical blocker of resolving company/branch context from incoming Retell.ai webhooks.

## Components Implemented

### 1. Enhanced PhoneNumberResolver Service
**Location**: `/app/Services/PhoneNumberResolver.php`

**Key Features**:
- Dual lookup: checks both `phone_numbers` table and `branches.phone_number` column
- Multiple fallback strategies for unknown numbers
- Test mode support for development
- Enhanced logging with resolution confidence scores
- Partial phone number matching
- Caller history resolution

**Resolution Methods** (in priority order):
1. **Metadata Resolution**: Uses `askproai_branch_id` from webhook metadata
2. **Phone Number Lookup**: Matches against phone_numbers table and branch phone_number
3. **Agent ID Resolution**: Resolves from Retell agent ID
4. **Caller History**: Uses previous interactions to determine branch
5. **Enhanced Fallback**: Multiple strategies including partial matches
6. **Final Fallback**: Uses default company

### 2. Test Mode Support
- Environment variable: `RETELL_TEST_MODE=true`
- Test phone numbers: `+15551234567`, `+4915551234567`, `+491234567890`
- Test agent IDs: `test_agent`, `demo_agent`
- Automatically resolves to test company/branch in test mode

### 3. Database Migration
**File**: `/database/migrations/2025_06_26_172057_populate_missing_phone_numbers.php`

**Actions**:
- Creates phone_numbers records from branches with phone numbers
- Updates missing company_id references
- Copies retell_agent_id from branches to phone_numbers
- Creates test phone number for development

### 4. Admin Tools

#### PhoneNumberResource (Filament)
**Location**: `/app/Filament/Admin/Resources/PhoneNumberResource.php`

**Features**:
- Full CRUD for phone numbers
- Branch/Company assignment
- Retell agent configuration
- Test resolution button
- Bulk activation/deactivation
- Navigation badge showing active count

#### Console Commands

**`php artisan phone:test-resolution`**
```bash
# Test phone resolution
php artisan phone:test-resolution +49123456789

# Test with webhook simulation
php artisan phone:test-resolution +49123456789 --webhook

# Test with test mode
php artisan phone:test-resolution +49123456789 --test-mode
```

**`php artisan phone:create`**
```bash
# Create phone number
php artisan phone:create +49123456789 --branch=uuid --agent=agent_id

# Create with company (uses first branch)
php artisan phone:create +49123456789 --company=1

# Create as primary number
php artisan phone:create +49123456789 --branch=uuid --primary
```

### 5. Validation Script
**File**: `/validate-phone-resolution.php`

Comprehensive validation that checks:
- Phone numbers table status
- Branch phone configuration
- Resolution testing
- Orphaned records
- Missing configurations

## Usage Examples

### 1. Normal Webhook Processing
```php
$resolver = new PhoneNumberResolver();
$result = $resolver->resolveFromWebhook($webhookData);

if ($result['confidence'] >= 0.7) {
    // High confidence - proceed
    $branchId = $result['branch_id'];
    $companyId = $result['company_id'];
} else {
    // Low confidence - may need manual review
    Log::warning('Low confidence phone resolution', $result);
}
```

### 2. Test Mode Development
```bash
# Set in .env
RETELL_TEST_MODE=true

# Or in webhook metadata
{
    "to": "+49123456789",
    "metadata": {
        "test_mode": true
    }
}
```

### 3. Admin Configuration
1. Navigate to `/admin/phone-numbers`
2. Click "New phone number"
3. Enter phone number in international format
4. Select branch
5. Configure Retell agent ID
6. Save and test resolution

## Troubleshooting

### Issue: "No branch found for phone number"
**Solutions**:
1. Check if phone number exists in database
2. Ensure branch is active (`is_active = true`)
3. Verify phone number format (must include country code)
4. Run migration: `php artisan migrate --force`

### Issue: "Low confidence resolution"
**Check**:
1. Phone number properly configured
2. Branch has retell_agent_id
3. Company/branch are active
4. No duplicate phone numbers

### Testing Resolution
```bash
# Quick test
php artisan phone:test-resolution +49123456789

# Full validation
php validate-phone-resolution.php

# Create test number
php artisan phone:create +49123456789 --branch=<uuid> --type=test
```

## Security Considerations

1. **Phone Number Validation**: All phone numbers are validated for E.164 format
2. **Tenant Isolation**: Resolution respects multi-tenancy boundaries
3. **Logging**: All resolutions are logged with correlation IDs
4. **Rate Limiting**: Cache layer prevents excessive lookups

## Future Enhancements

1. **Phone Number Portability**: Track number history when customers switch providers
2. **Geographic Resolution**: Use area codes for better branch matching
3. **Machine Learning**: Improve confidence scoring based on historical data
4. **Real-time Updates**: WebSocket notifications for phone number changes
5. **Bulk Import**: CSV import for multiple phone numbers

## Monitoring

Key metrics to monitor:
- Resolution success rate (target: >95%)
- Average confidence score (target: >0.8)
- Fallback usage rate (should be <10%)
- Resolution time (target: <50ms)

Check logs for:
```bash
grep "phone resolution" storage/logs/laravel.log
grep "Could not resolve company/branch" storage/logs/laravel.log
```

## Summary

The phone resolution system is now robust and production-ready with:
- ✅ Multiple resolution strategies
- ✅ Comprehensive fallback mechanisms
- ✅ Test mode for development
- ✅ Admin tools for configuration
- ✅ Monitoring and validation tools
- ✅ Clear troubleshooting guides

This implementation resolves the critical blocker and ensures reliable company/branch resolution for all incoming calls.