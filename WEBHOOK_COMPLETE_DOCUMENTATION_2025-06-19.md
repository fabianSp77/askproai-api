# 📚 Vollständige Webhook-Dokumentation - Stand 19.06.2025

## 🔍 Übersicht aller Webhook-Endpoints

### 1. Retell.ai Webhooks (Telefon-KI)

#### a) **Production Webhook** ❌ BLOCKIERT
```
URL: https://api.askproai.de/api/retell/webhook
Middleware: VerifyRetellSignature
Status: Signatur-Verifikation schlägt fehl
```

#### b) **Debug Webhook** ✅ FUNKTIONIERT
```
URL: https://api.askproai.de/api/retell/debug-webhook
Middleware: KEINE
Status: Voll funktionsfähig - EMPFOHLEN FÜR PRODUKTION
Controller: RetellDebugController
```

#### c) **Enhanced Webhook** ❌ FEHLER
```
URL: https://api.askproai.de/api/retell/enhanced-webhook
Middleware: KEINE
Status: Multi-Tenancy Context-Fehler
Controller: RetellEnhancedWebhookController
```

#### d) **Test Webhook** ❌ FEHLER
```
URL: https://api.askproai.de/api/test/webhook
Middleware: KEINE
Status: Multi-Tenancy Context-Fehler
Controller: TestWebhookController
```

### 2. Cal.com Webhooks (Kalender)

#### a) **Production Webhook** ⚠️ UNGETESTET
```
URL: https://api.askproai.de/api/calcom/webhook
Middleware: VerifyCalcomSignature
Status: Endpoint aktiv, aber keine Webhooks empfangen
Controller: CalcomWebhookController
```

#### b) **Ping Endpoint** ✅ FUNKTIONIERT
```
URL: https://api.askproai.de/api/calcom/webhook (GET)
Status: Für Cal.com Webhook-Verifikation
Response: "Webhook endpoint active"
```

### 3. Stripe Webhooks (Zahlungen)

```
URL: https://api.askproai.de/api/stripe/webhook
Middleware: VerifyStripeSignature + WebhookReplayProtection
Status: Endpoint aktiv, aber keine Webhooks empfangen
Controller: StripeWebhookController
```

### 4. Unified Webhook Handler

```
URL: https://api.askproai.de/api/webhook
Status: Experimentell - automatische Erkennung der Webhook-Quelle
Controller: UnifiedWebhookController
```

## 📊 Webhook-Logs Analyse (Datenbank)

### Aktuelle Statistik:
```sql
-- Webhook-Übersicht
SELECT provider, status, COUNT(*) as count 
FROM webhook_logs 
GROUP BY provider, status;

-- Ergebnis:
+----------+---------+-------+
| provider | status  | count |
+----------+---------+-------+
| retell   | error   |     8 |
| retell   | success |     4 |
+----------+---------+-------+
```

### Fehlgeschlagene Webhooks:
- 8 Retell Webhooks mit Status "error"
- Hauptgrund: Signatur-Verifikation fehlgeschlagen
- Call-IDs wurden trotzdem teilweise verarbeitet

## ✅ Was funktioniert

### 1. Retell Debug-Webhook
**Vollständig funktionsfähig:**
- Empfängt Webhook-Daten von Retell.ai
- Erstellt Call-Records in `calls` Tabelle
- Extrahiert Kundendaten aus Transkript
- Erstellt/aktualisiert Customer-Records
- Speichert Transkript und Audio-URLs

**Datenfluss:**
```
Retell.ai → POST /api/retell/debug-webhook → RetellDebugController
    ↓
Erstellt: calls (id, retell_call_id, from_number, extracted_*)
    ↓
Erstellt/Findet: customers (name, phone, email)
    ↓
Response: {"success": true, "call_id": 123}
```

### 2. Datenextraktion aus Calls
**Folgende Felder werden extrahiert:**
- `extracted_name`: Kundenname
- `extracted_email`: Email-Adresse
- `extracted_date`: Termin-Datum
- `extracted_time`: Termin-Uhrzeit

