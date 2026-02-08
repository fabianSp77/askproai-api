# IT-Support Agent v3.4 -- Testszenarien fuer Thomas

**Stand:** 07.02.2026
**Agent:** v3.4 (`agent_0b69369919d3c91349af8b38c9`)
**Telefonnummer:** _[wird vor Versand eingefuegt]_

---

## Anleitung

Bitte rufe die oben genannte Nummer an und spiele die folgenden Szenarien durch. Der Agent spricht Deutsch und fuehrt dich durch das Gespraech. Pro Szenario dauert ein Anruf ca. 1-3 Minuten.

**Wichtig:** Notiere dir nach jedem Anruf kurz:
- Hat der Agent die Kategorie richtig erkannt?
- Wurden alle genannten Infos korrekt zusammengefasst?
- Gab es unnoetige oder fehlende Rueckfragen?
- Wie war das Gespraechsgefuehl (natuerlich vs. roboterhaft)?

---

# Abschnitt 1: Kategorie-Tests (Szenarien 1-7)

Jede der 7 Kategorien wird einmal getestet. So pruefen wir, ob die Klassifizierung zuverlaessig funktioniert.

---

## Szenario 1: Netzwerk (VPN-Problem)
**Kategorie:** network
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text:** _"Willkommen beim IT-Support von [Firma]. Zur Bearbeitung Ihres Anliegens wird dieses Gespraech aufgezeichnet und ein Ticket erstellt. Sind Sie damit einverstanden?"_
2. **SIE:** "Ja, klar."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem. Worum geht es?"_
4. **SIE:** "Hallo, hier ist Thomas Stanner von VisionaryData. Ich komme seit heute Morgen nicht mehr ins VPN rein. Die Verbindung bricht immer nach ein paar Sekunden ab. Ich bin im Homeoffice und kann so nicht auf die Firmenserver zugreifen."
5. **Agent stellt EINE Triage-Rueckfrage** (z.B. "Betrifft das nur Ihren Rechner oder auch andere Geraete?")
6. **SIE:** "Nur mein Laptop, soweit ich weiss. Am Handy habe ich es nicht probiert."
7. **Agent fragt nach Rueckrufnummer**
8. **SIE:** "0151 12345678"
9. **Agent fasst zusammen und fragt nach Bestaetigung**
10. **SIE:** "Ja, passt."

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: network
- Prioritaet: normal
- Pflichtfelder erfasst: Name (Thomas Stanner), Firma (VisionaryData), Problem (VPN-Verbindung bricht ab), Rueckruf (0151 12345678)
- Optionale Felder: problem_since ("seit heute Morgen"), customer_location ("Homeoffice")

### Worauf achten
- Der Agent darf NICHT nochmal nach Name oder Firma fragen (beides wurde im ersten Satz genannt)
- Genau EINE Triage-Rueckfrage, nicht mehr
- Die Zusammenfassung sollte "VPN" und "Homeoffice" enthalten

---

## Szenario 2: M365 (Teams-Stoerung)
**Kategorie:** m365
**Prioritaet:** high (weil mehrere Kollegen betroffen)

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja, einverstanden."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Guten Tag, mein Name ist Sebastian Gesellensetter, ich rufe an von der Firma TechConsult. Bei uns funktioniert seit etwa einer Stunde Microsoft Teams nicht mehr richtig. Wir koennen keine Anrufe starten und der Chat laedt nicht. Das betrifft das ganze Team, also mindestens zehn Leute."
5. **Agent stellt EINE Triage-Rueckfrage** (z.B. "Funktioniert es im Browser oder nur in der Desktop-App?")
6. **SIE:** "Sowohl Desktop-App als auch im Browser. Outlook geht aber noch."
7. **Agent fragt nach Rueckrufnummer**
8. **SIE:** "Am besten unter 089 9876543, Durchwahl 22."
9. **Agent fasst zusammen**
10. **SIE:** "Stimmt, genau so."

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: m365
- Prioritaet: high (weil others_affected = ja)
- Pflichtfelder erfasst: Name (Sebastian Gesellensetter), Firma (TechConsult), Problem (Teams funktioniert nicht), Rueckruf (089 9876543 Durchwahl 22)
- Optionale Felder: others_affected ("ja", ca. 10 Personen), problem_since ("seit etwa einer Stunde")

