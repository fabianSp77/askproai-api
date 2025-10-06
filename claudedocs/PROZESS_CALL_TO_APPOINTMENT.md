# 📋 PROZESS: Call → Appointment Synchronisation

**Erstellt:** 2025-10-04
**Version:** 2.0 (mit Immediate Sync Fix)

---

## 🎯 WIE ES FUNKTIONIEREN SOLLTE

### Perfekter Flow (NEU - ab Phase 1 Fix):

```
1. Anruf kommt rein (+493083793369)
   ↓
2. Retell AI Agent beantwortet
   ↓
3. Kunde bucht Termin über Telefon
   ↓
4. RetellFunctionCallHandler::bookAppointment() wird aufgerufen
   ↓
5. Cal.com Booking API Call ✅
   ↓
6. 🔥 NEU: SOFORT lokale Appointment wird erstellt
   ├─ Customer wird resolved/erstellt
   ├─ Appointment mit allen Daten gespeichert
   ├─ call_id wird verknüpft
   └─ Alle Felder ausgefüllt
   ↓
7. ✅ SUCCESS Response zurück an Retell (mit appointment_id)
   ↓
8. Admin Portal zeigt Termin SOFORT (<100ms)
   ├─ Call ist verknüpft
   ├─ Customer ist verknüpft
   └─ Alle Daten vollständig

[Später, als Backup:]
9. Cal.com schickt Webhook (1-5 Sekunden delay)
   ↓
10. Webhook findet bereits existierende Appointment (via calcom_v2_booking_id)
    ↓
11. updateOrCreate() → Keine Duplikate, nur Update wenn nötig
```

---

## ❌ ALTER FLOW (VOR Phase 1 Fix):

```
1-5. [Wie oben]
   ↓
6. ❌ FEHLER: Keine lokale Appointment erstellt
   ↓
7. SUCCESS Response (aber nur Cal.com gebucht)
   ↓
8. ❌ Admin Portal zeigt NICHTS
   ↓
9. Webhook kommt (1-5s später)
   ↓
10. Webhook erstellt Appointment OHNE call_id
    ↓
11. ❌ Admin findet Appointment nicht (sucht nach call_id)
```

**Problem:** Call zeigt "booking_confirmed=1" aber Admin Portal findet Appointment nicht, weil call_id fehlt.

---

## 🔍 VALIDIERUNG (QUALITÄTSSICHERUNG)

### Für einzelnen Call:
```bash
php artisan appointments:validate-chain <call_id>
```

**Prüft:**
- ✅ company_id vorhanden
- ✅ customer_id vorhanden und Customer existiert
- ✅ Bei booking_confirmed=1 → Appointment muss existieren
- ✅ Appointment Customer = Call Customer
- ✅ Appointment Company = Call Company
- ✅ Cal.com Booking ID vorhanden

**Beispiel:**
```bash
$ php artisan appointments:validate-chain 559

🔍 Validating Call-Appointment-Customer Chain...

📞 Call ID: 559 (Retell: call_d9e5753db145aad308c8f72b0c2)
   Created: 2025-10-04 17:12:11

✓ Customer: Hans Schuster (farbhandy.askpro@gmail.com)
✓ Appointment: ID 632
   Time: 2025-10-07 14:00:00
   Status: confirmed
   Cal.com Booking: 11460989
✅ All validations passed!
```

### Für alle Calls:
```bash
php artisan appointments:validate-chain
```

Zeigt Tabelle mit allen Calls die booking_confirmed=1 haben oder booking_details enthalten.

---

## 🔧 NACHTRÄGLICHER IMPORT (Backfill)

Wenn ein Call FEHLT (z.B. vor dem Phase 1 Fix):

### Single Call Backfill:
```bash
php artisan appointments:backfill <call_id>
```

**Was es macht:**
1. Liest booking_details vom Call
2. Extrahiert Cal.com Booking Daten
3. Erstellt/Findet Customer
4. Erstellt Appointment mit allen Verknüpfungen
5. Setzt booking_confirmed=1

**Dry Run (Test ohne Änderungen):**
```bash
php artisan appointments:backfill <call_id> --dry-run
```

### Alle fehlenden Appointments erstellen:
```bash
php artisan appointments:backfill --all
```

Findet alle Calls mit:
- booking_confirmed=1
- booking_details vorhanden
- KEIN Appointment verknüpft

Und erstellt fehlende Appointments.

---

## 📊 DATENSTRUKTUR

### Call (calls table)
```php
id: 559
retell_call_id: "call_d9e5753db145aad308c8f72b0c2"
company_id: 15                          // ← MUSS gesetzt sein
customer_id: 338                        // ← MUSS gesetzt sein
branch_id: "9f4d5e2a-..."              // Optional
booking_confirmed: 1                    // Bei erfolgreicher Buchung
booking_details: {...}                  // Cal.com Booking JSON
```

### Customer (customers table)
```php
id: 338
name: "Hans Schuster"
email: "farbhandy.askpro@gmail.com"
phone: "+493083793369"
company_id: 15                          // ← MUSS mit Call übereinstimmen
```

