# Technische Anforderungen

Um AskProAI optimal nutzen zu können, sollte Ihr System diese technischen Anforderungen erfüllen.

## Systemanforderungen

### Unterstützte Browser

**Desktop-Browser (empfohlen):**
- **Google Chrome**: Version 90 oder höher
- **Mozilla Firefox**: Version 88 oder höher
- **Microsoft Edge**: Version 90 oder höher
- **Safari**: Version 14 oder höher

**Mobile Browser:**
- **Chrome Mobile**: Aktuelle Version
- **Safari iOS**: iOS 14 oder höher
- **Samsung Internet**: Version 14 oder höher

> **Hinweis**: Internet Explorer wird nicht unterstützt.

### Betriebssysteme

**Desktop:**
- Windows 10 oder höher
- macOS 10.15 (Catalina) oder höher
- Ubuntu 20.04 LTS oder höher
- Andere moderne Linux-Distributionen

**Mobile:**
- iOS 14.0 oder höher
- Android 8.0 (API Level 26) oder höher

## Internetverbindung

### Mindestanforderungen

**Bandbreite:**
- **Minimum**: 1 Mbps Download / 0.5 Mbps Upload
- **Empfohlen**: 10 Mbps Download / 5 Mbps Upload
- **Optimal**: 25 Mbps Download / 10 Mbps Upload

**Latenz:**
- Maximal 200ms zu unseren Servern
- Optimal unter 50ms

### Verbindungsqualität testen

```bash
# Geschwindigkeit testen
https://fast.com

# Latenz testen
ping api.askproai.de
```

## Hardware-Anforderungen

### Desktop/Laptop

**Minimum:**
- Prozessor: Dual-Core 2.0 GHz
- RAM: 4 GB
- Bildschirmauflösung: 1366x768
- Speicherplatz: 500 MB frei

**Empfohlen:**
- Prozessor: Quad-Core 2.5 GHz
- RAM: 8 GB oder mehr
- Bildschirmauflösung: 1920x1080
- SSD-Festplatte

### Mobile Geräte

**Smartphones:**
- RAM: Mindestens 3 GB
- Speicher: 100 MB für App
- Kamera: Für QR-Code-Scan

**Tablets:**
- RAM: Mindestens 4 GB
- Bildschirm: 9" oder größer
- Touch-Display erforderlich

## Browser-Einstellungen

### Erforderliche Funktionen

**Aktiviert sein müssen:**
- JavaScript
- Cookies (First-Party)
- LocalStorage
- WebSockets
- TLS 1.2 oder höher

**Empfohlene Einstellungen:**
- Popup-Blocker: Ausnahme für askproai.de
- Automatische Updates aktiviert
- Hardware-Beschleunigung ein

### Sicherheitseinstellungen

```javascript
// Prüfen Sie diese Einstellungen:
- Cookies von Drittanbietern: Optional
- Do-Not-Track: Kein Einfluss
- Privater Modus: Eingeschränkte Funktionen
```

## Netzwerk-Konfiguration

### Firewall-Regeln

**Ausgehende Verbindungen erlauben:**
- HTTPS (Port 443) zu *.askproai.de
- WSS (WebSocket) zu wss://api.askproai.de
- HTTP (Port 80) für Weiterleitungen

**Domains auf Whitelist:**
```
api.askproai.de
app.askproai.de
cdn.askproai.de
*.askproai.de
```

### Proxy-Konfiguration

Falls Sie einen Proxy verwenden:
- HTTPS-Proxy erforderlich
- WebSocket-Unterstützung
- Authentifizierung möglich
- PAC-Datei unterstützt

## API-Integration

### Technische Spezifikationen

**REST API:**
- Protokoll: HTTPS only
- Format: JSON
- Authentifizierung: Bearer Token
- Rate Limit: 100 Requests/Minute

**Webhook-Anforderungen:**
- Öffentlich erreichbare URL
- HTTPS mit gültigem Zertifikat
- Antwortzeit < 5 Sekunden
- Signature-Verifizierung

### Entwickler-Tools

**Unterstützte SDKs:**
- JavaScript/TypeScript
- Python
- PHP
- Java
- Go

**Dokumentation:**
- OpenAPI 3.0 Spezifikation
- Postman Collection
- Beispiel-Implementierungen

## Mobile App

### iOS-Anforderungen

**iPhone:**
- iOS 14.0 oder höher
- iPhone 6s oder neuer
- 100 MB freier Speicher

**iPad:**
- iPadOS 14.0 oder höher
- iPad (6. Generation) oder neuer
- iPad Mini 5 oder neuer

### Android-Anforderungen

**Smartphones:**
- Android 8.0 (API 26) oder höher
- 3 GB RAM minimum
- ARMv7 oder x86 Prozessor

**Berechtigungen:**
- Internet-Zugriff
- Kamera (optional)
- Mikrofon (für Sprachfunktionen)
- Benachrichtigungen

## Barrierefreiheit

### Unterstützte Technologien

**Screen Reader:**
- NVDA (Windows)
- JAWS (Windows)
- VoiceOver (macOS/iOS)
- TalkBack (Android)

**Tastatur-Navigation:**
- Vollständige Tastatur-Unterstützung
- Tab-Navigation
- Shortcuts verfügbar

**Visuelle Anpassungen:**
- High-Contrast-Modus
- Zoom bis 200%
- Farbblindheit-Modus

## Performance-Optimierung

### Browser-Optimierung

**Cache-Einstellungen:**
- Browser-Cache aktivieren
- Mindestens 100 MB Cache
- Automatische Bereinigung deaktivieren

**Erweiterungen:**
- Ad-Blocker können Probleme verursachen
- VPN kann Latenz erhöhen
- Minimal Erweiterungen verwenden

### System-Optimierung

**Windows:**
```cmd
# DNS-Cache leeren
ipconfig /flushdns

# Netzwerk optimieren
netsh int tcp set global autotuninglevel=normal
```

**macOS:**
```bash
# DNS-Cache leeren
sudo dscacheutil -flushcache

# mDNS Cache leeren
sudo killall -HUP mDNSResponder
```

## Fehlerbehebung

### Kompatibilitätsprüfung

Besuchen Sie: [app.askproai.de/check](https://app.askproai.de/check)

**Geprüft werden:**
- Browser-Version
- JavaScript-Support
- Cookie-Einstellungen
- WebSocket-Verbindung
- API-Erreichbarkeit

### Häufige Probleme

**"Browser nicht unterstützt":**
- Browser aktualisieren
- Anderen Browser testen
- JavaScript aktivieren

**"Verbindung langsam":**
- Bandbreite prüfen
- Näher am Router positionieren
- Hintergrund-Downloads pausieren

## Updates und Wartung

### Browser-Updates

**Automatische Updates:**
- Chrome: Standardmäßig aktiviert
- Firefox: Einstellungen → Allgemein
- Edge: Hilfe → Über Microsoft Edge

### System-Updates

Halten Sie Ihr System aktuell:
- Sicherheitsupdates installieren
- Treiber aktualisieren
- Firmware-Updates durchführen

## Support

Bei technischen Fragen:
- **E-Mail**: techsupport@askproai.de
- **Dokumentation**: docs.askproai.de
- **System-Check**: app.askproai.de/check

> **Tipp**: Führen Sie regelmäßig den System-Check durch, um sicherzustellen, dass Ihr System optimal konfiguriert ist.