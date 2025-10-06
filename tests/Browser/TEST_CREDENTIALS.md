# 🔐 Test User Credentials

**Erstellt:** 2025-10-06
**Zweck:** Puppeteer Security Tests für Widget-Zugriffskontrolle

---

## Test-User Zugangsdaten

### 1️⃣ SuperAdmin Test User
```
Email:    superadmin-test@askproai.de
Password: Test2024!
Role:     super_admin
```

**Erwartete Sichtbarkeit:**
- ✅ CallStatsOverview Widget sichtbar
- ✅ ALLE 7 Stats sichtbar inkl. "Profit Marge" und "Kosten Monat"
- ✅ Platform profit und margin Daten angezeigt
- ✅ Aggregierte Daten von ALLEN Companies

---

### 2️⃣ Reseller Test User
```
Email:    reseller-test@askproai.de
Password: Test2024!
Role:     reseller_owner
Company:  Test Reseller GmbH (is_reseller=true)
```

**Erwartete Sichtbarkeit:**
- ✅ CallStatsOverview Widget sichtbar
- ✅ 5 Stats sichtbar: Anrufe Heute, Erfolgsquote, Dauer, Kosten/Anruf, Conversion Rate
- ❌ "Profit Marge" NICHT sichtbar (platform profit)
- ❌ "Kosten Monat" NICHT sichtbar (mit platform profit)
- ✅ Daten nur von eigenen Kunden (parent_company_id filter)

---

### 3️⃣ Customer Test User
```
Email:    customer-test@askproai.de
Password: Test2024!
Role:     company_owner
Company:  Test Kunde GmbH (is_reseller=false)
Parent:   Test Reseller GmbH
```

**Erwartete Sichtbarkeit:**
- ❌ CallStatsOverview Widget KOMPLETT versteckt
- ❌ Keine financial widgets sichtbar
- ❌ Kein Zugriff auf platform profit/margin Daten

---

## Tests ausführen

### Quick Test (nur SuperAdmin)
```bash
node tests/Browser/quick-security-test.cjs
```

### Vollständiger Widget Security Test (alle 3 Rollen)
```bash
node tests/Browser/widget-security-test.cjs
```

**Mit expliziten Credentials (optional):**
```bash
export SUPERADMIN_TEST_EMAIL="superadmin-test@askproai.de"
export SUPERADMIN_TEST_PASSWORD="Test2024!"
export RESELLER_TEST_EMAIL="reseller-test@askproai.de"
export RESELLER_TEST_PASSWORD="Test2024!"
export CUSTOMER_TEST_EMAIL="customer-test@askproai.de"
export CUSTOMER_TEST_PASSWORD="Test2024!"

node tests/Browser/widget-security-test.cjs
```

**Hinweis:** Die Credentials sind bereits im Test-Script hardcoded (widget-security-test.cjs), daher ist das Setzen der Env-Variablen optional.

---

## Erwartete Test-Ausgabe

```
🔐 Widget Security Test Suite
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Testing CallStatsOverview widget visibility across roles
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔍 Testing Role: SUPERADMIN
   SuperAdmin should see ALL widgets including platform profit
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Login successful
✅ Widget IS visible (as expected)
✅ "Profit Marge" found
✅ "Kosten Monat" found

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔍 Testing Role: RESELLER
   Reseller should see basic widgets but NOT platform profit stats
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Login successful
✅ Widget IS visible (as expected)
✅ "Profit Marge" NOT found (correctly hidden)
✅ Security Issues: ✅ None

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
🔍 Testing Role: CUSTOMER
   Customer should NOT see CallStatsOverview widget at all
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ Login successful
✅ Widget NOT visible (as expected for customers)
✅ Security Issues: ✅ None

═══════════════════════════════════════════════════════════
📊 FINAL TEST SUMMARY
═══════════════════════════════════════════════════════════
✅ PASSED: SUPERADMIN
✅ PASSED: RESELLER
✅ PASSED: CUSTOMER
────────────────────────────────────────────────────────────
Total Tests: 3
Passed: 3
Failed: 0
Security Issues: ✅ 0
═══════════════════════════════════════════════════════════

✅ All security tests PASSED!
   Widget role-based visibility is working correctly.
```

---

## Screenshots

Nach jedem Test werden Screenshots erstellt:
```
tests/Browser/screenshots/
├── widget-test-superadmin-[timestamp].png
├── widget-test-reseller-[timestamp].png
└── widget-test-customer-[timestamp].png
```

Diese können manuell überprüft werden, um die korrekte Darstellung zu validieren.

---

## Test-User löschen (nach Tests)

```bash
php artisan tinker --execute="
\App\Models\User::where('email', 'superadmin-test@askproai.de')->delete();
\App\Models\User::where('email', 'reseller-test@askproai.de')->delete();
\App\Models\User::where('email', 'customer-test@askproai.de')->delete();
\App\Models\Company::where('name', 'Test Reseller GmbH')->delete();
\App\Models\Company::where('name', 'Test Kunde GmbH')->delete();
echo 'Test users deleted\n';
"
```

---

**Wichtig:** Diese Test-User sollten NICHT in Production verwendet werden. Nach Abschluss der Tests sollten sie gelöscht werden.
