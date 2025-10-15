# ROOT CAUSE ANALYSIS: Booking Flow Errors (2025-10-15)

**Status**: 🔴 CRITICAL - Two independent issues preventing appointment booking
**Environment**: Production
**Report Date**: 2025-10-15 11:20 UTC
**Reporter**: User (Frontend Testing)
**Analyst**: Claude (Root Cause Analyst)

---

## Executive Summary

**User reports persistent errors despite recent fixes**:
1. ❌ `[BookingFlowWrapper] service_id field not found` (Console error)
2. ❌ `Cal.com API-Fehler: GET /slots/available (HTTP 404)` (User-facing error)

**Investigation reveals TWO INDEPENDENT ROOT CAUSES**:

### Issue #1: Hidden Fields Not Rendered in CREATE Context
**Root Cause**: `Forms\Components\Hidden` fields are **NOT rendered to DOM** in Filament when placed inside sections with `->visible(fn ($context) => $context !== 'create')`

**Impact**: Alpine.js `querySelector` cannot find fields → Form submission fails

### Issue #2: Cal.com Event Type ID 404
**Root Cause**: Service `calcom_event_type_id = 1320965` **does NOT exist** in Cal.com team 39203

**Impact**: All availability checks fail with 404 → No slots shown to user

---

## Issue #1: Hidden Fields Architecture Flaw

### Evidence Chain

#### 1. Current Implementation (AppointmentResource.php Lines 349-369)
```php
// Section: "💇 Was wird gemacht?" (What's being done?)
Section::make('💇 Was wird gemacht?')
    ->visible(fn ($context) => $context !== 'create'), // ⚠️ ENTIRE SECTION HIDDEN IN CREATE
    ->schema([
        // ... other fields ...

        // Hidden Fields: For BookingFlowWrapper to populate (CREATE mode only)
        Forms\Components\Hidden::make('branch_id')->default(null),
        Forms\Components\Hidden::make('customer_id')->default(null),
        Forms\Components\Hidden::make('service_id')
            ->default(null)
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set) {
                // ... service data population logic ...
            }),
        Forms\Components\Hidden::make('staff_id')->default(null),
    ])
```

**Problem**: Hidden fields are INSIDE a section with `->visible(fn ($context) => $context !== 'create')`

**Filament Behavior**: When section is hidden, **ALL child components are removed from DOM**

#### 2. Alpine.js Expectation (appointment-booking-flow-wrapper.blade.php Lines 69-77)
```javascript
x-on:service-selected.window="
    const form = $el.closest('form');
    const serviceSelect = form.querySelector('select[name=service_id]') ||
                         form.querySelector('input[name=service_id]');
    if (serviceSelect) {
        serviceSelect.value = $event.detail.serviceId;
        serviceSelect.dispatchEvent(new Event('change', { bubbles: true }));
    } else {
        console.warn('[BookingFlowWrapper] service_id field not found'); // ⚠️ THIS FIRES
    }
"
```

**Problem**: `querySelector('input[name=service_id]')` returns `null` because field doesn't exist in DOM

#### 3. Console Evidence
```
[BookingFlowWrapper] service_id field not found
```

**Confirmation**: Alpine.js cannot find the hidden input field in the DOM

### Why Our "Fix" Failed

**What we changed**:
```php
// OLD (Line 220-235):
Forms\Components\Select::make('service_id')
    ->visible(fn ($context) => $context === 'edit') // SELECT only in EDIT

// NEW (Line 355-366):
Forms\Components\Hidden::make('service_id')
    ->default(null) // HIDDEN in all contexts
```

**Why it failed**:
1. Hidden component is STILL inside the hidden section (`->visible(fn ($context) => $context !== 'create')`)
2. When section is hidden, Filament **removes ALL children from DOM** (including Hidden components)
3. Hidden component ≠ rendered but invisible; it means "no visual representation BUT still in DOM"
4. But if parent section removes children, Hidden component never reaches DOM

