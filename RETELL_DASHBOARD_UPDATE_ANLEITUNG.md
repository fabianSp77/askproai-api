# 🚀 RETELL DASHBOARD UPDATE ANLEITUNG
**Datum:** 2025-10-07
**Version:** V2 mit Auto-Initialisierung

## 📋 ÜBERSICHT DER ÄNDERUNGEN

### ✅ NEUE FEATURES
1. **Auto-Zeitabfrage** - Agent ruft automatisch aktuelle Zeit/Datum ab
2. **Auto-Kundencheck** - Agent prüft automatisch ob Kunde bekannt ist
3. **Kontext-Nutzung** - Agent verwendet Kundendaten & Termine im Gespräch
4. **Optimierte Gesprächsführung** - Weniger Wiederholungen, natürlicherer Flow

### 🔧 KRITISCHE FIXES
- ❌ **PROBLEM:** Agent hatte KEINEN general_prompt im Dashboard
- ❌ **PROBLEM:** query_appointment Function nicht registriert
- ❌ **PROBLEM:** Bestehende Termine wurden ignoriert
- ❌ **PROBLEM:** 66.7% der Calls hatten repetitive Fragen

---

## 🎯 SCHRITT-FÜR-SCHRITT ANLEITUNG

### SCHRITT 1: Retell Dashboard öffnen
1. Gehe zu: https://beta.retellai.com/dashboard
2. Login mit deinen Credentials
3. Navigiere zu: **Agents** → **Online: Assistent für Fabian Spitzer Rechtliches/V33**
4. Agent ID: `agent_9a8202a740cd3120d96fcfda1e`

---

### SCHRITT 2: General Prompt hinzufügen

**WICHTIG:** Der Agent hat aktuell KEINEN Prompt! Das muss dringend behoben werden.

1. Klicke auf **"Edit Agent"** oder **"Settings"**
2. Suche das Feld: **"General Prompt"** oder **"System Prompt"**
3. **Kopiere** den kompletten Inhalt aus: `/var/www/api-gateway/retell_general_prompt_v2.md`

**📄 DATEI LESEN:**
```bash
cat /var/www/api-gateway/retell_general_prompt_v2.md
```

4. **Paste** den gesamten Text in das General Prompt Feld
5. **NICHT SPEICHERN** - erst alle Änderungen machen!

---

### SCHRITT 3: Begin Message aktualisieren

**Aktueller Status:** "NOT SET" (leer)

**NEUE BEGIN MESSAGE:**
```
Willkommen bei Ask Pro AI, Ihr Spezialist für KI-Telefonassistenten. Möchten Sie einen Termin mit Fabian Spitzer buchen oder haben Sie eine andere Frage?
```

**HINWEIS:** Diese Message sieht der Kunde NICHT direkt. Der Agent nutzt sie zur Initialisierung und greift dann personalisiert.

**Alternative (wenn Retell begin_message für interne Instructions nutzt):**
```
INITIALISIERUNG: Rufe sofort current_time_berlin und check_customer auf, dann begrüße kontextbezogen.
```

**EMPFEHLUNG:** Nutze die erste Variante (kundenfreundlich).

---

### SCHRITT 4: query_appointment Function registrieren

**KRITISCH:** Diese Function existiert im Code, ist aber NICHT im Agent registriert!

**Option A: JSON Upload (Empfohlen)**
1. Gehe zu: **Functions** oder **Tools**  im Retell Dashboard
2. Klicke: **"Add Custom Function"** oder **"Upload JSON"**
3. Öffne Datei: `https://api.askproai.de/retell-params.html`
4. Kopiere die Parameter-JSON (bereits optimiert ohne Control Characters!)
5. Paste in Retell Function Editor

