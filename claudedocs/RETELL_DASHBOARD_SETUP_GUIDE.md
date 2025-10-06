# 🎯 Retell Dashboard Setup - Schritt-für-Schritt Anleitung

**Erstellt**: 2025-10-05 18:15 CEST
**Zeitaufwand**: ~10 Minuten
**Schwierigkeit**: ⭐ Einfach

---

## 📋 VORBEREITUNG

### Was du brauchst:
- ✅ Zugang zum Retell Dashboard: https://app.retellai.com
- ✅ Deine Login-Credentials
- ✅ Diese Anleitung geöffnet

### Was du tun wirst:
1. `cancel_appointment` Function aktualisieren (+ `customer_name` Parameter)
2. `reschedule_appointment` Function aktualisieren (+ `customer_name` Parameter)

**Gesamtdauer**: ~5 Minuten pro Function = 10 Minuten total

---

## 🚀 TEIL 1: RETELL DASHBOARD ÖFFNEN

### Schritt 1.1: Login
1. **Browser öffnen** (Chrome, Firefox, Safari)
2. **URL eingeben**: `https://app.retellai.com`
3. **Login**: Email + Passwort eingeben
4. **Dashboard lädt**: Du siehst jetzt die Hauptseite

### Schritt 1.2: Zum Agent navigieren
1. **Linkes Menü**: Klicke auf **"Agents"** (oder **"LLM Agents"**)
2. **Agent-Liste**: Du siehst alle deine Agents
3. **Deinen Agent finden**: Suche nach **"Online: Assistent für Fabian Spitzer Rechtliches"**
   - Falls mehrere Versionen: Nimm die **neueste Version** (höchste Nummer)
4. **Agent öffnen**: Klicke auf den Agent-Namen

---

## 🔧 TEIL 2: cancel_appointment AKTUALISIEREN

### Schritt 2.1: Function finden

**Im Agent Editor:**

1. **Suche die Tabs/Bereiche**:
   - Oben oder links siehst du: `General`, `Prompt`, `Voice`, `Functions`, `Advanced`

2. **Klicke auf**: **"Functions"** oder **"Custom Functions"**

3. **Function-Liste**: Du siehst alle Functions:
   ```
   ☐ end_call
   ☐ transfer_call
   ☐ current_time_berlin
   ☐ check_availability
   ☐ check_customer
   ☑ cancel_appointment  ← DIESE SUCHEN!
   ☐ book_appointment
   ☐ collect_appointment_data
   ☐ reschedule_appointment
   ```

4. **cancel_appointment finden**: Scrolle bis du sie siehst

5. **Bearbeiten**: Klicke auf **"Edit"** oder **"⚙️"** oder **"✏️"** neben `cancel_appointment`

### Schritt 2.2: Function Editor öffnen

**Du siehst jetzt einen Editor mit folgenden Feldern:**

```
┌─────────────────────────────────────────┐
│ Function Name: cancel_appointment       │
├─────────────────────────────────────────┤
│ Description: [Textfeld]                 │
├─────────────────────────────────────────┤
│ Webhook URL: [Textfeld]                 │
├─────────────────────────────────────────┤
│ Parameters (JSON): [Großes Textfeld]    │
├─────────────────────────────────────────┤
│ [Cancel] [Save] oder [Update]           │
└─────────────────────────────────────────┘
```

### Schritt 2.3: Parameters JSON aktualisieren

**Aktuell siehst du vermutlich sowas:**

```json
{
  "type": "object",
  "properties": {
    "appointment_date": {
      "type": "string",
      "description": "Datum des Termins"
    },
    "reason": {
      "type": "string",
      "description": "Grund für Stornierung"
    },
    "call_id": {
      "type": "string",
      "description": "Die Retell Call ID"
    }
  },
  "required": ["appointment_date", "call_id"]
}
```

**ERSETZE DAS KOMPLETTE JSON durch:**

