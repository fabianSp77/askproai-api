# Verbindungsprobleme beheben

Haben Sie Schwierigkeiten, sich mit AskProAI zu verbinden? Diese Anleitung hilft Ihnen, häufige Verbindungsprobleme zu lösen.

## Schnelle Lösungen

### 1. Grundlegende Checks

**Internetverbindung prüfen**
- Öffnen Sie andere Websites
- Testen Sie die Geschwindigkeit auf [fast.com](https://fast.com)
- Starten Sie Ihren Router neu

**Browser aktualisieren**
- Chrome: Menü → Hilfe → Über Google Chrome
- Firefox: Menü → Hilfe → Über Firefox
- Edge: Menü → Hilfe → Über Microsoft Edge
- Safari: Safari → Über Safari

**Cache leeren**
- Windows/Linux: Strg + Shift + R
- Mac: Cmd + Shift + R
- Oder: Browser-Einstellungen → Browserdaten löschen

## Häufige Probleme

### "Seite kann nicht geladen werden"

**Mögliche Ursachen:**
1. Firewall blockiert Zugriff
2. DNS-Probleme
3. Proxy-Einstellungen
4. Wartungsarbeiten

**Lösungsschritte:**

1. **Firewall prüfen**
   - Fügen Sie askproai.de zur Whitelist hinzu
   - Deaktivieren Sie temporär die Firewall
   - Prüfen Sie Antivirus-Software

2. **DNS ändern**
   - Verwenden Sie Google DNS: 8.8.8.8
   - Oder Cloudflare: 1.1.1.1
   - DNS-Cache leeren: `ipconfig /flushdns`

3. **Proxy deaktivieren**
   - Browser-Einstellungen → Proxy
   - "Kein Proxy" auswählen
   - Direkte Verbindung testen

### "Zeitüberschreitung der Anforderung"

**Sofortmaßnahmen:**
1. Seite neu laden (F5)
2. Anderen Browser testen
3. Mobiles Internet verwenden
4. VPN deaktivieren

**Erweiterte Lösungen:**
```bash
# Windows - Netzwerk zurücksetzen
netsh winsock reset
netsh int ip reset
ipconfig /release
ipconfig /renew

# Mac/Linux - DNS-Cache leeren
sudo dscacheutil -flushcache  # Mac
sudo systemctl restart systemd-resolved  # Linux
```

### "Sichere Verbindung fehlgeschlagen"

**SSL/TLS-Probleme beheben:**

1. **Datum und Uhrzeit prüfen**
   - Muss korrekt eingestellt sein
   - Automatische Zeitzone aktivieren

2. **Browser-Sicherheit**
   - Ausnahme für askproai.de hinzufügen
   - HTTPS-Only-Modus prüfen
   - Erweiterungen deaktivieren

3. **Zertifikat prüfen**
   - Klicken Sie auf das Schloss-Symbol
   - "Zertifikat anzeigen"
   - Gültigkeit prüfen

## Plattform-spezifische Lösungen

### Windows

**Netzwerkproblembehandlung:**
1. Rechtsklick auf Netzwerksymbol
2. "Probleme beheben"
3. Den Anweisungen folgen

**Windows Defender:**
- Ausnahme für AskProAI hinzufügen
- Smart Screen temporär deaktivieren

### Mac

**Safari-spezifisch:**
- Entwickler-Menü aktivieren
- Cache leeren: Cmd + Option + E
- Website-Daten löschen

**Netzwerk-Diagnose:**
- Systemeinstellungen → Netzwerk
- "Diagnose" ausführen

### Mobile Geräte

**iOS:**
- Einstellungen → Safari → Verlauf löschen
- Flugmodus ein/aus
- Netzwerkeinstellungen zurücksetzen

**Android:**
- App-Cache leeren
- Chrome: Menü → Verlauf → Browserdaten löschen
- Mobile Daten aus/ein

## API-Verbindungsprobleme

### Webhook-Fehler

**Symptome:**
- Anrufe werden nicht verarbeitet
- Termine werden nicht synchronisiert
- Benachrichtigungen fehlen

**Prüfungen:**
1. API-Status auf [status.askproai.de](https://status.askproai.de)
2. Webhook-URL in Ihren Einstellungen
3. API-Schlüssel gültig?

### Rate Limiting

**429 - Too Many Requests:**
- Maximal 100 Anfragen/Minute
- Warten Sie 60 Sekunden
- Implementieren Sie Backoff-Strategie

## Erweiterte Diagnose

### Netzwerk-Tools

**Ping-Test:**
```bash
ping api.askproai.de
```

**Traceroute:**
```bash
# Windows
tracert api.askproai.de

# Mac/Linux
traceroute api.askproai.de
```

**Port-Test:**
```bash
telnet api.askproai.de 443
```

### Browser-Konsole

1. F12 drücken (Entwicklertools)
2. Tab "Netzwerk" oder "Network"
3. Seite neu laden
4. Rote Einträge = Fehler

**Häufige Fehler:**
- **404**: Seite nicht gefunden
- **500**: Serverfehler
- **503**: Service nicht verfügbar

## Workarounds

### Alternative Zugriffsmöglichkeiten

1. **Mobile App**
   - iOS: App Store
   - Android: Google Play

2. **API-Zugriff**
   - Direkter API-Zugriff
   - Postman Collection verfügbar

3. **Backup-URLs**
   - app.askproai.de
   - portal.askproai.de

### Offline-Funktionen

Einige Funktionen sind offline verfügbar:
- Gespeicherte Berichte
- Exportierte Daten
- Mobile App Cache

## Fehler melden

### Was wir benötigen

1. **Fehlerbeschreibung**
   - Was haben Sie versucht?
   - Wann trat der Fehler auf?
   - Fehlermeldung (Screenshot)

2. **Systeminformationen**
   - Browser und Version
   - Betriebssystem
   - Internetanbieter

3. **Diagnose-Daten**
   - HAR-Datei aus Browser
   - Console-Log
   - Netzwerk-Trace

### Kontakt

**Support-Kanäle:**
- **E-Mail**: support@askproai.de
- **Telefon**: +49 123 456789
- **Chat**: Auf der Website
- **Status**: [status.askproai.de](https://status.askproai.de)

## Präventive Maßnahmen

### Best Practices

1. **Browser aktuell halten**
2. **Regelmäßig Cache leeren**
3. **Sichere Verbindung nutzen**
4. **Backup-Internetzugang**

### Monitoring

- Status-Seite bookmarken
- Newsletter abonnieren
- Wartungsfenster beachten

> **Tipp**: Führen Sie ein Verbindungsprotokoll, um wiederkehrende Muster zu erkennen. Dies hilft unserem Support-Team bei der Diagnose.