### Worauf achten
- Kategorie muss "m365" sein, NICHT "network"
- Prioritaet muss HIGH sein wegen "betrifft das ganze Team"
- Agent soll erkennen, dass Outlook noch geht (Detail fuer Techniker)

---

## Szenario 3: Endgeraet (Laptop startet nicht)
**Kategorie:** endpoint
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Hi, ich bin die Maria Huber von der Stadtwerke Muenchen. Mein Laptop faehrt nicht mehr hoch. Bleibt beim Windows-Logo haengen und dann kommt ein blauer Bildschirm mit einer Fehlermeldung."
5. **Agent stellt EINE Triage-Rueckfrage** (z.B. "Haben Sie schon einen Neustart versucht?")
6. **SIE:** "Ja, schon dreimal. Immer das gleiche. Ich hab den auch schon mal zehn Minuten vom Strom genommen."
7. **Agent fragt nach Rueckrufnummer**
8. **SIE:** "0176 5551234"
9. **Agent fasst zusammen**
10. **SIE:** "Ja, genau."

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: endpoint
- Prioritaet: normal
- Pflichtfelder erfasst: Name (Maria Huber), Firma (Stadtwerke Muenchen), Problem (Laptop bleibt beim Windows-Logo haengen, Bluescreen), Rueckruf (0176 5551234)
- Optionale Felder: keine

### Worauf achten
- Kategorie muss "endpoint" sein, NICHT "other"
- Agent soll den Bluescreen als relevantes Detail aufnehmen
- Keine ueberfluessigen Fragen nach dem Neustart-Versuch (wurde bereits erwaehnt)

---

## Szenario 4: Drucker (Papierstau)
**Kategorie:** print
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Einverstanden."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Ja hallo, Mueller hier, von der Firma Baecker und Soehne. Der grosse Drucker im zweiten Stock hat staendig Papierstau. Wir haben schon Papier rausgenommen und neu eingelegt, aber nach zwei Seiten ist wieder Stau."
5. **Agent stellt EINE Triage-Rueckfrage** (z.B. "Zeigt der Drucker eine Fehlermeldung an?")
6. **SIE:** "Ja, da steht 'Papierstau Fach 2' im Display."
7. **Agent fragt nach Rueckrufnummer**
8. **SIE:** "Die Nummer im Display reicht."
9. **Agent fasst zusammen**
10. **SIE:** "Passt."

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: print
- Prioritaet: normal
- Pflichtfelder erfasst: Name (Mueller), Firma (Baecker und Soehne), Problem (Papierstau Drucker 2. Stock), Rueckruf (caller_id)
- Optionale Felder: customer_location ("zweiter Stock")

### Worauf achten
- Kategorie muss "print" sein
- Rueckrufnummer muss als "caller_id" gespeichert werden (nicht als leeres Feld)
- Standort "zweiter Stock" sollte erfasst werden
- In der Zusammenfassung sollte stehen: "...melden uns unter der angezeigten Nummer"

---

## Szenario 5: Identitaet (Passwort-Problem)
**Kategorie:** identity
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja, kein Problem."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Hallo, ich bin Klaus Weber von der Firma LogistikPlus. Ich komme nicht mehr in mein E-Mail-Konto rein. Mein Passwort wird abgelehnt, obwohl ich es vor zwei Tagen erst geaendert habe. Und jetzt sagt das System, mein Konto ist gesperrt."
5. **Agent stellt EINE Triage-Rueckfrage** (z.B. "Bei welchem System koennen Sie sich nicht anmelden?")
6. **SIE:** "Bei Outlook und auch beim Windows-Login."
7. **Agent fragt nach Rueckrufnummer**
8. **SIE:** "0172 4445566"
9. **Agent fasst zusammen**
10. **SIE:** "Ja, stimmt alles."

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: identity
- Prioritaet: normal
- Pflichtfelder erfasst: Name (Klaus Weber), Firma (LogistikPlus), Problem (Konto gesperrt, Passwort abgelehnt), Rueckruf (0172 4445566)
- Optionale Felder: problem_since ("vor zwei Tagen Passwort geaendert")

