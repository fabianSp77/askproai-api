# Telefonnummern - Finale Konfiguration (Stand: 03.07.2025)

## ✅ Aktuelle Konfiguration

### 1. Krückeberg Servicegruppe
- **Company ID**: 1
- **Telefonnummer**: +493033081738
- **Agent ID**: agent_b36ecd3927a81834b6d56ab07b
- **Terminbuchung**: ❌ DEAKTIVIERT (needs_appointment_booking = false)
- **Zweck**: Reine Datensammlung für Call Center Service
- **API Key**: ✅ Vorhanden

#### Funktionen:
- ✅ Anrufe entgegennehmen
- ✅ Kundendaten sammeln (`/api/retell/collect-data`)
- ❌ KEINE Terminbuchung möglich (durch Security Layer blockiert)

### 2. AskProAI
- **Company ID**: 15
- **Telefonnummer**: +493083793369
- **Agent ID**: agent_b36ecd3927a81834b6d56ab07b (temporär geteilt)
- **Terminbuchung**: ✅ AKTIVIERT (needs_appointment_booking = true)
- **Zweck**: Vollständige Terminbuchungs-Funktionalität
- **API Key**: ✅ Vorhanden (temporär von Krückeberg)

#### Funktionen:
- ✅ Anrufe entgegennehmen
- ✅ Kundendaten sammeln
- ✅ Termine buchen (`/api/retell/collect-appointment`)
- ✅ Verfügbarkeit prüfen (`/api/retell/check-availability`)
- ✅ Termine stornieren/verschieben

## 🔧 Was wurde heute geändert:

1. **Telefonnummer-Korrektur**:
   - +493033081738 → Krückeberg Servicegruppe ✅
   - +493083793369 → AskProAI ✅

2. **AskProAI Konfiguration**:
   - needs_appointment_booking auf `true` gesetzt
   - Retell API Key hinzugefügt (temporär von Krückeberg)
   - Agent ID zugewiesen (temporär geteilt)

3. **Security Implementation**:
   - Middleware für Appointment-Endpoints
   - Controller-Level Checks
   - Service-Level Protection
   - Job-Level Security

## ⚠️ Wichtige Hinweise:

### Für Krückeberg:
- Der Agent muss für "keine Terminbuchung" konfiguriert sein
- Nutzt nur die `collect_customer_data` Function
- Webhook: `https://api.askproai.de/api/retell/webhook-simple`

### Für AskProAI:
- Benötigt eigenen Retell Agent (aktuell wird Agent geteilt)
- Sollte eigenen API Key bekommen (nicht teilen)
- Kann alle Appointment-Functions nutzen

## 📊 Technische Details:

### Webhook URLs:
- **Beide Companies**: `https://api.askproai.de/api/retell/webhook-simple`

### Custom Functions:
- **Krückeberg**: Nur `collect_customer_data`
- **AskProAI**: Alle Functions verfügbar

### Branch Details:
- **Krückeberg Branch ID**: 34c4d48e-4753-4715-9c30-c55843a943e8
- **AskProAI Branch ID**: 9f4d5e2a-46f7-41b6-b81d-1532725381d4

## 🧹 Bereinigung empfohlen:

Es existieren noch verwaiste Daten von gelöschten Companies:
- Branch "Hauptfiliale" (Company ID 8) mit Nummer +49 30 22222222
- Branch "Hauptfiliale" (Company ID 9) mit Nummer +49 30 33333333
- Branch "Praxis Berlin-Mitte" (Company ID 11)

Diese sollten aus der Datenbank entfernt werden.

## ✨ Zusammenfassung:

Die Telefonnummern sind jetzt korrekt zugeordnet:
- **+493033081738** → Krückeberg (nur Datensammlung)
- **+493083793369** → AskProAI (volle Funktionalität)

Beide sind funktionsfähig und durch die implementierte Security-Layer geschützt.