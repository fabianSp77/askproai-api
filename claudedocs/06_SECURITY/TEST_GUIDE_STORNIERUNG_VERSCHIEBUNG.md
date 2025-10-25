# Test-Guide: Stornierung & Verschiebung

**Datum:** 2025-10-25
**System:** AskPro AI Gateway - Policy System
**Ziel:** Verifizierung der Stornierung/Verschiebung Funktionalität

---

## 🎯 TESTZIELE

1. ✅ Anonyme Anrufer können NUR Termine buchen (nicht stornieren/verschieben)
2. ✅ Bestandskunden können stornieren/verschieben (nur eigene Termine)
3. ✅ Policy-Regeln werden korrekt angewendet
4. ✅ Filial-Zuordnung funktioniert
5. ✅ Multi-Tenant Isolation ist aktiv

---

## 📋 TEST-VORBEREITUNG

### Voraussetzungen

```bash
# 1. System-Status prüfen
cd /var/www/api-gateway

# 2. Policies in DB prüfen
php artisan tinker --execute="echo 'Policies: ' . \App\Models\PolicyConfiguration::count();"

# 3. Log-Tail öffnen (in separatem Terminal)
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log
```

### Test-Telefonnummern

**Friseur1 Filialen:**
```
+493033081738  → Berlin Mitte
+493088888888  → Weitere Filiale (falls vorhanden)
```

**Test-Anrufer:**
```
ANONYM         → Unterdrückte Nummer (für anonyme Tests)
+49123456789   → Bekannte Nummer (für Bestandskunden-Tests)
```

---

## 🧪 TEST-SZENARIEN

### Test 1: Anonymer Anrufer - Termin buchen (SOLLTE FUNKTIONIEREN)

**Ziel:** Verifizieren, dass anonyme Anrufer Termine buchen können

#### Vorbereitung

```bash
# Log-Filter setzen (Terminal 1)
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "anonymous\|book_appointment"
```

#### Test-Durchführung

```
1. Anruf von UNTERDRÜCKTER Nummer
   ☎️ +493033081738

2. Agent-Begrüßung abwarten

3. Termin anfragen:
   🗣️ "Ich möchte einen Herrenhaarschnitt für morgen um 15 Uhr buchen"

4. Daten angeben:
   🗣️ Name: "Max Mustermann"
   🗣️ Email: "max@example.com" (optional)

5. Bestätigung abwarten
```

#### ✅ Erwartetes Ergebnis

**Voice Agent:**
```
✅ "Termin verfügbar"
✅ "Ich buche den Termin für Sie"
✅ "Termin wurde erfolgreich gebucht"
```

**Logs:**
```bash
✅ "Anonymous caller detected - creating NEW customer"
✅ "Phone: anonymous_<timestamp>_<hash>"
✅ "Appointment created successfully"
```

**Datenbank:**
```bash
# Prüfen, ob anonymer Kunde erstellt wurde
php artisan tinker --execute="
  \$customer = \App\Models\Customer::where('phone', 'LIKE', 'anonymous_%')
    ->latest()->first();
  echo 'Latest Anonymous: ' . \$customer->name . ' (' . \$customer->phone . ')';
"

✅ Erwartung: "Latest Anonymous: Max Mustermann (anonymous_<timestamp>_<hash>)"
```

---

### Test 2: Anonymer Anrufer - Termin stornieren (SOLLTE ABLEHNEN)

**Ziel:** Verifizieren, dass anonyme Anrufer NICHT stornieren können

#### Vorbereitung

```bash
# 1. Zuerst Termin buchen (Test 1 wiederholen)
# 2. Log-Filter setzen
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "anonymous.*cancel\|callback"
```

#### Test-Durchführung

```
1. Anruf von UNTERDRÜCKTER Nummer (gleiche wie Test 1)
   ☎️ +493033081738

2. Stornierung anfragen:
   🗣️ "Ich möchte meinen Termin für morgen 15 Uhr stornieren"
```