### Appointment (appointments table)
```php
id: 632
calcom_v2_booking_id: "11460989"       // ← Eindeutig von Cal.com
external_id: "s9MDW4QPyLsabShMMwKyRq"  // Cal.com UID
call_id: 559                            // ← KRITISCH: Link zum Call
customer_id: 338                        // ← MUSS mit Call übereinstimmen
company_id: 15                          // ← MUSS mit Call übereinstimmen
branch_id: "9f4d5e2a-..."
service_id: 47
staff_id: "28f22a49-..."
starts_at: "2025-10-07 14:00:00"
ends_at: "2025-10-07 14:30:00"
status: "confirmed"
source: "retell_phone"                  // ← Zeigt Herkunft
metadata: {...}                          // Zusätzliche Infos
```

---

## 🚨 HÄUFIGE PROBLEME & LÖSUNGEN

### Problem 1: "Call zeigt keine Terminbuchung"
**Symptom:** booking_confirmed=1 aber Appointment fehlt

**Diagnose:**
```bash
php artisan appointments:validate-chain <call_id>
```

**Lösung:**
```bash
php artisan appointments:backfill <call_id>
```

---

### Problem 2: "Customer fehlt"
**Symptom:** Call hat keine customer_id

**Lösung:** Backfill erstellt automatisch Customer aus booking_details

---

### Problem 3: "company_id ist NULL"
**Symptom:** Call orphaned (keine Company)

**Lösung:** Backfill versucht company_id zu erkennen:
1. Via Service (calcom_event_type_id)
2. Via Phone Number (to_number)
3. Default: Company ID 1

---

### Problem 4: "Duplikate verhindern"
**Lösung:**
- calcom_v2_booking_id ist UNIQUE
- Backfill verwendet `updateOrCreate()` → Kein Duplikat möglich

---

## 📈 MONITORING

### Real-time Monitoring (während Test-Anruf):
```bash
/var/www/api-gateway/scripts/monitor-new-calls.sh
```

Zeigt sofort:
- Neue Calls
- Automatische Validation
- Probleme/Fehler

---

### Logs prüfen:
```bash
# Appointment Creation Logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i appointment

# Call Logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i "call_"

# Retell Webhook Logs
tail -f /var/www/api-gateway/storage/logs/laravel.log | grep -i retell
```

---

## ✅ BEST PRACTICES

### Für jeden neuen Call validieren:
```bash
# Nach Anruf
LATEST_CALL=$(mysql -u askproai_user -paskproai_secure_pass_2024 askproai_db -Nse "SELECT id FROM calls ORDER BY id DESC LIMIT 1")
php artisan appointments:validate-chain $LATEST_CALL
```

### Regelmäßige Konsistenz-Checks:
```bash
# Wöchentlich
php artisan appointments:validate-chain > /var/log/appointment-validation.log

# Bei Problemen alle backfillen
php artisan appointments:backfill --all --dry-run
# Wenn OK:
php artisan appointments:backfill --all
```

### Vor wichtigen Releases:
```bash
# Full Validation
php artisan appointments:validate-chain

# Ensure all bookings have appointments
php artisan appointments:backfill --all --dry-run
```

---

## 🎯 ERFOLGSKRITERIEN

Ein Call ist **vollständig synchronisiert**, wenn:

1. ✅ Call hat company_id
2. ✅ Call hat customer_id
3. ✅ Customer existiert in DB
4. ✅ Bei booking_confirmed=1: Appointment existiert
5. ✅ Appointment.call_id = Call.id
6. ✅ Appointment.customer_id = Call.customer_id
7. ✅ Appointment.company_id = Call.company_id
8. ✅ Appointment hat calcom_v2_booking_id
9. ✅ Appointment status ist "confirmed" oder "scheduled"

**Validation Command bestätigt:** "✅ All validations passed!"

---

## 📞 SUPPORT COMMANDS

```bash
# Validation
php artisan appointments:validate-chain <call_id>   # Single call
php artisan appointments:validate-chain             # All calls

# Backfill
php artisan appointments:backfill <call_id>         # Single call
php artisan appointments:backfill <call_id> --dry-run  # Test mode
php artisan appointments:backfill --all             # All missing
php artisan appointments:backfill --all --dry-run   # Test all

# Monitoring
/var/www/api-gateway/scripts/monitor-new-calls.sh   # Real-time watch

# Database Queries
mysql -u askproai_user -paskproai_secure_pass_2024 askproai_db -e "
SELECT
    c.id, c.booking_confirmed,
    a.id as apt_id,
    cu.name
FROM calls c
LEFT JOIN appointments a ON c.id = a.call_id
LEFT JOIN customers cu ON c.customer_id = cu.id
WHERE c.company_id = 15
ORDER BY c.id DESC
LIMIT 10;
"
```

---

**Status:** ✅ PRODUKTIONSBEREIT
**Nächster Review:** Nach 100 Production Calls
