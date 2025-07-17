# Notion Webhook Setup Guide

## Overview

Dieser Guide erklärt, wie Sie den Notion Webhook für Ihre AskProAI-Installation einrichten.

## Webhook-Endpunkt Details

### Produktions-URL
```
https://api.askproai.de/api/notion/webhook
```

### Test-URL
```
https://api.askproai.de/api/notion/webhook/test
```

## Einrichtung in Notion

### Schritt 1: Notion Integration erstellen

1. Gehen Sie zu: https://www.notion.so/my-integrations
2. Klicken Sie auf "New integration"
3. Geben Sie folgende Details ein:
   - **Name**: AskProAI Integration
   - **Logo**: Optional
   - **Associated workspace**: Wählen Sie Ihren Workspace

### Schritt 2: Capabilities konfigurieren

Aktivieren Sie folgende Berechtigungen:
- ✅ Read content
- ✅ Update content
- ✅ Insert content
- ✅ Read comments
- ✅ Read user information (ohne Email-Adressen)

### Schritt 3: Webhook konfigurieren

1. Im Integration-Dashboard, gehen Sie zum Tab "Capabilities"
2. Scrollen Sie zu "Webhooks"
3. Aktivieren Sie "Use webhooks"
4. Fügen Sie Ihre Webhook-URL hinzu:
   ```
   https://api.askproai.de/api/notion/webhook
   ```

### Schritt 4: Event-Typen auswählen

Wählen Sie die Events, die Sie empfangen möchten:
- ✅ `page.updated` - Wenn eine Seite aktualisiert wird
- ✅ `page.created` - Wenn eine neue Seite erstellt wird
- ✅ `database.updated` - Wenn eine Datenbank geändert wird
- ✅ `block.updated` - Wenn Inhaltsblöcke geändert werden
- ✅ `comment.created` - Wenn neue Kommentare erstellt werden

### Schritt 5: Integration aktivieren

1. Speichern Sie die Webhook-Konfiguration
2. Kopieren Sie den "Internal Integration Token" (bereits in .env gespeichert)
3. Die Integration ist jetzt aktiv

## Webhook testen

### Test-Request senden
```bash
curl -X POST https://api.askproai.de/api/notion/webhook/test \
  -H "Content-Type: application/json" \
  -d '{
    "type": "test",
    "timestamp": "'$(date -u +%Y-%m-%dT%H:%M:%SZ)'"
  }'
```

### Logs überprüfen
```bash
# Auf dem Server
tail -f storage/logs/laravel.log | grep "Notion webhook"
```

## Unterstützte Events

### page.updated
Wird ausgelöst wenn:
- Seiteneigenschaften geändert werden
- Seitentitel aktualisiert wird
- Seite verschoben wird

**Aktion**: Cache wird geleert, Änderung wird in Memory Bank gespeichert

### page.created
Wird ausgelöst wenn:
- Neue Seite erstellt wird
- Seite dupliziert wird

**Aktion**: Bei Task-Seiten wird automatisch eine Benachrichtigung erstellt

### database.updated
Wird ausgelöst wenn:
- Datenbank-Schema geändert wird
- Neue Properties hinzugefügt werden

**Aktion**: Cache für Datenbankabfragen wird geleert

### block.updated
Wird ausgelöst wenn:
- Inhaltsblöcke geändert werden
- Neue Blöcke hinzugefügt werden
- Blöcke gelöscht werden

**Aktion**: Seiten-Cache wird aktualisiert

### comment.created
Wird ausgelöst wenn:
- Neuer Kommentar erstellt wird
- Jemand eine Seite kommentiert

**Aktion**: Kommentar wird für potenzielle AI-Antwort gespeichert

## Integration mit Laravel

### Memory Bank Integration
Alle Webhook-Events werden automatisch in der Memory Bank gespeichert:
```php
// Beispiel: Abrufen der letzten Webhook-Events
$memoryBank = app(MemoryBankMCPServer::class);
$events = $memoryBank->executeTool('search_memories', [
    'query' => 'notion webhook',
    'context' => 'webhooks',
    'limit' => 10
]);
```

### Cache-Management
Bei Updates werden relevante Caches automatisch geleert:
- Seiten-Cache: `notion:page:{page_id}`
- Datenbank-Cache: `notion:database:{database_id}:*`

### Event-Verarbeitung
Events werden asynchron verarbeitet und lösen je nach Typ verschiedene Aktionen aus:
- Task-Erstellung → Benachrichtigung
- Seiten-Update → Cache-Refresh
- Kommentar → AI-Response-Queue

## Sicherheit

### IP-Whitelist (Optional)
Notion sendet Webhooks von folgenden IP-Bereichen:
- Noch nicht dokumentiert von Notion

### Signature Verification
Notion unterstützt derzeit keine Webhook-Signaturen. Als Alternative:
1. Verwenden Sie HTTPS
2. Implementieren Sie Rate Limiting
3. Validieren Sie empfangene Daten

## Troubleshooting

### Webhook empfängt keine Events
1. Überprüfen Sie die Integration-Berechtigungen
2. Stellen Sie sicher, dass die Integration Zugriff auf die relevanten Seiten hat
3. Testen Sie mit der Test-URL

### 401 Unauthorized
- Überprüfen Sie den API-Token in .env
- Stellen Sie sicher, dass die Integration aktiv ist

### Events werden nicht verarbeitet
- Überprüfen Sie die Laravel Logs
- Stellen Sie sicher, dass Queue Worker läuft
- Überprüfen Sie Memory Bank auf gespeicherte Events

## Nächste Schritte

1. **Integration zu Seiten hinzufügen**: 
   - Öffnen Sie relevante Notion-Seiten
   - Klicken Sie auf "..." → "Add connections"
   - Wählen Sie Ihre Integration

2. **Automatisierungen einrichten**:
   - Definieren Sie Aktionen für verschiedene Event-Typen
   - Erstellen Sie Custom Handler für spezifische Use Cases

3. **Monitoring einrichten**:
   - Überwachen Sie Webhook-Performance
   - Setzen Sie Alerts für fehlgeschlagene Events