#### ✅ Erwartetes Ergebnis

**Voice Agent:**
```
✅ "Aus Sicherheitsgründen kann ich Termine ohne Telefonnummer-Übertragung nicht stornieren"
✅ "Ich notiere Ihren Rückrufwunsch"
✅ "Unser Team wird Sie zurückrufen"
```

**Logs:**
```bash
✅ "Anonymous caller tried to cancel - redirecting to callback request"
✅ "CallbackRequest created for anonymous caller"
❌ NICHT: "Appointment cancelled successfully"
```

**Datenbank:**
```bash
# Prüfen, dass Termin NICHT storniert wurde
php artisan tinker --execute="
  \$appt = \App\Models\Appointment::latest()->first();
  echo 'Status: ' . \$appt->status;
"

✅ Erwartung: "Status: scheduled" (NICHT cancelled)
```

---

### Test 3: Anonymer Anrufer - Termin verschieben (SOLLTE ABLEHNEN)

**Ziel:** Verifizieren, dass anonyme Anrufer NICHT verschieben können

#### Vorbereitung

```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "anonymous.*reschedule\|callback"
```

#### Test-Durchführung

```
1. Anruf von UNTERDRÜCKTER Nummer
   ☎️ +493033081738

2. Verschiebung anfragen:
   🗣️ "Ich möchte meinen Termin von morgen 15 Uhr auf 16 Uhr verschieben"
```

#### ✅ Erwartetes Ergebnis

**Voice Agent:**
```
✅ "Aus Sicherheitsgründen kann ich Termine ohne Telefonnummer-Übertragung nicht verschieben"
✅ "Ich notiere Ihren Rückrufwunsch"
```

**Logs:**
```bash
✅ "Anonymous caller tried to reschedule - redirecting to callback request"
✅ "CallbackRequest created"
❌ NICHT: "Appointment rescheduled successfully"
```

---

### Test 4: Bestandskunde - Termin stornieren (SOLLTE FUNKTIONIEREN)

**Ziel:** Verifizieren, dass Bestandskunden stornieren können (eigene Termine)

#### Vorbereitung

```bash
# 1. Bestandskunden-Nummer in DB prüfen
php artisan tinker --execute="
  \$customer = \App\Models\Customer::whereNotNull('phone')
    ->where('phone', 'NOT LIKE', 'anonymous_%')
    ->first();
  echo 'Customer: ' . \$customer->name . ' (' . \$customer->phone . ')';
"

# 2. Termin für diesen Kunden buchen (über Admin oder Voice Agent)

# 3. Log-Filter
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "cancel_appointment\|policy"
```

#### Test-Durchführung

```
1. Anruf von BEKANNTER Nummer (aus DB)
   ☎️ [Nummer des Bestandskunden]

2. Stornierung anfragen:
   🗣️ "Ich möchte meinen Termin für [Datum] [Uhrzeit] stornieren"
```

#### ✅ Erwartetes Ergebnis

**Voice Agent:**
```
✅ "Ich prüfe Ihren Termin..."
✅ "Termin gefunden: [Service] am [Datum] um [Uhrzeit]"
✅ "Möchten Sie diesen Termin wirklich stornieren?"
  🗣️ "Ja"
✅ "Termin wurde erfolgreich storniert"
```

**Logs:**
```bash
✅ "Regular caller detected: phone=[Nummer]"
✅ "Customer found: [Name]"
✅ "Policy check: can_cancel=true" (falls Policy konfiguriert)
✅ "Appointment cancelled successfully"
```

**Datenbank:**
```bash
# Prüfen, dass Termin storniert wurde
php artisan tinker --execute="
  \$appt = \App\Models\Appointment::latest()->first();
  echo 'Status: ' . \$appt->status . ' | Cancelled: ' . \$appt->cancelled_at;
"

✅ Erwartung: "Status: cancelled | Cancelled: 2025-10-25 ..."
```

---