**Felder ausfüllen:**
- **Name:** `query_appointment`
- **Description:** `Findet einen bestehenden Termin fuer den Anrufer. Nutze diese Funktion wenn der Kunde fragt Wann ist mein Termin, Um wie viel Uhr habe ich gebucht oder Informationen ueber einen gebuchten Termin haben moechte. WICHTIG: Diese Funktion funktioniert NUR wenn die Telefonnummer des Anrufers uebertragen wurde, nicht bei unterdrueckter Nummer.`
- **URL:** `https://api.askproai.de/api/retell/function-call`
- **Method:** `POST`
- **Parameters:** (Kopiere von https://api.askproai.de/retell-params.html - MAXIMALE VERSION)
- **Execution Message:** `Ich suche Ihren Termin`
- **Timeout:** `30000` (30 Sekunden)
- **Speak During Execution:** `true`
- **Speak After Execution:** `false`

**Response Variables:**
```json
{
  "success": "$.success",
  "error": "$.error",
  "message": "$.message",
  "requires_phone_number": "$.requires_phone_number",
  "appointment_count": "$.appointment_count",
  "appointment_id": "$.appointment.id",
  "appointment_date": "$.appointment.date",
  "appointment_time": "$.appointment.time",
  "service_name": "$.appointment.service",
  "staff_name": "$.appointment.staff"
}
```

---

### SCHRITT 5: Alle Functions überprüfen

**Stelle sicher, dass folgende Functions AKTIV sind:**

✅ **current_time_berlin** - Zeitabfrage
- URL: `https://api.askproai.de/api/zeitinfo?locale=de`
- Method: `GET`
- Description: "Liefert aktuelles Datum, Uhrzeit und Wochentag in deutscher Zeit"

✅ **check_customer** - Kundencheck
- URL: `https://api.askproai.de/api/retell/check-customer`
- Method: `POST`
- Parameters: `{"call_id": "{{call_id}}"}`
- Description: "Pruefe ob ein Kunde im System existiert. MUSS IMMER zu Beginn des Gespraechs aufgerufen werden!"

✅ **collect_appointment_data** - Termin buchen
✅ **reschedule_appointment** - Termin verschieben
✅ **cancel_appointment** - Termin stornieren
✅ **query_appointment** - Termin abfragen (NEU!)

---

### SCHRITT 6: Agent Settings überprüfen

**Voice Settings:**
- Voice ID: `custom_voice_191b11197fd8c3e92dab972a5a`
- Voice Model: `eleven_turbo_v2_5`
- Temperature: `0.2`
- Speed: `1.06`
- Volume: `1.0`

**Conversation Settings:**
- Interruption Sensitivity: `0.6`
- Responsiveness: `0.9`
- Enable Backchannel: `true`
- Backchannel Frequency: `0.2`

**LLM Settings:**
- Model: `gemini-2.0-flash`
- Temperature: `0.04`
- Tool Call Strict Mode: `false`

**Timeouts:**
- End Call After Silence: `50000ms` (50 Sekunden)
- Max Call Duration: `300000ms` (5 Minuten)

---

### SCHRITT 7: SPEICHERN & PUBLISHEN

1. Klicke: **"Save Draft"**
2. Überprüfe alle Änderungen nochmal
3. Klicke: **"Publish"** oder **"Deploy"**
4. Warte auf Bestätigung: "Agent successfully published"

---

## 🧪 TESTING NACH DEPLOYMENT

### Test 1: Auto-Initialisierung (Bekannter Kunde)
**Setup:** Rufe von einer registrierten Nummer an (z.B. +4915112345678)

**Erwartetes Verhalten:**
1. Agent ruft `current_time_berlin` auf (unsichtbar)
2. Agent ruft `check_customer` auf (unsichtbar)
3. Agent begrüßt: "Guten [Morgen/Tag/Abend], Herr [Nachname]! Schön, dass Sie anrufen."

**Validierung:**
✅ Zeitbasierte Begrüßung (Morgen/Tag/Abend korrekt)
✅ Kundenname wird verwendet
✅ Natürlicher, personalisierter Ton

---

### Test 2: Terminabfrage (query_appointment)
**Setup:** Sage: "Wann ist mein Termin?"

**Erwartetes Verhalten:**
1. Agent ruft `query_appointment(call_id={{call_id}})` auf
2. Agent antwortet: "Ihr Termin ist am [Datum] um [Uhrzeit]."
3. Agent fragt: "Kann ich sonst noch etwas für Sie tun?"

**Validierung:**
✅ KEINE Stille (wie bei Call 691)
✅ Termin wird korrekt vorgelesen
✅ Kein erneutes Fragen nach Telefonnummer

---

### Test 3: Termin buchen (Bekannter Kunde mit bestehendem Termin)
**Setup:** Sage: "Ich möchte einen Termin buchen."

**Erwartetes Verhalten:**
1. Agent sagt: "Herr [Name], ich sehe Sie haben einen Termin am [Datum] um [Uhrzeit]. Möchten Sie diesen verschieben oder einen weiteren Termin buchen?"
2. Agent nutzt den BESTEHENDEN Termin im Gespräch

**Validierung:**
✅ Agent erwähnt bestehenden Termin (NICHT ignorieren!)
✅ Agent bietet Optionen (verschieben oder neu buchen)
✅ Keine redundanten Fragen

---

### Test 4: Unbekannter Kunde
**Setup:** Rufe von NEUER Nummer an

**Erwartetes Verhalten:**
1. Agent: "Guten [Morgen/Tag/Abend]! Sie erreichen Ask Pro AI. Mit wem spreche ich?"
2. Agent fragt nach Name (normal für unbekannte Kunden)

**Validierung:**
✅ Keine Namensverwendung (Kunde ist unbekannt)
✅ Höfliche Nachfrage nach Identifikation
✅ Zeitbasierte Begrüßung funktioniert

---

### Test 5: Anonymer Anruf (unterdrückte Nummer)
**Setup:** Rufe mit unterdrückter Nummer an

**Erwartetes Verhalten:**
1. Agent: "Guten [Morgen/Tag/Abend]! Hier ist Ask Pro AI. Wie kann ich helfen?"
2. Wenn Terminabfrage: "Aus Sicherheitsgründen benötige ich Ihre Telefonnummer. Bitte rufen Sie ohne Rufnummernunterdrückung an."

**Validierung:**
✅ Keine Kundenabfrage (keine Nummer verfügbar)
✅ Höfliche Ablehnung bei Terminabfrage
✅ Alternative wird angeboten

---

## 📊 ERWARTETE VERBESSERUNGEN

**Vor Update:**
- ❌ 80% der Calls hatten Qualitätsprobleme
- ❌ 66.7% hatten repetitive Fragen
- ❌ 40% excessive confirmations
- ❌ 13.3% ignorierten bestehende Termine
- ❌ 0% Auto-Initialisierung

**Nach Update:**
- ✅ <20% Qualitätsprobleme (Ziel: <15%)
- ✅ <10% repetitive Fragen
- ✅ <25% confirmations
- ✅ 100% Termin-Nutzung
- ✅ 100% Auto-Initialisierung

---

## 🚨 TROUBLESHOOTING

### Problem: Agent begrüßt nicht mit Namen
**Check:**
1. Ist `check_customer` Function aktiv?
2. Wird Function zu Beginn aufgerufen? (Check Logs)
3. Ist Telefonnummer im System registriert?

### Problem: query_appointment führt zu Stille
**Check:**
1. Ist Function in Retell Dashboard registriert?
2. Sind Response Variables korrekt gemappt?
3. Backend Logs prüfen: `/var/www/api-gateway/storage/logs/laravel.log`

### Problem: Wiederholte Fragen nach Name
**Check:**
1. Ist General Prompt korrekt hochgeladen?
2. Steht "KEINE WIEDERHOLUNGEN" Sektion im Prompt?
3. LLM Temperature zu hoch? (sollte 0.04 sein)

### Problem: Keine zeitbasierte Begrüßung
**Check:**
1. Ist `current_time_berlin` Function aktiv?
2. URL korrekt: `https://api.askproai.de/api/zeitinfo?locale=de`
3. API erreichbar? Test: `curl https://api.askproai.de/api/zeitinfo?locale=de`

---

## 📞 SUPPORT

Bei Problemen:
1. **Logs prüfen:** `/var/www/api-gateway/storage/logs/laravel.log`
2. **API testen:** `https://api.askproai.de/test-checklist`
3. **Call Details:** `https://api.askproai.de/admin/calls`

---

## ✅ DEPLOYMENT CHECKLIST

Vor Go-Live alles abhaken:

```
□ General Prompt hochgeladen (280 Zeilen aus retell_general_prompt_v2.md)
□ Begin Message aktualisiert
□ query_appointment Function registriert
□ current_time_berlin Function aktiv
□ check_customer Function aktiv
□ collect_appointment_data Function aktiv
□ reschedule_appointment Function aktiv
□ cancel_appointment Function aktiv
□ Agent Settings überprüft (Voice, LLM, Timeouts)
□ GESPEICHERT & PUBLISHED
□ Test 1: Bekannter Kunde - PASSED
□ Test 2: Terminabfrage - PASSED
□ Test 3: Termin buchen mit bestehendem Termin - PASSED
□ Test 4: Unbekannter Kunde - PASSED
□ Test 5: Anonymer Anruf - PASSED
```

**READY TO GO LIVE!** 🚀