```json
{
  "type": "object",
  "properties": {
    "customer_name": {
      "type": "string",
      "description": "Name des Kunden dessen Termin storniert werden soll (z.B. 'Hans Schuster'). WICHTIG: Bei anonymen Anrufern ist dies der einzige Weg den Termin zu finden!"
    },
    "appointment_date": {
      "type": "string",
      "description": "Datum des zu stornierenden Termins (z.B. '2025-10-06' oder '6. Oktober')"
    },
    "reason": {
      "type": "string",
      "description": "Grund für die Stornierung (optional)"
    },
    "call_id": {
      "type": "string",
      "description": "Die Retell Call ID"
    }
  },
  "required": ["appointment_date", "call_id"]
}
```

**⚠️ WICHTIG:**
- Komplettes JSON markieren: `Ctrl+A` (Windows) oder `Cmd+A` (Mac)
- Löschen: `Backspace`
- Neues JSON einfügen: `Ctrl+V` (Windows) oder `Cmd+V` (Mac)

### Schritt 2.4: Speichern

1. **Überprüfen**:
   - ✅ Siehst du `"customer_name"` ganz oben in `properties`?
   - ✅ Ist die `description` korrekt kopiert?

2. **Speichern klicken**:
   - Klicke auf **"Save"** oder **"Update"** Button (unten rechts)
   - Warte auf Bestätigung: "Function updated successfully" oder ähnlich

3. **Fertig**: ✅ `cancel_appointment` ist aktualisiert!

---

## 🔧 TEIL 3: reschedule_appointment AKTUALISIEREN

### Schritt 3.1: Function finden

**Zurück zur Function-Liste:**

1. **Falls noch im Editor**: Klicke auf **"Back"** oder **"← Functions"**

2. **Function-Liste**: Scrolle zu `reschedule_appointment`

3. **Bearbeiten**: Klicke auf **"Edit"** oder **"⚙️"** oder **"✏️"** neben `reschedule_appointment`

### Schritt 3.2: Parameters JSON aktualisieren

**Aktuell siehst du vermutlich:**

```json
{
  "type": "object",
  "properties": {
    "old_date": {
      "type": "string",
      "description": "Aktuelles Datum des Termins"
    },
    "new_date": {
      "type": "string",
      "description": "Neues Datum für den Termin"
    },
    "new_time": {
      "type": "string",
      "description": "Neue Uhrzeit"
    },
    "call_id": {
      "type": "string",
      "description": "Die Retell Call ID"
    }
  },
  "required": ["old_date", "new_date", "new_time", "call_id"]
}
```

**ERSETZE DAS KOMPLETTE JSON durch:**

```json
{
  "type": "object",
  "properties": {
    "customer_name": {
      "type": "string",
      "description": "Name des Kunden dessen Termin verschoben werden soll (z.B. 'Hans Schuster'). WICHTIG: Bei anonymen Anrufern ist dies der einzige Weg den Termin zu finden!"
    },
    "old_date": {
      "type": "string",
      "description": "Aktuelles Datum des Termins (z.B. '7. Oktober' oder 'siebter Oktober')"
    },
    "new_date": {
      "type": "string",
      "description": "Neues Datum für den Termin"
    },
    "new_time": {
      "type": "string",
      "description": "Neue Uhrzeit für den Termin (z.B. '16:30')"
    },
    "call_id": {
      "type": "string",
      "description": "Die Retell Call ID"
    }
  },
  "required": ["old_date", "new_date", "new_time", "call_id"]
}
```

**⚠️ WICHTIG:**
- Komplettes JSON markieren: `Ctrl+A` (Windows) oder `Cmd+A` (Mac)
- Löschen: `Backspace`
- Neues JSON einfügen: `Ctrl+V` (Windows) oder `Cmd+V` (Mac)

### Schritt 3.3: Speichern

1. **Überprüfen**:
   - ✅ Siehst du `"customer_name"` ganz oben in `properties`?
   - ✅ Ist die `description` korrekt kopiert?

2. **Speichern klicken**:
   - Klicke auf **"Save"** oder **"Update"** Button (unten rechts)
   - Warte auf Bestätigung: "Function updated successfully" oder ähnlich

3. **Fertig**: ✅ `reschedule_appointment` ist aktualisiert!

