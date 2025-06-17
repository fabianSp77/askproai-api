# Prompt-Ergänzung für Mitarbeiterwünsche

## Fügen Sie diese Abschnitte zu Ihrem bestehenden Prompt hinzu:

### Nach "## Kontaktdaten-Erfassung" ergänzen Sie:

```
## Mitarbeiterwünsche erfassen

### Wenn der Kunde einen bestimmten Mitarbeiter wünscht:
- Erfasse den Namen im Feld `mitarbeiter_wunsch`
- Beispiele: "Ich möchte zu Frau Schmidt", "Kann ich wieder zu Thomas?", "Bei Dr. Müller bitte"
- Bestätige: "Gerne, ich prüfe die Verfügbarkeit von [Mitarbeitername]"

### Wichtige Hinweise:
- Erfasse den Mitarbeiterwunsch genau wie genannt
- Das System prüft automatisch ob dieser Mitarbeiter die gewünschte Dienstleistung anbietet
- Falls der Mitarbeiter nicht verfügbar ist, werden Alternativen vorgeschlagen
```

### Im "## Gesprächsleitfaden" nach Dienstleistung ergänzen:

```
- Nach der Dienstleistung frage: "Haben Sie einen Wunsch-Mitarbeiter bei dem Sie den Termin möchten?"
- Wenn ja: Erfasse den Namen
- Wenn nein: "Kein Problem, dann schaue ich wer verfügbar ist"
```

## Beispiel-Dialog:

```
Kunde: "Ich brauche einen Termin für Physiotherapie"
Clara: "Gerne, haben Sie einen Wunsch-Mitarbeiter bei dem Sie den Termin möchten?"
Kunde: "Ja, bei Frau Schmidt war ich schon mal, die war super"
Clara: "Sehr gerne, ich prüfe die Verfügbarkeit von Frau Schmidt. Wann hätten Sie denn Zeit?"
```

## Das finale JSON Schema für collect_appointment_data:

```json
{
  "type": "object",
  "properties": {
    "datum": {
      "type": "string"
    },
    "name": {
      "type": "string"
    },
    "telefonnummer": {
      "type": "string"
    },
    "dienstleistung": {
      "type": "string"
    },
    "uhrzeit": {
      "type": "string"
    },
    "email": {
      "type": "string"
    },
    "kundenpraeferenzen": {
      "type": "string"
    },
    "mitarbeiter_wunsch": {
      "type": "string"
    }
  }
}
```

## Was das System automatisch macht:

1. **Mitarbeiter-Suche**: Findet den Mitarbeiter anhand des Namens
2. **Service-Check**: Prüft ob der Mitarbeiter die gewünschte Dienstleistung anbietet
3. **Verfügbarkeit**: Checkt die Verfügbarkeit des gewünschten Mitarbeiters
4. **Alternativen**: Falls nicht verfügbar, sucht das System andere Mitarbeiter oder Zeiten

## Zusätzliche Logik (bereits implementiert):

- Sucht nach Namen, Vornamen oder Nachnamen
- Funktioniert auch mit Teilnamen (z.B. "Schmidt" findet "Frau Dr. Schmidt")
- Berücksichtigt nur Mitarbeiter der jeweiligen Firma
- Loggt alle Suchanfragen für spätere Optimierung