### Test 5: Bestandskunde - Fremden Termin stornieren (SOLLTE ABLEHNEN)

**Ziel:** Verifizieren, dass Kunden NICHT fremde Termine stornieren können

#### Vorbereitung

```bash
# 1. Zwei verschiedene Kunden in DB identifizieren
php artisan tinker --execute="
  \$customers = \App\Models\Customer::whereNotNull('phone')
    ->where('phone', 'NOT LIKE', 'anonymous_%')
    ->limit(2)->get();
  foreach (\$customers as \$c) {
    echo \$c->name . ' (' . \$c->phone . ')' . PHP_EOL;
  }
"

# 2. Termin für Kunde A buchen
# 3. Mit Kunde B versuchen zu stornieren
```

#### Test-Durchführung

```
1. Termin wurde für Kunde A gebucht

2. Anruf von Kunde B
   ☎️ [Nummer von Kunde B]

3. Versuche Kunde A's Termin zu stornieren:
   🗣️ "Ich möchte den Termin von [Name von A] für [Datum] stornieren"
```

#### ✅ Erwartetes Ergebnis

**Voice Agent:**
```
✅ "Dieser Termin gehört nicht Ihnen"
   ODER
✅ "Ich konnte keinen Termin für Sie finden"
```

**Logs:**
```bash
✅ "Appointment not found or does not belong to customer"
✅ "Security: Prevented cross-customer cancellation"
```

**Datenbank:**
```bash
# Termin sollte NICHT storniert sein
php artisan tinker --execute="
  \$appt = \App\Models\Appointment::find([ID von Kunde A's Termin]);
  echo 'Status: ' . \$appt->status;
"

✅ Erwartung: "Status: scheduled" (NICHT cancelled)
```

---

### Test 6: Policy-Validierung - Zu kurzfristige Stornierung (SOLLTE ABLEHNEN)

**Ziel:** Verifizieren, dass Policy-Regeln (Mindestvorlauf) eingehalten werden

#### Vorbereitung

```bash
# 1. Policy mit 24h Vorlauf erstellen (Admin-Panel)
#    - Entität: Friseur1
#    - Typ: Stornierung
#    - Vorlauf: 24 Stunden

# 2. Termin für MORGEN (< 24h) buchen

# 3. Log-Filter
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "policy\|hours_notice"
```

#### Test-Durchführung

```
1. Aktuell: 2025-10-25 20:00
2. Termin: 2025-10-26 15:00 (19 Stunden vorher)
3. Policy: 24 Stunden Mindestvorlauf

4. Anruf von Bestandskunde:
   ☎️ [Bekannte Nummer]

5. Stornierung anfragen:
   🗣️ "Ich möchte meinen Termin für morgen 15 Uhr stornieren"
```

#### ✅ Erwartetes Ergebnis

**Voice Agent:**
```
✅ "Leider ist eine Stornierung nicht mehr möglich"
✅ "Stornierung ist nur bis 24 Stunden vor dem Termin möglich"
✅ "Ihr Termin ist in 19 Stunden"
✅ "Bitte rufen Sie uns direkt an unter [Filial-Nummer]"
```

**Logs:**
```bash
✅ "Policy violation: Cancellation requires 24 hours notice"
✅ "Hours remaining: 19, required: 24"
✅ "Cancellation denied by policy"
```

**Datenbank:**
```bash
# Termin sollte NICHT storniert sein
✅ Erwartung: "Status: scheduled"
```

---

### Test 7: Filial-Isolation - Termin in falscher Filiale (SOLLTE ABLEHNEN)

**Ziel:** Verifizieren, dass Kunden nur Termine in ihrer Filiale verwalten können

#### Vorbereitung

```bash
# Annahme: 2 Filialen
# Filiale A: +493033081738 → branch_id=1
# Filiale B: +493088888888 → branch_id=2
```

#### Test-Durchführung

