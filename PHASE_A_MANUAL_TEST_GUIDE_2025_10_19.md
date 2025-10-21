# Phase A Manual Test Guide - Alternative Finding
**Date**: 2025-10-19
**Phase**: A - Alternative Finding Aktivierung
**Duration**: 15-20 Minuten

---

## 🎯 Test Objectives

Verify that Phase A implementation works correctly:
1. ✅ Feature Flag aktiviert Alternatives
2. ✅ Smart Alternative Selection (max 2 alternatives)
3. ✅ Timeout Handling (3s timeout, graceful fallback)
4. ✅ User-friendly Error Messages

---

## 📋 Pre-Test Setup

### 1. Verify Feature Flag Configuration

```bash
# Check config/features.php
grep -A 5 "skip_alternatives_for_voice" config/features.php

# Expected output:
# 'skip_alternatives_for_voice' => env('FEATURE_SKIP_ALTERNATIVES_FOR_VOICE', false),
```

### 2. Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
```

### 3. Check Environment

```bash
# Ensure FEATURE_SKIP_ALTERNATIVES_FOR_VOICE is NOT set or set to false
grep FEATURE_SKIP_ALTERNATIVES_FOR_VOICE .env || echo "Not set - using default (false = ENABLED)"
```

---

## 🧪 Test Cases

### Test Case 1: Alternatives Enabled (Happy Path)

**Objective**: Verify alternatives are offered when requested time is unavailable

**Steps**:

1. **Make a Retell test call** to your phone number
2. **Request an unavailable time** (e.g., "Ich möchte einen Termin um 20 Uhr" - after business hours)
3. **Expected Behavior**:
   - Agent responds: "Leider ist 20:00 Uhr nicht verfügbar."
   - Agent offers 2 alternatives: "Ich habe folgende Alternativen: [Zeit 1] oder [Zeit 2]"
   - Alternatives are REAL times from Cal.com (not fake)

**Success Criteria**:
- ✅ Agent offers exactly 2 alternatives
- ✅ Alternatives are within business hours (09:00-18:00)
- ✅ Alternatives are logically sorted (closest times first)

**Debug Logs**:

```bash
# Monitor logs during call
tail -f storage/logs/laravel.log | grep -E "RETELL|Alternative|skip_alternatives"

# Expected logs:
# - "skip_alternatives_for_voice = false" (or not logged if default)
# - "Found alternatives: count=2"
# - "Alternatives: [datetime1, datetime2]"
```

---

### Test Case 2: Timeout Handling

**Objective**: Verify graceful fallback when Cal.com is slow/unreachable

**Steps**:

1. **Simulate Cal.com timeout** (optional - requires network manipulation):
   ```bash
   # Block Cal.com temporarily (requires sudo)
   sudo iptables -A OUTPUT -d api.cal.com -j DROP
   ```

2. **Make a test call** and request a time
3. **Expected Behavior**:
   - Cal.com request times out after 3 seconds
   - Agent responds: "Terminbuchungssystem ist nicht erreichbar. Bitte überprüfen Sie Ihre Internetverbindung."
   - Call continues (doesn't crash)

4. **Restore network** (if blocked):
   ```bash
   sudo iptables -D OUTPUT -d api.cal.com -j DROP
   ```

**Success Criteria**:
- ✅ Timeout occurs at ~3 seconds (not 30s or infinity)
- ✅ User-friendly error message (German, clear)
- ✅ Call continues without crash

**Debug Logs**:

```bash
tail -f storage/logs/laravel.log | grep -E "ConnectionException|timeout|network error"

