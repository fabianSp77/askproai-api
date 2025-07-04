# Telefonnummern-Übersicht (Stand: 03.07.2025)

## 🏢 Aktive Companies mit Telefonnummern

### 1. Krückeberg Servicegruppe (ID: 1)
- **Status**: ❌ KEINE Terminbuchung (needs_appointment_booking = false)
- **Retell API Key**: Vorhanden

#### Branch: Krückeberg Servicegruppe Zentrale
- **Branch ID**: 34c4d48e-4753-4715-9c30-c55843a943e8
- **Retell Agent ID**: agent_b36ecd3927a81834b6d56ab07b ✅

##### Telefonnummer:
- **Nummer**: +493033081738 (NEUE NUMMER - heute aktualisiert!)
- **Typ**: hotline
- **Status**: 🟢 AKTIV
- **Agent ID**: agent_b36ecd3927a81834b6d56ab07b ✅
- **Erstellt**: 27.06.2025

### 2. AskProAI (ID: 15)
- **Status**: ❌ KEINE Terminbuchung (needs_appointment_booking = false)

#### Branch: AskProAI Hauptsitz München
- **Branch ID**: 9f4d5e2a-46f7-41b6-b81d-1532725381d4
- **Retell Agent ID**: ❌ KEIN AGENT
- **Telefonnummern**: ❌ KEINE

## ⚠️ Verwaiste Daten (Companies existieren nicht mehr)

### Branch: Hauptfiliale (Company ID: 8 - EXISTIERT NICHT)
- **Branch ID**: 9f4935d5-b778-44bb-8950-fbd24c69fa00
- **Telefonnummer**: +49 30 22222222
- **Problem**: Company wurde gelöscht, Branch und Phone Number noch vorhanden

### Branch: Hauptfiliale (Company ID: 9 - EXISTIERT NICHT)
- **Branch ID**: 9f4935f7-4c28-49a3-8d4a-816cb4d08258
- **Telefonnummer**: +49 30 33333333
- **Problem**: Company wurde gelöscht, Branch und Phone Number noch vorhanden

### Branch: Praxis Berlin-Mitte (Company ID: 11 - EXISTIERT NICHT)
- **Branch ID**: 9f49ab77-2811-46b2-a609-305975c423da
- **Telefonnummern**: Keine
- **Problem**: Company wurde gelöscht, Branch noch vorhanden

## 📊 Zusammenfassung

### Funktionierende Konfiguration:
- ✅ **Krückeberg Servicegruppe**: +493033081738 → agent_b36ecd3927a81834b6d56ab07b
  - Nummer wurde heute von +493083793369 auf +493033081738 aktualisiert
  - Agent ist korrekt zugeordnet
  - Kann Anrufe empfangen und Daten sammeln

### Probleme:
- ⚠️ 3 verwaiste Branches von gelöschten Companies
- ⚠️ 2 verwaiste Telefonnummern ohne gültige Company

## 🔧 Empfohlene Bereinigung

```sql
-- Verwaiste Phone Numbers löschen
DELETE FROM phone_numbers WHERE company_id NOT IN (SELECT id FROM companies);

-- Verwaiste Branches löschen
DELETE FROM branches WHERE company_id NOT IN (SELECT id FROM companies);
```

## 📞 Wichtige Information für Krückeberg

Die Telefonnummer **+493033081738** ist jetzt aktiv mit dem Agent **agent_b36ecd3927a81834b6d56ab07b** verknüpft.

**Webhook URL**: https://api.askproai.de/api/retell/webhook-simple
**Data Collection URL**: https://api.askproai.de/api/retell/collect-data

Der Agent sollte so konfiguriert sein, dass er:
1. Keine Termine bucht
2. Nur Kundendaten sammelt
3. Die `collect_customer_data` Function nutzt