```
1. Kunde in Filiale A:
   - Termin gebucht über +493033081738
   - branch_id = 1

2. Anruf bei Filiale B:
   ☎️ +493088888888 (branch_id = 2)

3. Versuche Termin zu stornieren:
   🗣️ "Ich möchte meinen Termin stornieren"
```

#### ✅ Erwartetes Ergebnis

**Voice Agent:**
```
✅ "Ich konnte keinen Termin für Sie finden"
   (weil Termin in anderer Filiale ist)
```

**Logs:**
```bash
✅ "Call context: branch_id=2"
✅ "Appointment query: WHERE branch_id=2"
✅ "No appointments found for customer in this branch"
```

---

## 📊 TEST-PROTOKOLL

### Test-Ergebnisse Dokumentation

| Test | Datum | Tester | Status | Notizen |
|------|-------|--------|--------|---------|
| Test 1: Anonym buchen | ______ | ______ | ⬜ | _____________ |
| Test 2: Anonym stornieren | ______ | ______ | ⬜ | _____________ |
| Test 3: Anonym verschieben | ______ | ______ | ⬜ | _____________ |
| Test 4: Bestand stornieren | ______ | ______ | ⬜ | _____________ |
| Test 5: Fremd stornieren | ______ | ______ | ⬜ | _____________ |
| Test 6: Policy Vorlauf | ______ | ______ | ⬜ | _____________ |
| Test 7: Filial-Isolation | ______ | ______ | ⬜ | _____________ |

**Status-Codes:**
- ✅ = Passed (wie erwartet)
- ⚠️ = Partial (teilweise funktioniert)
- ❌ = Failed (funktioniert nicht)

---

## 🔍 MONITORING & VERIFIZIERUNG

### Real-Time Log Monitoring

**Terminal 1: Alle relevanten Events**
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -i "anonymous\|cancel\|reschedule\|policy"
```

**Terminal 2: Nur Fehler**
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep -i "error\|exception"
```

**Terminal 3: Nur Policy-Violations**
```bash
tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep "Policy violation"
```

---

### Post-Test Analysen

#### 1. Anzahl anonymer Kunden prüfen

```bash
php artisan tinker --execute="
  echo 'Anonymous customers: ' .
    \App\Models\Customer::where('phone', 'LIKE', 'anonymous_%')->count();
"
```

**Erwartung:** Anzahl sollte nach Test 1 gestiegen sein

---

#### 2. Stornierungen zählen

```bash
php artisan tinker --execute="
  echo 'Cancelled today: ' .
    \App\Models\Appointment::whereNotNull('cancelled_at')
      ->whereDate('cancelled_at', today())->count();
"
```

**Erwartung:** Nur Stornierungen von Bestandskunden (Test 4)

---

#### 3. CallbackRequests prüfen

```bash
php artisan tinker --execute="
  \$callbacks = \App\Models\CallbackRequest::whereDate('created_at', today())->get();
  foreach (\$callbacks as \$cb) {
    echo \$cb->reason . ' - ' . \$cb->created_at . PHP_EOL;
  }
"
```

**Erwartung:** CallbackRequests von anonymen Stornierungsversuchen (Test 2, 3)

---

## ⚠️ TROUBLESHOOTING

### Problem: Test 1 schlägt fehl (Anonym kann nicht buchen)

**Diagnose:**
```bash
# 1. Prüfe, ob Retell AI Agent aktiv ist
curl -s -H "Authorization: Bearer $RETELL_API_KEY" \
  https://api.retellai.com/v2/get-agent/[AGENT_ID]

# 2. Prüfe Logs für Fehler
grep -i "book_appointment\|error" storage/logs/laravel-$(date +%Y-%m-%d).log
```

**Mögliche Ursachen:**
- Retell Agent offline
- Cal.com API nicht erreichbar
- Service nicht verfügbar

---

### Problem: Test 2/3 schlägt fehl (Anonym KANN stornieren)

**KRITISCHER BUG!**