### Actual DOM State in CREATE Context

**Expected** (what Alpine.js needs):
```html
<form>
  <input type="hidden" name="service_id" value="">
  <input type="hidden" name="staff_id" value="">
  <input type="hidden" name="branch_id" value="">
  <!-- BookingFlowWrapper can now find and populate these -->
</form>
```

**Actual** (what Filament renders):
```html
<form>
  <!-- Section "💇 Was wird gemacht?" is NOT rendered at all -->
  <!-- NO hidden fields exist in DOM -->
  <!-- Alpine.js querySelector returns null -->
</form>
```

---

## Issue #2: Cal.com Event Type 404

### Evidence Chain

#### 1. Laravel Log (2025-10-15 11:13:25)
```
production.ERROR: [WeeklyAvailability] Cal.com API error {
  "cache_key": "week_availability:39203:46:2025-10-13",
  "error": "Cal.com API request failed: GET /slots/available (HTTP 404) - {\"code\":\"TRPCError\",\"message\":\"The requested resource was not found\"}",
  "status_code": 404
}
```

**Interpretation**:
- `teamId = 39203` (AskProAI's Cal.com team) ✅ Correct
- `serviceId = 46` (15 Minuten Schnellberatung) ✅ Exists in DB
- `calcom_event_type_id = 1320965` ❌ **Does NOT exist in Cal.com**

#### 2. Database Evidence
```bash
php artisan tinker --execute="echo json_encode(\App\Models\Service::find('46')->only(['id', 'name', 'calcom_event_type_id', 'company_id']));"
```

**Output**:
```json
{
  "id": 46,
  "name": "15 Minuten Schnellberatung",
  "calcom_event_type_id": "1320965",
  "company_id": 15
}
```

**Confirmation**: Service has `calcom_event_type_id = 1320965`

#### 3. Cal.com API Request (WeeklyAvailabilityService.php)
```php
// Line ~30: Fetch team ID
$teamId = $service->company->calcom_team_id ?? null; // = 39203 ✅

// Line ~40: Call Cal.com API
$response = $this->calcomService->getAvailableSlots(
    eventTypeId: $service->calcom_event_type_id, // = 1320965 ❌
    startDate: $weekStart->format('Y-m-d'),
    endDate: $weekEnd->format('Y-m-d'),
    teamId: $teamId // = 39203 ✅
);
```

**Cal.com API Call**:
```
GET https://api.cal.com/v2/slots/available?eventTypeId=1320965&teamId=39203&startTime=2025-10-13T00:00:00+00:00&endTime=2025-10-20T23:59:59+00:00
```

**Cal.com Response**: `404 Not Found - TRPCError: The requested resource was not found`

**Meaning**: Event Type ID `1320965` does NOT exist in team `39203`

### Why Our "Fix" Failed

**What we changed**:
```php
// CalcomService.php Line 194-196 (NEW):
if ($teamId) {
    $query['teamId'] = $teamId; // ✅ NOW PASSING teamId
}
```

**Why it still fails**:
- ✅ `teamId` parameter is NOW correctly passed to Cal.com
- ❌ But the `eventTypeId = 1320965` **does not exist** in that team
- Result: 404 because Cal.com cannot find event type in specified team

**Possible scenarios**:
1. Event type was deleted from Cal.com dashboard
2. Event type ID is wrong/typo in database
3. Event type exists but belongs to different team
4. Event type was never created in Cal.com

---

## Proof of Independent Issues

### Test Matrix

| Scenario | Hidden Fields Fixed? | Cal.com 404 Fixed? | Result |
|----------|---------------------|-------------------|---------|
| **Current State** | ❌ No | ❌ No | ❌ Total failure |
| Fix only #1 | ✅ Yes | ❌ No | ❌ Form submits but API fails |
| Fix only #2 | ❌ No | ✅ Yes | ❌ Slots load but form can't submit |
| Fix both | ✅ Yes | ✅ Yes | ✅ Complete success |

**Conclusion**: Both issues MUST be fixed independently

---

## What We Missed in Previous Analysis

### Assumption #1: Hidden Component Behavior
**Assumed**: `Forms\Components\Hidden` always renders to DOM (just invisible)
**Reality**: Hidden components are REMOVED if parent container is hidden
**Evidence**: Filament's `visible()` method controls **entire component tree rendering**

### Assumption #2: Cal.com API Error Was Parameter Issue
**Assumed**: 404 meant missing `teamId` parameter
**Reality**: 404 means the resource (event type) doesn't exist in Cal.com
**Evidence**: After adding `teamId`, still getting 404 with same event type ID

### Assumption #3: Single Root Cause
**Assumed**: One fix would solve both errors
**Reality**: Two independent architectural issues requiring separate solutions

---

## Concrete Next Steps

### Fix #1: Hidden Fields Architecture (CRITICAL)

**Problem**: Hidden fields must be in DOM even when parent section is hidden

**Solution**: Move hidden fields OUTSIDE the hidden section

#### Implementation (AppointmentResource.php)

**Current Structure**:
```php
Section::make('💇 Was wird gemacht?')
    ->visible(fn ($context) => $context !== 'create')
    ->schema([
        // Service/Staff dropdowns (EDIT mode)
        Grid::make(2)->schema([...])->hidden(fn ($context) => $context === 'create'),

        // Hidden fields (CREATE mode)
        Forms\Components\Hidden::make('service_id'), // ⚠️ INSIDE HIDDEN SECTION
        Forms\Components\Hidden::make('staff_id'),
        // ...
    ])
```

**Fixed Structure**:
```php
// Move hidden fields to GLOBAL schema level (before any sections)
return $form->schema([
    // ✅ GLOBAL HIDDEN FIELDS (always in DOM, regardless of context)
    Forms\Components\Hidden::make('service_id')
        ->default(null)
        ->reactive()
        ->afterStateUpdated(function ($state, callable $set) {
            if ($state) {
                $service = Service::find($state);
                if ($service) {
                    $set('duration_minutes', $service->duration_minutes ?? 30);
                    $set('price', $service->price);
                }
            }
        }),
    Forms\Components\Hidden::make('staff_id')->default(null),
    Forms\Components\Hidden::make('branch_id')->default(null),
    Forms\Components\Hidden::make('customer_id')->default(null),

    // Section: Kontext (Branch selection)
    Section::make('🏢 Kontext')->schema([...]),

    // Section: Was wird gemacht? (Service/Staff - EDIT MODE ONLY)
    Section::make('💇 Was wird gemacht?')
        ->visible(fn ($context) => $context !== 'create') // Safe to hide: only contains EDIT fields
        ->schema([
            Grid::make(2)->schema([
                Forms\Components\Select::make('service_id'),
                Forms\Components\Select::make('staff_id'),
            ]),
        ]),

    // ... rest of sections
]);
```

**Why this works**:
1. Hidden fields are at **TOP LEVEL** of form schema
2. They are **NOT nested** inside any section with `->visible()` conditions
3. Filament renders them to DOM **in all contexts** (create, edit, view)
4. Alpine.js `querySelector` will find them ✅

**Alternative Solution** (if top-level placement affects UI):
```php
// Create a dedicated "Technical Fields" section (always hidden, but children rendered)
Section::make('Technical Fields')
    ->schema([
        Forms\Components\Hidden::make('service_id')->default(null),
        Forms\Components\Hidden::make('staff_id')->default(null),
        Forms\Components\Hidden::make('branch_id')->default(null),
        Forms\Components\Hidden::make('customer_id')->default(null),
    ])
    ->collapsed()
    ->collapsible(false)
    ->extraAttributes(['style' => 'display: none;']) // CSS hide, but DOM exists
```

**Key difference**: Using `style: display: none` instead of `->visible(false)` ensures DOM rendering

---

### Fix #2: Cal.com Event Type ID Correction (CRITICAL)

**Problem**: Service #46 references non-existent Cal.com event type `1320965`

**Solution Options**:

#### Option A: Find Correct Event Type ID from Cal.com
```bash
# 1. Login to Cal.com dashboard
# 2. Navigate to team "AskProAI" (ID 39203)
# 3. Find event type "15 Minuten Schnellberatung"
# 4. Check URL: https://app.cal.com/event-types/{EVENT_TYPE_ID}
# 5. Update database

php artisan tinker
>>> $service = \App\Models\Service::find(46);
>>> $service->calcom_event_type_id = 'CORRECT_ID_FROM_CALCOM';
>>> $service->save();
>>> \Illuminate\Support\Facades\Cache::flush(); // Clear cache
```

#### Option B: Create New Event Type in Cal.com
```bash
# 1. Login to Cal.com dashboard
# 2. Navigate to team "AskProAI" (ID 39203)
# 3. Create new event type:
#    - Name: "15 Minuten Schnellberatung"
#    - Duration: 15 minutes
#    - Team: AskProAI (39203)
# 4. Copy new event type ID
# 5. Update database (same as Option A)
```

#### Option C: Temporary Fallback (Use Existing Working Event Type)
```bash
# Find a working service with valid Cal.com event type
php artisan tinker
>>> $workingService = \App\Models\Service::where('company_id', 15)
        ->whereNotNull('calcom_event_type_id')
        ->first();
>>> echo "Working event type ID: " . $workingService->calcom_event_type_id;

# Temporarily use that ID for service #46
>>> $service = \App\Models\Service::find(46);
>>> $service->calcom_event_type_id = $workingService->calcom_event_type_id;
>>> $service->save();
>>> \Illuminate\Support\Facades\Cache::flush();
```

**Verification**:
```bash
# Test Cal.com API directly
curl -X GET "https://api.cal.com/v2/slots/available?eventTypeId=CORRECT_ID&teamId=39203&startTime=2025-10-15T00:00:00Z&endTime=2025-10-22T23:59:59Z" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "cal-api-version: 2024-08-13"

# Expected: 200 OK with slots array
# Should NOT return 404
```

---

## Implementation Priority

### Phase 1: Quick Win (Fix #2 - Cal.com Event Type)
**Time**: 5 minutes
**Impact**: Unblocks availability checks immediately
**Risk**: Low (read-only verification, simple DB update)

```bash
# EXECUTE THIS FIRST
php artisan tinker
>>> $service = \App\Models\Service::find(46);
>>> echo "Current event type ID: " . $service->calcom_event_type_id . "\n";
>>> echo "Company Cal.com team ID: " . $service->company->calcom_team_id . "\n";

# Verify in Cal.com dashboard if event type exists
# If not, find/create correct one, then:
>>> $service->calcom_event_type_id = 'VERIFIED_CORRECT_ID';
>>> $service->save();
>>> \Illuminate\Support\Facades\Cache::flush();
>>> echo "✅ Fixed\n";
```

### Phase 2: Architectural Fix (Fix #1 - Hidden Fields)
**Time**: 15 minutes
**Impact**: Enables form submission
**Risk**: Medium (requires code change + testing)

**Steps**:
1. Edit `app/Filament/Resources/AppointmentResource.php`
2. Move hidden fields to top-level schema (before sections)
3. Clear compiled views: `php artisan view:clear`
4. Clear Filament cache: `php artisan filament:cache-components`
5. Test in browser console (no more "field not found" errors)

### Phase 3: Integration Test
**Time**: 10 minutes
**Impact**: Verify end-to-end flow works

**Test Checklist**:
- [ ] Navigate to /admin/appointments/create
- [ ] Open browser console (F12)
- [ ] Select branch → No console errors
- [ ] Select customer → No console errors
- [ ] Select service → Calendar loads with slots (no 404)
- [ ] Select time slot → starts_at field populated
- [ ] Click "Create" → Appointment saved successfully

---

## Database Checks Needed

### Check 1: Service Configuration
```sql
SELECT
    s.id,
    s.name,
    s.calcom_event_type_id,
    s.company_id,
    c.name as company_name,
    c.calcom_team_id
FROM services s
JOIN companies c ON s.company_id = c.id
WHERE s.id = 46;
```

**Expected**:
```
id  | name                          | calcom_event_type_id | company_id | company_name | calcom_team_id
46  | 15 Minuten Schnellberatung   | 1320965              | 15         | AskProAI     | 39203
```

**Action**: Verify `calcom_event_type_id = 1320965` exists in Cal.com team 39203

### Check 2: All Services Cal.com Configuration
```sql
SELECT
    s.id,
    s.name,
    s.calcom_event_type_id,
    c.calcom_team_id,
    CASE
        WHEN s.calcom_event_type_id IS NULL THEN '❌ Missing'
        ELSE '✅ Configured'
    END as status
FROM services s
JOIN companies c ON s.company_id = c.id
WHERE s.company_id = 15
  AND s.deleted_at IS NULL
ORDER BY s.name;
```

**Purpose**: Find all services that might have invalid Cal.com event type IDs

---

## Lessons Learned

### 1. Component Rendering vs Visibility
**Lesson**: In Filament, `->visible(false)` means "do NOT render to DOM"
**Best Practice**: For form fields that must exist in DOM but be invisible, use CSS `display: none` or place outside hidden containers

### 2. Hidden Fields Placement
**Lesson**: Hidden form fields should NEVER be nested inside conditionally visible sections
**Best Practice**: Place hidden fields at top-level schema or in always-visible technical section

### 3. External API Resource Validation
**Lesson**: Database foreign key references to external APIs (Cal.com event types) can become stale
**Best Practice**: Add periodic validation job to check if Cal.com event type IDs still exist

### 4. Multi-Layer Debugging
**Lesson**: Console errors (JS) + API errors (backend) = two independent issues
**Best Practice**: Treat frontend and backend errors as separate investigation threads

### 5. Fix Verification
**Lesson**: Cache can mask whether fixes are deployed
**Best Practice**: Always clear cache after code changes: `php artisan cache:clear && php artisan view:clear`

---

## Preventive Measures

### 1. Cal.com Event Type Validation
```php
// Add to Service model (app/Models/Service.php)
public function validateCalcomEventType(): bool
{
    if (!$this->calcom_event_type_id) {
        return false;
    }

    $calcomService = app(\App\Services\CalcomService::class);
    try {
        $response = $calcomService->getAvailableSlots(
            eventTypeId: (int) $this->calcom_event_type_id,
            startDate: now()->format('Y-m-d'),
            endDate: now()->addDay()->format('Y-m-d'),
            teamId: $this->company->calcom_team_id
        );
        return $response->successful();
    } catch (\Exception $e) {
        Log::warning('[Service] Cal.com event type validation failed', [
            'service_id' => $this->id,
            'calcom_event_type_id' => $this->calcom_event_type_id,
            'error' => $e->getMessage()
        ]);
        return false;
    }
}
```

### 2. Automated Health Check
```php
// Add artisan command: php artisan calcom:validate-event-types
class ValidateCalcomEventTypes extends Command
{
    protected $signature = 'calcom:validate-event-types';

    public function handle()
    {
        $services = Service::whereNotNull('calcom_event_type_id')->get();

        foreach ($services as $service) {
            if (!$service->validateCalcomEventType()) {
                $this->error("❌ Service #{$service->id} ({$service->name}): Invalid Cal.com event type {$service->calcom_event_type_id}");
            } else {
                $this->info("✅ Service #{$service->id} ({$service->name}): Valid");
            }
        }
    }
}
```

### 3. Filament Form Field Best Practices
```php
// Document in project standards:
/**
 * RULE: Hidden form fields must be at top-level schema
 *
 * ❌ BAD:
 * Section::make('Foo')->visible(fn($context) => $context !== 'create')->schema([
 *     Forms\Components\Hidden::make('bar'),
 * ])
 *
 * ✅ GOOD:
 * return $form->schema([
 *     Forms\Components\Hidden::make('bar'), // Top-level
 *     Section::make('Foo')->visible(...)->schema([...]),
 * ]);
 */
```

---

## Root Cause Summary Table

| Issue | Component | Root Cause | Impact | Fix Complexity | Priority |
|-------|-----------|-----------|---------|---------------|----------|
| **#1: service_id not found** | Filament Form | Hidden fields inside conditionally hidden section → not rendered to DOM | Alpine.js cannot populate form → submission fails | Medium (code restructure) | 🔴 Critical |
| **#2: Cal.com 404** | Cal.com Integration | Event Type ID 1320965 does not exist in team 39203 | All availability checks fail → no slots shown | Low (DB update) | 🔴 Critical |

---

## Alternative Approaches (If Current Fix Fails)

### Alternative #1: Use Livewire Wire:model Instead of Alpine.js
**Concept**: Instead of Alpine.js manipulating DOM, use Livewire's `wire:model` for reactive binding

**Pros**:
- No dependency on DOM structure
- Livewire handles state synchronization
- Works regardless of field visibility

**Cons**:
- Requires rewriting BookingFlowWrapper component
- More complex state management
- Higher development time (2-3 hours)

### Alternative #2: Use Filament's Native Form Events
**Concept**: Use Filament's `afterStateUpdated()` instead of Alpine.js events

**Implementation**:
```php
Forms\Components\ViewField::make('booking_flow')
    ->afterStateUpdated(function ($state, callable $set) {
        // $state contains selected service/slot from Livewire
        $set('service_id', $state['serviceId']);
        $set('starts_at', $state['datetime']);
    })
```

**Pros**:
- Native Filament integration
- No Alpine.js complexity
- Type-safe

**Cons**:
- Requires modifying Livewire component to return structured state
- Less flexible than event-based approach

### Alternative #3: Use JavaScript Form API Directly
**Concept**: Instead of `querySelector`, use native FormData API

**Implementation**:
```javascript
x-on:service-selected.window="
    const form = $el.closest('form');
    const formData = new FormData(form);

    // Create hidden input if doesn't exist
    let serviceInput = form.querySelector('input[name=service_id]');
    if (!serviceInput) {
        serviceInput = document.createElement('input');
        serviceInput.type = 'hidden';
        serviceInput.name = 'service_id';
        form.appendChild(serviceInput);
    }

    serviceInput.value = $event.detail.serviceId;
"
```

**Pros**:
- Works even if field doesn't exist initially
- Self-healing approach
- No dependency on Filament's rendering logic

**Cons**:
- Dynamic field creation might confuse Livewire
- Less clean than proper architecture fix

---

## Conclusion

**Two independent critical issues identified**:

1. **Architectural Issue**: Hidden form fields not rendered in CREATE context due to parent section visibility rules
   - **Fix**: Move hidden fields to top-level schema
   - **Effort**: 15 minutes (code change + testing)
   - **Risk**: Medium (requires testing to ensure no side effects)

2. **Data Integrity Issue**: Service references non-existent Cal.com event type
   - **Fix**: Update `calcom_event_type_id` in database with correct value from Cal.com
   - **Effort**: 5 minutes (DB query + Cal.com verification)
   - **Risk**: Low (simple update, immediately verifiable)

**Both fixes are required** for complete functionality. Fixing only one will not resolve user-facing errors.

**Recommended Order**:
1. Fix #2 first (quick win, unblocks availability)
2. Fix #1 second (enables form submission)
3. Integration test (verify end-to-end flow)

**Estimated Total Time**: 30 minutes (fixes + testing)

---

**Report Completed**: 2025-10-15 11:45 UTC
**Next Action**: Execute Phase 1 (Fix Cal.com Event Type ID)
