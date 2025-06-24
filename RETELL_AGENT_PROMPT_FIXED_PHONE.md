# WICHTIGE ÄNDERUNG für Telefonnummer-Handling

## Das Problem:
Retell interpretiert `"caller_number"` als literalen String, nicht als Variable.

## Die Lösung:
Im Prompt bei der `collect_appointment_data` Funktion das Telefonnummer-Feld LEER lassen oder WEGLASSEN.

## Geänderter Abschnitt für den Prompt:

### Bei der collect_appointment_data Funktion:

```json
{
  "datum": "24.06.2025",
  "uhrzeit": "16:30",
  "name": "Hans Schuster",
  "dienstleistung": "Beratung",
  "email": "hans.schuster@email.de"
}
```

**WICHTIG**: Das Feld `telefonnummer` KOMPLETT WEGLASSEN! Das Backend holt sich die Nummer automatisch aus dem Call-Objekt.

### Alternative wenn Retell ein Feld erwartet:

```json
{
  "datum": "24.06.2025",
  "uhrzeit": "16:30",
  "name": "Hans Schuster",
  "telefonnummer": "",  // Leer lassen!
  "dienstleistung": "Beratung",
  "email": "hans.schuster@email.de"
}
```

## Vollständiger korrigierter Prompt-Abschnitt:

Ersetze im Prompt den Abschnitt "Kontaktdaten-Erfassung" mit:

```
## Kontaktdaten-Erfassung
### Telefonnummer - KRITISCH WICHTIG:
- Die Telefonnummer des Anrufers wird AUTOMATISCH vom System erfasst
- Du musst die Telefonnummer NIEMALS abfragen oder in die Funktion eintragen
- Das System holt sich die Nummer automatisch aus den Anrufdaten
- NUR wenn das System dir explizit sagt "Telefonnummer unbekannt", dann frage nach
- Ansonsten: IGNORIERE die Telefonnummer komplett

### Bei der collect_appointment_data Funktion:
- Trage NIE eine Telefonnummer ein
- Lasse das Feld `telefonnummer` WEG oder leer
- Das System fügt die Nummer automatisch hinzu

### E-Mail (für Bestätigung):
[Rest bleibt gleich...]
```

## Test-Szenario:

1. Prompt mit dieser Änderung aktualisieren
2. Testanruf machen
3. Agent sollte NICHT mehr nach Telefonnummer fragen
4. Backend erhält Nummer aus `call.from_number`