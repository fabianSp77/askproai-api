# 🏢 Neue Firma bei AskProAI einrichten - Schritt-für-Schritt Anleitung

## 📋 Übersicht

Diese Anleitung zeigt, wie Sie eine neue Firma im AskProAI System komplett einrichten - von der Erstellung bis zum ersten Testanruf.

## 🚀 Schnellstart (5 Minuten)

### Option 1: Automatisches Setup (Empfohlen)

```bash
# Beispiel: Zahnarztpraxis mit 2 Standorten
php artisan onboarding:automated \
  --name="Zahnarztpraxis Dr. Müller" \
  --industry=medical \
  --branches=2 \
  --email=info@zahnarzt-mueller.de \
  --phone=+493012345678
```

**Was wird automatisch erstellt:**
- ✅ Firma mit korrekten Einstellungen
- ✅ 2 Filialen (Hauptpraxis & Zweigstelle)
- ✅ Typische Services (Kontrolluntersuchung, Zahnreinigung, etc.)
- ✅ 3 Mitarbeiter pro Filiale
- ✅ Arbeitszeiten (Mo-Fr 8-18 Uhr)
- ✅ Retell.ai Agent mit medizinischem Prompt
- ✅ Test-Telefonnummer

## 📝 Manuelles Setup (Detailliert)

### Schritt 1: Firma anlegen

1. **Im Admin Panel**
   ```
   Admin → Täglicher Betrieb → Companies → Neu
   ```

2. **Pflichtfelder ausfüllen:**
   - **Name**: Zahnarztpraxis Dr. Müller
   - **Email**: info@zahnarzt-mueller.de
   - **Telefon**: +493012345678
   - **Subscription Status**: trial
   - **Timezone**: Europe/Berlin

### Schritt 2: Filialen einrichten

1. **Hauptfiliale erstellen**
   ```
   Admin → Unternehmensstruktur → Branches → Neu
   ```
   - **Name**: Hauptpraxis Berlin-Mitte
   - **Adresse**: Friedrichstr. 123, 10117 Berlin
   - **Telefon**: +493012345678
   - **Email**: mitte@zahnarzt-mueller.de

2. **Arbeitszeiten definieren**
   ```json
   {
     "monday": {"start": "08:00", "end": "18:00"},
     "tuesday": {"start": "08:00", "end": "18:00"},
     "wednesday": {"start": "08:00", "end": "18:00"},
     "thursday": {"start": "08:00", "end": "20:00"},
     "friday": {"start": "08:00", "end": "16:00"},
     "saturday": {"closed": true},
     "sunday": {"closed": true}
   }
   ```

### Schritt 3: Services anlegen

**Typische Services für Zahnarztpraxis:**

1. **Kontrolluntersuchung**
   - Dauer: 30 Minuten
   - Preis: 50€

2. **Professionelle Zahnreinigung**
   - Dauer: 60 Minuten
   - Preis: 80€

3. **Füllungstherapie**
   - Dauer: 45 Minuten
   - Preis: 120€

### Schritt 4: Mitarbeiter anlegen

```
Admin → Unternehmensstruktur → Staff → Neu
```

**Beispiel-Mitarbeiter:**
1. Dr. Müller (Zahnarzt)
2. Dr. Schmidt (Zahnärztin)
3. Fr. Weber (Prophylaxe)

### Schritt 5: Retell.ai Agent konfigurieren

1. **Agent erstellen**
   ```
   Admin → Einrichtung → Retell Configuration → Create Agent
   ```

2. **Prompt Template verwenden**
   ```
   Sie sind die freundliche Empfangskraft der Zahnarztpraxis Dr. Müller.
   Ihre Aufgabe ist es, Termine für Patienten zu vereinbaren.
   
   Verfügbare Leistungen:
   - Kontrolluntersuchung (30 Min)
   - Professionelle Zahnreinigung (60 Min)
   - Füllungstherapie (45 Min)
   
   Fragen Sie höflich nach:
   1. Name des Patienten
   2. Telefonnummer
   3. Gewünschte Behandlung
   4. Bevorzugter Termin
   ```

