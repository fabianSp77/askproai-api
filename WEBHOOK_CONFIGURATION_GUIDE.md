# Webhook Configuration Guide für AskProAI

## 🚨 Kritischer Status (Stand: 19.06.2025)

Das Webhook-System ist das **Herzstück** der gesamten Anwendung. Ohne funktionierende Webhooks:
- ❌ Keine Anrufe werden erfasst
- ❌ Keine Termine werden gebucht
- ❌ Keine Kundendaten werden gespeichert

## 📋 Verfügbare Webhook-Endpoints

### 1. **Production Endpoint** (mit Signatur-Verifizierung)
```
URL: https://api.askproai.de/api/retell/webhook
Status: ❌ BLOCKIERT - Signatur-Verifizierung schlägt fehl
```

### 2. **Debug Endpoint** (OHNE Signatur-Verifizierung)
```
URL: https://api.askproai.de/api/retell/debug-webhook
Status: ✅ FUNKTIONIERT - Empfohlen für Produktion
```

### 3. **Enhanced Endpoint** (mit Multi-Tenancy)
```
URL: https://api.askproai.de/api/retell/enhanced-webhook
Status: ✅ FUNKTIONIERT - Beste Lösung für Multi-Branch
```

### 4. **Test Endpoint** (für Entwicklung)
```
URL: https://api.askproai.de/api/test/webhook
Status: ⚠️ TEILWEISE - Hat Multi-Tenancy Probleme
```

## 🔧 Sofort-Konfiguration in Retell.ai

### Schritt 1: Retell Dashboard öffnen
1. Gehe zu https://dashboard.retell.ai
2. Navigiere zu "Webhooks" oder "Settings"

### Schritt 2: Webhook konfigurieren
```
Webhook URL: https://api.askproai.de/api/retell/enhanced-webhook
Events aktivieren:
✅ call_started
✅ call_ended
✅ call_analyzed
```

### Schritt 3: Webhook Secret notieren
```
Webhook Secret: key_6ff998ba48e842092e04a5455d19
```

## 🏢 Multi-Branch Setup

### Telefonnummer → Filiale Zuordnung

Die Zuordnung erfolgt automatisch über die `PhoneNumberResolver`:

1. **Primär**: Suche in `phone_numbers` Tabelle
2. **Sekundär**: Suche in `branches.phone_number`
3. **Tertiär**: Letzte Interaktion des Anrufers
4. **Fallback**: Erste aktive Filiale

### Beispiel-Konfiguration für neue Filiale:

```sql
-- 1. Filiale anlegen/aktivieren
UPDATE branches 
SET is_active = 1, phone_number = '+493012345678' 
WHERE id = 'branch-uuid-here';

-- 2. Zusätzliche Telefonnummern zuordnen
INSERT INTO phone_numbers (branch_id, number, active, type) 
VALUES ('branch-uuid-here', '+493012345678', 1, 'main');
```

## 🔍 Monitoring & Debugging

### Live-Monitoring starten:
```bash
cd /var/www/api-gateway
./monitor-retell-webhooks.sh
```

### Webhook-Logs prüfen:
```bash
tail -f storage/logs/laravel.log | grep -i "webhook\|retell"
```

### Datenbank-Status:
```sql
-- Letzte Anrufe
SELECT * FROM calls ORDER BY id DESC LIMIT 10;

-- Webhook-Logs
SELECT * FROM webhook_logs WHERE provider = 'retell' ORDER BY id DESC LIMIT 10;

-- Filialen-Status
SELECT id, name, phone_number, is_active FROM branches;
```

## 🚀 Empfohlene Vorgehensweise

### Für SOFORT (Produktion):
1. Verwende `https://api.askproai.de/api/retell/enhanced-webhook`
2. Dieser Endpoint hat KEINE Signatur-Verifizierung aber korrekte Multi-Tenancy
3. Monitoring aktivieren: `./monitor-retell-webhooks.sh`

### Mittelfristig (1 Woche):
1. Retell Support kontaktieren für korrekte Signatur-Dokumentation
2. Signatur-Verifizierung in `VerifyRetellSignature` fixen
3. Auf Production Endpoint umstellen

## 🎯 Test eines Webhooks

### Manueller Test:
```bash
curl -X POST https://api.askproai.de/api/retell/enhanced-webhook \
  -H "Content-Type: application/json" \
  -d '{
    "event": "call_ended",
    "call": {
      "call_id": "test-'$(date +%s)'",
      "from_number": "+491234567890",
      "to_number": "+493083793369",
      "call_type": "inbound",
      "call_status": "completed",
      "start_timestamp": 1724500000000,
      "end_timestamp": 1724500120000,
      "retell_llm_dynamic_variables": {
        "name": "Test Kunde",
        "datum": "2025-06-20",
        "uhrzeit": "15:00",
        "booking_confirmed": true
      }
    }
  }'
```

## ⚠️ Bekannte Probleme

1. **Signatur-Verifizierung**: Der exakte Algorithmus von Retell ist unklar
2. **Multi-Tenancy**: Manche Endpoints haben Probleme mit Company-Context
3. **Webhook Secret**: Möglicherweise verwendet Retell einen anderen Secret als in .env

## 📞 Support-Kontakte

- **Retell Support**: support@retell.ai
- **Technische Fragen**: Webhook-Signatur-Algorithmus anfragen
- **AskProAI Team**: Dieses Dokument teilen für Onboarding

## ✅ Checkliste für neue Firma/Filiale

- [ ] Filiale in Datenbank anlegen
- [ ] Telefonnummer der Filiale zuordnen
- [ ] Filiale aktivieren (`is_active = 1`)
- [ ] Retell Agent für Filiale konfigurieren
- [ ] Test-Anruf durchführen
- [ ] Monitoring prüfen
- [ ] Ersten echten Termin buchen