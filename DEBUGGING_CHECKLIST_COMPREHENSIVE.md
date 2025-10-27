# Comprehensive Debugging Checklist - Voice AI Appointment System

**Created**: 2025-10-25
**Project**: AskPro AI Gateway - Retell AI Integration
**Purpose**: Structured information gathering before implementing fixes for 4 critical bugs

---

## BUG #1: Agent Hallucination (Service Selection Mismatch)

### Context & Background
- **Symptom**: Agent suggests services that don't exist in system
- **Impact**: Causes booking failures, user confusion
- **Related Files**:
  - `/var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php`
  - `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`
  - Retell conversation flow configuration (external to codebase)

### Information Needed

#### 1. Where is Retell conversation flow stored?
**Question**: Is it in:
- [ ] Retell Dashboard only (cloud-hosted)?
- [ ] JSON file in codebase (e.g., `/public/friseur1_flow_*.json`)?
- [ ] Database table (`conversation_flows` table)?
- [ ] Multiple places (synced)?

**Commands to verify**:
```bash
# Check for local flow files
ls -la /var/www/api-gateway/public/*.json | grep -i flow
# Check for flow-related database tables
mysql -h localhost -u root -p -e "SHOW TABLES LIKE '%flow%';"
# Check for flow migration
ls -la /var/www/api-gateway/database/migrations/*flow*
```

#### 2. How to update conversation flow?
**Question**: What's the update mechanism:
- [ ] Manual edit in Retell Dashboard + republish?
- [ ] API endpoint (`/api/conversation-flow/{id}` update)?
- [ ] Laravel artisan command?
- [ ] Deployment script (`.claude/commands/` or `/scripts/deployment/`)?

**Key file references**:
```
/var/www/api-gateway/app/Console/Commands/DeployConversationFlow.php
/var/www/api-gateway/scripts/deployment/auto_publish_retell.php
/var/www/api-gateway/scripts/retell_deploy.sh
/var/www/api-gateway/app/Services/Retell/RetellPromptTemplateService.php
```

**Verification commands**:
```bash
# Check for deployment scripts
ls -la /var/www/api-gateway/scripts/deployment/
grep -r "conversation.*flow" /var/www/api-gateway/app/Console/Commands/
# Search for Retell API calls
grep -r "conversation_flow" /var/www/api-gateway/app/
```

#### 3. Can we test flow changes locally?
**Question**: Is there a simulator or testing mechanism:
- [ ] Retell Simulator available in dashboard?
- [ ] Local mock executor?
- [ ] Test call history available?

**Key locations**:
```
/var/www/api-gateway/app/Services/Testing/CallFlowSimulator.php
/var/www/api-gateway/app/Services/Testing/FlowValidationEngine.php
/var/www/api-gateway/app/Services/Testing/MockFunctionExecutor.php
/var/www/api-gateway/tests/ (test suite with 15+ tests)
```

**Verification commands**:
```bash
# Check for test capabilities
grep -r "Simulator\|MockFunctionExecutor\|FlowValidation" /var/www/api-gateway/app/
# List available test files
find /var/www/api-gateway/tests/ -name "*Flow*" -o -name "*Retell*"
```

#### 4. How are function results passed to conversation flow?
**Question**: The mechanism for bridging collect_appointment_info() → flow:
- [ ] Return JSON in specific format?
- [ ] Update state variable?
- [ ] Function modifies flow context?
- [ ] Webhook response includes next action?

**Key file**:
```
/var/www/api-gateway/app/Services/Retell/WebhookResponseService.php
/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php (lines 1-100)
```

**Verification commands**:
```bash
# Check response format
grep -A 20 "function_call_request\|webhook_response" /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php
# Search for response structure
grep -r "return_value\|function_result" /var/www/api-gateway/app/Services/Retell/
```

#### 5. What's the exact condition syntax for flow transitions?
**Question**: How does flow define transitions between nodes:
- [ ] JavaScript expressions?
- [ ] Simple equality checks?
- [ ] Complex logic operators?
- [ ] Regex patterns?

**Look at**:
- Actual conversation flow JSON structure
- Example transitions in `/public/friseur1_flow_*.json` files
- Retell documentation references

