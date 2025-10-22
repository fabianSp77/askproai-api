# Retell Agent Admin - Troubleshooting Guide

**For**: Developers, DevOps, IT Support
**Date**: 2025-10-21
**Status**: ✅ LIVE

---

## Quick Diagnosis

### Is It Deployed?

```bash
# Check migration status
php artisan migrate:status | grep "retell_agent_prompts"
# Should show: [1123] Ran

# Check templates exist
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php';
\$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo \App\Models\RetellAgentPrompt::where('is_template', true)->count() . ' templates';"
# Should show: 3 templates
```

---

## Problem 1: Tab Not Visible in Filament

### Symptom
"Retell Agent" tab doesn't appear on Branch edit page

### Diagnosis

**Step 1: Check User Role**
```bash
# In Filament, check current user
php artisan tinker
>>> auth()->user()->hasRole('admin')
# Should return: true
```

**Step 2: Check If Admin**
- Only admins see the Retell Agent tab
- Regular users won't see it

**Step 3: Check Code is Loaded**
```bash
# Verify BranchResource was updated
grep -n "Retell Agent" app/Filament/Resources/BranchResource.php
# Should find multiple matches
```

### Solutions

**If User Not Admin**:
```bash
# Make user admin in database
php artisan tinker
>>> \$user = \App\Models\User::find(1);
>>> \$user->assignRole('admin');
```

**If Code Not Loaded**:
```bash
# Clear Filament cache
php artisan cache:clear
php artisan config:clear

# Restart queue if running
# Sometimes old code cached
```

**If Still Not Working**:
```bash
# Hard cache clear
rm -rf bootstrap/cache/*
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

---

## Problem 2: Can't Select Template

### Symptom
Dropdown shows "Select template..." but nothing happens

### Diagnosis

**Step 1: Check Templates Exist**
```bash
php artisan tinker
>>> \App\Models\RetellAgentPrompt::where('is_template', true)->get();
# Should return: 3 templates
```

**Step 2: Check Branch is Saved**
```php
// In Filament, check if you're editing new branch
// Branch must be saved before template selection works
```

**Step 3: Check Network**
- Open browser DevTools (F12)
- Click dropdown
- Check Network tab for errors

### Solutions

**If Templates Missing**:
```bash
# Re-seed templates
php artisan db:seed --class=RetellTemplateSeeder
```

**If Branch Not Saved**:
- Click "Save" button on the branch form first
- Wait for success message
- Then try template dropdown

**If Network Error**:
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log | grep -i error

# Look for 500 errors
```

---

## Problem 3: Deploy Button Not Working

### Symptom
Click "Aus Template deployen" but nothing happens

### Diagnosis

**Step 1: Check Network Request**
- Open browser DevTools (F12)
- Click Network tab
- Click deploy button
- Check if request was sent

**Step 2: Check Browser Console**
- F12 → Console tab
- Look for red errors
- Check for CSRF token errors

**Step 3: Check Server Logs**
```bash
tail -f storage/logs/laravel.log | grep -E "error|exception"
```

### Solutions

**If No Request Sent**:
- Refresh page completely (Ctrl+Shift+R)
- Try again

**If CSRF Error**:
```bash
# Clear session cache
php artisan cache:clear
# Refresh page and login again
```

**If 500 Error**:
```bash
# Check logs
tail storage/logs/laravel.log

# Common causes:
# 1. Database not migrated
# 2. Template not found
# 3. Permission error
```

**If Deployment Hangs**:
- Wait 10 seconds
- If still not done, check logs
- May be slow database query

---

## Problem 4: Deployment Shows Error

### Symptom
"❌ Bereitstellung fehlgeschlagen" message appears

### Error Type 1: "Template nicht gefunden"

**Cause**: Template doesn't exist in database

**Solution**:
```bash
# Check what templates exist
php artisan tinker
>>> \App\Models\RetellAgentPrompt::where('is_template', true)->pluck('template_name');

# Re-seed if missing
php artisan db:seed --class=RetellTemplateSeeder
```

### Error Type 2: "Prompt-Validierung fehlgeschlagen"

**Cause**: Prompt content failed validation

