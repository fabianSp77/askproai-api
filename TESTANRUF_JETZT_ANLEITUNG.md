# 📞 Testanruf Jetzt Machen - Schritt für Schritt

**Status:** ✅ System bereit | Middleware funktioniert | Warte auf Test

---

## Schnell-Übersicht

✅ **Problem behoben:** Webhooks waren blockiert (Middleware + OPcache)
✅ **Middleware funktioniert:** Verifiziert um 11:11:07
✅ **Monitoring bereit:** Real-time Überwachung verfügbar
⏳ **Warte auf:** Verifikations-Testanruf

---

## Schritt 1: Terminal vorbereiten

```bash
cd /var/www/api-gateway

# Monitoring starten (empfohlen)
./monitor_test_call.sh

# Alternative: Manuell überwachen
# Terminal 1:
watch -n 1 cat /tmp/retell_middleware_test.log

# Terminal 2:
tail -f /var/log/nginx/access.log | grep retell
```

---

## Schritt 2: Testanruf machen

**Telefonnummer anrufen:** [Deine Retell Nummer]

**Was sagen:**
1. "Guten Tag, ich möchte einen Termin vereinbaren"
2. "Mein Name ist [Name]"
3. "Ich hätte gerne einen Herrenhaarschnitt"
4. "Heute um 16:00 Uhr" ← NUR EINMAL sagen!
5. Auf Verfügbarkeitsabfrage warten
6. Termin bestätigen

**Was beobachten:**
- Agent sollte NICHT mehrfach nach Zeit fragen
- Agent sollte Verfügbarkeit prüfen
- Agent sollte Termin erfolgreich buchen
- Gespräch sollte positiv enden

---

## Schritt 3: Sofort nach Anruf

```bash
# 1. Monitoring beenden (CTRL+C im monitor_test_call.sh)

# 2. Webhook Count prüfen
cat /tmp/retell_middleware_test.log | wc -l
# Erwartung: > 1 (mehrere Webhooks)

# 3. Database prüfen
php debug_latest_calls.php
# Erwartung: Neuester Call hat Events > 0

# 4. Call analysieren (Call ID aus debug_latest_calls.php kopieren)
# Erst Call ID in analyze_test_call.php updaten:
nano analyze_test_call.php
# → Zeile 9: $callId = 'call_XXX...'; # Neue Call ID einfügen

# Dann analysieren:
php analyze_test_call.php
```

---

## ✅ Erfolgskriterien

Der Test ist erfolgreich wenn:

```
✅ Webhooks angekommen
   → /tmp/retell_middleware_test.log zeigt mehrere Einträge

✅ Database aktualisiert
   → RetellCallSession hat from_number & to_number (nicht "N/A")
   → Events > 0 (mindestens call_started, call_ended, call_analyzed)
   → Function Traces > 0

✅ Functions erfolgreich
   → initialize_call: success=true
   → check_availability: Gibt echte Daten zurück
   → collect_appointment_info (falls gebucht): success=true

✅ User Experience gut
   → Agent fragte NICHT mehrfach nach Zeit
   → Verfügbarkeit wurde geprüft
   → Termin wurde gebucht
   → Sentiment: Positive
   → Call Successful: true
```

---

## ❌ Wenn Test fehlschlägt

### Problem: Keine Webhooks empfangen

```bash
# Check 1: Middleware log leer?
cat /tmp/retell_middleware_test.log
# Wenn leer: Webhooks kommen nicht an

# Check 2: Nginx logs prüfen
tail -100 /var/log/nginx/access.log | grep "POST /api/webhooks/retell"
# Wenn leer: Retell schickt keine Webhooks

# Check 3: Agent Config prüfen
php check_agent_webhook.php
# Webhook URL muss sein: https://api.askproai.de/api/webhooks/retell
```

### Problem: Webhooks kommen an, aber keine Events

```bash
# Check: Laravel logs
tail -100 storage/logs/laravel.log | grep -i "retell\|error"
# Suche nach Fehlern beim Webhook Processing
```

