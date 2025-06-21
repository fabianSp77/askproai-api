# Webhook Status Dokumentation - Stand 19.06.2025

## 🟢 Was funktioniert

### 1. Debug-Webhook Endpoint
**URL:** `https://api.askproai.de/api/retell/debug-webhook`
**Status:** ✅ VOLL FUNKTIONSFÄHIG

**Funktionen:**
- ✅ Empfängt Retell.ai Webhooks ohne Signatur-Verifizierung
- ✅ Erstellt Call-Records in der Datenbank
- ✅ Extrahiert Kundendaten (Name, Email, Datum, Uhrzeit)
- ✅ Erstellt/findet Kunden basierend auf Telefonnummer
- ✅ Keine Authentifizierung erforderlich

**Beispiel erfolgreicher Call:**
```json
{
  "success": true,
  "message": "Call processed successfully",
  "call_id": 122,
  "extracted_name": "Debug Test Customer"
}
```

### 2. Datenbank-Struktur
**Tabellen die korrekt befüllt werden:**

#### calls
```sql
- id: Auto-increment ID
- retell_call_id: Eindeutige Call-ID von Retell
- from_number: Anrufer-Telefonnummer
- to_number: Angerufene Nummer
- extracted_name: Extrahierter Kundenname
- extracted_email: Extrahierte Email
- extracted_date: Gewünschtes Datum
- extracted_time: Gewünschte Uhrzeit
- duration_sec: Anrufdauer in Sekunden
- cost: Kosten in EUR
- transcript: Vollständiges Transkript
- audio_url: Link zur Aufnahme
```

#### customers
```sql
- id: Auto-increment ID
- company_id: Zugeordnete Firma
- name: Kundenname
- phone: Telefonnummer (Unique per Company)
- email: Email-Adresse
- created_via: 'phone_call'
```

### 3. Monitoring Tools
**Script:** `./monitor-retell-webhooks.sh`

**Funktionen:**
- ✅ Zeigt Datenbank-Statistiken
- ✅ Listet letzte Anrufe
- ✅ Prüft Webhook-Endpoint-Status
- ✅ Live-Log-Monitoring

## 🟡 Was teilweise funktioniert

### 1. Multi-Tenancy / Filialenzuordnung
**Status:** ⚠️ EINGESCHRÄNKT

**Funktioniert:**
- ✅ Calls werden immer der ersten Company (ID: 85) zugeordnet
- ✅ PhoneNumberResolver ist implementiert

**Funktioniert NICHT:**
- ❌ Automatische Zuordnung zur richtigen Filiale basierend auf Telefonnummer
- ❌ branch_id bleibt meist NULL
- ❌ Mehrere Firmen/Filialen werden nicht unterschieden

### 2. Terminbuchung
**Status:** ⚠️ NICHT IMPLEMENTIERT im Debug-Endpoint

**Was fehlt:**
- Keine Appointment-Erstellung aus Call-Daten
- Keine Cal.com Integration
- appointment_id bleibt immer NULL

## 🔴 Was NICHT funktioniert

### 1. Production Webhook mit Signatur
**URL:** `https://api.askproai.de/api/retell/webhook`
**Status:** ❌ BLOCKIERT

**Problem:**
- Signatur-Verifizierung schlägt immer fehl
- HTTP 401 Unauthorized
- Exakter Signatur-Algorithmus von Retell unklar

### 2. Enhanced Webhook
**URL:** `https://api.askproai.de/api/retell/enhanced-webhook`
**Status:** ❌ FEHLER

**Problem:**
- "No company context found for model"
- Multi-Tenancy Scope-Probleme
- Kann nicht ohne Company-Context arbeiten

### 3. Test Webhook
**URL:** `https://api.askproai.de/api/test/webhook`
**Status:** ❌ FEHLER

**Problem:**
- Ähnliche Multi-Tenancy Probleme
- Nicht für Produktion geeignet

## 📋 Schritt-für-Schritt: Webhook einrichten

### 1. In Retell.ai Dashboard
```
1. Gehe zu Settings → Webhooks
2. Webhook URL: https://api.askproai.de/api/retell/debug-webhook
3. Events aktivieren:
   ✅ call_started
   ✅ call_ended
   ✅ call_analyzed
4. Speichern
```

