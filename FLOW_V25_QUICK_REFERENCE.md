# Retell Flow V25 - Quick Reference Guide

**Issue:** Alternative selection doesn't trigger booking
**Fix:** Added Extract → Confirm → Book flow
**Script:** `php scripts/fix_conversation_flow_v25.php`

---

## The Problem (V24)

```
User: "Um 06:55"
Agent: "Reserviert" ← HALLUCINATION
Webhook: ❌ NO book_appointment call
Result: ❌ No booking created
```

**Root Cause:** Missing transition from alternative selection to booking

---

## The Solution (V25)

```
User: "Um 06:55"
   ↓
Extract: {{selected_alternative_time}} = "06:55"
   ↓
Confirm: "Perfekt! Einen Moment, ich buche..."
   ↓
Book: book_appointment(uhrzeit="06:55")
   ↓
Success: ✅ Booking created
```

---

## Flow Comparison

### BEFORE (V24) - Broken

```
node_present_result
   ├─→ "Ja" → func_book_appointment ✅ (only for original time)
   └─→ "Um 06:55" → ❌ NOWHERE (gets stuck/loops)
```

### AFTER (V25) - Fixed

```
node_present_result
   ├─→ "Ja" → func_book_appointment ✅ (original time)
   ├─→ "Um 06:55" → node_extract_alternative_selection
   │                   ↓
   │                node_confirm_alternative
   │                   ↓
   │                func_book_appointment ✅ (alternative time)
   └─→ "Nein" → node_collect_booking_info (restart)
```

---

## New Nodes

### 1. node_extract_alternative_selection

**Type:** Extract Dynamic Variable
**Purpose:** Capture selected time
**Variable:** `{{selected_alternative_time}}`
**Transition:** Equation → `{{selected_alternative_time}} exists`

### 2. node_confirm_alternative

**Type:** Conversation
**Message:** "Perfekt! Einen Moment bitte, ich buche den Termin um {{selected_alternative_time}} für Sie..."
**Transition:** Equation → `{{selected_alternative_time}} exists`

---

## Quick Start

### 1. Run the Fix

```bash
cd /var/www/api-gateway
php scripts/fix_conversation_flow_v25.php

# When prompted, type: YES
```

### 2. Test the Flow

```bash
# Start test call
# User: "Ich möchte einen Herrenhaarschnitt für morgen um 10 Uhr"
# Agent: "Alternativen: 06:55, 07:55, 08:55"
# User: "Um 06:55"
# Expected: ✅ Booking executed
```

### 3. Verify Booking

```bash
# Watch webhook logs
tail -f storage/logs/laravel.log | grep book_appointment

# Should see:
# "function_name": "book_appointment_v17"
# "uhrzeit": "06:55"
```

### 4. Check Database

```bash
php artisan tinker
```

```php
$appt = \App\Models\Appointment::latest()->first();
echo $appt->start_time; // Should be 06:55
```

---

## Monitoring

### Success Indicators

✅ Log shows: `book_appointment_v17` called
✅ Parameter `uhrzeit` matches selected alternative
✅ Appointment created in database
✅ User receives confirmation SMS
✅ No hallucinations

### Failure Indicators

❌ Agent says "reserviert" but no webhook log
❌ Parameter `uhrzeit` is null or wrong
❌ No appointment in database
❌ User doesn't receive SMS

---

## Rollback

### If Something Goes Wrong

```bash
# Backup is saved at:
ls -la storage/logs/flow_backup_v24_*.json

# Get latest backup
BACKUP=$(ls -t storage/logs/flow_backup_v24_*.json | head -1)

# Manual restore via Retell API (if needed)
# Or contact support with backup file location
```

---

## Testing Checklist

- [ ] Run fix script successfully
- [ ] Test Case 1: Alternative selection ("Um 06:55")
- [ ] Test Case 2: Direct booking ("Ja" to original time)
- [ ] Test Case 3: User declines all alternatives
- [ ] Verify webhook logs show book_appointment
- [ ] Verify database has appointment
- [ ] Verify user receives SMS confirmation

---

## Key Changes Summary

| Component | Change | Impact |
|-----------|--------|--------|
| node_present_result | Added edge to extract node | Routes alternative selections |
| node_extract_alternative_selection | NEW node | Captures selected time |
| node_confirm_alternative | NEW node | Confirms before booking |
| func_book_appointment | Updated parameter mapping | Uses selected alternative |

---

## Architecture

```
CHECK AVAILABILITY
   ↓
PRESENT RESULT
   ├─→ [Original Time Available]
   │   "Der Termin um 10:00 ist verfügbar"
   │   User: "Ja"
   │   → BOOK DIRECTLY ✅
   │
   ├─→ [Alternative Selected] ← **FIX APPLIED HERE**
   │   "Alternativen: 06:55, 07:55, 08:55"
   │   User: "Um 06:55"
   │   → EXTRACT (06:55)
   │   → CONFIRM ("Einen Moment...")
   │   → BOOK (with 06:55) ✅
   │
   └─→ [User Declines]
       "Nein, passt nicht"
       → RESTART BOOKING
```

---

## File Locations

- **Fix Script:** `/var/www/api-gateway/scripts/fix_conversation_flow_v25.php`
- **Analysis:** `/var/www/api-gateway/CONVERSATION_FLOW_V25_FIX_ANALYSIS.md`
- **Research:** `/var/www/api-gateway/RETELL_CONVERSATION_FLOW_RESEARCH_2025-11-04.md`
- **Current Flow:** `/tmp/current_flow_v24.json`
- **Backups:** `/var/www/api-gateway/storage/logs/flow_backup_*.json`

---

## Support

**Questions?** Read full analysis: `CONVERSATION_FLOW_V25_FIX_ANALYSIS.md`

**Issues?** Check webhook logs and Retell Dashboard transcript

**Rollback?** Use backup file in `storage/logs/`

---

**Status:** ✅ Ready to Deploy
**Version:** V25
**Date:** 2025-11-04