**Expected findings**:
```json
// Example condition format (hypothesis)
{
  "type": "function_node",
  "transitions": {
    "on_service_selected": {
      "type": "condition",
      "condition": "service_name && service_availability > 0",
      "next_node": "select_time"
    }
  }
}
```

---

## BUG #2: Date Parsing Errors

### Context & Background
- **Symptom**: Incorrect date interpretation from user input
- **Impact**: Wrong appointments created, time parsing failures
- **Status**: Well-documented service with extensive fixes
- **Key File**: `/var/www/api-gateway/app/Services/Retell/DateTimeParser.php` (945 lines)

### Information Needed

#### 1. Which DateTimeParser methods exist?
**Status**: VERIFIED - File read shows:
- `parseDateTime(array $params)` - Main entry point
- `parseRelativeDate(string $relativeDay, ?string $time)` - German relative dates
- `inferDateFromTime(string $time, ?string $callId = null)` - Smart inference
- `parseDateString(?string $dateString)` - Flexible date parsing
- `parseTimeString(?string $timeString)` - Time-only parsing
- `parseTimeOnly(?string $timeString, ?string $contextDate = null)` - Time with context
- `isTimeOnly(string $input)` - Detector
- `parseDuration($duration, int $default = 60)` - Duration handling
- `parseRelativeWeekday(string $weekday, string $modifier)` - "dieser" vs "nächster"
- `parseWeekRange(string $modifier)` - Week ranges
- `extractTimeComponents(string $time)` - Helper for time parsing

#### 2. Does it handle relative dates already?
**Status**: VERIFIED - Extensive support:
```php
// German relative dates supported (GERMAN_DATE_MAP):
'heute' => 'today'
'morgen' => 'tomorrow'
'übermorgen' => '+2 days'
'montag' to 'sonntag' => weekday mappings

// Complex patterns supported:
- "dieser Donnerstag" (this week's Thursday)
- "nächster Donnerstag" (next occurrence)
- "nächste Woche Mittwoch" (next week's Wednesday)
- "15.1" (German short format with month inference)
- "01.10.2025" (German long format)
- ISO format (2025-10-01)
```

#### 3. What's the expected input/output format?
**Status**: VERIFIED

**Input formats supported**:
- ISO: `"2025-10-25"`, `"2025-10-25 14:30"`
- German: `"25.10.2025"`, `"25.10.2025 14:30"`
- German short: `"25.10"`, `"15.1"` (mid-month defaults to current month)
- German relative: `"heute"`, `"morgen"`, `"montag"`, `"dieser Donnerstag"`
- Time-only: `"14:00"`, `"14 Uhr"`, `"vierzehn Uhr"`
- Numeric hour: `"14"`

**Output format**:
- `Carbon` instance (PHP datetime object with timezone: Europe/Berlin)
- Or null on failure
- All times are Berlin timezone (UTC+1/+2)

**Verification command**:
```bash
grep -A 5 "Carbon::" /var/www/api-gateway/app/Services/Retell/DateTimeParser.php | head -30
```

#### 4. Are there unit tests for DateTimeParser?
**Status**: YES - Found test file

```bash
# Run tests
cd /var/www/api-gateway
vendor/bin/pest tests/Unit/Services/Retell/DateTimeParserShortFormatTest.php

# Run all Retell service tests
vendor/bin/pest tests/Unit/Services/Retell/
```

**Test file location**:
- `/var/www/api-gateway/tests/Unit/Services/Retell/DateTimeParserShortFormatTest.php`

#### 5. What timezone considerations?
**Status**: VERIFIED - Berlin-centric

**Key points**:
- All times calculated in `Europe/Berlin` timezone
- Smart caching: `getCachedBerlinTime()` reuses `now('Europe/Berlin')` per request (5-10ms savings)
- Month inference logic: If month is "1" AND day is mid-month (>10) AND it's not January → assume current month
- Past date handling: If date is >7 days in past → tries next year
- Daylight saving transitions: Handled by Carbon (Oct/Mar changes)

