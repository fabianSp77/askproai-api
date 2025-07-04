# 🎯 Claude-Befehle für dich - Einfach kopieren & einfügen!

> **So funktioniert's**: Kopiere einfach den Befehl und gib ihn Claude. Er weiß dann genau, was zu tun ist!

## 📑 Inhaltsverzeichnis

1. [Claude Self-Update](#claude-self-update)
2. [Neuen Kunden einrichten](#neuen-kunden-einrichten)
3. [Telefonnummer testen](#telefonnummer-testen)
4. [Business KPIs anzeigen](#business-kpis-anzeigen)
5. [Änderungen vor Deployment prüfen](#änderungen-vor-deployment-prüfen)
6. [Beste Lösung finden (MCP)](#beste-lösung-finden-mcp)
7. [Webhooks überwachen](#webhooks-überwachen)
8. [Notfall - Alles fixen](#notfall---alles-fixen)
9. [System-Gesundheit prüfen](#system-gesundheit-prüfen)
10. [Datenfluss debuggen](#datenfluss-debuggen)
11. [Deployment vorbereiten](#deployment-vorbereiten)
12. [Datenbank-Abfragen](#datenbank-abfragen)
13. [Kombinierte Befehle](#kombinierte-befehle)
14. [Die 5 wichtigsten für deinen Alltag](#die-5-wichtigsten-für-deinen-alltag)

---

## Claude Self-Update

### Befehl für Claude:
```
Claude, führe einen Self-Update durch: Lies CLAUDE_CONTEXT_SUMMARY.md, BEST_PRACTICES_IMPLEMENTATION.md und alle heute geänderten Dateien. Bestätige dein Verständnis der neuen Features.
```

**Wann nutzen**: Am Anfang einer neuen Session oder nach großen Änderungen

---

## Neuen Kunden einrichten

### Befehl für Claude:
```
Claude, richte einen neuen Kunden ein mit: Firmenname "Zahnarztpraxis Dr. Müller", Telefon "+49 30 555 1234", Email "info@zahnarzt-mueller.de", Filiale "Praxis Berlin-Mitte"
```

**Dein konkretes Beispiel**:
```
Claude, nutze ask-setup für: "Friseursalon Beauty Style", "+49 30 123 4567", "kontakt@beauty-style.de", "Hauptsalon"
```

---

## Telefonnummer testen

### Befehl für Claude:
```
Claude, teste warum die Telefonnummer +49 30 555 1234 nicht richtig zugeordnet wird. Zeige mir den kompletten Flow.
```

**Dein konkretes Beispiel**:
```
Claude, die Anrufe von +49 30 987 6543 kommen nicht an. Nutze ask-phone zum Debuggen.
```

---

## Business KPIs anzeigen

### Befehl für Claude:
```
Claude, zeige mir die KPIs für Company ID 1 in einem übersichtlichen Format. Ich brauche Anrufe, Termine und Conversion Rate.
```

**Dein konkretes Beispiel**:
```
Claude, erstelle mir einen Wochenbericht mit allen KPIs für unseren Hauptkunden (Company 1).
```

---

## Änderungen vor Deployment prüfen

### Befehl für Claude:
```
Claude, analysiere die Auswirkungen der letzten Änderungen bevor wir deployen. Gibt es Breaking Changes?
```

**Dein konkretes Beispiel**:
```
Claude, wir wollen gleich live gehen. Mache einen ask-impact Check und sage mir ob es sicher ist.
```

---

## Beste Lösung finden (MCP)

### Befehl für Claude:
```
Claude, finde den besten MCP-Server für: "Ich muss alle Termine von heute exportieren und per Email versenden"
```

**Dein konkretes Beispiel**:
```
Claude, nutze MCP Discovery für: "Kunde anrufen lassen und automatisch Folgetermin buchen"
```

---

## Webhooks überwachen

### Befehl für Claude:
```
Claude, überprüfe ob alle Webhooks funktionieren. Zeige mir Failed Webhooks der letzten Stunde.
```

**Dein konkretes Beispiel**:
```
Claude, wir bekommen keine Anrufe mehr rein. Checke die Webhook Health!
```

---

## Notfall - Alles fixen

### Befehl für Claude:
```
Claude, führe den Emergency Fix aus! Die App zeigt nur Fehler.
```

**Dein konkretes Beispiel**:
```
Claude, ich bekomme "Access Denied" Fehler. Nutze ask-fix zum Reparieren!
```

---

## System-Gesundheit prüfen

### Befehl für Claude:
```
Claude, mache einen kompletten Health Check. Ich will wissen ob alles läuft.
```

**Dein konkretes Beispiel**:
```
Claude, bevor ich ins Wochenende gehe: Ist das System gesund? Mache ask-health.
```

---

## Datenfluss debuggen

### Befehl für Claude:
```
Claude, zeige mir den Datenfluss für Call ID "call_xyz123". Wo hängt es?
```

**Dein konkretes Beispiel**:
```
Claude, der Anruf kam rein aber kein Termin wurde erstellt. Tracke den Flow!
```

---

## Deployment vorbereiten

### Befehl für Claude:
```
Claude, bereite das Deployment vor. Alle Tests, Impact Analysis und Quality Checks.
```

**Dein konkretes Beispiel**:
```
Claude, wir wollen Montag deployen. Ist alles ready? Nutze ask-deploy!
```

---

## Datenbank-Abfragen

### Befehl für Claude:
```
Claude, zeige mir die letzten 50 Anrufe mit Kundennamen und Firma.
```

**Dein konkretes Beispiel**:
```
Claude, ich brauche alle Anrufe von heute für den Kunden "Zahnarztpraxis Dr. Müller".
```

---

## Kombinierte Befehle

### Morgen-Routine:
```
Claude, guten Morgen! Mache einen Health Check, zeige mir die KPIs von gestern und liste alle Failed Webhooks.
```

### Vor dem Wochenende:
```
Claude, Wochenend-Check: System Health, offene Todos, kritische Errors der Woche.
```

### Neuer Feature Launch:
```
Claude, wir launchen das neue Feature. Mache: Impact Analysis, alle Tests, Documentation Check, dann Deployment Vorbereitung.
```

### Problem-Debugging:
```
Claude, Kunde beschwert sich dass keine Termine gebucht werden. Debugge: Phone Resolution für +49 30 123456, Webhook Health, Data Flows der letzten Stunde.
```

---

## Die 5 wichtigsten für deinen Alltag

1. **Bei Problemen**: "Claude, nutze ask-fix - nichts geht mehr!"
2. **Neuer Kunde**: "Claude, setup für [Firma, Telefon, Email]"
3. **Morgens**: "Claude, ask-health - läuft alles?"
4. **Für Reports**: "Claude, KPIs für Company 1 diese Woche"
5. **Vor Deploy**: "Claude, ask-deploy - können wir live gehen?"

---

## 💡 Pro-Tipps:

- **Sei spezifisch**: Statt "zeig mir Anrufe" → "zeig mir Failed Calls von heute"
- **Nutze IDs**: Wenn du Company ID, Call ID etc. kennst, gib sie mit
- **Kombiniere**: Du kannst mehrere Befehle in einer Nachricht geben
- **Frage nach**: Wenn du unsicher bist, frag Claude nach dem besten Befehl

---

**Speicher dir diese Datei!** Dann hast du immer die richtigen Befehle zur Hand. 🚀