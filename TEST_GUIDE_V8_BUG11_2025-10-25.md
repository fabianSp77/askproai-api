# Test Guide: V8 Bug #11 Fix Verification

**Version:** V8
**Bug Fixed:** Minimum Booking Notice Validation
**Test Duration:** ~15 minutes
**Phone Number:** +493033081738

---

## 🎯 QUICK START

**Was gefixt:** System sagt jetzt ehrlich "zu kurzfristig" statt falsche "verfügbar" Meldung bei Terminen < 15 Minuten

**Was du testen sollst:** 3 Szenarien - zu früh, gültig, Grenzfall

---

## ✅ TEST 1: Zu kurzfristiger Termin (Sollte ablehnen)

### Vorbereitung
1. Schau auf die Uhr - notiere aktuelle Zeit
2. Berechne: Aktuelle Zeit + 5 Minuten
3. Beispiel: Jetzt 20:00 → Teste mit 20:05

### Test durchführen
```
📞 Anrufen: +493033081738

Agent: "Willkommen bei AskPro..."

Du: "Guten Tag, ich möchte einen Herrenhaarschnitt für heute
     [deine berechnete Zeit] buchen für Hans Schuster"

Beispiel: "...für heute 20:05 Uhr buchen..."
```

### ✅ Erwartetes Verhalten (RICHTIG)
Agent sollte sagen:
- ✅ "Dieser Termin liegt leider zu kurzfristig"
- ✅ "Termine können frühestens 15 Minuten im Voraus gebucht werden"
- ✅ "Der nächste verfügbare Termin ist..."
- ✅ Bietet Alternative an (z.B. 20:15, 20:30)

### ❌ Fehlerhafte Antwort (wenn du das hörst → Bug nicht gefixt)
Agent sagt:
- ❌ "Der Termin ist verfügbar"
- ❌ "Soll ich den Termin buchen?"
- ❌ Dann Fehler bei Buchung: "Es ist ein Fehler aufgetreten"

### Log Verification
Nach dem Test:
```bash
# Check ob Validation lief
tail -100 /var/www/api-gateway/storage/logs/laravel.log | \
  grep "Booking notice validation failed"

# Du solltest sehen:
# ⏰ Booking notice validation failed
# "requested_time": "2025-10-25 20:05:00"
# "minimum_notice_minutes": 15
# "alternatives_count": 2
```

---

## ✅ TEST 2: Gültiger Termin (Sollte funktionieren)

### Test durchführen
```
📞 Anrufen: +493033081738

Du: "Guten Tag, ich möchte einen Herrenhaarschnitt für
     morgen 14 Uhr buchen für Maria Müller"
```

### ✅ Erwartetes Verhalten
- ✅ Agent prüft Verfügbarkeit
- ✅ Agent sagt "Termin ist verfügbar" (oder "leider belegt")
- ✅ KEINE Fehlermeldung "zu kurzfristig"
- ✅ Wenn verfügbar: Buchung funktioniert normal

### Log Verification
```bash
tail -100 /var/www/api-gateway/storage/logs/laravel.log | \
  grep "Booking notice validation passed"

# Du solltest sehen:
# ✅ Booking notice validation passed
# "requested_time": "2025-10-26 14:00:00"
# "minimum_notice_minutes": 15
```

---

## ✅ TEST 3: Grenzfall - Exakt 15 Minuten (Sollte akzeptieren)

### Vorbereitung
1. Aktuelle Zeit notieren
2. Exakt 15 Minuten addieren
3. Beispiel: Jetzt 20:00 → Teste mit 20:15

**WICHTIG:** Muss EXAKT 15 Minuten sein (nicht 14, nicht 16)

### Test durchführen
```
📞 Anrufen: +493033081738

Du: "Herrenhaarschnitt für heute [exakt +15 Minuten]
     für Peter Schmidt"

Beispiel: Jetzt 20:00 → "für heute 20:15"
```

### ✅ Erwartetes Verhalten
- ✅ Agent akzeptiert (>= nicht nur >)
- ✅ Agent sagt "Termin ist verfügbar" (wenn Slot frei)
- ✅ KEINE "zu kurzfristig" Meldung
- ✅ Buchung funktioniert (wenn verfügbar)

**Grund:** >= 15 Minuten ist GÜLTIG (nicht zu kurz)

### Log Verification
```bash
tail -100 /var/www/api-gateway/storage/logs/laravel.log | \
  grep "Booking notice validation passed"

# Requested time sollte EXAKT 15 Minuten in Zukunft sein
```

---

## 📊 ERGEBNIS MATRIX

| Test | Erwartung | Status | Notizen |
|------|-----------|--------|---------|
| 1. Zu früh (+5min) | ❌ Ablehnung mit Alternative | ☐ | |
| 2. Gültig (morgen) | ✅ Normal verfügbar/belegt | ☐ | |
| 3. Grenzfall (+15min) | ✅ Akzeptiert | ☐ | |