**Relevant code section**:
```php
// Line 35-44: Request-scoped caching
private function getCachedBerlinTime(?string $callId = null): Carbon
{
    $cacheKey = $callId ?? 'default';
    if (!isset(self::$callTimeCache[$cacheKey])) {
        self::$callTimeCache[$cacheKey] = Carbon::now('Europe/Berlin');
    }
    return self::$callTimeCache[$cacheKey]->copy();
}

// Clear cache between tests
public static function clearTimeCache(): void
{
    self::$callTimeCache = [];
}
```

---

## BUG #3: Email Crash (ICS Attachment)

### Context & Background
- **Symptom**: Email notifications fail when attaching calendar
- **Impact**: Users don't receive booking confirmations
- **Related**: Notification system, calendar integration
- **Key File**: Unknown (need to locate)

### Information Needed

#### 1. Which ICS library is installed?
**Status**: VERIFIED

**Installed library**:
```json
"spatie/icalendar-generator": "^3.0"
```

**Verification**:
```bash
# Check version
cd /var/www/api-gateway
composer show spatie/icalendar-generator

# Check if installed
php -r "require 'vendor/autoload.php'; echo class_exists('Spatie\ICalendar\CalendarFactory') ? 'YES' : 'NO';"
```

#### 2. What version is installed?
**Status**: REQUIRES RUNTIME CHECK

**Commands**:
```bash
# Get exact version
php /var/www/api-gateway/vendor/bin/composer show spatie/icalendar-generator

# Check changelog for breaking changes
cat /var/www/api-gateway/vendor/spatie/icalendar-generator/CHANGELOG.md | head -50
```

#### 3. Was it recently updated?
**Status**: REQUIRES GIT CHECK

**Commands**:
```bash
# Check composer.lock updates
git log --oneline composer.lock | grep -i spatie | head -10

# Check when dependency was last locked
git log --oneline -1 -- composer.lock

# See if version constraint changed
git diff HEAD~10..HEAD composer.json | grep -A2 -B2 spatie
```

#### 4. Are there other email notifications that work?
**Status**: REQUIRES INVESTIGATION

**Known notification types** (from codebase):
- Twilio notifications (configured in config/services.php)
- Postmark, Resend, AWS SES (configured)
- Telegram notifications (laravel-notification-channels/telegram)

**Commands to verify**:
```bash
# Find notification classes
find /var/www/api-gateway/app -name "*Notification.php" -type f

# Check mailing tests
grep -r "Mail::" /var/www/api-gateway/tests/ | head -10

# Check queue jobs that send mail
grep -r "Mailable\|Mail::send\|->notify" /var/www/api-gateway/app/Jobs/ | head -15
```

#### 5. Can we disable ICS attachment temporarily?
**Status**: LIKELY YES

**Approach**:
1. Find where ICS is generated (likely in Notification class)
2. Add config flag: `DISABLE_ICS_ATTACHMENT=true`
3. Conditional attachment logic
4. Fallback to plain text notification

**Key files to search**:
```bash
# Find ICS generation
grep -r "icalendar\|\.ics\|ICalendar" /var/www/api-gateway/app/ --include="*.php"

# Find mailable classes
find /var/www/api-gateway/app -name "*Mail.php" -o -name "*Mailable.php"

# Find notification senders
grep -r "->attachFrom\|->attach" /var/www/api-gateway/app/ --include="*.php"
```

---

## BUG #4: V9 Not Deployed (Cache/Version Issue)

### Context & Background
- **Symptom**: Code changes not reflected in running system
- **Root Cause Possibilities**: OPcache, git state, deployment script failure
- **Status**: Critical deployment verification needed

### Information Needed

#### 1. How to verify which code version is running?
**Status**: MULTIPLE APPROACHES

**Approach 1 - Git-based verification**:
```bash
# Current HEAD commit
cd /var/www/api-gateway
git log --oneline -1

# Current branch
git branch

# Check for uncommitted changes
git status

# Check if V9 is in recent commits
git log --oneline -20 | grep -i "v9\|version.*9"
```

**Approach 2 - Version constant check**:
```bash
# Search for version constant in code
grep -r "VERSION\|v[0-9]\|const.*VERSION" /var/www/api-gateway/app --include="*.php" | head -20

# Check config files
grep -r "app.version\|APP_VERSION" /var/www/api-gateway/config /var/www/api-gateway/.env* 2>/dev/null
```

