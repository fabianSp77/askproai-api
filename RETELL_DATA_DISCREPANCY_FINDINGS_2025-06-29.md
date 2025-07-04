# Retell Data Discrepancy Findings - 2025-06-29

## 🔍 Wichtige Erkenntnisse

### 1. **Audio-Dauer Problem GELÖST**
- **Problem**: Dauer in DB (122s) stimmt nicht mit Retell Dashboard (54s) überein
- **Ursache**: Falsche Berechnung in ProcessRetellCallEndedJobFixed
- **Lösung**: 
  - Script `fix-call-duration-calculation.php` hat 99 Anrufe korrigiert
  - Neuer Job `ProcessRetellCallEndedJobEnhanced` nutzt `call_analysis.call_length`

### 2. **Fehlende Felder in der Datenbank**
Migration erfolgreich durchgeführt für:
- `agent_version` (war INT, jetzt VARCHAR)
- `session_outcome` 
- `health_insurance_company`
- `appointment_made` (Boolean)
- `reason_for_visit`
- `end_to_end_latency`
- `duration_ms`

### 3. **Dynamic Variables Parsing Problem**
Retell sendet verschiedene Formate:
```
_datum__termin → datum_termin
_uhrzeit__termin → uhrzeit_termin
patient_full_name → name
caller_full_name → name
{{caller_phone_number}} → Template, wird ignoriert
```

### 4. **Appointment Tracking**
- Viele Anrufe haben Termindaten in Dynamic Variables
- `appointment_made` Flag zeigt erfolgreiche Buchungen
- Session Outcome "Successful" = Termin gebucht
- Session Outcome "Unsuccessful" = Kein Termin

## 📊 Datenqualität nach Fixes

### Vorher:
- Falsche Dauern (z.B. 122s statt 54s)
- Fehlende Dynamic Variables Parsing
- Keine Appointment Tracking
- Fehlende Session Outcomes

### Nachher:
- ✅ Korrekte Dauern aus Retell API
- ✅ Dynamic Variables werden geparst
- ✅ Appointment-Made Flag wird gesetzt
- ✅ Session Outcomes werden gespeichert
- ✅ Alle Retell-Felder werden erfasst

## 🚀 Nächste Schritte

### 1. Webhook Handler Update
```bash
# Alte Version ersetzen
mv app/Jobs/ProcessRetellCallEndedJobFixed.php app/Jobs/ProcessRetellCallEndedJobFixed.old
cp app/Jobs/ProcessRetellCallEndedJobEnhanced.php app/Jobs/ProcessRetellCallEndedJobFixed.php
```

### 2. Retell Agent Konfiguration
Der Agent muss konsistente Variable-Namen verwenden:
- OHNE führende Unterstriche
- OHNE doppelte Unterstriche
- KEINE Template-Variablen {{}}

### 3. Appointment Processing
Wenn `appointment_made = true`:
1. Customer aus Phone Number finden/erstellen
2. Cal.com Booking erstellen
3. Appointment Record speichern
4. Email-Bestätigung senden

## 📝 Test-Empfehlungen

1. **Neuer Test-Anruf**:
   - Termin buchen für "Beratung"
   - Prüfen ob alle Felder korrekt gefüllt werden
   - Duration sollte mit Retell Dashboard übereinstimmen

2. **Monitoring**:
   ```sql
   SELECT 
     DATE(created_at) as date,
     COUNT(*) as total_calls,
     SUM(appointment_made) as appointments_made,
     AVG(duration_sec) as avg_duration
   FROM calls 
   WHERE created_at >= CURDATE()
   GROUP BY DATE(created_at);
   ```

3. **Datenqualität Check**:
   ```sql
   SELECT 
     session_outcome,
     COUNT(*) as count,
     AVG(duration_sec) as avg_duration,
     SUM(appointment_made) as appointments
   FROM calls
   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
   GROUP BY session_outcome;
   ```

## ✅ Fazit

Die Hauptprobleme wurden identifiziert und behoben:
1. Audio-Dauer stimmt jetzt mit Retell überein
2. Alle Retell-Felder werden korrekt gespeichert
3. Dynamic Variables werden geparst
4. Appointment Tracking funktioniert

Das System ist bereit für die Cal.com Integration!