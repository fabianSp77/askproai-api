# Testanleitung für erweiterte Verfügbarkeitsprüfung

## 1. Technischer Test (ohne Retell.ai)

### Schritt 1: Server starten
```bash
cd /var/www/api-gateway
php artisan serve
```

### Schritt 2: Test-Scripts ausführen

**Basis-Test:**
```bash
php test_bidirectional_retell.php
```

**Erweiterte Tests mit Präferenzen:**
```bash
php test_advanced_availability.php
```

### Was Sie sehen sollten:
- Test 1: Prüfung eines spezifischen Termins
- Test 2: Nur donnerstags verfügbar
- Test 3: Donnerstags 16-19 Uhr
- Test 4: Nur vormittags

## 2. Live-Test mit Retell.ai

### Schritt 1: Agent-Prompt aktualisieren

1. Öffnen Sie Retell.ai Dashboard
2. Bearbeiten Sie Ihren Agent
3. Fügen Sie die Erweiterungen aus `RETELL_AGENT_PROMPT_UPDATED.md` hinzu

### Schritt 2: Webhook konfigurieren

Stellen Sie sicher, dass der Webhook auf den richtigen Endpoint zeigt:
```
https://ihre-domain.de/api/retell/webhook
```

### Schritt 3: Test-Szenarien durchspielen

**Szenario 1: Einfache Terminanfrage**
- Anrufen: "Ich hätte gerne einen Termin morgen um 14 Uhr"
- Erwartung: Agent prüft Verfügbarkeit und antwortet direkt

**Szenario 2: Mit Präferenzen**
- Anrufen: "Ich kann nur donnerstags zwischen 16 und 19 Uhr"
- Erwartung: Agent findet passende Donnerstag-Termine

**Szenario 3: Nur vormittags**
- Anrufen: "Bei mir geht es nur vormittags"
- Erwartung: Agent schlägt Termine vor 12 Uhr vor

**Szenario 4: Termin nicht verfügbar**
- Anrufen: "Ich möchte heute um 23 Uhr einen Termin"
- Erwartung: Agent bietet Alternativen an

## 3. Debug und Monitoring

### Logs überprüfen:
```bash
tail -f storage/logs/laravel.log | grep -E "availability|Retell"
```

### Was in den Logs stehen sollte:
```
[2025-06-16] local.INFO: Handling inbound call for real-time response
[2025-06-16] local.INFO: Availability check completed with preferences
[2025-06-16] local.INFO: Sending real-time response to Retell.ai
```

## 4. Troubleshooting

### Problem: Agent antwortet nicht auf Verfügbarkeitsprüfung
**Lösung:** 
- Prüfen Sie, ob `check_availability` richtig gesetzt wird
- Schauen Sie in die Logs für Fehler

### Problem: Keine Alternativen werden gefunden
**Lösung:**
- Prüfen Sie Cal.com API-Key
- Stellen Sie sicher, dass Event Types existieren
- Checken Sie, ob Slots in Cal.com verfügbar sind

### Problem: Präferenzen werden nicht verstanden
**Lösung:**
- Testen Sie mit den exakten Formulierungen aus der Anleitung
- Erweitern Sie ggf. den Parser in `parseCustomerPreferences()`

## 5. Erweiterte Tests

### Custom Präferenzen testen:
```bash
# Erstellen Sie eine test_custom_preferences.php
php -r "
\$prefs = [
    'nur montags und mittwochs',
    'ab 15 Uhr',
    'vormittags außer freitags',
    'donnerstags zwischen 14 und 18 Uhr'
];

foreach (\$prefs as \$pref) {
    echo \"Testing: \$pref\n\";
    // Hier würde der API-Call kommen
}
"
```

### Performance testen:
- Messen Sie die Response-Zeit der Verfügbarkeitsprüfung
- Ziel: < 500ms für optimale Gesprächsqualität

## 6. Produktiv-Checkliste

- [ ] Agent-Prompt ist aktualisiert
- [ ] Webhook-URL ist korrekt konfiguriert
- [ ] Cal.com API-Key ist gesetzt
- [ ] Event Types sind in Cal.com angelegt
- [ ] Verfügbare Slots existieren in Cal.com
- [ ] Logs werden überwacht
- [ ] Backup der alten Konfiguration erstellt

## Beispiel-Response für Debugging

Wenn alles funktioniert, sollte die Response so aussehen:
```json
{
  "response": {
    "agent_id": "agent_xxx",
    "dynamic_variables": {
      "requested_slot_available": false,
      "alternative_slots": "Donnerstag, den 19. Juni um 16:30 Uhr oder Donnerstag, den 26. Juni um 17:00 Uhr",
      "alternative_dates": ["2025-06-19", "2025-06-26"],
      "slots_count": 2,
      "preference_matched": true,
      "availability_checked": true
    }
  }
}
```