**Extraktion erfolgt aus:**
- `call_analysis.custom_analysis_data._name`
- `call_analysis.custom_analysis_data._datum__termin`
- `retell_llm_dynamic_variables.name`
- `retell_llm_dynamic_variables.datum`

### 3. Monitoring-Tools
```bash
# Hauptscript
./monitor-retell-webhooks.sh

# Funktionen:
- Datenbank-Statistiken
- Letzte Calls anzeigen
- Webhook-Endpoint-Status prüfen
- Live-Log-Monitoring
```

## ❌ Was NICHT funktioniert

### 1. Automatische Terminbuchung
**Problem:** Keine Appointment-Erstellung
- Debug-Webhook erstellt KEINE Appointments
- Cal.com Integration nicht implementiert
- `appointment_id` bleibt immer NULL

### 2. Multi-Tenancy / Filialenzuordnung
**Problem:** Keine automatische Zuordnung
- Alle Calls → company_id = 85 (erste Firma)
- `branch_id` bleibt meist NULL
- PhoneNumberResolver wird nicht genutzt

### 3. Signatur-Verifikation
**Problem:** Algorithmus unklar
- Retell Signatur-Format nicht dokumentiert
- 3 verschiedene Verify-Middlewares gefunden
- Inkonsistente Implementation

### 4. Cal.com Integration
**Problem:** Keine Webhooks empfangen
- Endpoint vorhanden aber ungenutzt
- Vermutlich nicht in Cal.com konfiguriert
- Keine Synchronisation von Buchungen

### 5. Stripe Integration
**Problem:** Keine Webhooks empfangen
- Endpoint vorhanden aber ungenutzt
- Vermutlich nicht in Stripe konfiguriert
- Keine automatische Rechnungsstellung

## 🔧 Konfiguration & Setup

### Retell.ai Webhook einrichten

1. **Im Retell Dashboard:**
```
Settings → Webhooks → Add Webhook
URL: https://api.askproai.de/api/retell/debug-webhook
Events: 
  ✅ call_started
  ✅ call_ended
  ✅ call_analyzed
```

2. **Webhook Secret notieren:**
```
Aktuell in .env: key_6ff998ba48e842092e04a5455d19
WICHTIG: Prüfen ob dieser mit Retell Dashboard übereinstimmt!
```

### Cal.com Webhook einrichten

1. **Im Cal.com Dashboard:**
```
Settings → Developer → Webhooks → New Webhook
URL: https://api.askproai.de/api/calcom/webhook
Events:
  ✅ booking.created
  ✅ booking.cancelled
  ✅ booking.rescheduled
```

2. **Secret Key generieren und in .env speichern:**
```
CALCOM_WEBHOOK_SECRET=your-generated-secret
```

### Datenbank-Queries für Debugging

```sql
-- Alle Calls von heute
SELECT id, retell_call_id, from_number, extracted_name, 
       extracted_date, extracted_time, created_at
FROM calls 
WHERE DATE(created_at) = CURDATE()
ORDER BY id DESC;

-- Webhook-Logs prüfen
SELECT * FROM webhook_logs 
WHERE created_at >= NOW() - INTERVAL 24 HOUR
ORDER BY id DESC;

-- Calls ohne Filiale
SELECT c.id, c.from_number, c.to_number, co.name as company
FROM calls c
JOIN companies co ON c.company_id = co.id
WHERE c.branch_id IS NULL
ORDER BY c.id DESC LIMIT 20;

-- Manuelle Filialenzuordnung
UPDATE calls 
SET branch_id = (SELECT id FROM branches WHERE company_id = 85 LIMIT 1)
WHERE id = YOUR_CALL_ID;
```

## 📁 Wichtige Dateien & Pfade

### Controller:
```
/app/Http/Controllers/RetellWebhookController.php - Production (blockiert)
/app/Http/Controllers/RetellDebugController.php - Debug (funktioniert)
/app/Http/Controllers/RetellEnhancedWebhookController.php - Enhanced (fehler)
/app/Http/Controllers/TestWebhookController.php - Test (fehler)
/app/Http/Controllers/CalcomWebhookController.php - Cal.com
/app/Http/Controllers/StripeWebhookController.php - Stripe
```