3. **Custom Functions aktivieren**
   - ✅ appointment_booking
   - ✅ check_availability
   - ✅ customer_identification

### Schritt 6: Telefonnummer einrichten

1. **Nummer kaufen** (falls noch nicht vorhanden)
   ```
   Admin → Phone Numbers → Buy Number
   ```

2. **Nummer zuweisen**
   - Branch auswählen
   - Retell Agent verknüpfen
   - Aktivieren

### Schritt 7: Quick Setup Wizard ausführen

Nach der Basis-Einrichtung:
```
Admin → Companies → [Ihre Firma] → Quick Setup
```

Der Wizard führt durch:
1. ✅ Cal.com Integration
2. ✅ Retell.ai Verknüpfung
3. ✅ Test-Anruf
4. ✅ Erste Termine

## 🧪 Testen & Verifizieren

### 1. Preflight Check
```bash
php artisan preflight:check --company="Zahnarztpraxis Dr. Müller"
```

### 2. Test-Anruf durchführen
```bash
php artisan retell:test-call --phone=+493012345678
```

### 3. Monitoring prüfen
```
Admin → System → System Monitoring
```

Prüfen Sie:
- ✅ API Status (grün)
- ✅ Active Phone Numbers
- ✅ Queue Status

## 📊 Wichtige Einstellungen

### Retell.ai Konfiguration
```yaml
voice_id: "11labs-Rachel"
language: "de-DE"
webhook_url: "https://api.askproai.de/api/retell/webhook"
end_call_after_silence_ms: 10000
enable_backchannel: true
```

### Cal.com Event Types
- Kontrolluntersuchung → 30min_checkup
- Zahnreinigung → 60min_cleaning
- Füllungstherapie → 45min_filling

### Arbeitszeiten-Sonderfälle
```json
{
  "breaks": [
    {"start": "12:00", "end": "13:00", "days": ["monday", "tuesday", "wednesday", "thursday", "friday"]}
  ],
  "holidays": [
    {"date": "2025-12-24", "name": "Heiligabend"},
    {"date": "2025-12-25", "name": "1. Weihnachtstag"}
  ]
}
```

## 🚨 Häufige Fehler vermeiden

### ❌ Fehler 1: Keine Services zugewiesen
**Lösung**: Mindestens einen Service pro Mitarbeiter zuweisen

### ❌ Fehler 2: Retell Agent nicht aktiviert
**Lösung**: "Activate" Button im Agent Editor klicken

### ❌ Fehler 3: Keine Arbeitszeiten
**Lösung**: Working Hours für Branch UND Staff definieren

### ❌ Fehler 4: Falsche Timezone
**Lösung**: Immer "Europe/Berlin" für deutsche Firmen

## 📞 Erster Kundenanruf

Nach erfolgreicher Einrichtung:

1. **Kunde ruft an**: +493012345678
2. **AI begrüßt**: "Zahnarztpraxis Dr. Müller, guten Tag..."
3. **Kunde**: "Ich möchte einen Termin für eine Kontrolluntersuchung"
4. **AI**: "Gerne. Darf ich nach Ihrem Namen fragen?"
5. **Termin wird gebucht** und erscheint in:
   - Admin → Appointments
   - Cal.com Kalender
   - Email-Bestätigung

## 📈 Nach der Einrichtung

### Tägliche Überwachung
- Failed Calls prüfen
- Appointment Conversion Rate
- No-Show Rate

### Optimierungen
- Prompt anpassen basierend auf Feedback
- Services verfeinern
- Arbeitszeiten optimieren

### Erweiterte Features
- SMS-Erinnerungen aktivieren
- WhatsApp Integration
- Online-Buchungsportal

## 🆘 Support

Bei Problemen:
1. **System Monitoring** Dashboard prüfen
2. **Logs** durchsuchen: `tail -f storage/logs/laravel.log`
3. **Debug Command**: `php artisan debug:company "Zahnarztpraxis Dr. Müller"`

---

**Tipp**: Nutzen Sie das automatisierte Setup für neue Firmen - es spart 30+ Minuten Konfigurationszeit!