**Diagnose:**
```bash
# 1. Prüfe AnonymousCallDetector
php artisan tinker --execute="
  echo \App\ValueObjects\AnonymousCallDetector::fromNumber(null) ? 'TRUE' : 'FALSE';
"
# Erwartung: TRUE

# 2. Prüfe RetellFunctionCallHandler
grep -n "cancel_appointment.*anonymous" app/Http/Controllers/RetellFunctionCallHandler.php
# Erwartung: Code sollte bei Zeile ~1550 sein
```

**Fix:** Siehe `app/Http/Controllers/RetellFunctionCallHandler.php:1550-1562`

---

### Problem: Test 4 schlägt fehl (Bestandskunde kann NICHT stornieren)

**Diagnose:**
```bash
# 1. Prüfe Kunden-Erkennung
php artisan tinker --execute="
  \$phone = '+49123456789';
  \$customer = \App\Models\Customer::where('phone', \$phone)->first();
  echo \$customer ? 'Found: ' . \$customer->name : 'NOT FOUND';
"

# 2. Prüfe Policy
php artisan tinker --execute="
  \$policy = \App\Models\PolicyConfiguration::where('policy_type', 'cancellation')->first();
  dd(\$policy?->toArray());
"
```

**Mögliche Ursachen:**
- Kunde nicht in DB (falsche Telefonnummer)
- Policy zu streng (Vorlauf zu lang)
- Termin gehört anderem Kunden

---

### Problem: Test 6 schlägt fehl (Policy wird ignoriert)

**Diagnose:**
```bash
# 1. Cache leeren
php artisan config:clear
php artisan cache:clear

# 2. Policy-Engine Test
php artisan tinker --execute="
  use App\Services\Policies\AppointmentPolicyEngine;
  use App\Models\Appointment;

  \$engine = app(AppointmentPolicyEngine::class);
  \$appt = Appointment::latest()->first();
  \$result = \$engine->canCancel(\$appt);

  dd([
    'allowed' => \$result->allowed,
    'reason' => \$result->reason,
    'details' => \$result->details,
  ]);
"
```

---

## ✅ SUCCESS CRITERIA

**Alle Tests bestanden, wenn:**

- [x] Test 1: Anonyme Buchung funktioniert
- [x] Test 2: Anonyme Stornierung wird blockiert
- [x] Test 3: Anonyme Verschiebung wird blockiert
- [x] Test 4: Bestandskunden-Stornierung funktioniert
- [x] Test 5: Fremd-Stornierung wird blockiert
- [x] Test 6: Policy-Regeln werden eingehalten
- [x] Test 7: Filial-Isolation funktioniert

**Zusätzliche Kriterien:**

- [x] Keine Fehler in Logs (außer erwartete Policy violations)
- [x] Anonyme Kunden-Datensätze werden korrekt erstellt
- [x] CallbackRequests werden für anonyme Stornierungen erstellt
- [x] Multi-Tenant Isolation aktiv (kein Cross-Company Zugriff)

---

## 📋 NÄCHSTE SCHRITTE NACH ERFOLGREICHEN TESTS

1. **Monitoring aktivieren (7 Tage):**
   ```bash
   # Daily check
   grep -c "Anonymous caller tried to cancel" \
     storage/logs/laravel-$(date +%Y-%m-%d).log
   ```

2. **Metriken sammeln:**
   - Anzahl anonyme Anrufe pro Tag
   - Anzahl blockierte Stornierungen
   - Anzahl erfolgreiche Stornierungen
   - Policy violations pro Tag

3. **Optional: Zusätzliche Policies konfigurieren:**
   - Service-spezifisch (Dauerwelle: 48h)
   - Filial-spezifisch (Flagship: strenger)
   - Mitarbeiter-spezifisch (Top-Stylisten: länger)

4. **Dokumentation aktualisieren:**
   - Test-Ergebnisse eintragen
   - Bekannte Issues dokumentieren
   - Best Practices festhalten

---

**Erstellt:** 2025-10-25
**Version:** 1.0
**Autor:** Claude Code (Sonnet 4.5)