**Approach 3 - Runtime behavior verification**:
```bash
# Check if specific file exists/was modified (V9 indicator)
stat /var/www/api-gateway/app/Services/Retell/ServiceSelectionService.php

# Check modification timestamp
ls -l /var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php | awk '{print $6, $7, $8}'
```

#### 2. Check git log - when was V9 committed?
**Status**: REQUIRES GIT INSPECTION

**Commands**:
```bash
# All commits mentioning V9/version 9
git log --all --oneline | grep -i "v9\|version 9"

# If commits exist, show details
git show <commit-hash>

# Check if commit is in current branch
git branch --contains <commit-hash>

# Show commits in last 24 hours
git log --oneline --since="24 hours ago"

# Show what changed in most recent commit
git show --stat HEAD
```

**Expected findings**:
```
9db80272 fix(critical): Resolve race condition in anonymous call initialization
7152bbf0 fix(critical): Handle versioned function names in RetellFunctionCallHandler
7a942393 feat: P0 critical fixes - service selection, SAGA pattern, race condition, timeout optimization
```

#### 3. Are there version constants in code?
**Status**: PARTIALLY VERIFIED

**Known version references**:
```bash
# Search for version patterns
grep -r "VERSION\s*=\|v[0-9][0-9]*\|V[0-9][0-9]*" /var/www/api-gateway/app --include="*.php"

# Check for versioned class names
grep -r "v[0-9].*Service\|Agent.*v[0-9]" /var/www/api-gateway/app --include="*.php"

# Check Retell prompt versions
grep -r "v[0-9]\|prompt.*version" /var/www/api-gateway/app/Services/Retell/ --include="*.php"
```

#### 4. How to verify OPcache cleared?
**Status**: CRITICAL STEP

**Commands**:
```bash
# Check OPcache status
php -r "var_dump(opcache_get_status());"

# Check if OPcache is enabled
php -r "echo ini_get('opcache.enable') ? 'ENABLED' : 'DISABLED';"

# Check OPcache files directory
ls -la /var/run/php/opcache/ 2>/dev/null || echo "OPcache dir not found"

# Clear OPcache (requires CLI)
php -r "opcache_reset();" && echo "OPcache reset attempted"

# Alternative: Check via web interface
curl http://localhost/opcache-status.php 2>/dev/null | grep -i "status\|memory" | head -10
```

**Laravel-specific clearing**:
```bash
# Clear all Laravel caches
php /var/www/api-gateway/artisan cache:clear
php /var/www/api-gateway/artisan config:clear
php /var/www/api-gateway/artisan view:clear
php /var/www/api-gateway/artisan route:clear

# Verify caches cleared
ls -la /var/www/api-gateway/storage/framework/cache/
```

#### 5. Alternative: Check PHP-FPM restart timestamp
**Status**: ADDITIONAL VERIFICATION

**Commands**:
```bash
# Check PHP-FPM process start time
ps aux | grep php-fpm | head -5

# Check when PHP-FPM master process started
ps aux | grep "php-fpm: master" | awk '{print $2}' | xargs -I {} sh -c 'echo "PID: {}"; stat /proc/{} 2>/dev/null | grep Access | tail -1'

# Check web server restart
systemctl status php-fpm | grep "Active:"
systemctl status nginx | grep "Active:"

# Check if code was reloaded (via timestamps)
find /var/www/api-gateway/app -type f -name "*.php" -newer /var/www/api-gateway/storage/logs/laravel.log 2>/dev/null | wc -l
```

---

## TESTING & VALIDATION

### Can we use Retell Simulator?
**Question**: Access to Retell's testing infrastructure:
- [ ] Simulator available in dashboard?
- [ ] Test phone numbers configured?
- [ ] Mock mode available?

**Verification**:
```bash
# Check Retell configuration
grep -r "RETELL\|retell" /var/www/api-gateway/.env* 2>/dev/null | grep -v token | head -20

# Check for agent ID
grep -r "agent_id\|AGENT_ID" /var/www/api-gateway/config/

# Look for test mode configuration
grep -r "test.*mode\|sandbox\|mock" /var/www/api-gateway/config/
```