### Problem: Functions schlagen fehl

```bash
# Analyze call details
php analyze_test_call.php
# Prüfe "Function Calls" Sektion für error messages
```

---

## 📊 Was passiert bei erfolgreichem Test

```
1. Retell sendet call_started Webhook
   → RetellCallSession wird erstellt in Database
   → from_number, to_number, agent_id gespeichert

2. Agent ruft initialize_call()
   → Function findet RetellCallSession
   → Returns company context
   → success=true

3. Agent sammelt Informationen
   → Dynamic Variables: datum, uhrzeit, dienstleistung

4. Agent ruft check_availability_v17()
   → Function hat call context
   → Prüft Cal.com availability
   → Returns verfügbare Zeiten

5. Agent ruft collect_appointment_info()
   → Function erstellt Appointment
   → Synced zu Cal.com
   → success=true

6. Retell sendet call_ended Webhook
   → Call Status aktualisiert
   → Duration gespeichert

7. Retell sendet call_analyzed Webhook
   → Transcript gespeichert
   → Sentiment analysiert
   → Call Summary erstellt
```

---

## Nach erfolgreichem Test

Wenn Test erfolgreich:

```bash
# 1. Documentation updaten
# → TESTANRUF_ANALYSE_2025-10-24_[TIME].md erstellen

# 2. Nächste Phase starten: Proper Signature Verification
# → app/Http/Middleware/VerifyRetellWebhookSignature.php
# → HMAC-SHA256 Implementation
# → Mit echten Webhooks testen
```

Wenn Test fehlschlägt:

```bash
# 1. Neue Root Cause Analysis
# → Sammle alle Logs
# → Identifiziere neues Problem
# → Dokumentiere in RCA

# 2. Fix implementieren
# → Basierend auf neuen Erkenntnissen

# 3. Erneut testen
```

---

## Quick Commands Reference

```bash
# Monitoring starten
./monitor_test_call.sh

# Webhook count
cat /tmp/retell_middleware_test.log | wc -l

# Latest calls
php debug_latest_calls.php

# Analyze call
php analyze_test_call.php

# Check logs
tail -f storage/logs/laravel.log
tail -f /var/log/nginx/access.log

# Middleware status
cat /tmp/retell_middleware_test.log

# Database direct
psql askproai_db -c "SELECT call_id, call_status, created_at FROM retell_call_sessions ORDER BY created_at DESC LIMIT 5;"
psql askproai_db -c "SELECT COUNT(*) as event_count FROM retell_call_events;"
```

---

## Wichtige Dateien

```
✅ TESTCALL_RCA_2025-10-24_1103.md
   → Detaillierte Analyse des fehlgeschlagenen Calls

✅ SYSTEM_STATUS_2025-10-24_1117.md
   → Aktueller System Status + Technical Details

✅ monitor_test_call.sh
   → Real-time Monitoring Script

✅ debug_latest_calls.php
   → Database State Checker

✅ analyze_test_call.php
   → Retell API Call Analyzer

✅ app/Http/Middleware/VerifyRetellWebhookSignature.php
   → Middleware (temporär vereinfacht)
```

---

## Kontakt / Support

Wenn etwas unerwartetes passiert:

1. **Nicht Panik** - Middleware ist bewusst einfach gehalten für debugging
2. **Logs sammeln** - Alle Ausgaben speichern
3. **Call ID notieren** - Von Retell Dashboard oder debug_latest_calls.php
4. **Analyse durchführen** - analyze_test_call.php mit Call ID
5. **Dokumentieren** - Was passierte, was erwartet war

---

**Ready:** ✅ Ja
**Monitoring:** ✅ Verfügbar
**System:** ✅ Funktioniert
**Action:** 📞 Jetzt Testanruf machen!

---

**Created:** 2025-10-24 11:20 CET
**Status:** Ready for Verification Test
**Next:** User macht Testanruf mit Monitoring
