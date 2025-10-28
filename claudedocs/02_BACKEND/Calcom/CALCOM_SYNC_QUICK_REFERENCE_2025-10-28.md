# Cal.com Sync - Quick Reference Guide

## Status Column Icons

```
┌──────────────┬──────────────┬──────────────┐
│  SYNC STATUS │ ACTIVE STATE │ ONLINE STATE │
├──────────────┼──────────────┼──────────────┤
│ ✓ synced     │ ✓ active     │ 🌐 online    │
│ ⏳ pending   │ ○ inactive   │ ○ offline    │
│ ❌ error     │              │              │
│ ⚪ never     │              │              │
└──────────────┴──────────────┴──────────────┘
```

**Can Be Booked If**: `sync_status = 'synced' AND is_active = true`

---

## Sync Flows at a Glance

### 1. You Edit Service in Platform ✅

```
Edit Service Name/Price/Duration
    ↓
ServiceObserver marks sync_status = 'pending'
    ↓
UpdateCalcomEventTypeJob queued
    ↓
Job syncs PLATFORM → CAL.COM
    ↓
sync_status = 'synced' (✓)
```

**Direction**: PLATFORM → CAL.COM (one-way, safe)

---

### 2. Cal.com Event Type Changes ⚠️ DANGER

```
Someone edits Event Type in Cal.com UI
    ↓
Cal.com sends EVENT_TYPE.UPDATED webhook
    ↓
ImportEventTypeJob runs
    ↓
OVERWRITES your Platform data with Cal.com data
    ↓
sync_status = 'synced' but YOUR DATA IS GONE!
```

**Direction**: CAL.COM → PLATFORM (overwrites!)

**Issue**: If someone changes Event Type in Cal.com, your platform loses that data.

---

## Fields Synced

### Platform → Cal.com (Safe ✅)
- name
- duration_minutes  
- price
- is_active (inverted to "hidden")
- buffer_time_minutes
- requires_confirmation
- disable_guests

### Cal.com → Platform (Overwrites ⚠️)
- name (LOSS OF YOUR DATA)
- duration_minutes (LOSS OF YOUR DATA)
- price (LOSS OF YOUR DATA)
- is_active (LOSS OF YOUR DATA)
- All other Event Type fields

---

## Sync Status Values

| Status | Meaning | Action Required |
|--------|---------|---|
| **synced** ✓ | Data is synchronized with Cal.com | None - ready to book |
| **pending** ⏳ | Changed locally, waiting to sync | Wait for sync job to complete |
| **error** ❌ | Sync failed - check logs | Fix error and retry manually |
| **never** ⚪ | Service never synced to Cal.com | Link to Cal.com Event Type & sync |

---

## How Sync Gets Triggered

| Trigger | What Happens | Direction |
|---------|---|---|
| Edit service in Platform | Auto sync job queued | Platform → Cal.com |
| Edit Event Type in Cal.com | Auto import job runs | Cal.com → Platform ⚠️ |
| Delete Event Type in Cal.com | Service deactivated | Cal.com → Platform ⚠️ |
| Run `php artisan calcom:sync-services` | Pulls all Event Types from Cal.com | Cal.com → Platform ⚠️ |

---

## Protected Fields (Never Sync)

These fields stay under Platform control:

- company_id (multi-tenant isolation)
- branch_id (organization structure)
- assignment_method (Platform-specific)
- composite (Platform feature)
- segments (Platform feature)

---

## Last Sync Timestamp

**Field**: `last_calcom_sync`

Shows when sync last happened (success or failure).

Displayed in Status tooltip:
```
✓ Cal.com Sync (synced 2 hours ago)
```

---

## Critical Issues

### Issue 1: Silent Data Overwrites ⚠️⚠️⚠️

Your data can be silently overwritten by Cal.com webhooks.

**Example**:
```
Platform: name = "Premium Cut" ($45)
         ↓
Someone edits in Cal.com: name = "Standard Cut" ($60)
         ↓
WEBHOOK EVENT_TYPE.UPDATED
         ↓
Your Platform: name = "Standard Cut" ($60) ← YOUR DATA LOST!
```

---

### Issue 2: Confusing Status Column

Three independent icons show different things:
- Icon 1: Cal.com sync status
- Icon 2: Service active status
- Icon 3: Service online status

Users don't understand what syncing means or direction.

---

### Issue 3: No Conflict Detection

If Platform and Cal.com differ:
- System silently overwrites with Cal.com data
- No warning to user
- No audit trail

---

## What You Should Do

**RULE #1**: Edit services in YOUR PLATFORM, not Cal.com

**RULE #2**: Don't edit Event Types directly in Cal.com UI

**RULE #3**: If prices/duration/names change, change them in Platform

**RULE #4**: Platform → Cal.com is the safe direction

---

## Workflow

```
1. Service created with Cal.com Event Type ID
   sync_status: initially 'synced'

2. You edit service details in Platform
   sync_status: 'pending' (sync queued)
   
3. UpdateCalcomEventTypeJob runs
   Syncs changes to Cal.com
   sync_status: 'synced' (on success)

4. Service is now bookable if is_active = true
   Can accept bookings from Retell AI

5. AVOID: Editing Event Type directly in Cal.com
   Would trigger overwrite of Platform data
```

---

## Manual Sync Commands

```bash
# Check what would sync without doing it
php artisan calcom:sync-services --check-only

# Force resync everything
php artisan calcom:sync-services --force

# Regular sync (gets missed webhooks)
php artisan calcom:sync-services
```

**Note**: All commands are Cal.com → Platform (overwrites!)

---

## Files to Know

| File | What It Does |
|------|---|
| `app/Observers/ServiceObserver.php` | Detects when service changes |
| `app/Jobs/UpdateCalcomEventTypeJob.php` | Syncs Platform → Cal.com ✅ |
| `app/Jobs/ImportEventTypeJob.php` | Syncs Cal.com → Platform ⚠️ |
| `app/Http/Controllers/CalcomWebhookController.php` | Handles webhooks from Cal.com |
| `database/migrations/*add_calcom_sync_fields*` | Sync fields schema |

---

## Key Takeaway

**Your Platform is the Source of Truth**

→ You should control the sync direction (Platform → Cal.com)
→ Avoid Cal.com → Platform overwrites
→ Don't edit Event Types in Cal.com UI
→ Make all changes in your Platform first
→ Let Platform push changes to Cal.com