**Alle Tests bestanden?** → ✅ Bug #11 Fix funktioniert!

---

## 🔍 DETAILLIERTE LOG ANALYSE (Optional)

### Nach allen Tests - Komplette Analyse

```bash
cd /var/www/api-gateway

# 1. Wie viele Validierungen liefen?
echo "=== Validation Statistics ==="
grep -c "Booking notice validation" storage/logs/laravel-$(date +%Y-%m-%d).log

# 2. Wie viele wurden abgelehnt?
echo "=== Rejected (too soon) ==="
grep -c "Booking notice validation failed" storage/logs/laravel-$(date +%Y-%m-%d).log

# 3. Wie viele wurden akzeptiert?
echo "=== Accepted ==="
grep -c "Booking notice validation passed" storage/logs/laravel-$(date +%Y-%m-%d).log

# 4. Gibt es noch Cal.com 400 Fehler wegen "too soon"?
echo "=== Cal.com 400 Errors (should be 0) ==="
grep "Cal.com API request failed.*400" storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -c "too soon\|minimum booking notice"
```

**Erwartete Ergebnisse:**
- Validierungen: 3 (aus deinen Tests)
- Rejected: 1 (Test 1)
- Accepted: 2 (Test 2 + 3)
- Cal.com 400: 0 (keine mehr!)

---

## 🐛 TROUBLESHOOTING

### Problem: Agent sagt bei Test 1 noch "verfügbar"
**Diagnose:** Fix nicht aktiv

**Lösung:**
```bash
cd /var/www/api-gateway
php artisan config:clear
php artisan cache:clear
sudo systemctl restart php8.3-fpm

# Verify fix exists
grep -n "🔧 FIX 2025-10-25: Bug #11" app/Http/Controllers/RetellFunctionCallHandler.php
```

### Problem: Agent sagt immer "zu kurzfristig" (auch bei morgen)
**Diagnose:** Config falsch oder zu hoch

**Lösung:**
```bash
php artisan tinker
>>> config('calcom.minimum_booking_notice_minutes')

# Sollte sein: 15
# Wenn anders: Check .env CALCOM_MIN_BOOKING_NOTICE
```

### Problem: Keine Logs sichtbar
**Diagnose:** Logging Issue

**Lösung:**
```bash
# Check log file permissions
ls -la storage/logs/laravel.log

# Check today's log exists
ls -la storage/logs/laravel-$(date +%Y-%m-%d).log

# Force log rotation (if needed)
php artisan log:clear
```

---

## 📝 TEST PROTOKOLL (Zum Ausfüllen)

```
TESTER: ___________________
DATUM: ____________________
ZEIT: _____________________

TEST 1 (Zu früh):
- Anruf um: _______
- Gewünschte Zeit: _______
- Agent Antwort: _______________________
- ✅ / ❌ Passed?

TEST 2 (Gültig):
- Anruf um: _______
- Gewünschte Zeit: _______
- Agent Antwort: _______________________
- ✅ / ❌ Passed?

TEST 3 (Grenzfall):
- Anruf um: _______
- Gewünschte Zeit: _______
- Agent Antwort: _______________________
- ✅ / ❌ Passed?

GESAMTERGEBNIS: ✅ / ❌

NOTIZEN:
_________________________________
_________________________________
_________________________________
```

---

## 🎯 QUICK CHECKLIST

**Vor dem Test:**
- ☐ Telefon bereit (+493033081738)
- ☐ Uhr/Timer bereit (für Zeitberechnung)
- ☐ Notizblock für Ergebnisse

**Test 1 (5 min vorher):**
- ☐ Anruf gemacht
- ☐ Agent lehnt ab
- ☐ Alternative angeboten
- ☐ Log checked

**Test 2 (morgen):**
- ☐ Anruf gemacht
- ☐ Normal Verfügbarkeit
- ☐ Keine "zu früh" Meldung
- ☐ Log checked

**Test 3 (exakt 15min):**
- ☐ Anruf gemacht
- ☐ Agent akzeptiert
- ☐ An Grenze = gültig
- ☐ Log checked

**Nach allen Tests:**
- ☐ Log Statistiken geprüft
- ☐ Keine Cal.com 400 Fehler
- ☐ Alle 3 Tests passed
- ☐ Ergebnis dokumentiert

---

## ✅ SUCCESS CRITERIA

**Fix ist erfolgreich wenn:**
1. ✅ Test 1 lehnt ab mit Alternative
2. ✅ Test 2 funktioniert normal
3. ✅ Test 3 akzeptiert (Grenzfall)
4. ✅ Logs zeigen Validierung
5. ✅ Keine Cal.com 400 "too soon" Fehler mehr

**Wenn alles ✅:** 🎉 **BUG #11 GEFIXT!**

---

**Erstellt:** 2025-10-25 20:30
**Version:** V8
**Geschätzte Testdauer:** 15 Minuten
**Voraussetzung:** Deployment V8 abgeschlossen
