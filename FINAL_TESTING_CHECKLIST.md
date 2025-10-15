# FINAL TESTING CHECKLIST - 2025-10-11
**Estimated Time**: 15-20 minutes

---

## 🧪 TEST 1: Appointment #632 (Pop-up Error + Compliance)

**URL**: `https://api.askproai.de/admin/appointments/632`

### Prüfungen:
- [ ] ✅ **Kein Pop-up Error mehr** (Hauptproblem!)
- [ ] ✅ Alle Sections standardmäßig aufgeklappt
- [ ] ✅ "Buchungsdetails" Section (nicht "Cal.com Integration")
- [ ] ✅ "Online-Buchungs-ID" Label (nicht "Cal.com Booking ID")
- [ ] ✅ KEIN "Cal.com" oder "Retell" sichtbar
- [ ] ✅ Text gut lesbar (WCAG Kontrast)

---

## 🧪 TEST 2: Appointment #675 (Timeline + History)

**URL**: `https://api.askproai.de/admin/appointments/675`

### Prüfungen:
- [ ] ✅ Timeline Widget am Ende der Seite
- [ ] ✅ 3 Events sichtbar (create, reschedule, cancel)
- [ ] ✅ "KI-Telefonsystem" (nicht "Retell AI")
- [ ] ✅ "Online-Buchung" (nicht "Cal.com")
- [ ] ✅ Call #834 Link klickbar
- [ ] ✅ Historische Daten: 15:00 → 15:30 sichtbar
- [ ] ✅ Alle Sections aufgeklappt

---

## 🧪 TEST 3: CustomerNoteResource (German Translation)

**URL**: `https://api.askproai.de/admin/customer-notes`

### Prüfungen:
- [ ] ✅ Navigation: "Kundennotizen"
- [ ] ✅ Alle Labels deutsch
- [ ] ✅ Type Options: "Anrufnotiz", "Vertrieb", etc.
- [ ] ✅ KEINE englischen Texte
- [ ] ✅ Date Format: d.m.Y H:i (deutsch)

---

## 🧪 TEST 4: Role-Based Visibility (WICHTIG!)

**Accounts benötigt**:
- Endkunde (viewer)
- Mitarbeiter (operator/manager)
- Admin

### Test mit Endkunde-Account:
URL: `https://api.askproai.de/admin/appointments/675`
- [ ] ❌ "Technische Details" Section NICHT sichtbar
- [ ] ❌ "Zeitstempel" Section NICHT sichtbar
- [ ] ❌ "Buchungsdetails" Section NICHT sichtbar

### Test mit Mitarbeiter-Account:
URL: gleiche
- [ ] ✅ "Technische Details" Section SICHTBAR
- [ ] ❌ "Zeitstempel" Section NICHT sichtbar
- [ ] ✅ "Buchungsdetails" Section SICHTBAR

### Test mit Admin-Account:
URL: gleiche
- [ ] ✅ "Technische Details" Section SICHTBAR
- [ ] ✅ "Zeitstempel" Section SICHTBAR
- [ ] ✅ "Buchungsdetails" Section SICHTBAR

---

## 🧪 TEST 5: WCAG Kontrast Visual Check

**Method**: Browser DevTools + Visual Inspection

### Light Mode:
1. Öffne: `/admin/appointments/675`
2. Prüfe: Alle Texte gut lesbar (keine blassen Grautöne)
3. Inspect Element: Keine text-gray-400 oder text-gray-500

### Dark Mode:
1. Toggle Dark Mode (im User-Menu)
2. Prüfe: Texte gut lesbar auf dunklem Hintergrund
3. Inspect Element: Keine dark:text-gray-400 oder dark:text-gray-500

**Tools (optional)**:
- WebAIM Contrast Checker
- Chrome Lighthouse Audit
- axe DevTools Extension

---

## ✅ ACCEPTANCE CRITERIA

**Alle Tests müssen bestehen**:

- [ ] Test 1: No errors ✅
- [ ] Test 2: Timeline funktioniert ✅
- [ ] Test 3: Alles deutsch ✅
- [ ] Test 4: Rollen-Gates funktionieren ✅
- [ ] Test 5: WCAG konform ✅

**If all pass**: ✅ **APPROVED FOR PRODUCTION**

---

## 📊 QUICK VALIDATION COMMANDS

```bash
# Check for remaining vendor names
grep -r "Cal\.com\|Retell" app/Filament/Resources/ --include="*.php" | grep -v ".backup"

# Check for English labels
grep -r "label.*Customer\|label.*Type\|label.*Category" app/Filament/Resources/CustomerNoteResource.php

# Check for gray-400/500
grep -r "text-gray-400\|text-gray-500" resources/views/filament/resources/appointment-resource/

# Validate syntax
find app/Filament/Resources -name "*.php" -exec php -l {} \; | grep -c "No syntax errors"
```

---

## 🚨 IF ISSUES FOUND

**Report Format**:
```
Test: [Test Number]
URL: [URL tested]
Issue: [Description]
Screenshot: [Attach if possible]
Browser: [Chrome/Firefox/Safari + Version]
```

**Quick Fixes Available**:
- Vendor name still visible → Check specific file
- Pop-up error → Check error console (F12)
- Section not visible → Check user role
- Text unreadable → Check contrast with DevTools

---

**Testing Date**: 2025-10-11
**Tester**: _________________
**Duration**: _____ minutes
**Result**: PASS / FAIL
**Approved for Production**: YES / NO
**Signature**: _________________
