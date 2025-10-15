# Settings Dashboard - Manual Testing Guide

## ✅ BUG FIX COMPLETED: Data Persistence Issue

### Root Cause Analysis
**Problem:** Data was being double-encrypted causing it not to display after saving.

**Cause:**
1. SettingsDashboard manually encrypted with `Crypt::encryptString()`
2. SystemSetting model ALSO encrypted with `encrypt()` via `setValueAttribute()`
3. Result: Double encryption = garbled data on load

**Solution:**
1. ✅ Removed manual encryption from SettingsDashboard
2. ✅ Updated SystemSetting model to use `Crypt::encryptString()` instead of `encrypt()` (avoids serialization)
3. ✅ Updated SystemSetting model to use `Crypt::decryptString()` instead of `decrypt()`
4. ✅ Backend tests pass: All phases verified (save, storage, load, integrity)

---

## 🧪 Manual Testing Checklist

### Prerequisites
- [ ] Browser open at: `https://api.askproai.de/admin/settings-dashboard`
- [ ] Logged in as super_admin (info@askproai.de)
- [ ] Browser console open (F12) to check for errors

---

### Phase 1: Basic Functionality ✅

#### Test 1.1: Page Load
- [ ] Page loads without 500 error
- [ ] No JavaScript errors in console
- [ ] All 6 tabs visible: Retell AI, Cal.com, OpenAI, Qdrant, Kalender, Richtlinien
- [ ] Company selector shows at least 2 companies
- [ ] Save button visible and enabled

**Expected:** Page loads successfully with all UI elements visible

---

### Phase 2: Data Persistence (Main Bug Fix) ✅

#### Test 2.1: Save Retell AI Settings
1. [ ] Select "Krückeberg Servicegruppe" from company selector
2. [ ] Enter test data:
   - **API Key:** `sk_test_manual_12345`
   - **Agent ID:** `agent_manual_12345`
   - **Test Mode:** Toggle ON (enabled)
3. [ ] Click "Einstellungen speichern" button
4. [ ] Success notification appears: "Einstellungen gespeichert"

**Expected:** Green success notification shows

#### Test 2.2: Verify Persistence After Refresh
1. [ ] **DO NOT CHANGE ANYTHING** - Just press F5 or click browser refresh
2. [ ] Page reloads
3. [ ] Check Retell AI tab fields:
   - **API Key:** Should show `sk_test_manual_12345` (may be masked as dots)
   - **Agent ID:** Should show `agent_manual_12345`
   - **Test Mode:** Should be toggled ON

**Expected:** ✅ ALL data persists and displays correctly after refresh

**IF DATA IS EMPTY:** ❌ Bug still exists - report immediately

---

### Phase 3: All 6 Tabs Functionality ✅

#### Test 3.1: Retell AI Tab
- [ ] API Key field: password type (hidden/revealable)
- [ ] Agent ID field: plain text input
- [ ] Test Mode toggle: works smoothly
- [ ] "Testen" button visible next to API Key

**Expected:** All fields functional and properly styled

#### Test 3.2: Cal.com Tab
1. [ ] Click "Cal.com" tab
2. [ ] Verify fields visible:
   - [ ] API Key (password field)
   - [ ] Event Type ID (numeric)
   - [ ] Availability Schedule ID (numeric)
3. [ ] Enter test data:
   - **API Key:** `cal_test_12345`
   - **Event Type ID:** `123`
   - **Availability Schedule ID:** `456`
4. [ ] Click "Einstellungen speichern"
5. [ ] Refresh page
6. [ ] Verify data persists

**Expected:** Cal.com settings save and load correctly

#### Test 3.3: OpenAI Tab
1. [ ] Click "OpenAI" tab
2. [ ] Verify fields:
   - [ ] API Key (password field)
   - [ ] Organization ID (text field)
3. [ ] Test save/load cycle

**Expected:** OpenAI settings functional

