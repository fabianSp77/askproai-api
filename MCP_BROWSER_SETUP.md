# MCP Browser Control Setup für Claude

## Installation auf deinem lokalen Rechner

### 1. MCP Puppeteer Server installieren

```bash
# Global installieren
npm install -g @modelcontextprotocol/server-puppeteer

# Oder mit yarn
yarn global add @modelcontextprotocol/server-puppeteer
```

### 2. Claude Desktop Configuration

Finde die Config-Datei:
- **macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`
- **Linux**: `~/.config/Claude/claude_desktop_config.json`

Füge folgende Konfiguration hinzu:

```json
{
  "mcpServers": {
    "puppeteer": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-puppeteer"
      ],
      "env": {
        "PUPPETEER_EXECUTABLE_PATH": "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
      }
    }
  }
}
```

**Für Windows:**
```json
{
  "mcpServers": {
    "puppeteer": {
      "command": "npx",
      "args": [
        "-y",
        "@modelcontextprotocol/server-puppeteer"
      ],
      "env": {
        "PUPPETEER_EXECUTABLE_PATH": "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe"
      }
    }
  }
}
```

### 3. Claude Desktop neu starten

Nach der Konfiguration:
1. Beende Claude Desktop komplett
2. Starte Claude Desktop neu
3. In den Einstellungen sollte jetzt "MCP" erscheinen

## Verwendung in Claude

### Browser öffnen und navigieren:
```
Öffne Chrome und navigiere zu http://localhost:3000/admin
```

### Screenshot erstellen:
```
Mache einen Screenshot der aktuellen Seite
```

### Formular ausfüllen:
```
Fülle das Login-Formular aus:
- Email: fabian@askproai.de
- Passwort: Qwe421as1!1
Klicke auf Submit
```

### Element-Interaktion:
```
Klicke auf den Button "Neue Filiale"
Scrolle zum Footer
Hover über das Dropdown-Menü
```

### JavaScript ausführen:
```
Führe JavaScript aus: document.querySelector('.status').innerText
```

## Beispiel-Workflows

### 1. UI-Testing nach Änderungen:
```
1. Öffne http://localhost:3000/admin/company-integration-portal
2. Mache einen Screenshot
3. Klicke auf "AskProAI GmbH"
4. Warte 2 Sekunden
5. Mache einen Screenshot der geladenen Daten
6. Prüfe ob alle Integrationen sichtbar sind
```

### 2. Formular-Test:
```
1. Navigiere zum Appointment-Formular
2. Fülle alle Pflichtfelder aus
3. Mache Screenshot vor Submit
4. Klicke Submit
5. Prüfe Erfolgsmeldung
```

### 3. Responsive Design Test:
```
1. Setze Viewport auf 375x667 (iPhone SE)
2. Navigiere zur Startseite
3. Mache Screenshot
4. Setze Viewport auf 1920x1080
5. Mache Screenshot
6. Vergleiche beide Layouts
```

## Debugging

### Häufige Probleme:

**"MCP Server not found"**
- Stelle sicher, dass der globale npm-Pfad in PATH ist
- Versuche absolute Pfade in der Config

**"Chrome not found"**
- Passe PUPPETEER_EXECUTABLE_PATH an
- Installiere Chrome falls nicht vorhanden

**"Permission denied"**
- macOS: Erlaube Bildschirmaufnahme in Systemeinstellungen
- Windows: Als Administrator ausführen

## Vorteile dieser Lösung

1. **Direkte Kontrolle**: Ich kann deinen Browser wie du steuern
2. **Screenshots**: Automatische Erfassung und Analyse
3. **Interaktion**: Klicks, Eingaben, Scrolling
4. **Testing**: Automatisierte UI-Tests
5. **Debugging**: Console-Logs und Network-Analyse
6. **Lokal & Remote**: Funktioniert mit localhost und live URLs

## Sicherheit

- MCP läuft nur lokal auf deinem Rechner
- Keine Daten werden nach außen gesendet
- Du kontrollierst welche Seiten ich öffnen kann
- Sessions bleiben auf deinem Rechner

Nach der Einrichtung kann ich direkt:
- Screenshots von deinen lokalen Entwicklungen machen
- UI-Änderungen live testen
- Bugs reproduzieren
- Design-Feedback geben
- Automatisierte Tests durchführen