### Worauf achten
- Kategorie muss "identity" sein, NICHT "m365" (obwohl Outlook erwaehnt wird, ist das Kernproblem ein gesperrtes Konto)
- Agent sollte erkennen, dass es um Konto-Sperrung geht, nicht um ein Outlook-Problem

---

## Szenario 6: Security (Ransomware -- KRITISCH)
**Kategorie:** security
**Prioritaet:** critical (automatische Eskalation)

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja, schnell bitte!"
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Hier ist Frank Berger von der AutoTech GmbH. Wir haben ein grosses Problem! Auf allen Rechnern in der Buchhaltung erscheint ein Bildschirm mit der Meldung, dass unsere Daten verschluesselt wurden. Da steht was von Ransomware und Loesegeld in Bitcoin. Wir koennen auf nichts mehr zugreifen!"
5. **Agent:** _"Ich verstehe, das klingt nach einem dringenden Sicherheitsvorfall. Ich erstelle sofort ein Ticket mit hoechster Prioritaet. Bitte aendern Sie keine Passwoerter und schalten Sie betroffene Geraete nicht aus, bis sich ein Techniker bei Ihnen meldet."_ (KEINE Triage-Rueckfragen!)
6. **Agent fragt direkt nach Rueckrufnummer**
7. **SIE:** "0160 9998877, bitte schnell!"
8. **Agent fasst zusammen**
9. **SIE:** "Ja, bitte sofort!"

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: security
- Prioritaet: CRITICAL (automatische Eskalation)
- Pflichtfelder erfasst: Name (Frank Berger), Firma (AutoTech GmbH), Problem (Ransomware, Daten verschluesselt), Rueckruf (0160 9998877)
- Optionale Felder: others_affected (implizit ja -- "alle Rechner in der Buchhaltung")
- security_critical: true
- use_case_detail enthaelt: "escalation=critical"

### Worauf achten
- **KEINE Triage-Rueckfrage!** Der Agent muss direkt zur Kontaktdaten-Erfassung springen
- Agent muss den Hinweis geben: "Keine Passwoerter aendern, Geraete nicht ausschalten"
- Prioritaet muss CRITICAL sein (nicht nur HIGH)
- Das Wort "Ransomware" ODER "Loesegeld" muss den Security-Trigger ausloesen

---

## Szenario 7: Sonstiges (Allgemeine IT-Frage)
**Kategorie:** other
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Guten Tag, hier spricht Anna Schmidt von der Designagentur Kreativ. Wir brauchen fuer einen neuen Mitarbeiter einen kompletten Arbeitsplatz eingerichtet. Also Laptop, E-Mail-Konto, Zugang zum Fileserver und so weiter. Der faengt naechste Woche Montag an."
5. **Agent stellt EINE allgemeine Rueckfrage** (z.B. "Gibt es noch ein Detail, das dem Techniker helfen koennte?")
6. **SIE:** "Er braucht auch Zugang zu unserem Grafik-Server und Adobe Creative Cloud."
7. **Agent fragt nach Rueckrufnummer**
8. **SIE:** "030 1234567"
9. **Agent fasst zusammen**
10. **SIE:** "Genau, das passt."

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: other
- Prioritaet: normal
- Pflichtfelder erfasst: Name (Anna Schmidt), Firma (Designagentur Kreativ), Problem (Neuer Mitarbeiter, Arbeitsplatz einrichten), Rueckruf (030 1234567)
- Optionale Felder: keine

### Worauf achten
- Kategorie muss "other" sein (Onboarding ist keine der spezifischen Kategorien)
- Agent sollte den Zeitdruck ("naechste Woche Montag") in die Problembeschreibung aufnehmen

---

# Abschnitt 2: Edge Cases (Szenarien 8-15)

Diese Szenarien testen ungewoehnliche Situationen und Grenzfaelle.

---