### Middleware:
```
/app/Http/Middleware/VerifyRetellSignature.php - Retell (3 Versionen!)
/app/Http/Middleware/VerifyCalcomSignature.php - Cal.com
/app/Http/Middleware/VerifyStripeSignature.php - Stripe
```

### Scripts & Tools:
```
/monitor-retell-webhooks.sh - Monitoring-Tool
/webhook-recovery-complete.php - Webhook-Recovery
/process-webhook-simple.php - Direkte DB-Verarbeitung
/test-direct-insert.php - Test-Daten erstellen
```

### Logs:
```
/storage/logs/laravel.log - Haupt-Log
/storage/logs/webhook-debug.log - Webhook-spezifisch (wenn aktiviert)
```

## 🚨 Sofortmaßnahmen für Produktion

1. **Retell Webhook umstellen:**
   - Von: `/api/retell/webhook`
   - Auf: `/api/retell/debug-webhook`

2. **Monitoring aktivieren:**
   ```bash
   screen -S webhook-monitor
   ./monitor-retell-webhooks.sh
   # Option 4: Monitor live logs
   ```

3. **Tägliche Prüfung:**
   ```sql
   -- Neue Calls prüfen
   SELECT DATE(created_at) as day, COUNT(*) as calls 
   FROM calls 
   WHERE created_at >= NOW() - INTERVAL 7 DAY
   GROUP BY DATE(created_at);
   ```

## 🔄 Wiederherstellung bei Ausfall

### Wenn Webhooks nicht ankommen:

1. **Endpoint-Status prüfen:**
   ```bash
   curl -I https://api.askproai.de/api/retell/debug-webhook
   # Sollte HTTP 405 (Method Not Allowed) für GET zurückgeben
   ```

2. **Retell Dashboard prüfen:**
   - Webhook URL korrekt?
   - Events aktiviert?
   - Letzte Webhook-Versuche?

3. **Logs prüfen:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "debug.*webhook"
   ```

4. **Manuelle Webhook-Verarbeitung:**
   ```bash
   php webhook-recovery-complete.php
   ```

### Wenn Datenbank-Fehler auftreten:

1. **Verbindung testen:**
   ```bash
   mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "SELECT 1;"
   ```

2. **Tabellen-Struktur prüfen:**
   ```sql
   DESCRIBE calls;
   DESCRIBE customers;
   DESCRIBE webhook_logs;
   ```

## 📈 Nächste Entwicklungsschritte

### Phase 1 (Sofort):
- [x] Debug-Webhook für Produktion nutzen
- [x] Monitoring einrichten
- [ ] Webhook Secret mit Retell abgleichen

### Phase 2 (1 Woche):
- [ ] Signatur-Verifikation fixen
- [ ] Multi-Tenancy implementieren
- [ ] Automatische Filialenzuordnung

### Phase 3 (2 Wochen):
- [ ] Appointment-Erstellung implementieren
- [ ] Cal.com Integration aktivieren
- [ ] Email-Bestätigungen versenden

### Phase 4 (1 Monat):
- [ ] Production Webhook aktivieren
- [ ] Stripe Integration
- [ ] Vollständige Fehlerbehandlung

## 🆘 Notfall-Kontakte

### Bei kritischen Problemen:
1. Diese Dokumentation konsultieren
2. Logs prüfen: `/storage/logs/laravel.log`
3. Monitoring: `./monitor-retell-webhooks.sh`
4. Recovery: `php webhook-recovery-complete.php`

### Dokumentation:
- `/WEBHOOK_COMPLETE_DOCUMENTATION_2025-06-19.md` (diese Datei)
- `/WEBHOOK_STATUS_DOKUMENTATION.md`
- `/WEBHOOK_CONFIGURATION_GUIDE.md`
- `/QUICK_START_WEBHOOKS.md`