**Solution**:
```bash
# Check template prompt content
php artisan tinker
>>> \$t = \App\Models\RetellAgentPrompt::where('template_name', 'dynamic-service-selection-v127')->first();
>>> strlen(\$t->prompt_content)
# Should be > 100

# Check validation
>>> \$service = new \App\Services\Retell\RetellPromptValidationService();
>>> \$errors = \$service->validate(\$t->prompt_content, \$t->functions_config);
>>> \$errors
# Should return empty array
```

### Error Type 3: "Funktionen-Validierung fehlgeschlagen"

**Cause**: Functions config is invalid

**Solution**:
```bash
# Check template functions
php artisan tinker
>>> \$t = \App\Models\RetellAgentPrompt::where('template_name', 'dynamic-service-selection-v127')->first();
>>> \$t->functions_config
# Should show array of functions

# Validate functions
>>> \$service = new \App\Services\Retell\RetellPromptValidationService();
>>> \$errors = \$service->validateFunctionsConfig(\$t->functions_config);
>>> \$errors
# Should return empty array
```

### Error Type 4: "Datenbankfehler"

**Cause**: Version creation failed

**Solution**:
```bash
# Check database connection
php artisan tinker
>>> \App\Models\Branch::count()
# Should return number > 0

# Check if user has permission
>>> auth()->user()->can('create', \App\Models\RetellAgentPrompt::class)

# Try manual version creation
>>> \$branch = \App\Models\Branch::first();
>>> \App\Models\RetellAgentPrompt::create([
>>>   'branch_id' => \$branch->id,
>>>   'version' => 1,
>>>   'prompt_content' => 'test',
>>>   'functions_config' => [],
>>>   'is_template' => false
>>> ]);
```

---

## Problem 5: Agent Not Using New Configuration

### Symptom
Deployed new template but agent still uses old configuration

### Root Cause
Agent caches configuration. New config takes time to propagate.

### Diagnosis

**Step 1: Verify Deployment**
```bash
# Check if version is active in database
php artisan tinker
>>> \$branch = \App\Models\Branch::first();
>>> \$active = \$branch->retellAgentPrompts()->where('is_active', true)->first();
>>> \$active->template_name
# Should show new template name
```

**Step 2: Check Cache**
```bash
# Agent caches configuration
# Check Redis/Memcached
php artisan tinker
>>> \$value = cache()->get('retell_config_' . \$branch_id);
>>> \$value
# Should show cached config or null
```

**Step 3: Make Test Call**
- Make a test call to the agent
- Agent should refresh cache on new call

### Solutions

**If Database Shows Old Config**:
- Recheck step 1 - verify new version is marked active
- If not active, use "Switch to version" in history

**If Cache Is Stale**:
```bash
# Clear specific branch cache
php artisan tinker
>>> cache()->forget('retell_config_' . \$branch_id);
```

**If Agent Still Old After 2 Minutes**:
```bash
# Clear all Retell caches
php artisan tinker
>>> cache()->tags('retell')->flush();

# Or full cache clear
php artisan cache:clear
```

**If Issues Persist**:
1. Make test call to trigger cache refresh
2. Wait 2-3 minutes for agent to reload
3. Try test call again

---

## Problem 6: Version History Missing

### Symptom
"Version History" link doesn't show or shows no versions

### Diagnosis

**Step 1: Check Versions in Database**
```bash
php artisan tinker
>>> \$branch = \App\Models\Branch::first();
>>> \$branch->retellAgentPrompts()->count()
# Should be > 0
```

**Step 2: Check Active Version**
```bash
>>> \$active = \$branch->retellAgentPrompts()->where('is_active', true)->first();
>>> \$active
# Should show one version
```

### Solutions

**If No Versions Exist**:
- Deploy first template to create v1
- Version history builds as you deploy

**If Versions Exist But Not Shown**:
```bash
# Refresh page
# Or:
php artisan cache:clear
# Then refresh
```

**If Only One Version Shows**:
- Normal! Versions only created when you deploy
- Deploy another template to see history

---

## Problem 7: Database Issues

### Symptom
Errors mentioning "retell_agent_prompts table doesn't exist"

### Diagnosis

```bash
# Check if migration ran
php artisan migrate:status | grep "2025_10_21_131415"

# Check if table exists
php artisan tinker
>>> \Schema::hasTable('retell_agent_prompts')
# Should return: true
```