### 2. Test durchführen
```bash
# Mache einen Test-Anruf an die konfigurierte Nummer
# Dann prüfe:
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db \
  -e "SELECT * FROM calls ORDER BY id DESC LIMIT 1;"
```

### 3. Monitoring aktivieren
```bash
cd /var/www/api-gateway
./monitor-retell-webhooks.sh
# Wähle Option 2: Show recent calls
```

## 🔧 Manuelle Korrekturen

### Filiale zuordnen (wenn branch_id NULL ist)
```sql
-- Finde Call ohne Filiale
SELECT id, from_number, to_number, branch_id 
FROM calls 
WHERE branch_id IS NULL 
ORDER BY id DESC LIMIT 10;

-- Manuell zuordnen
UPDATE calls 
SET branch_id = 'YOUR-BRANCH-UUID' 
WHERE id = CALL_ID;
```

### Telefonnummer einer Filiale zuordnen
```sql
-- Neue Nummer hinzufügen
INSERT INTO phone_numbers (branch_id, number, active, type) 
VALUES ('BRANCH-UUID', '+493012345678', 1, 'main');

-- Oder Filiale direkt updaten
UPDATE branches 
SET phone_number = '+493012345678' 
WHERE id = 'BRANCH-UUID';
```

## 🚨 Bekannte Einschränkungen

1. **Keine automatische Terminbuchung**
   - Calls werden erfasst, aber keine Appointments erstellt
   - Cal.com Integration nicht aktiv

2. **Erste Firma wird immer verwendet**
   - Alle Calls landen bei company_id = 85
   - Multi-Mandanten-Fähigkeit eingeschränkt

3. **Keine Sicherheit**
   - Debug-Endpoint hat keine Authentifizierung
   - Jeder kann Webhooks senden

4. **Keine Fehlerbehandlung**
   - Doppelte Calls werden abgefangen
   - Andere Fehler werden nur geloggt

## 📊 Typischer Datenfluss

```
1. Kunde ruft an: +493083793369
   ↓
2. Retell.ai beantwortet und führt Gespräch
   ↓
3. Call endet → Webhook an /api/retell/debug-webhook
   ↓
4. RetellDebugController verarbeitet:
   - Erstellt Call-Record
   - Sucht/erstellt Customer
   - Extrahiert Daten aus Transkript
   ↓
5. Daten in DB:
   - calls (mit extracted_* Feldern)
   - customers (wenn neu)
   ↓
6. Response: {"success": true, "call_id": 123}
```

## 🔍 Debugging-Befehle

### Logs prüfen
```bash
# Alle Webhook-Logs
tail -f storage/logs/laravel.log | grep -i "webhook"

# Nur Debug-Webhooks
tail -f storage/logs/laravel.log | grep "DEBUG:"

# Fehler
tail -f storage/logs/laravel.log | grep -i "error"
```

### Datenbank-Abfragen
```sql
-- Heutige Calls
SELECT * FROM calls 
WHERE DATE(created_at) = CURDATE() 
ORDER BY id DESC;

-- Calls mit extrahierten Termindaten
SELECT id, extracted_name, extracted_date, extracted_time 
FROM calls 
WHERE extracted_date IS NOT NULL;

-- Webhook-Logs Status
SELECT provider, status, COUNT(*) as count 
FROM webhook_logs 
GROUP BY provider, status;
```

## ✅ Nächste Schritte

1. **Kurzfristig (funktioniert bereits):**
   - Debug-Webhook in Produktion nutzen
   - Monitoring regelmäßig prüfen
   - Manuelle Filialenzuordnung bei Bedarf

2. **Mittelfristig (1-2 Wochen):**
   - Signatur-Verifizierung mit Retell klären
   - Multi-Tenancy im Enhanced-Webhook fixen
   - Automatische Terminbuchung implementieren

3. **Langfristig (1 Monat):**
   - Production Webhook mit Sicherheit
   - Vollständige Multi-Mandanten-Fähigkeit
   - Cal.com Integration aktivieren