## Szenario 8: Alle Infos auf einmal
**Kategorie:** endpoint
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Hallo, Peter Meier hier von der MedTech Solutions, mein Monitor flackert seit gestern staendig und ich sitze im Buero Frankfurt, dritter Stock. Rueckrufnummer ist 069 7773344."
5. _(Agent sollte KEINE Frage nach Name, Firma oder Rueckrufnummer stellen -- alles wurde bereits genannt)_

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: endpoint
- Prioritaet: normal
- Pflichtfelder erfasst: Name (Peter Meier), Firma (MedTech Solutions), Problem (Monitor flackert), Rueckruf (069 7773344)
- Optionale Felder: customer_location ("Buero Frankfurt, dritter Stock"), problem_since ("seit gestern")

### Worauf achten
- Agent darf NICHT nochmal nach Name, Firma oder Rueckrufnummer fragen
- Agent sollte trotzdem eine kurze Triage-Rueckfrage zum Problem stellen (z.B. "Haben Sie einen anderen Monitor zum Testen?")
- Standort "Frankfurt, dritter Stock" muss erfasst werden

---

## Szenario 9: Name fehlt
**Kategorie:** print
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Hallo, unser Netzwerkdrucker druckt nur noch leere Seiten. Ich bin von der Firma DataFlow."
5. **Agent sollte nach dem Namen fragen** (z.B. "Darf ich noch Ihren Namen erfahren?")
6. **SIE:** "Ach ja, entschuldigung. Lisa Braun."
7. _(Weiter mit Triage und Rueckrufnummer wie ueblich)_
8. **Agent stellt Triage-Rueckfrage**
9. **SIE:** "Ja, alle anderen Drucker funktionieren, nur der eine nicht."
10. **Agent fragt nach Rueckrufnummer**
11. **SIE:** "0171 2223344"
12. **Agent fasst zusammen**
13. **SIE:** "Ja."

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: print
- Pflichtfelder erfasst: Name (Lisa Braun), Firma (DataFlow), Problem (Netzwerkdrucker druckt leere Seiten), Rueckruf (0171 2223344)

### Worauf achten
- Agent MUSS nach dem Namen fragen
- Frage soll natuerlich klingen ("Darf ich noch Ihren Namen erfahren?")
- Agent soll NICHT nach der Firma fragen (wurde schon genannt)

---

## Szenario 10: Consent verweigert
**Kategorie:** -- (kein Ticket)
**Prioritaet:** --

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Nein, ich moechte nicht aufgezeichnet werden."
3. **Agent:** _"Kein Problem. Ohne Ihre Zustimmung kann ich leider kein Ticket aufnehmen. Sie koennen uns gerne per E-Mail kontaktieren. Auf Wiederhoeren."_

### Erwartetes Ergebnis
- Ticket erstellt: Nein
- Gespraech wird hoeflich beendet
- E-Mail-Alternative wird angeboten

### Worauf achten
- Agent muss das Gespraech sofort beenden
- Agent darf NICHT versuchen, den Anrufer zu ueberreden
- Hoeflicher Ton bei der Ablehnung

---

## Szenario 11: Security mitten im Gespraech
**Kategorie:** security
**Prioritaet:** critical (Eskalation muss nachtraeglich ausgeloest werden)

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Hallo, Martin Koch von der Steuerberatung Bergmann. Ich hab heute Morgen komische E-Mails bekommen. Irgendwas mit einer Rechnung die ich oeffnen soll. Und jetzt verhaelt sich mein Rechner seltsam."
5. **Agent ordnet erstmal als "endpoint" oder "other" ein und stellt eine Triage-Rueckfrage**
6. **SIE:** "Moment, jetzt poppt gerade eine Meldung auf... da steht, dass meine Dateien verschluesselt wurden und ich soll Loesegeld zahlen! Das ist Ransomware!"
7. **Agent muss jetzt sofort in den Security-Eskalationsmodus wechseln!**

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: security
- Prioritaet: CRITICAL
- security_critical: true