### Solutions

**If Migration Not Applied**:
```bash
# Run migrations
php artisan migrate

# Verify
php artisan migrate:status | grep "2025_10_21_131415"
# Should show: [1123] Ran
```

**If Migration Failed**:
```bash
# Check for errors
php artisan migrate --step=1 -vvv

# Look for foreign key or syntax errors
```

**If Table Exists But Foreign Key Error**:
```bash
# Check table structure
php artisan tinker
>>> \Schema::getColumns('retell_agent_prompts')

# May need to:
# 1. Backup data
# 2. Drop table
# 3. Re-migrate
php artisan migrate:rollback --step=1
php artisan migrate
```

---

## Problem 8: Performance Issues

### Symptom
Deployment takes > 10 seconds or UI is slow

### Diagnosis

**Step 1: Check Query Performance**
```bash
# Enable query logging
php artisan tinker
>>> \DB::enableQueryLog();
>>> \$branch = \App\Models\Branch::first();
>>> \$branch->retellAgentPrompts;
>>> dd(\DB::getQueryLog());
# Check query times
```

**Step 2: Check Database Performance**
```bash
# Check for slow queries
# In MySQL/PostgreSQL:
SHOW PROCESSLIST;  # MySQL
SELECT pid, state, query FROM pg_stat_activity;  # PostgreSQL
```

### Solutions

**If Queries Slow**:
```bash
# Verify indexes exist
php artisan tinker
>>> \Schema::getIndexes('retell_agent_prompts')
# Should show composite index on (branch_id, version)
```

**If Database Slow**:
```bash
# Check table size
# SQL: SELECT pg_size_pretty(pg_total_relation_size('retell_agent_prompts'));

# May need optimization/cleanup of old versions
# But should still be fast even with 1000+ versions
```

**If UI Slow**:
```bash
# Refresh page
php artisan cache:clear

# May be caching old Filament JS
```

---

## Problem 9: Permission Errors

### Symptom
"Unauthorized" or "Permission denied" errors

### Diagnosis

```bash
# Check user permissions
php artisan tinker
>>> \$user = auth()->user();
>>> \$user->hasRole('admin')
>>> \$user->can('update', \App\Models\Branch::class)
```

### Solutions

**If Not Admin**:
```bash
# Grant admin role
php artisan tinker
>>> \$user = \App\Models\User::find(1);
>>> \$user->syncRoles('admin');
```

**If Admin But Still No Permission**:
```bash
# Check gate/policy
# In BranchResource, check:
// visible(fn () => auth()->user()?->hasRole('admin'))

# May need to add explicit permission check
```

---

## Problem 10: Rollback Issues

### Symptom
Can't switch to previous version

### Diagnosis

```bash
# Check if previous version exists
php artisan tinker
>>> \$branch = \App\Models\Branch::first();
>>> \$branch->retellAgentPrompts()->orderBy('version')->get();
# Should show multiple versions
```

### Solutions

**If No Previous Version**:
- First deployment only creates v1
- Deploy another template to have v2
- Then you can rollback to v1

**If Versions Exist But Switch Fails**:
```bash
# Try manual switch
php artisan tinker
>>> \$version = \App\Models\RetellAgentPrompt::where('branch_id', \$branch_id)
>>> ->where('version', 1)->first();
>>> \$version->markAsActive();
```

---

## Emergency Procedures

### Complete Reset

If everything is broken:

```bash
# 1. Backup data first!
php artisan backup:run

# 2. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 3. Re-seed templates
php artisan db:seed --class=RetellTemplateSeeder

# 4. Test
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php';
\$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo \App\Models\RetellAgentPrompt::where('is_template', true)->count() . ' templates';"
```

### Rollback Everything

If feature is broken:

```bash
# 1. Backup data
php artisan backup:run

# 2. Rollback migration
php artisan migrate:rollback --step=1

# 3. Verify rollback
php artisan migrate:status | grep "2025_10_21_131415"
# Should show: Pending
```

### Disable UI Only

To keep data but disable feature:

```bash
# Edit BranchResource.php
# Comment lines 252-351 (Retell Agent tab)

# No data loss
# Just UI unavailable
```