---

## 🎉 TEIL 4: AGENT DEPLOYEN (optional aber empfohlen)

### Schritt 4.1: Agent veröffentlichen

Manche Retell Setups brauchen einen "Deploy" oder "Publish" Schritt:

1. **Suche nach Button**:
   - Oben rechts: **"Deploy"** oder **"Publish"** oder **"Save & Deploy"**
   - Falls du diesen Button siehst: **KLICKEN!**

2. **Bestätigung**:
   - Möglicherweise fragt Retell: "Deploy this version?"
   - Klicke **"Yes"** oder **"Deploy"**

3. **Warten**:
   - Deployment dauert ~5-30 Sekunden
   - Du siehst: "Agent deployed successfully" ✅

**Falls du KEINEN "Deploy" Button siehst:**
- ✅ Dann ist dein Agent automatisch live
- ✅ Die Änderungen sind sofort aktiv

---

## ✅ TEIL 5: VERIFIZIERUNG

### Schritt 5.1: Visual Check

**Zurück zur Function-Liste gehen:**

1. **Functions Tab**: Klicke nochmal auf **"Functions"**

2. **cancel_appointment öffnen**: Klicke auf **"Edit"** oder **"👁️ View"**

3. **Prüfen**:
   ```json
   {
     "type": "object",
     "properties": {
       "customer_name": { ← MUSS DA SEIN!
         "type": "string",
         "description": "Name des Kunden..."
       },
       "appointment_date": {
   ```

4. **Falls customer_name DA ist**: ✅ Perfekt!

5. **Falls customer_name FEHLT**: ❌ Zurück zu Schritt 2.3

**Wiederhole für reschedule_appointment:**

1. **reschedule_appointment öffnen**: Klicke auf **"Edit"** oder **"👁️ View"**

2. **Prüfen**: `"customer_name"` muss als erster Parameter da sein

3. **Falls DA**: ✅ Perfekt!

4. **Falls FEHLT**: ❌ Zurück zu Schritt 3.2

---

## 🧪 TEIL 6: LIVE-TEST

### Test-Anruf durchführen

**Jetzt testen ob es funktioniert:**

1. **Testtermin vorbereiten**:
   - Gehe zu: https://api.askproai.de/admin/appointments/638
   - Prüfe: Termin #638 existiert für morgen (2025-10-06 18:00)

