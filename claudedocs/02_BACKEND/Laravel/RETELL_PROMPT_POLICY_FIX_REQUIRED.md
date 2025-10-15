# Retell Prompt - Policy Kommunikation Fix
**Datum:** 2025-10-13
**Priorität:** 🔴 HOCH
**Status:** ⚠️ ERFORDERLICH

---

## 🎯 PROBLEM

Der Retell Agent **kommuniziert falsche Gebühren** an Kunden!

### Aktueller Zustand

**Was der Code macht:** ✅ **KORREKT**
```
Retell Agent → AppointmentPolicyEngine → PolicyConfigurationService → Datenbank
```
→ System liest und validiert Policies korrekt aus der Datenbank

**Was der Agent SAGT:** ❌ **FALSCH**
```
Agent: "Die Umbuchung kostet 10€ wenn Sie 24-48h vorher umbuchen"
```
→ Agent kommuniziert hardcodierte Werte aus dem Prompt, nicht die echten Policies!

---

## 📊 DISKREPANZ

### Was im Prompt steht (FALSCH):

**Datei:** `RETELL_PROMPT_V78_FINAL.txt` Zeilen 214-244

```
GEBÜHREN (Du berechnest!):
>48h → Kostenlos
24-48h → 10€
<24h → 15€

24-STUNDEN-REGEL (Du prüfst!):
Wenn >=24h: Storniere
Wenn <24h: Ablehnen, Verschiebung anbieten
```

### Was tatsächlich gilt (Datenbank):

**Company Policy #15 (Stornierung):**
```json
{
  "hours_before": 24,
  "max_cancellations_per_month": 5,
  "fee_percentage": 0   ← KEINE GEBÜHR!
}
```

**Company Policy #16 (Umbuchung):**
```json
{
  "hours_before": 1,    ← NUR 1h VORLAUF!
  "max_reschedules_per_appointment": 3,
  "fee_percentage": 0   ← KEINE GEBÜHR!
}
```

---

## 🚨 IMPACT

### Kundenverwirrung

**Szenario 1:**
```
Kunde: "Ich möchte meinen Termin verschieben"
Agent: "Kein Problem. Da Sie mehr als 48h vorher umbuchen, ist das kostenlos."
        ↑ FALSCH! Echte Policy: 1h Vorlauf, immer kostenlos
```

**Szenario 2:**
```
Kunde: "Ich möchte 30h vorher stornieren"
Agent: "Das kostet 10€ da Sie zwischen 24-48h vorher stornieren."
        ↑ FALSCH! Echte Policy: 0€ Gebühr

System validiert: ✅ Erlaubt, 0€ Gebühr
Agent hat gesagt: ❌ 10€ Gebühr
Kunde: 🤔 Verwirrung!
```

---

## ✅ LÖSUNG

### Option A: Prompt komplett entfernen (EMPFOHLEN)

**VORHER (Zeilen 214-244):**
```
🔄 FUNCTION: reschedule_appointment

GEBÜHREN (Du berechnest!):
>48h → Kostenlos
24-48h → 10€
<24h → 15€

Kommuniziere Gebühr VORHER, dann frage nach neuem Termin!

❌ FUNCTION: cancel_appointment

24-STUNDEN-REGEL (Du prüfst!):
Wenn >=24h: Storniere
Wenn <24h: Ablehnen, Verschiebung anbieten
```

**NACHHER:**
```
🔄 FUNCTION: reschedule_appointment

GEBÜHREN:
→ System prüft automatisch die aktuellen Policy-Regeln
→ Wenn Gebühr anfällt, informiert System dich VOR der Umbuchung
→ Kommuniziere dem Kunden was System sagt
→ NIEMALS eigene Gebühren erfinden oder berechnen!

ABLAUF:
1. Rufe reschedule_appointment() auf
2. System antwortet:
   - SUCCESS: "Umgebucht, Gebühr: X€" → Kommuniziere an Kunde
   - DENIED: "Nicht erlaubt, Grund: Y" → Kommuniziere an Kunde

❌ FUNCTION: cancel_appointment

REGELN:
→ System prüft automatisch die aktuellen Policy-Regeln
→ Wenn Stornierung nicht erlaubt, informiert System dich mit Grund
→ Kommuniziere dem Kunden was System sagt
→ NIEMALS eigene Regeln erfinden (z.B. "24h-Regel")!

ABLAUF:
1. Rufe cancel_appointment() auf
2. System antwortet:
   - SUCCESS: "Storniert" → Bestätige dem Kunde
   - DENIED: "Nicht erlaubt, Grund: Y" → Erkläre und biete Alternative
     (z.B. "Verschiebung statt Stornierung?")
```

---

### Option B: Dynamische Referenz (FORTGESCHRITTEN)

**Idee:**
Agent ruft Policy-Info ab BEVOR er mit Kunde spricht.

**Prompt-Addition:**
```
VOR DEM GESPRÄCH:
1. Rufe get_policy_info(company_id={{company_id}}) auf
2. Merke dir die aktuellen Regeln:
   - cancellation_hours_before
   - cancellation_fee
   - reschedule_hours_before
   - reschedule_fee
3. Nutze DIESE Werte in der Kommunikation mit dem Kunden

BEISPIEL:
System sagt: {cancellation_hours_before: 24, cancellation_fee: 0}
→ Du sagst: "Sie können bis 24 Stunden vorher kostenfrei absagen."
```

**Vorteil:**
- Agent kommuniziert immer aktuelle Werte
- Keine hardcoded Regeln im Prompt

**Nachteil:**
- Erfordert neue Function `get_policy_info()`
- Komplexer zu implementieren