---

## Checking Logs

### Real-Time Monitoring

```bash
# Watch Laravel logs
tail -f storage/logs/laravel.log | grep -i "retell"

# Watch for errors
tail -f storage/logs/laravel.log | grep -i "error"

# Watch specific errors
tail -f storage/logs/laravel.log | grep -E "retell|error|exception"
```

### Search Logs

```bash
# Find all Retell-related entries
grep -i "retell" storage/logs/laravel.log

# Find errors in last hour
find storage/logs -mmin -60 -exec grep -l "error" {} \;

# Find deployment errors
grep -i "deployment\|deploy" storage/logs/laravel.log
```

---

## Database Inspection

### View All Templates

```bash
php artisan tinker
>>> \App\Models\RetellAgentPrompt::where('is_template', true)->get(['id', 'template_name', 'version', 'is_active']);
```

### View All Versions for Branch

```bash
php artisan tinker
>>> \$branch = \App\Models\Branch::where('name', 'Branch Name')->first();
>>> \$branch->retellAgentPrompts()->get(['id', 'version', 'is_active', 'created_at']);
```

### Find Active Version

```bash
php artisan tinker
>>> \$branch = \App\Models\Branch::first();
>>> \$active = \$branch->retellAgentPrompts()->where('is_active', true)->first();
>>> echo "Active: v" . \$active->version . " - " . \$active->template_name;
```

---

## Performance Optimization

### Clear Old Versions

```bash
// Keep last 5 versions, delete rest
php artisan tinker
>>> \$branch = \App\Models\Branch::first();
>>> \$branch->deleteOldVersions(5);  // Keeps last 5
```

### Verify Indexes

```bash
php artisan tinker
>>> \Schema::getIndexes('retell_agent_prompts')
// Should show:
// - id (primary)
// - branch_id, version (composite unique)
// - is_template, template_name
// - is_active
```

---

## Testing

### Test Full Workflow

```bash
# Run test suite
vendor/bin/pest tests/Feature/RetellIntegration/

# Run specific test
vendor/bin/pest tests/Feature/RetellIntegration/RetellPromptTest.php

# Run with output
vendor/bin/pest tests/Feature/RetellIntegration/ -v
```

### Manual Testing Checklist

- [ ] Tab visible
- [ ] Dropdown works
- [ ] Deploy succeeds
- [ ] New version created
- [ ] Old version deactivated
- [ ] History shows versions
- [ ] Can rollback
- [ ] Agent uses new config

---

## Support Escalation

### When to Escalate

| Issue | Escalate To |
|-------|-------------|
| User permission | Admin |
| UI not rendering | Frontend Dev |
| Database error | DBA |
| Agent not updated | Retell API Team |
| Cache issues | DevOps |
| Performance | Backend Dev |

### Information to Provide

1. Error message (exact)
2. Steps to reproduce
3. Browser/system info
4. Latest log entries
5. Database state
6. What worked before

---

## Quick Reference

| Issue | Command | Expected |
|-------|---------|----------|
| Is deployed? | `php artisan migrate:status \| grep retell` | [1123] Ran |
| Templates exist? | `php artisan tinker` → `\App\Models\RetellAgentPrompt::where('is_template', true)->count()` | 3 |
| Active version? | `\$branch->retellAgentPrompts()->where('is_active', true)->first()` | One version |
| Validate prompt? | `\$service->validate(\$prompt, \$functions)` | Empty array |
| Clear cache? | `php artisan cache:clear` | Success |
| Seed templates? | `php artisan db:seed --class=RetellTemplateSeeder` | 3 templates |

---

## FAQ

### Q: How do I see what's happening?
**A**: `tail -f storage/logs/laravel.log | grep -i retell`

### Q: How do I test it?
**A**: Make a test call to the agent. It should use the new config.

### Q: How do I fix deployment?
**A**: Check database logs. Use `php artisan tinker` to inspect state.

### Q: How do I rollback?
**A**: Use Version History UI or `markAsActive()` on old version.

### Q: What if cache is stale?
**A**: `php artisan cache:clear` and test again.

---

**Troubleshooting Guide v1.0**
**Last Updated**: 2025-10-21
**Status**: ✅ READY