### Worauf achten
- **Kritischer Test:** Der Agent muss erkennen, dass sich die Situation waehrend des Gespraechs geaendert hat
- Nach dem Wort "Ransomware" oder "Loesegeld" MUSS der Security-Pfad aktiviert werden
- Der Agent soll den Hinweis geben: "Keine Passwoerter aendern, Geraete nicht ausschalten"
- Keine weiteren Triage-Fragen nach der Security-Erkennung

---

## Szenario 12: Zweites Problem im selben Anruf
**Kategorie:** Erst network, dann print
**Prioritaet:** beide normal

### Dialog (was Sie sagen)
1. _(Normaler Ablauf fuer ein Netzwerk-Problem, Ticket wird erstellt)_
2. **Agent:** _"Danke, ich habe das Ticket erstellt. Kann ich sonst noch etwas fuer Sie tun?"_
3. **SIE:** "Ja, ich haette tatsaechlich noch ein Problem. Unser Drucker im Empfang zeigt 'Toner leer' an, aber wir haben erst letzte Woche neuen Toner eingesetzt."
4. _(Agent sollte jetzt ein zweites Ticket-Gespraech starten)_

### Erwartetes Ergebnis
- **Zwei** Tickets erstellt
- Erstes Ticket: Kategorie network
- Zweites Ticket: Kategorie print

### Worauf achten
- Agent muss nach dem ersten Ticket fragen ob noch etwas ist
- Bei "Ja" muss ein neues Gespraech fuer das zweite Problem starten
- Name und Firma muessen NICHT erneut abgefragt werden (sind aus dem ersten Gespraech bekannt)

---

## Szenario 13: Nummer im Display
**Kategorie:** m365
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. _(Normaler Ablauf bis zur Rueckrufnummer)_
2. **Agent:** _"Unter welcher Nummer koennen wir Sie fuer Rueckfragen am besten erreichen?"_
3. **SIE:** "Ach, die Nummer im Display reicht."
4. **Agent:** _"Alles klar, dann nutzen wir die angezeigte Nummer."_

### Erwartetes Ergebnis
- customer_phone wird als "caller_id" gespeichert (nicht als leerer Wert)
- In der Zusammenfassung steht: "...melden uns unter der angezeigten Nummer"

### Worauf achten
- Agent muss die Formulierung "Nummer im Display" korrekt verstehen
- Darf NICHT nochmal nachfragen
- Zusammenfassung muss korrekt formuliert sein

---

## Szenario 14: Sehr kurze Problembeschreibung
**Kategorie:** print
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Drucker kaputt."
5. _(Agent muss trotzdem Name und Firma erfragen)_
6. **Agent:** _"Darf ich noch kurz Ihren Namen und Ihre Firma erfahren?"_
7. **SIE:** "Jens Klein, Autohaus Mayer."
8. **Agent stellt EINE Triage-Rueckfrage** (z.B. "Welchen Drucker betrifft es?")
9. **SIE:** "Den im Showroom."
10. **Agent fragt nach Rueckrufnummer**
11. **SIE:** "0152 8887766"
12. **Agent fasst zusammen**
13. **SIE:** "Ja."

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: print
- Pflichtfelder erfasst: Name (Jens Klein), Firma (Autohaus Mayer), Problem (Drucker kaputt, Showroom), Rueckruf (0152 8887766)
- Optionale Felder: customer_location ("Showroom")

### Worauf achten
- Agent muss mit minimalen Infos umgehen koennen
- Trotzdem muss eine Triage-Rueckfrage kommen (um dem Techniker mehr Kontext zu geben)
- Name und Firma muessen nachgefragt werden

---

