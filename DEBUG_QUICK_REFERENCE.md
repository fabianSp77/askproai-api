# Debug Quick Reference - Essential Commands

**Purpose**: Fastest way to gather critical information for each bug

---

## RUN ALL VERIFICATION AT ONCE

```bash
bash /var/www/api-gateway/run_debug_verification.sh
```

This generates a timestamped report: `DEBUG_VERIFICATION_RESULTS_YYYY-MM-DD_HH-MM-SS.txt`

---

## BUG #1: Agent Hallucination - 2 MIN CHECK

```bash
# 1. Check where conversation flow is stored
ls -la /var/www/api-gateway/public/*.json | grep -i flow

# 2. Check for flow-related database tables
mysql -h localhost -u root -p -e "SHOW TABLES LIKE '%flow%';"

# 3. Check deployment scripts
ls -la /var/www/api-gateway/scripts/deployment/

# 4. Find where ICS is being updated
grep -r "conversation_flow" /var/www/api-gateway/app/Console/Commands/
```

**Expected output**: Identifies flow storage location and update mechanism

---

## BUG #2: Date Parsing - 1 MIN CHECK

```bash
# 1. Verify DateTimeParser exists and is complete
wc -l /var/www/api-gateway/app/Services/Retell/DateTimeParser.php
# Expected: ~945 lines

# 2. Check for German relative dates support
grep "GERMAN_DATE_MAP\|heute\|morgen" /var/www/api-gateway/app/Services/Retell/DateTimeParser.php | head -5

# 3. Run existing tests
cd /var/www/api-gateway
vendor/bin/pest tests/Unit/Services/Retell/DateTimeParserShortFormatTest.php -v

# 4. Check timezone handling
grep "Europe/Berlin" /var/www/api-gateway/app/Services/Retell/DateTimeParser.php | wc -l
# Expected: Multiple timezone references (>5)
```

**Expected output**: DateTimeParser is comprehensive; tests show coverage

---

## BUG #3: Email Crash - 2 MIN CHECK

```bash
# 1. Check ICS library version
cd /var/www/api-gateway && composer show spatie/icalendar-generator

# 2. Find where ICS is attached
grep -r "icalendar\|\.ics\|attach" /var/www/api-gateway/app --include="*.php" | grep -v vendor | cut -d: -f1 | sort | uniq

# 3. Check last updates to mail config
git log --oneline --all -- /var/www/api-gateway/config/mail.php | head -5

# 4. Find notification classes
find /var/www/api-gateway/app -name "*Notification.php" | head -5
```

**Expected output**: Identifies notification class and attachment location

---

## BUG #4: V9 Not Deployed - 3 MIN CHECK

```bash
# 1. Check current deployed commit
cd /var/www/api-gateway
git log --oneline -1
git branch

# 2. Check for V9 in commit history
git log --oneline --all | grep -i "v9\|version.*9" | head -5

# 3. Check for uncommitted code changes
git status --short

# 4. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 5. Check OPcache status
php -r "var_dump(opcache_get_status());" | head -20

# 6. Verify code is loaded
stat /var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php
# Check timestamp - should be recent if V9 is deployed
```

**Expected output**: Shows deployment status, uncommitted changes, cache state

---

## COMPREHENSIVE DEBUGGING STEPS (FULL INVESTIGATION)

### Step 1: Gather All Information (5 min)
```bash
bash /var/www/api-gateway/run_debug_verification.sh
cat DEBUG_VERIFICATION_RESULTS_*.txt
```

### Step 2: Per-Bug Analysis (10 min each)

#### BUG #1: Agent Hallucination
```bash
# Check ServiceSelectionService implementation
cat /var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php | head -100

# Check if service validation is present
grep -A 10 "function.*select\|function.*validate" /var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php

# Look for hardcoded service names
grep -r "service_name.*=\|hardcode\|fake\|mock" /var/www/api-gateway/app/Services/Retell/ --include="*.php"
```

#### BUG #2: Date Parsing
```bash
# Test DateTimeParser manually
php artisan tinker
# > app(\App\Services\Retell\DateTimeParser::class)->parseDateTime(['time' => '14:00', 'relative_day' => 'morgen'])
# > app(\App\Services\Retell\DateTimeParser::class)->parseDateString('dieser Donnerstag')

# Run specific test scenarios
vendor/bin/pest tests/Unit/Services/Retell/DateTimeParserShortFormatTest.php::test_parse_short_format -v
```

#### BUG #3: Email Crash
```bash
# Find the notification class sending emails with ICS
find /var/www/api-gateway/app -name "*Notification.php" -exec grep -l "icalendar\|attach\|mailable" {} \;

# Check the exact error
tail -100 /var/www/api-gateway/storage/logs/laravel.log | grep -i "ical\|attach\|mail\|notification"

# Try sending a test notification
php artisan tinker
# > app(\App\Notifications\YourNotification::class)->send(user_instance)
```