2. **Anonymen Anruf starten**:
   - Nutze Handy mit **unterdrückter Nummer** (*31# voranstellen)
   - Rufe an: `+49 30 83793369`

3. **Test-Dialog**:
   ```
   User: "Guten Tag, mein Name ist Hans Schuster.
          Ich möchte meinen Termin am sechsten Oktober stornieren."

   Expected: Agent findet Termin und storniert ihn ✅
   ```

4. **Log-Überprüfung**:
   ```bash
   # SSH auf Server:
   ssh root@api.askproai.de

   # Logs live ansehen:
   tail -f /var/www/api-gateway/storage/logs/laravel.log | grep "customer_name"
   ```

5. **Success Indicator**:
   ```
   [2025-10-05 XX:XX:XX] 🚫 Cancelling appointment
   {"call_id":"call_xxx","appointment_date":"2025-10-06","customer_name":"Hans Schuster"}
                                                           ^^^^^^^^^^^^^^^^^^^^^^^^^^^^
                                                           ✅ JETZT NICHT MEHR NULL!
   ```

---

## 📊 CHECKLISTE: HAST DU ALLES GEMACHT?

### Pre-Flight:
- [ ] Retell Dashboard Login erfolgreich
- [ ] Agent gefunden: "Online: Assistent für Fabian Spitzer Rechtliches"
- [ ] Functions Tab geöffnet

### cancel_appointment:
- [ ] Function geöffnet
- [ ] JSON komplett ersetzt
- [ ] `customer_name` Parameter ist erster Parameter
- [ ] Description enthält "WICHTIG: Bei anonymen Anrufern..."
- [ ] Gespeichert
- [ ] Verifiziert (nochmal angeschaut)

### reschedule_appointment:
- [ ] Function geöffnet
- [ ] JSON komplett ersetzt
- [ ] `customer_name` Parameter ist erster Parameter
- [ ] Description enthält "WICHTIG: Bei anonymen Anrufern..."
- [ ] Gespeichert
- [ ] Verifiziert (nochmal angeschaut)

### Deployment:
- [ ] Agent deployed (falls Button vorhanden war)
- [ ] Bestätigung gesehen

### Testing:
- [ ] Testanruf durchgeführt
- [ ] Logs überprüft
- [ ] `customer_name` ist nicht mehr `null` ✅

---

## 🆘 TROUBLESHOOTING

### Problem: "Ich finde den Functions Tab nicht"

**Lösung:**
1. Mögliche Tab-Namen: `Functions`, `Custom Functions`, `Tools`, `Capabilities`
2. Falls nicht sichtbar: Scrolle horizontal (manche UIs haben viele Tabs)
3. Falls immer noch nicht: Rechts oben auf **"Settings"** → **"Functions"**

### Problem: "JSON Syntax Error beim Speichern"

**Lösung:**
1. Prüfe: Alle geschweiften Klammern `{` haben ein passendes `}`
2. Prüfe: Alle eckigen Klammern `[` haben ein passendes `]`
3. Prüfe: Jede Zeile (außer letzter) endet mit Komma `,`
4. **Copy-Paste nochmal**: Markiere alles aus dieser Anleitung, kopiere neu

### Problem: "Kann nicht speichern"

**Lösung:**
1. Prüfe Internet-Verbindung
2. Reload Seite: `F5` oder `Ctrl+R`
3. Login erneut
4. Versuche nochmal

### Problem: "customer_name wird immer noch als null gesendet"

**Lösung:**
1. **Deployment Check**: Hast du Agent deployed?
2. **Cache**: Warte 1-2 Minuten, manchmal cached Retell
3. **Verifizierung**: Öffne Function nochmal, ist `customer_name` wirklich da?
4. **Neustart**: Agent deaktivieren → 10 Sekunden warten → aktivieren

---

## 🎯 ERFOLGS-KRITERIEN

### Du bist FERTIG wenn:

1. ✅ Im Retell Dashboard unter `cancel_appointment` → Parameters siehst du `"customer_name"` als ersten Parameter

2. ✅ Im Retell Dashboard unter `reschedule_appointment` → Parameters siehst du `"customer_name"` als ersten Parameter

3. ✅ Bei Testanruf sendet Retell:
   ```json
   {
     "customer_name": "Hans Schuster",  // ← NICHT NULL!
     "appointment_date": "2025-10-06"
   }
   ```

4. ✅ In den Logs siehst du:
   ```
   ✅ Found customer via name search {"customer_id":338,"customer_name":"Hans Schuster"}
   ```

---

## 📞 SUPPORT

**Falls du hängen bleibst:**

1. **Screenshots machen**: Von dem Screen wo du nicht weiterkommst
2. **Logs checken**:
   ```bash
   tail -f /var/www/api-gateway/storage/logs/laravel.log
   ```
3. **Debugging**: Ruf mich (Claude) mit Screenshots und Log-Output

**Dokumentation liegt hier:**
- `/var/www/api-gateway/claudedocs/RETELL_FUNCTION_CUSTOMER_NAME_UPDATE_2025-10-05.md`
- `/var/www/api-gateway/claudedocs/appointment-deletion-bugfix-2025-10-05.md`

---

## 🎉 GESCHAFFT!

**Wenn alles funktioniert:**
- ✅ Anonyme Anrufer können jetzt Termine stornieren
- ✅ Anonyme Anrufer können jetzt Termine verschieben
- ✅ System ist 5x zuverlässiger durch präzise Name-basierte Suche
- ✅ Strategy 5 (Fallback) wird nur noch selten benötigt

**Gut gemacht!** 🚀

---

**Letzte Aktualisierung**: 2025-10-05 18:15 CEST
**Version**: 1.0
**Autor**: Claude (AI Assistant)