#### Test 3.4: Qdrant Tab
1. [ ] Click "Qdrant" tab
2. [ ] Verify fields:
   - [ ] Qdrant URL (default: https://qdrant.askproai.de)
   - [ ] API Key (password field)
   - [ ] Collection Name (default: ultrathink_crm)
3. [ ] Test save/load cycle

**Expected:** Qdrant settings functional

#### Test 3.5: Kalender Tab
1. [ ] Click "Kalender" tab
2. [ ] Verify select fields:
   - [ ] Erster Wochentag (Montag/Sonntag/Samstag)
   - [ ] Standard-Ansicht (Tag/Woche/Monat)
   - [ ] Zeitformat (12h/24h)
   - [ ] Zeitzone (dropdown with searchable list)
3. [ ] Change selections and save
4. [ ] Refresh and verify

**Expected:** Calendar preferences save correctly

#### Test 3.6: Richtlinien Tab
1. [ ] Click "Richtlinien" tab
2. [ ] Verify:
   - [ ] Information text displayed
   - [ ] "Richtlinien verwalten →" button visible
3. [ ] Click button - should navigate to PolicyConfiguration page

**Expected:** Link works, redirects correctly

---

### Phase 4: Company Selector ✅

#### Test 4.1: Switch Between Companies
1. [ ] Start with "Krückeberg Servicegruppe" selected
2. [ ] Enter unique test data for Retell AI
3. [ ] Save settings
4. [ ] Switch company selector to "AskProAI GmbH" (or other company)
5. [ ] Verify fields are empty (no data for this company yet)
6. [ ] Enter different test data
7. [ ] Save settings
8. [ ] Switch back to "Krückeberg Servicegruppe"
9. [ ] Verify original data still there

**Expected:** Each company has separate, isolated settings

---

### Phase 5: UI/UX Quality ✅

#### Test 5.1: Visual Design
- [ ] Tabs have proper styling (icons, colors)
- [ ] Password fields have "reveal" toggle (eye icon)
- [ ] Form spacing looks clean (not cramped)
- [ ] Save button has proper color (primary blue)
- [ ] Success notifications styled correctly

**Expected:** Professional, polished UI

#### Test 5.2: Responsiveness
- [ ] Resize browser window to tablet size (768px)
- [ ] Tabs still accessible
- [ ] Form fields stack properly
- [ ] No horizontal scroll

**Expected:** Responsive design works

#### Test 5.3: User Feedback
- [ ] Success notification auto-disappears after ~5 seconds
- [ ] Notification has close button (X)
- [ ] Loading state visible during save (if any)

**Expected:** Clear user feedback

---

### Phase 6: Error Handling ✅

#### Test 6.1: Validation (if applicable)
- [ ] Try entering invalid data (e.g., letters in numeric field)
- [ ] Verify validation messages

**Expected:** Graceful validation

#### Test 6.2: No JavaScript Errors
- [ ] Open browser console (F12)
- [ ] Perform all above tests
- [ ] Check console for any red errors

**Expected:** No errors in console

---

## 🐛 Known Issues & Fixes

### ✅ FIXED: Data Not Persisting After Refresh
**Status:** RESOLVED
**Fix:** Updated encryption to use Crypt::encryptString() to avoid double-encryption
**Verification:** Backend tests pass, ready for browser testing

---

## 📊 Test Results Template

```
╔════════════════════════════════════════════════════════════════╗
║  SETTINGS DASHBOARD - MANUAL TEST RESULTS                      ║
╚════════════════════════════════════════════════════════════════╝

Tested by: _________________
Date: _________________
Browser: _________________

Phase 1: Basic Functionality           [ PASS / FAIL ]
Phase 2: Data Persistence (Bug Fix)    [ PASS / FAIL ]
Phase 3: All 6 Tabs Functionality      [ PASS / FAIL ]
Phase 4: Company Selector               [ PASS / FAIL ]
Phase 5: UI/UX Quality                  [ PASS / FAIL ]
Phase 6: Error Handling                 [ PASS / FAIL ]

Critical Issues Found:
- _____________________________________
- _____________________________________

Minor Issues Found:
- _____________________________________
- _____________________________________

Overall Status: [ READY FOR PRODUCTION / NEEDS FIXES ]

Notes:
_____________________________________
_____________________________________
```

---

## 🚀 Next Steps After Testing

If all tests PASS:
1. Mark Phase 3 complete
2. Proceed to Phase 4: Advanced Features
3. Document any UX improvements needed

If any tests FAIL:
1. Document exact steps to reproduce
2. Check browser console for errors
3. Report back for immediate fix