# Expected logs:
# - "Cal.com API network error during getAvailableSlots"
# - "endpoint: /slots/available"
# - "timeout: 3s"
```

---

### Test Case 3: Feature Flag Toggle

**Objective**: Verify feature flag correctly enables/disables alternatives

**Steps**:

1. **Disable alternatives** via environment:
   ```bash
   echo "FEATURE_SKIP_ALTERNATIVES_FOR_VOICE=true" >> .env
   php artisan config:clear
   ```

2. **Make a test call** with unavailable time
3. **Expected Behavior**:
   - Agent says: "Dieser Termin ist leider nicht verfügbar. Welche Zeit würde Ihnen alternativ passen?"
   - NO alternatives offered
   - Agent asks user for alternative time

4. **Re-enable alternatives**:
   ```bash
   sed -i '/FEATURE_SKIP_ALTERNATIVES_FOR_VOICE/d' .env
   php artisan config:clear
   ```

**Success Criteria**:
- ✅ Flag=true: NO alternatives, asks user
- ✅ Flag=false: Offers 2 alternatives

---

### Test Case 4: Alternative Quality

**Objective**: Verify alternatives are smart and relevant

**Steps**:

1. **Request time at 13:00** (mid-day, likely busy)
2. **Check offered alternatives**:
   - Should prefer same day (13:30, 14:00)
   - Should NOT offer next week if same day available
   - Should be sorted by proximity

**Expected Output**:

```
Requested: 13:00
Alternative 1: 13:30 (same day, 30 min later) ← Best
Alternative 2: 14:00 (same day, 1h later)    ← Second best
```

**Success Criteria**:
- ✅ Alternatives are chronologically close to requested time
- ✅ Same-day alternatives preferred over next-day
- ✅ Business hours respected (09:00-18:00)

---

## 📊 Success Metrics

After all tests, verify these metrics:

| Metric | Before Phase A | After Phase A | Target |
|--------|----------------|---------------|--------|
| Alternatives Offered | 0% (disabled) | 100% | 100% |
| Average Response Time | N/A | <3s | <3s |
| Timeout Graceful | ❌ | ✅ | ✅ |
| User-Friendly Errors | ❌ | ✅ | ✅ |
| Max Alternatives | N/A | 2 | 2 |

---

## 🐛 Debugging Common Issues

### Issue 1: No Alternatives Offered

**Symptoms**: Agent says "nicht verfügbar" but doesn't offer alternatives

**Diagnosis**:
```bash
# Check feature flag
php artisan tinker
>>> config('features.skip_alternatives_for_voice')
# Should return: false

# Check logs
tail -f storage/logs/laravel.log | grep skip_alternatives
```

**Fix**:
```bash
# Ensure .env does NOT have FEATURE_SKIP_ALTERNATIVES_FOR_VOICE=true
grep FEATURE_SKIP_ALTERNATIVES_FOR_VOICE .env
# If found, remove or set to false
```

---

### Issue 2: Timeout Takes Too Long

**Symptoms**: Call hangs for 5+ seconds on unavailable times

**Diagnosis**:
```bash
# Check CalcomService timeout
grep -n "timeout(" app/Services/CalcomService.php

# Line 214 should show: ->timeout(3)
# Line 130 should show: ->timeout(5)
```

**Fix**:
```bash
# Verify Phase A.4 edits were applied
git diff app/Services/CalcomService.php
```

---

### Issue 3: Alternatives Are Fake/Invalid

**Symptoms**: Agent offers times that aren't actually available in Cal.com

**Diagnosis**:
```bash
# Check if Cal.com validation is enabled
grep -A 10 "generateFallbackAlternatives" app/Services/AppointmentAlternativeFinder.php

# Should see: Cal.com validation with isTimeSlotAvailable()
```

**Fix**: This was fixed in previous deployments. If still seeing fake alternatives, check AppointmentAlternativeFinder.php lines 588-668.

---

## ✅ Test Completion Checklist

After completing all tests:

- [ ] Test Case 1: Alternatives offered successfully
- [ ] Test Case 2: Timeout handled gracefully (if tested)
- [ ] Test Case 3: Feature flag toggle works
- [ ] Test Case 4: Alternative quality is good
- [ ] All success metrics met
- [ ] No errors in logs (except expected timeout logs)

---

## 📝 Next Steps

Once Phase A testing is complete:

1. **Document Results**: Note any issues in PHASE_A_TEST_RESULTS.md
2. **Move to Phase B**: Confirmation Optimization + V87 Integration
3. **Monitor Production**: If deployed, track booking success rate

---

## 🚨 Rollback Plan

If critical issues found:

```bash
# 1. Disable alternatives immediately
echo "FEATURE_SKIP_ALTERNATIVES_FOR_VOICE=true" >> .env
php artisan config:clear

# 2. Restart services
pm2 restart all

# 3. Verify rollback
curl -s "https://api.askproai.de/api/health"
```

---

**Test Guide Version**: 1.0
**Last Updated**: 2025-10-19
**Estimated Test Duration**: 15-20 minutes
**Prerequisites**: Active Retell agent, Cal.com integration configured