### Do we have test phone numbers?
**Question**: Testing infrastructure:
- [ ] Dedicated test phone numbers?
- [ ] Staging environment?
- [ ] Production vs test agent IDs?

**Commands**:
```bash
# Check for phone number configuration
grep -r "phone.*test\|test.*phone\|PHONE_" /var/www/api-gateway/.env*

# Look for test data
grep -r "test.*number\|+49.*test" /var/www/api-gateway/database/seeders/ --include="*.php"

# Check for call records from tests
mysql -h localhost -u root -p -e "SELECT COUNT(*), MAX(created_at) FROM calls WHERE call_status IN ('test', 'simulated') LIMIT 5;" 2>/dev/null
```

### How to check Cal.com without creating real bookings?
**Question**: Non-destructive testing:
- [ ] Dry-run mode available?
- [ ] Test calendar/event type?
- [ ] Availability check without booking?

**Key services**:
```
/var/www/api-gateway/app/Services/CalcomService.php
/var/www/api-gateway/app/Services/Appointments/CalcomAvailabilityService.php
/var/www/api-gateway/app/Services/Booking/CompositeBookingService.php
```

**Verification commands**:
```bash
# Check for test/dry-run configuration
grep -r "dry.*run\|test.*event\|sandbox" /var/www/api-gateway/app/Services/Cal* --include="*.php"

# Check Cal.com API key scope
grep -r "CALCOM.*API\|calcom_api_key" /var/www/api-gateway/config/

# Look for availability-only endpoints
grep -r "availability\|getSlots\|free.*busy" /var/www/api-gateway/app/Services/Appointments/
```

### Staging environment available?
**Question**: Deployment targets:
- [ ] Staging URL configured?
- [ ] Separate Retell agent for staging?
- [ ] Separate database?

**Verification**:
```bash
# Check environment configuration
grep -r "APP_ENV\|APP_URL\|STAGING" /var/www/api-gateway/.env* 2>/dev/null

# Check for environment-based agent selection
grep -r "if.*production\|if.*staging" /var/www/api-gateway/app --include="*.php" | head -10

# Check deployment targets
ls -la /var/www/api-gateway/.github/workflows/ 2>/dev/null || echo "GitHub Actions not configured"
cat /var/www/api-gateway/.gitlab-ci.yml 2>/dev/null || echo "GitLab CI not configured"
```

---

## SUMMARY: Information Gathering Plan

### Phase 1: Automatic Verification (No Manual Testing)
Run all commands in "Verification" sections to automatically gather:
1. File locations and sizes
2. Library versions
3. Git history
4. Configuration values
5. Test file inventory
6. Cache status

### Phase 2: Analysis & Decision Points
Based on Phase 1 output:
1. Identify bug #1 mechanism (flow location & update process)
2. Verify bug #2 status (DateTimeParser completeness)
3. Locate bug #3 source (email notification + ICS)
4. Confirm bug #4 state (V9 deployment status)

### Phase 3: Manual Testing (Only if Needed)
- Run Retell simulator tests
- Make test call to verify deployment
- Check Cal.com test availability
- Verify notification sending

### Phase 4: Implementation
Execute fixes in order:
1. Bug #4 first (ensure code is deployed)
2. Bug #2 (DateTimeParser - existing tests available)
3. Bug #3 (Email - likely quick fix)
4. Bug #1 (Agent - requires flow update)

---

## Quick Reference: Key Files

| Bug | Primary File | Test File | Config |
|-----|-------------|-----------|--------|
| #1 (Hallucination) | ServiceSelectionService.php | ServiceSelectionServiceTest.php | config/services.php |
| #2 (Date Parsing) | DateTimeParser.php | DateTimeParserShortFormatTest.php | config/app.php (timezone) |
| #3 (Email Crash) | [TBD - Notification class] | [TBD] | config/mail.php |
| #4 (V9 Not Deployed) | Any service file | [All tests] | .env, composer.lock |

---

## Next Steps

1. **Run verification commands** from each bug section
2. **Document findings** in this checklist
3. **Create focused RCA** for each bug based on findings
4. **Implement fixes** in priority order
5. **Run test suite** to validate: `vendor/bin/pest`
6. **Manual verification** with test calls