---

## 🛠️ UMSETZUNG (Option A - Empfohlen)

### Schritt 1: Prompt-Datei bearbeiten

**Datei:** `/var/www/api-gateway/RETELL_PROMPT_V78_FINAL.txt`

**Zeilen 206-244 ersetzen durch:**
```
═══════════════════════════════════════════════════════════════
🔄 FUNCTION: reschedule_appointment
═══════════════════════════════════════════════════════════════

TRIGGERS:
├─ "termin verschieben"
├─ "anderen tag"
└─ "umbuchen"

WICHTIG: System prüft automatisch alle Regeln!
→ Vorlaufzeiten
→ Maximale Umbuchungen pro Termin
→ Eventuelle Gebühren

Du musst NICHTS berechnen oder prüfen!

ABLAUF:
1. Sammle neue Wunschzeit vom Kunden
2. Rufe Function auf:

reschedule_appointment(
  call_id: {{call_id}},
  old_date: "YYYY-MM-DD",  ← Aktueller Termin
  new_date: "YYYY-MM-DD",  ← Neuer Wunschtermin
  new_time: "HH:MM"
)

3. System antwortet:
   - ✅ SUCCESS: "Umgebucht" → Bestätige dem Kunden
   - ❌ DENIED: "Grund: X" → Erkläre dem Kunden warum nicht möglich

WENN ABGELEHNT:
→ Erkläre den Grund (System gibt dir die Info)
→ Biete Alternative an (z.B. anderen Termin vorschlagen)

BEISPIEL:
User: "Ich möchte meinen Termin verschieben"
Agent: [Sammelt neue Wunschzeit]
Agent: [Ruft Function auf]
System: {"status": "denied", "reason": "Maximale Umbuchungen erreicht (3)"}
Agent: "Leider kann dieser Termin nicht mehr verschoben werden, da er bereits 3x verschoben wurde. Möchten Sie stattdessen einen neuen Termin buchen?"

═══════════════════════════════════════════════════════════════
❌ FUNCTION: cancel_appointment
═══════════════════════════════════════════════════════════════

TRIGGERS:
├─ "termin absagen"
├─ "stornieren"
└─ "termin löschen"

WICHTIG: System prüft automatisch alle Regeln!
→ Vorlaufzeiten
→ Maximale Stornierungen pro Monat
→ Eventuelle Gebühren

Du musst NICHTS berechnen oder prüfen!

ABLAUF:
1. Frage welchen Termin stornieren
2. Rufe Function auf:

cancel_appointment(
  call_id: {{call_id}},
  appointment_date: "YYYY-MM-DD"
)

3. System antwortet:
   - ✅ SUCCESS: "Storniert" → Bestätige dem Kunden
   - ❌ DENIED: "Grund: X" → Erkläre dem Kunden warum nicht möglich

WENN ABGELEHNT:
→ Erkläre den Grund (System gibt dir die Info)
→ Biete Alternative an:
  - Wenn zu kurzfristig: "Möchten Sie stattdessen verschieben?"
  - Wenn Quota erreicht: "Kann leider nicht mehr storniert werden"

BEISPIEL:
User: "Ich möchte meinen Termin morgen absagen"
Agent: [Ruft Function auf]
System: {"status": "denied", "reason": "Stornierung erfordert 24h Vorlauf"}
Agent: "Leider ist die Stornierung nicht mehr möglich, da wir 24 Stunden Vorlauf benötigen. Möchten Sie den Termin stattdessen auf einen anderen Tag verschieben?"
```

### Schritt 2: Prompt in Retell Dashboard hochladen

1. Gehe zu https://app.retellai.com/
2. Navigiere zu **Agents**
3. Finde Agent: **"Online: Assistent für Fabian Spitzer Rechtliches/V33"**
4. Kopiere den KOMPLETTEN neuen Prompt (mit obigen Änderungen)
5. Einfügen in Agent Prompt Feld
6. **Speichern**

### Schritt 3: Testen

**Testszenarien:**

1. **Umbuchung Test:**
   - Anrufen und Termin verschieben wollen
   - Agent sollte KEINE Gebühren erwähnen (außer System sagt es)
   - Agent sollte KEINE "48h-Regel" erwähnen

2. **Stornierung Test:**
   - Anrufen und Termin absagen wollen
   - Agent sollte KEINE "24h-Regel" erwähnen
   - Agent sollte System-Antwort kommunizieren

---

## 📋 VALIDATION CHECKLIST

Nach dem Update testen:

- [ ] Agent erwähnt KEINE hardcoded Gebühren mehr (10€, 15€)
- [ ] Agent erwähnt KEINE hardcoded Vorlaufzeiten mehr (24h, 48h)
- [ ] Agent kommuniziert was System zurückgibt
- [ ] Bei Ablehnung: Agent erklärt den Grund vom System
- [ ] Bei Ablehnung: Agent bietet Alternative an
- [ ] Policies in Datenbank und Agent-Aussagen stimmen überein

---

## 🔗 RELATED

**Admin-Interface:**
- Policies ändern: https://api.askproai.de/admin/policy-configurations
- Benutzerhandbuch: `/claudedocs/POLICY_ADMIN_BENUTZERHANDBUCH_2025-10-13.md`

**Technische Details:**
- Policy-Guide: `/claudedocs/POLICY_CONFIGURATION_GUIDE_2025-10-13.md`
- Code: `app/Services/Policies/AppointmentPolicyEngine.php`

---

**Erstellt:** 2025-10-13
**Priorität:** 🔴 HOCH
**Action Required:** Retell Prompt aktualisieren und deployen
**Verantwortlich:** Technical Team / Admin