## Szenario 15: Lange, ausfuehrliche Erklaerung
**Kategorie:** network
**Prioritaet:** high (andere betroffen)

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja, klar."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Also, guten Tag, Hildegard Meisterfeld hier, von der Meisterfeld Steuerberatung. Ich muss Ihnen mal was erzaehlen. Also, es fing gestern Nachmittag so gegen drei an. Da hat ploetzlich das Internet nicht mehr funktioniert. Erst dachte ich, es liegt an meinem Rechner, aber dann kam der Kollege rueber und sagte, bei ihm geht es auch nicht. Und die Kollegin im Nebenzimmer hat das gleiche Problem. Wir haben dann den Router mal aus- und wieder eingeschaltet, das hat aber auch nichts gebracht. Und das WLAN auf dem Handy funktioniert auch nicht. Wir hatten letzten Monat schon mal so ein Problem, da musste dann ein Techniker kommen und irgendetwas am Switch machen. Und jetzt geht seit heute Morgen auch das Telefon nicht mehr richtig, also das IP-Telefon meine ich. Die Kunden koennen uns nicht erreichen und das ist natuerlich sehr schlecht fuer uns als Steuerbuero."
5. _(Agent muss die wichtigsten Infos aus dem langen Text extrahieren, ohne nochmal alles nachzufragen)_

### Erwartetes Ergebnis
- Ticket erstellt: Ja
- Kategorie: network
- Prioritaet: high (mehrere Kollegen betroffen)
- Pflichtfelder erfasst: Name (Hildegard Meisterfeld), Firma (Meisterfeld Steuerberatung), Problem (Internet und IP-Telefon ausgefallen), Rueckruf
- Optionale Felder: others_affected (ja), problem_since ("gestern Nachmittag gegen drei")

### Worauf achten
- Agent muss aus dem langen Text die Kernpunkte extrahieren
- Zusammenfassung muss praegnant sein (nicht den ganzen Monolog wiederholen)
- "Mehrere Kollegen betroffen" muss erkannt werden -> HIGH Prioritaet
- Agent soll NOT erneut fragen "Seit wann besteht das Problem?" (wurde ausfuehrlich geschildert)

---

# Abschnitt 3: Workflow-Tests (Szenarien 16-19)

Diese Szenarien testen den Gespraechsablauf und Sonderfaelle im Workflow.

---

## Szenario 16: Zusammenfassung korrigieren
**Kategorie:** endpoint
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. _(Normaler Ablauf bis zur Zusammenfassung)_
2. **Agent fasst zusammen:** _"Thomas Stanner von VisionaryData -- Laptop faehrt nicht hoch. Wir melden uns unter 0151 12345678. Stimmt das so?"_
3. **SIE:** "Fast. Aber es ist nicht der Laptop, sondern der Desktop-PC im Buero. Und die Nummer ist 0151 12345679, nicht 678."
4. **Agent muss die Korrektur aufnehmen und erneut zusammenfassen**
5. **SIE:** "Ja, jetzt passt es."

### Erwartetes Ergebnis
- Ticket erstellt: Ja (mit den KORRIGIERTEN Daten)
- Problem: Desktop-PC (nicht Laptop)
- Rueckrufnummer: 0151 12345679 (korrigiert)

### Worauf achten
- Agent muss Korrekturen annehmen und das Ticket mit den richtigen Daten erstellen
- Die Korrektur muss in einer neuen Zusammenfassung bestaetigt werden
- Es darf NICHT das urspruengliche (falsche) Ticket erstellt werden

---

## Szenario 17: Unklare Consent-Antwort
**Kategorie:** -- (erstmal kein Ticket)
**Prioritaet:** --

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Hmm, was heisst das genau? Wer hoert sich das an?"
3. **Agent sollte nochmal nachfragen** (z.B. "Entschuldigung, ich habe Sie nicht ganz verstanden. Darf ich das Gespraech aufzeichnen, um ein Ticket zu erstellen?")
4. **SIE:** "Ja okay, wenn das sein muss."

### Erwartetes Ergebnis
- Agent erkennt die unklare Antwort und fragt nochmal nach
- Bei der zweiten Antwort ("Ja okay") wird es als Zustimmung gewertet
- Gespraech geht normal weiter

### Worauf achten
- Agent darf die erste Antwort NICHT als Zustimmung werten
- Agent darf die erste Antwort auch NICHT als Ablehnung werten
- Es muss eine hoefliche Nachfrage kommen
- "Ja okay, wenn das sein muss" muss als Zustimmung akzeptiert werden

---

## Szenario 18: Firma fehlt trotz Nachfrage
**Kategorie:** endpoint
**Prioritaet:** normal