#### BUG #4: V9 Not Deployed
```bash
# Show exact commit status
git log -1 --format="%H %s %ai" /var/www/api-gateway/

# Check if specific files show V9 markers
grep -r "v9\|V9\|version.*9" /var/www/api-gateway/app/Services/Retell/ --include="*.php"

# Force clear PHP cache and reload
sudo systemctl restart php-fpm
php artisan optimize:clear

# Verify changes are loaded
php -r "require 'vendor/autoload.php'; echo app(\App\Services\Retell\ServiceSelectionService::class) ? 'Loaded' : 'Failed';"
```

### Step 3: Test Each Fix
```bash
# Run full test suite
vendor/bin/pest tests/Unit/Services/Retell/ -v

# Run specific bug-related tests
vendor/bin/pest tests/Unit/Services/Retell/DateTimeParserShortFormatTest.php -v
vendor/bin/pest tests/Unit/Services/Retell/ServiceSelectionServiceTest.php -v
```

### Step 4: Manual System Test
```bash
# Make a test call to Retell
# (Requires Retell dashboard or test phone number)

# Check logs for errors
tail -f /var/www/api-gateway/storage/logs/laravel.log

# Monitor calls being processed
mysql -h localhost -u root -p -e "SELECT id, retell_call_id, status, created_at FROM calls ORDER BY created_at DESC LIMIT 10;"
```

---

## DIAGNOSTIC COMMANDS FOR EACH COMPONENT

### Cache Status
```bash
# Check all cache stores
redis-cli INFO stats  # if using Redis
ls -lah /var/www/api-gateway/storage/framework/cache/
cat /var/www/api-gateway/storage/logs/laravel.log | grep -i cache | tail -10
```

### Database Status
```bash
# Check recent calls
mysql -h localhost -u root -p -e "
SELECT COUNT(*) as total_calls,
       MAX(created_at) as latest,
       COUNT(CASE WHEN status='failed' THEN 1 END) as failed_count
FROM calls;
"

# Check appointments
mysql -h localhost -u root -p -e "
SELECT COUNT(*) as total_appointments,
       COUNT(CASE WHEN confirmed=1 THEN 1 END) as confirmed
FROM appointments;
"
```

### Retell Agent Status
```bash
# Check agent configuration
grep -r "agent_id" /var/www/api-gateway/config/

# Get agent details (requires API key)
curl -s -H "Authorization: Bearer $RETELL_TOKEN" \
  https://api.retell.ai/agents/$(cat /var/www/api-gateway/.env | grep RETELL_AGENT | cut -d= -f2) | jq .
```

### Laravel Status
```bash
# Check app health
php artisan env
php artisan config:show --key=app

# Check service providers loaded
php artisan list | grep "retell\|calendar" -i

# Clear all caches (nuclear option)
php artisan cache:clear && \
php artisan config:clear && \
php artisan view:clear && \
php artisan route:clear && \
php artisan optimize:clear && \
echo "All caches cleared!"
```

---

## DEBUGGING WORKFLOW

### For Bug #1 (Hallucination):
1. Check ServiceSelectionService - is it querying the database correctly?
2. Check Retell flow - does it send correct service name?
3. Check webhook response - is the error being returned to the user?

### For Bug #2 (Date Parsing):
1. Run DateTimeParser tests - do they all pass?
2. Check log files for parsing failures
3. Test with actual user inputs that failed

### For Bug #3 (Email Crash):
1. Identify notification class sending ICS
2. Check ICS library version and recent changes
3. Look for error messages in logs
4. Try disabling ICS attachment temporarily

### For Bug #4 (V9 Not Deployed):
1. Verify commit is in current branch
2. Check git log shows V9-related fixes
3. Clear all caches
4. Restart PHP-FPM
5. Verify files are loading new code

---

## EXPECTED FINDINGS

### BUG #1
- Flow stored in: Retell Dashboard OR `/public/*.json` OR database table
- Update mechanism: API call OR artisan command OR deployment script
- ServiceSelectionService validates against database

### BUG #2
- DateTimeParser has 945 lines with 10+ public methods
- Supports German relative dates ("heute", "morgen", "dieser Donnerstag")
- Tests pass (DateTimeParserShortFormatTest.php exists)
- Timezone: Europe/Berlin consistent throughout

### BUG #3
- ICS library: spatie/icalendar-generator v3.0
- Notification class found in /app/Notifications/ or /app/Mail/
- Error in logs points to specific attachment method
- Fix: either update library OR disable attachment

### BUG #4
- V9 commits visible in git log (last 20 commits)
- Code changes present in service files
- OPcache might be caching old code
- Solution: clear caches + restart PHP-FPM

---

## WHEN STUCK

1. **Run the automated script**: `bash run_debug_verification.sh`
2. **Check the output file**: `DEBUG_VERIFICATION_RESULTS_*.txt`
3. **Review the logs**: `tail -200f storage/logs/laravel.log`
4. **Run tests**: `vendor/bin/pest tests/Unit/Services/Retell/ -v`
5. **Ask for clarification**: What's the exact error message you're seeing?
