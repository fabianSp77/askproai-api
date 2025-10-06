# Admin-Bedienungsanleitung: Policy System

Willkommen zur Bedienungsanleitung für das Policy System! Diese Anleitung erklärt Schritt für Schritt, wie Sie die drei neuen Admin-Bereiche nutzen können.

## Inhaltsverzeichnis

1. [Policy-Konfiguration](#1-policy-konfiguration)
2. [Benachrichtigungskonfiguration](#2-benachrichtigungskonfiguration)
3. [Terminänderungsprotokoll](#3-terminänderungsprotokoll)
4. [Hierarchie verstehen](#4-hierarchie-verstehen)
5. [Häufige Fehler & Lösungen](#5-häufige-fehler--lösungen)
6. [FAQ - Häufig gestellte Fragen](#6-faq---häufig-gestellte-fragen)
7. [Support & Hilfe](#7-support--hilfe)

---

## 1. Policy-Konfiguration

### Was sind Policies?

Policies sind **Regeln**, die festlegen, unter welchen Bedingungen Kunden Termine stornieren oder umbuchen dürfen. Sie können Policies für verschiedene Ebenen Ihres Unternehmens erstellen:

- **Unternehmen**: Gilt für alle Filialen und Services
- **Filiale**: Gilt nur für eine bestimmte Filiale
- **Service**: Gilt nur für einen bestimmten Service (z.B. "Premium Haarschnitt")
- **Mitarbeiter**: Gilt nur für einen bestimmten Mitarbeiter

### Wann brauche ich eine Policy?

**Typische Anwendungsfälle:**

- ✅ Stornogebühren einführen (z.B. 50% bei kurzfristiger Absage)
- ✅ Mindestvorlauf festlegen (z.B. 24 Stunden vor Termin)
- ✅ Anzahl der Stornos begrenzen (z.B. max. 3 pro Monat)
- ✅ Unterschiedliche Regeln für verschiedene Services
- ✅ Spezielle Regeln für bestimmte Mitarbeiter

### Schritt-für-Schritt: Policy erstellen

#### Schritt 1: Navigation

1. Öffnen Sie das Admin-Panel
2. Klicken Sie im Menü auf **"Richtlinien"**
3. Wählen Sie **"Richtlinienkonfigurationen"**
4. Klicken Sie auf **"Erstellen"** (grüner Button oben rechts)

#### Schritt 2: Zuordnung - Für wen gilt die Policy?

**Feld: Zugeordnete Entität**

Wählen Sie hier aus, auf welcher Ebene die Policy gelten soll:

| Ebene | Beispiel | Wann verwenden? |
|-------|----------|-----------------|
| **Unternehmen** | "Salon Meier GmbH" | Für alle Filialen und Services gleich |
| **Filiale** | "Filiale München" | Nur für eine bestimmte Filiale |
| **Service** | "Premium Haarschnitt" | Nur für einen bestimmten Service |
| **Mitarbeiter** | "Anna Schmidt" | Nur für einen Mitarbeiter |

**Tipp:** Geben Sie einfach den Namen in das Suchfeld ein (z.B. "München")

#### Schritt 3: Policy-Typ wählen

**Feld: Richtlinientyp**

Wählen Sie aus, wofür die Regel gilt:

- **Stornierung**: Regeln für Terminabsagen
- **Umbuchung**: Regeln für Terminverschiebungen
- **Wiederkehrend**: Regeln für wiederkehrende Termine

#### Schritt 4: Konfiguration - Die eigentlichen Regeln

**Feld: Konfiguration** (Key-Value-Paare)

Hier legen Sie die konkreten Regeln fest. Klicken Sie auf **"Einstellung hinzufügen"** und geben Sie folgende Werte ein:

**Verfügbare Einstellungen:**

| Einstellung | Wert | Bedeutung | Beispiel |
|-------------|------|-----------|----------|
| `hours_before` | Zahl | Mindestvorlauf in Stunden | `24` = 24 Stunden vorher |
| `fee_percentage` | Zahl (0-100) | Gebühr in Prozent | `50` = 50% Gebühr |
| `max_cancellations_per_month` | Zahl | Max. Stornos pro Monat | `3` = maximal 3 Stornos |
| `min_reschedule_notice_hours` | Zahl | Mindestvorlauf für Umbuchung | `48` = 48 Stunden |

**Wichtig bei der Eingabe:**
- Nur **Zahlen** eingeben (keine Texte wie "%" oder "Stunden")
- Keine Anführungszeichen verwenden
- Keine Kommas in Zahlen (also `50` statt `50,00`)

#### Schritt 5: (Optional) Hierarchie & Überschreibung

**Wann brauche ich das?**
Nur wenn Sie eine bestehende Policy für einen Spezialfall überschreiben möchten.

**Felder:**

- **Ist Überschreibung**: Aktivieren Sie dieses Häkchen
- **Überschreibt Richtlinie**: Wählen Sie die übergeordnete Policy aus

**Beispiel:** Sie haben eine Unternehmens-Policy, aber für den Service "VIP-Treatment" soll eine strengere Regel gelten.

#### Schritt 6: Speichern

Klicken Sie auf **"Erstellen"** - fertig!

---

### Beispiele für typische Policies

#### Beispiel 1: Strenge Storno-Policy für Premium-Services

**Situation:** Premium-Services sollen besser geschützt werden

**Konfiguration:**
- Zugeordnete Entität: Service → "Premium Haarschnitt"
- Richtlinientyp: Stornierung
- Einstellungen:
  - `hours_before`: `48`
  - `fee_percentage`: `100`
  - `max_cancellations_per_month`: `1`

**Bedeutung:** Kunden müssen mindestens 48 Stunden vorher absagen, sonst zahlen sie 100% Gebühr. Maximal 1 Storno pro Monat erlaubt.

---

#### Beispiel 2: Lockere Policy für Standard-Services

**Situation:** Standard-Services sollen kundenfreundlich sein

**Konfiguration:**
- Zugeordnete Entität: Unternehmen → "Ihr Salon"
- Richtlinientyp: Stornierung
- Einstellungen:
  - `hours_before`: `2`
  - `fee_percentage`: `0`
  - `max_cancellations_per_month`: `10`

**Bedeutung:** Kunden können bis 2 Stunden vorher kostenlos stornieren, bis zu 10x pro Monat.

---

#### Beispiel 3: Umbuchungs-Policy mit Vorlauf

**Situation:** Umbuchungen sollen früh genug erfolgen

**Konfiguration:**
- Zugeordnete Entität: Filiale → "Filiale Berlin"
- Richtlinientyp: Umbuchung
- Einstellungen:
  - `min_reschedule_notice_hours`: `24`
  - `fee_percentage`: `20`

**Bedeutung:** Umbuchungen müssen 24 Stunden vorher erfolgen, sonst 20% Gebühr.

---

## 2. Benachrichtigungskonfiguration

### Was sind Benachrichtigungskonfigurationen?

Hier legen Sie fest, **wie und wann** Kunden und Mitarbeiter über Ereignisse informiert werden sollen.

**Beispiele für Ereignisse:**
- Termin wurde gebucht
- Termin wurde storniert
- Termin wurde umgebucht
- Erinnerung an bevorstehenden Termin
- Mitarbeiter wurde zugewiesen

### Wann brauche ich eine Benachrichtigungskonfiguration?

- ✅ Automatische Bestätigungs-E-Mails nach Buchung
- ✅ SMS-Erinnerungen 24h vor Termin
- ✅ WhatsApp-Benachrichtigung bei Stornierung
- ✅ Push-Benachrichtigung für Mitarbeiter
- ✅ Unterschiedliche Kanäle für verschiedene Filialen

### Schritt-für-Schritt: Benachrichtigung erstellen

#### Schritt 1: Navigation

1. Klicken Sie im Menü auf **"Benachrichtigungen"**
2. Wählen Sie **"Benachrichtigungskonfigurationen"**
3. Klicken Sie auf **"Erstellen"**

#### Schritt 2: Zuordnung - Für wen gilt die Benachrichtigung?

**Feld: Zugeordnete Entität**

Genau wie bei Policies wählen Sie hier die Ebene:
- Unternehmen (alle Filialen)
- Filiale (nur eine Filiale)
- Service (nur ein Service)
- Mitarbeiter (nur ein Mitarbeiter)

#### Schritt 3: Event-Typ - Wann soll benachrichtigt werden?

**Feld: Event-Typ**

Wählen Sie aus **13 verfügbaren Events**:

| Event | Wann wird es ausgelöst? |
|-------|------------------------|
| `appointment.created` | Neuer Termin wurde gebucht |
| `appointment.updated` | Termin wurde geändert |
| `appointment.cancelled` | Termin wurde storniert |
| `appointment.rescheduled` | Termin wurde umgebucht |
| `appointment.confirmed` | Termin wurde bestätigt |
| `appointment.completed` | Termin wurde abgeschlossen |
| `appointment.no_show` | Kunde ist nicht erschienen |
| `appointment.reminder_24h` | 24h vor Termin (Erinnerung) |
| `appointment.reminder_1h` | 1h vor Termin (Erinnerung) |
| `staff.assigned` | Mitarbeiter wurde zugewiesen |
| `customer.registered` | Neuer Kunde registriert |
| `payment.received` | Zahlung eingegangen |
| `payment.failed` | Zahlung fehlgeschlagen |

**Tipp:** Nutzen Sie die Suchfunktion im Dropdown

#### Schritt 4: Kanäle - Wie soll benachrichtigt werden?

**Feld: Primärer Kanal**

Wählen Sie den Hauptkanal für die Benachrichtigung:

- **E-Mail** 📧 - Klassische E-Mail
- **SMS** 📱 - Textnachricht aufs Handy
- **WhatsApp** 💬 - WhatsApp-Nachricht
- **Push** 🔔 - Push-Benachrichtigung in der App

**Feld: Fallback-Kanal** (optional)

Falls der primäre Kanal nicht funktioniert (z.B. E-Mail nicht zustellbar), wird dieser Kanal genutzt.

**Empfehlung:**
- Primär: E-Mail → Fallback: SMS
- Primär: WhatsApp → Fallback: E-Mail

#### Schritt 5: Aktivierung

**Feld: Aktiviert**

- ✅ **An** = Benachrichtigungen werden gesendet
- ❌ **Aus** = Benachrichtigungen werden NICHT gesendet

**Tipp:** Erstellen Sie die Konfiguration zuerst mit "Aus", testen Sie sie, und aktivieren Sie sie dann.

#### Schritt 6: Wiederholungslogik

**Was ist das?**
Falls die Benachrichtigung nicht zugestellt werden kann, versucht das System es erneut.

**Felder:**

| Feld | Standard | Bedeutung |
|------|----------|-----------|
| Wiederholungsversuche | `3` | Wie oft soll es erneut versucht werden? |
| Wiederholungsverzögerung | `5` Minuten | Wie lange warten zwischen Versuchen? |

**Beispiel:** 3 Versuche mit 5 Minuten = insgesamt 15 Minuten Zeitfenster

#### Schritt 7: (Optional) Template-Überschreibung

**Wann brauche ich das?**
Nur wenn Sie eine individuelle Nachricht für diese Konfiguration verwenden möchten.

**Feld: Template-Überschreibung**

Hier können Sie einen eigenen Text eingeben, der statt der Standard-Nachricht verwendet wird.

**Beispiel:**
```
Hallo {{customer_name}},

Ihr Termin am {{appointment_date}} um {{appointment_time}} wurde erfolgreich gebucht.

Service: {{service_name}}
Mitarbeiter: {{staff_name}}

Bei Fragen rufen Sie uns an: 089-12345678

Ihr Salon-Team
```

#### Schritt 8: (Optional) Metadaten

**Wann brauche ich das?**
Für zusätzliche Einstellungen, die nicht in den Hauptfeldern sind.

**Beispiele:**

| Schlüssel | Wert | Bedeutung |
|-----------|------|-----------|
| `send_time_preference` | `morning` | Nur morgens versenden |
| `language` | `de` | Sprache der Nachricht |
| `priority` | `high` | Hohe Priorität |
| `include_ics` | `true` | Kalender-Datei anhängen |

#### Schritt 9: Speichern & Testen

1. Klicken Sie auf **"Erstellen"**
2. In der Übersicht: Klicken Sie auf **"Aktionen" → "Test senden"**
3. Geben Sie eine Test-E-Mail oder Telefonnummer ein
4. Prüfen Sie, ob die Benachrichtigung ankommt

**Wichtig:** Testen Sie IMMER, bevor Sie die Konfiguration aktivieren!

---

### Beispiele für typische Benachrichtigungen

#### Beispiel 1: Buchungsbestätigung per E-Mail

**Situation:** Jeder Kunde soll nach Buchung eine E-Mail erhalten

**Konfiguration:**
- Zugeordnete Entität: Unternehmen → "Ihr Salon"
- Event-Typ: `appointment.created`
- Primärer Kanal: E-Mail
- Fallback-Kanal: Keine
- Aktiviert: Ja
- Wiederholungsversuche: 3
- Wiederholungsverzögerung: 5 Minuten

---

#### Beispiel 2: SMS-Erinnerung 24h vor Termin

**Situation:** Kunden sollen 24h vorher per SMS erinnert werden

**Konfiguration:**
- Zugeordnete Entität: Unternehmen → "Ihr Salon"
- Event-Typ: `appointment.reminder_24h`
- Primärer Kanal: SMS
- Fallback-Kanal: E-Mail
- Aktiviert: Ja
- Wiederholungsversuche: 2
- Wiederholungsverzögerung: 10 Minuten

---

#### Beispiel 3: WhatsApp für Premium-Kunden

**Situation:** VIP-Services sollen WhatsApp-Benachrichtigungen erhalten

**Konfiguration:**
- Zugeordnete Entität: Service → "VIP Treatment"
- Event-Typ: `appointment.created`
- Primärer Kanal: WhatsApp
- Fallback-Kanal: E-Mail
- Aktiviert: Ja
- Metadaten:
  - `priority`: `high`
  - `include_ics`: `true`

---

## 3. Terminänderungsprotokoll

### Was ist das Terminänderungsprotokoll?

Dies ist ein **nur-lesender Bereich**, in dem alle Stornierungen und Umbuchungen automatisch protokolliert werden.

**Sie können hier:**
- ✅ Alle Änderungen nachvollziehen
- ✅ Sehen, ob Policies eingehalten wurden
- ✅ Gebühren überprüfen
- ✅ Statistiken auswerten

**Sie können hier NICHT:**
- ❌ Einträge erstellen (wird automatisch gemacht)
- ❌ Einträge bearbeiten (Audit-Trail ist unveränderbar)
- ❌ Einträge löschen (gesetzliche Aufbewahrungspflicht)

### Navigation

1. Klicken Sie im Menü auf **"Termine"**
2. Wählen Sie **"Änderungsprotokoll"**

### Was sehe ich hier?

**Spalten in der Übersicht:**

| Spalte | Bedeutung | Beispiel |
|--------|-----------|----------|
| **ID** | Eindeutige Nummer | #1234 |
| **Termin** | Link zum Termin | #5678 |
| **Kunde** | Name des Kunden | "Max Mustermann" |
| **Typ** | Art der Änderung | Stornierung / Umplanung |
| **Grund** | Warum geändert? | "Krankheit" |
| **Richtlinien** | Policy eingehalten? | ✅ Ja / ❌ Nein |
| **Gebühr** | Berechnete Gebühr | 25,00 € |
| **Geändert von** | Wer hat geändert? | Kunde / Mitarbeiter / System |
| **Erstellt** | Wann protokolliert? | 15.10.2025 14:30 |

### Nützliche Filter

**So finden Sie schnell, was Sie suchen:**

1. **Nach Typ filtern:**
   - Nur Stornierungen anzeigen
   - Nur Umbuchungen anzeigen

2. **Nach Richtlinienkonformität filtern:**
   - Nur Verstöße gegen Policies anzeigen
   - Nur konforme Änderungen anzeigen

3. **Nach Gebühr filtern:**
   - Nur kostenpflichtige Stornos
   - Nur kostenlose Stornos

4. **Nach Kunde filtern:**
   - Alle Änderungen eines bestimmten Kunden

5. **Nach Zeitraum filtern:**
   - Letzte 7 Tage
   - Letzter Monat
   - Benutzerdefiniert

### Detailansicht

Klicken Sie auf einen Eintrag, um alle Details zu sehen:

**Was sehe ich hier?**

1. **Änderungsdetails:**
   - Typ (Stornierung/Umbuchung)
   - Grund
   - Wer hat geändert
   - Policy eingehalten?
   - Berechnete Gebühr

2. **Termindetails:**
   - Termin-ID (klickbar)
   - Aktueller Status
   - Ursprüngliche Zeit
   - Service

3. **Kundendetails:**
   - Name (klickbar)
   - E-Mail (kopierbar)
   - Telefon (kopierbar)

4. **Zeitstempel:**
   - Wann erstellt
   - Wann zuletzt aktualisiert

### Typische Anwendungsfälle

#### Anwendungsfall 1: Policy-Verstöße prüfen

**Situation:** Sie möchten sehen, welche Stornierungen gegen Ihre Policies verstoßen haben

**Vorgehen:**
1. Öffnen Sie das Änderungsprotokoll
2. Filter: **Richtlinienkonform** → **Nein**
3. Filter: **Änderungstyp** → **Stornierung**
4. Prüfen Sie die Liste

**Was tun?**
- Kontaktieren Sie die Kunden
- Passen Sie ggf. die Policy an

---

#### Anwendungsfall 2: Häufige Stornierer identifizieren

**Situation:** Sie möchten wissen, welche Kunden oft stornieren

**Vorgehen:**
1. Exportieren Sie die Daten (Export-Button oben rechts)
2. Sortieren Sie nach Kunde
3. Zählen Sie die Stornierungen pro Kunde

**Alternative:**
- Nutzen Sie die Statistik-Widgets oben im Dashboard

---

#### Anwendungsfall 3: Gebührenabrechnung

**Situation:** Monatsende - Sie möchten alle berechneten Gebühren sehen

**Vorgehen:**
1. Filter: **Erstellt von** → Letzter Monat
2. Filter: **Gebühr berechnet** → **Ja**
3. Exportieren Sie die Liste
4. Summe der Gebühren berechnen

---

## 4. Hierarchie verstehen

### Was ist die Policy-Hierarchie?

Policies können auf verschiedenen Ebenen definiert werden. Wenn mehrere Policies auf einen Termin zutreffen, gilt die **spezifischste** Policy.

### Prioritätsreihenfolge (von höchster zu niedrigster)

```
1. Mitarbeiter-Policy    (höchste Priorität)
   ↓
2. Service-Policy
   ↓
3. Filial-Policy
   ↓
4. Unternehmens-Policy   (niedrigste Priorität)
```

### Beispiel: So funktioniert die Hierarchie

**Ihre Konfiguration:**

1. **Unternehmens-Policy** (Stornierung):
   - `hours_before`: 24
   - `fee_percentage`: 50
   - `max_cancellations_per_month`: 5

2. **Filial-Policy** für "Filiale München" (Stornierung):
   - `hours_before`: 48
   - `fee_percentage`: 75

3. **Service-Policy** für "VIP Treatment" (Stornierung):
   - `fee_percentage`: 100

**Welche Policy gilt wann?**

| Situation | Geltende Policy | Ergebnis |
|-----------|----------------|----------|
| Standard-Service in Filiale Berlin | Unternehmens-Policy | 24h Vorlauf, 50% Gebühr, max. 5/Monat |
| Standard-Service in Filiale München | Filial-Policy | 48h Vorlauf, 75% Gebühr, max. 5/Monat* |
| VIP Treatment in Filiale München | Service-Policy | 48h Vorlauf*, 100% Gebühr, max. 5/Monat* |

*Diese Werte werden von der übergeordneten Policy übernommen, weil sie in der spezifischen Policy nicht definiert sind.

### Effektive Konfiguration

**Was ist das?**
Die "effektive Konfiguration" ist die Kombination aller anwendbaren Policies.

**Wo sehe ich das?**
In der Detailansicht einer Policy gibt es zwei Bereiche:

1. **Rohe Konfiguration**: Nur die Werte, die in DIESER Policy definiert sind
2. **Effektive Konfiguration**: Alle Werte inkl. geerbter Werte

**Beispiel:**

```
Rohe Konfiguration (Service-Policy "VIP Treatment"):
  fee_percentage: 100

Effektive Konfiguration (mit Vererbung):
✓ hours_before: 48        (geerbt von Filial-Policy)
✓ fee_percentage: 100     (eigener Wert)
✓ max_cancellations_per_month: 5  (geerbt von Unternehmens-Policy)
```

### Best Practices

1. **Basis-Policy auf Unternehmensebene:**
   - Definieren Sie eine "Standard-Policy" für das gesamte Unternehmen
   - Alle Werte sollten hier gesetzt sein

2. **Spezifische Policies nur für Ausnahmen:**
   - Erstellen Sie Filial/Service/Mitarbeiter-Policies nur für Abweichungen
   - Setzen Sie nur die Werte, die anders sein sollen

3. **Übersichtlichkeit bewahren:**
   - Zu viele Policies = unübersichtlich
   - Lieber wenige, klare Policies als viele komplexe

---

## 5. Häufige Fehler & Lösungen

### Fehler 1: Policy greift nicht

**Symptom:** Sie haben eine Policy erstellt, aber sie wird nicht angewendet

**Mögliche Ursachen:**

1. **Falsche Ebene gewählt**
   - ❌ Problem: Policy für Filiale Berlin, aber Termin ist in München
   - ✅ Lösung: Prüfen Sie die Zuordnung in der Policy

2. **Policy-Typ stimmt nicht**
   - ❌ Problem: Umbuchungs-Policy, aber Kunde storniert
   - ✅ Lösung: Erstellen Sie separate Policies für Stornierung und Umbuchung

3. **Übergeordnete Policy überschreibt**
   - ❌ Problem: Spezifischere Policy existiert
   - ✅ Lösung: Prüfen Sie die Hierarchie in der Detailansicht

**Wie prüfe ich das?**
1. Öffnen Sie das Änderungsprotokoll
2. Sehen Sie sich die Änderung an
3. Prüfen Sie, welche Policy angewendet wurde

---

### Fehler 2: Benachrichtigung wird nicht gesendet

**Symptom:** Kunden erhalten keine E-Mails/SMS

**Mögliche Ursachen:**

1. **Konfiguration ist deaktiviert**
   - ❌ Problem: "Aktiviert" ist auf "Aus"
   - ✅ Lösung: Aktivieren Sie die Konfiguration

2. **Falsches Event gewählt**
   - ❌ Problem: Event `appointment.updated` statt `appointment.created`
   - ✅ Lösung: Prüfen Sie, welches Event tatsächlich ausgelöst wird

3. **Falsche Zuordnung**
   - ❌ Problem: Konfiguration für Service A, aber Event für Service B
   - ✅ Lösung: Erstellen Sie eine Unternehmens-Konfiguration als Fallback

4. **Kontaktdaten fehlen**
   - ❌ Problem: Kunde hat keine E-Mail hinterlegt
   - ✅ Lösung: Konfigurieren Sie einen Fallback-Kanal (z.B. SMS)

**Wie teste ich das?**
1. Nutzen Sie die **"Test senden"**-Funktion
2. Prüfen Sie die System-Logs
3. Kontaktieren Sie den Support

---

### Fehler 3: Falsche Konfigurationswerte

**Symptom:** Policy funktioniert nicht wie erwartet

**Typische Eingabefehler:**

| ❌ Falsch | ✅ Richtig | Problem |
|-----------|-----------|---------|
| Key: `hours_before`, Value: `"24 Stunden"` | Key: `hours_before`, Value: `24` | Nur Zahlen erlaubt |
| Key: `fee_percentage`, Value: `"50%"` | Key: `fee_percentage`, Value: `50` | Keine Prozentzeichen |
| Key: `hours_before`, Value: `24,5` | Key: `hours_before`, Value: `24` | Nur ganze Zahlen |
| Key: `max_cancellations`, Value: `3` | Key: `max_cancellations_per_month`, Value: `3` | Falscher Schlüssel |

**Tipp:** Kopieren Sie die Schlüssel aus dieser Anleitung!

---

### Fehler 4: Gebühren werden nicht berechnet

**Symptom:** Stornierung außerhalb der Frist, aber keine Gebühr

**Mögliche Ursachen:**

1. **`fee_percentage` nicht gesetzt**
   - ✅ Lösung: Fügen Sie `fee_percentage` zur Policy hinzu

2. **Termin hat keinen Preis**
   - ✅ Lösung: Prüfen Sie, ob der Service einen Preis hat

3. **Sonderfall greift**
   - ✅ Lösung: Prüfen Sie die effektive Konfiguration

---

## 6. FAQ - Häufig gestellte Fragen

### Allgemeine Fragen

**Q: Kann ich eine Policy temporär deaktivieren?**
A: Ja! Erstellen Sie eine Überschreibungs-Policy mit lockeren Regeln, oder löschen Sie die Policy temporär (sie kann wiederhergestellt werden).

**Q: Was passiert, wenn ich eine Policy lösche?**
A: Die Policy wird "soft deleted" (nicht endgültig gelöscht). Sie können sie über den Filter "Gelöschte anzeigen" wiederherstellen.

**Q: Kann ich sehen, welche Policy für einen Termin gilt?**
A: Ja! Öffnen Sie die Policy-Detailansicht und schauen Sie unter "Effektive Konfiguration".

**Q: Wie viele Policies kann ich erstellen?**
A: Unbegrenzt. Aber: Weniger ist mehr! Zu viele Policies machen das System unübersichtlich.

---

### Policies

**Q: Kann ich unterschiedliche Regeln für neue vs. Stammkunden?**
A: Nicht direkt über Policies. Sie könnten aber Metadaten in der Benachrichtigungskonfiguration nutzen oder manuelle Anpassungen vornehmen.

**Q: Kann ich Policies zeitabhängig machen (z.B. nur im Dezember)?**
A: Aktuell nicht automatisch. Sie können Policies manuell aktivieren/deaktivieren oder überschreiben.

**Q: Was passiert bei 0% Gebühr?**
A: Die Policy wird trotzdem geprüft (z.B. Vorlauf), aber es wird keine Gebühr berechnet.

**Q: Kann ich Gebühren nachträglich ändern?**
A: Nein, die Gebühr wird beim Ereignis berechnet und protokolliert. Sie können nur manuelle Anpassungen in Ihrem Abrechnungssystem vornehmen.

---

### Benachrichtigungen

**Q: Kann ich Benachrichtigungen für bestimmte Kunden deaktivieren?**
A: Ja, über die Kundeneinstellungen (falls verfügbar), oder erstellen Sie eine deaktivierte Konfiguration auf Kundenebene.

**Q: Wie funktioniert die Fallback-Logik?**
A: Wenn der primäre Kanal fehlschlägt (z.B. E-Mail bounced), wird automatisch der Fallback-Kanal versucht.

**Q: Kann ich mehrere Kanäle gleichzeitig nutzen?**
A: Aktuell: Primär + Fallback. Für mehrere Kanäle erstellen Sie mehrere Konfigurationen für dasselbe Event.

**Q: Wo sehe ich, ob eine Benachrichtigung gesendet wurde?**
A: In den System-Logs oder kontaktieren Sie den Support für erweiterte Logging-Funktionen.

**Q: Kann ich eigene Events erstellen?**
A: Nein, nur die 13 vordefinierten Events sind verfügbar. Kontaktieren Sie den Support für Feature-Requests.

---

### Terminänderungsprotokoll

**Q: Kann ich Einträge im Protokoll löschen?**
A: Nein, das Audit-Trail ist unveränderbar (Compliance-Anforderung).

**Q: Wie lange werden Einträge gespeichert?**
A: Unbegrenzt, gemäß gesetzlicher Aufbewahrungspflicht.

**Q: Kann ich Daten exportieren?**
A: Ja, über den Export-Button (CSV, Excel, PDF).

**Q: Kann ich Custom-Reports erstellen?**
A: Nutzen Sie die Statistik-Widgets oder exportieren Sie die Daten für eigene Analysen.

---

## 7. Support & Hilfe

### Wo finde ich weitere Hilfe?

1. **In-App-Hilfe:**
   - Fahren Sie mit der Maus über die ℹ️-Symbole
   - Lesen Sie die Hilfetexte unter den Eingabefeldern

2. **System-Logs:**
   - Bei technischen Problemen: Kontaktieren Sie Ihren Admin
   - Logs enthalten detaillierte Fehlerinformationen

3. **Support kontaktieren:**
   - **E-Mail:** support@ihr-salon-system.de
   - **Telefon:** 089 - 123 456 789
   - **Support-Portal:** https://support.ihr-salon-system.de

### Was sollte ich beim Support angeben?

Damit wir Ihnen schnell helfen können, geben Sie bitte an:

1. **Was wollten Sie tun?**
   - "Ich wollte eine Policy für Filiale München erstellen"

2. **Was ist passiert?**
   - "Die Policy wird nicht angewendet"

3. **Screenshots:**
   - Machen Sie Screenshots von der Konfiguration
   - Machen Sie einen Screenshot der Fehlermeldung

4. **IDs:**
   - Policy-ID (z.B. #123)
   - Termin-ID (z.B. #5678)
   - Kunde (Name)

---

## Anhang: Cheat Sheet - Schnellreferenz

### Policy-Konfiguration - Schnelleinstieg

```
1. Richtlinien → Richtlinienkonfigurationen → Erstellen
2. Zuordnung wählen (Unternehmen/Filiale/Service/Mitarbeiter)
3. Policy-Typ wählen (Stornierung/Umbuchung/Wiederkehrend)
4. Einstellungen hinzufügen:
   - hours_before: [Zahl]
   - fee_percentage: [0-100]
   - max_cancellations_per_month: [Zahl]
5. Speichern
```

### Benachrichtigungskonfiguration - Schnelleinstieg

```
1. Benachrichtigungen → Benachrichtigungskonfigurationen → Erstellen
2. Zuordnung wählen
3. Event-Typ wählen (z.B. appointment.created)
4. Primärer Kanal wählen (E-Mail/SMS/WhatsApp/Push)
5. Optional: Fallback-Kanal wählen
6. Aktiviert: Zunächst AUS
7. Test senden → Prüfen
8. Aktiviert: AN
9. Speichern
```

### Nützliche Tastenkombinationen

| Aktion | Tastenkombination |
|--------|-------------------|
| Neue Policy erstellen | `C` (auf Index-Seite) |
| Suchen | `CMD + K` (Mac) / `STRG + K` (Windows) |
| Filter öffnen | `F` |
| Export | `E` |

---

**Viel Erfolg mit dem Policy System!**

Bei Fragen oder Problemen stehen wir Ihnen jederzeit zur Verfügung.

---

*Stand: Oktober 2025 | Version 1.0*