### Dialog (was Sie sagen)
1. **Agent sagt Consent-Text**
2. **SIE:** "Ja."
3. **Agent:** _"Vielen Dank. Bitte schildern Sie mir kurz Ihr Problem."_
4. **SIE:** "Mein Bildschirm flackert."
5. **Agent fragt nach Name und Firma**
6. **SIE:** "Thomas Stanner."
7. **Agent fragt nochmal nach der Firma** ("Von welcher Firma rufen Sie an?")
8. **SIE:** "Ach so, VisionaryData."

### Erwartetes Ergebnis
- Agent erkennt, dass nur der Name genannt wurde und fragt gezielt nach der Firma
- Nach der zweiten Antwort sind alle Daten vorhanden

### Worauf achten
- Agent muss erkennen, dass nur der Name (ohne Firma) genannt wurde
- Nachfrage nur fuer die Firma, NICHT nochmal fuer den Namen
- Formulierung: "Von welcher Firma rufen Sie an?" oder aehnlich natuerlich

---

## Szenario 19: Error-Fallback (Backend-Test)
**Kategorie:** -- (Backend-Fehler)
**Prioritaet:** --

### Was passieren soll
Dieses Szenario kann nicht direkt per Telefon getestet werden. Es beschreibt das erwartete Verhalten, wenn die Ticket-Erstellung im Backend fehlschlaegt.

### Erwartetes Verhalten bei Backend-Fehler
1. **Agent sagt:** _"Es tut mir leid, es gab ein technisches Problem bei der Ticket-Erstellung. Ich habe Ihre Daten notiert und ein Kollege wird sich bei Ihnen melden. Auf Wiederhoeren."_
2. **Backend sendet automatisch eine Fallback-E-Mail** an `ticket-support@visionarydata.de` mit allen gesammelten Daten
3. **Das Gespraech wird beendet**

### Worauf achten
- Der Anrufer bekommt eine freundliche Entschuldigung
- Es wird NICHT gesagt "Fehler 500" oder aehnliche technische Details
- Die Fallback-E-Mail muss alle gesammelten Daten enthalten (Name, Firma, Problem, Nummer)
- Dieses Szenario wird separat im Backend getestet

---

# Checkliste nach dem Testen

Bitte fuelle diese Checkliste nach allen Tests aus:

| # | Szenario | Kategorie OK? | Prioritaet OK? | Daten korrekt? | Natuerlich? | Anmerkungen |
|---|----------|:---:|:---:|:---:|:---:|---|
| 1 | Netzwerk (VPN) | | | | | |
| 2 | M365 (Teams) | | | | | |
| 3 | Endgeraet (Laptop) | | | | | |
| 4 | Drucker (Papierstau) | | | | | |
| 5 | Identitaet (Passwort) | | | | | |
| 6 | Security (Ransomware) | | | | | |
| 7 | Sonstiges (Neuer MA) | | | | | |
| 8 | Alle Infos auf einmal | | | | | |
| 9 | Name fehlt | | | | | |
| 10 | Consent verweigert | -- | -- | -- | | |
| 11 | Security mitten im Gespraech | | | | | |
| 12 | Zweites Problem | | | | | |
| 13 | Nummer im Display | | | | | |
| 14 | Kurze Beschreibung | | | | | |
| 15 | Lange Erklaerung | | | | | |
| 16 | Zusammenfassung korrigieren | | | | | |
| 17 | Unklare Consent-Antwort | -- | -- | -- | | |
| 18 | Firma fehlt nach Nachfrage | | | | | |

**Bewertungsskala fuer "Natuerlich?":**
- Sehr gut = Fuehlte sich an wie ein echtes Gespraech
- Gut = Leicht roboterhaft, aber akzeptabel
- Maessig = Spuerbar kuenstlich, aber funktional
- Schlecht = Stoerend kuenstlich oder verwirrend

---

**Bei Fragen oder Problemen:** Einfach kurz Bescheid geben, welches Szenario nicht wie erwartet funktioniert hat. Am besten mit einer kurzen Beschreibung, was stattdessen passiert ist.
