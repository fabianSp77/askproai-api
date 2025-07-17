# Business Portal Test Checklist

## 🔍 Test-Übersicht
**Datum**: _________________  
**Tester**: _________________  
**Umgebung**: [ ] Lokal [ ] Staging [ ] Production  
**Version**: _________________  
**Browser**: _________________  

---

## 1. Authentication & Access

### Login Process
- [ ] Login-Seite lädt korrekt (/business/login)
- [ ] Login mit korrekten Credentials funktioniert
- [ ] Login mit falschen Credentials zeigt Fehlermeldung
- [ ] "Passwort vergessen" Link funktioniert
- [ ] 2FA Challenge wird angezeigt (wenn aktiviert)
- [ ] Logout funktioniert korrekt
- [ ] Session-Timeout leitet zur Login-Seite um
- [ ] CSRF-Token ist vorhanden und wird validiert

### Admin Viewing Mode
- [ ] Admin kann als Kunde einloggen
- [ ] Admin-Banner wird oben angezeigt
- [ ] "Admin-Zugriff beenden" funktioniert
- [ ] Keine Zugriffsprobleme bei Admin-Viewing
- [ ] Admin-Aktionen sind korrekt eingeschränkt

### Berechtigungen
- [ ] Nicht-authentifizierte Nutzer werden umgeleitet
- [ ] 403-Fehler bei fehlenden Berechtigungen
- [ ] Rollenbasierte Zugriffskontrolle funktioniert

---

## 2. Dashboard (/business/dashboard)

### Statistiken & Widgets
- [ ] Anruf-Statistiken werden korrekt angezeigt
- [ ] Termin-Statistiken sind korrekt (wenn Modul aktiv)
- [ ] Kosten werden angezeigt (wenn Berechtigung vorhanden)
- [ ] Alle Zahlen sind korrekt formatiert
- [ ] Zeiträume-Filter funktioniert (Heute, 7 Tage, 30 Tage)
- [ ] Refresh-Button aktualisiert Daten

### Recent Calls Widget
- [ ] Anrufliste wird angezeigt
- [ ] Telefonnummern sind korrekt formatiert (+49 XXX XXX XXXX)
- [ ] Anrufdauer ist korrekt formatiert (mm:ss)
- [ ] Status-Badges haben korrekte Farben
- [ ] Links zu Call-Details funktionieren
- [ ] "Alle anzeigen" Link funktioniert

### Performance
- [ ] Seite lädt in unter 2 Sekunden
- [ ] Keine JavaScript-Fehler in der Console
- [ ] Alpine.js Komponenten initialisieren korrekt
- [ ] Charts/Diagramme werden gerendert
- [ ] Lazy Loading für große Datenmengen

---

## 3. Anrufe (/business/calls)

### Liste (Redesigned View)
- [ ] Moderne Tabelle wird korrekt angezeigt
- [ ] Pagination funktioniert
- [ ] Sortierung nach allen Spalten funktioniert
- [ ] Filter funktionieren (Status, Datum, Dauer)
- [ ] Suche nach Telefonnummer funktioniert
- [ ] Suche nach Kundennamen funktioniert
- [ ] Multi-Select für Bulk-Actions funktioniert
- [ ] Export-Buttons funktionieren (CSV, PDF)
- [ ] Echtzeit-Updates bei neuen Anrufen

### Call Detail Page (/business/calls/{id})
- [ ] Header mit allen wichtigen Infos
- [ ] Transkript wird korrekt formatiert angezeigt
- [ ] Zusammenfassung ist sichtbar
- [ ] Extrahierte Daten werden angezeigt
- [ ] Audio-Player funktioniert
- [ ] Download Audio-Button funktioniert
- [ ] Übersetzungs-Button funktioniert
- [ ] Copy-to-Clipboard für Daten funktioniert
- [ ] Status-Update funktioniert
- [ ] Notizen können hinzugefügt/bearbeitet werden
- [ ] Rückruf planen funktioniert
- [ ] PDF Export generiert korrekte Datei
- [ ] Druckansicht ist korrekt formatiert
- [ ] Timeline zeigt alle Events

### Email-Aktionen
- [ ] E-Mail-Modalfenster öffnet sich
- [ ] Vorlagen werden geladen
- [ ] Variablen werden ersetzt
- [ ] E-Mail-Versand funktioniert
- [ ] Versand-Bestätigung wird angezeigt

### Alpine.js Features
- [ ] Dropdown-Menus funktionieren
- [ ] Modals öffnen/schließen korrekt
- [ ] Form-Validierung funktioniert
- [ ] Loading-States werden angezeigt
- [ ] Toast-Benachrichtigungen erscheinen

---

## 4. Termine (/business/appointments) - wenn Modul aktiv

### Listen-Ansicht
- [ ] Termine werden korrekt angezeigt
- [ ] Filter nach Status funktioniert
- [ ] Filter nach Datum funktioniert
- [ ] Filter nach Service funktioniert
- [ ] Filter nach Mitarbeiter funktioniert
- [ ] Sortierung funktioniert
- [ ] Pagination funktioniert

### Kalender-Ansicht
- [ ] Kalender wird geladen
- [ ] Termine werden im Kalender angezeigt
- [ ] Wechsel zwischen Ansichten (Tag/Woche/Monat)
- [ ] Navigation zwischen Zeiträumen
- [ ] Klick auf Termin öffnet Details
- [ ] Drag & Drop für Terminverschiebung

### Termin erstellen
- [ ] Modal/Seite öffnet sich
- [ ] Kundensuche funktioniert
- [ ] Service-Auswahl zeigt Preise/Dauer
- [ ] Verfügbare Slots werden angezeigt
- [ ] Validierung verhindert Doppelbuchungen
- [ ] Erfolgsmeldung nach Erstellung
- [ ] E-Mail-Bestätigung wird versendet

### Termin-Details
- [ ] Alle Informationen werden angezeigt
- [ ] Bearbeitung funktioniert
- [ ] Verschiebung auf anderen Slot möglich
- [ ] Absage mit Grund möglich
- [ ] Bestätigungs-E-Mail senden
- [ ] Erinnerungs-E-Mail planen
- [ ] Historie zeigt alle Änderungen

---

## 5. Kunden (/business/customers)

### Kundenliste
- [ ] Alle Kunden werden angezeigt
- [ ] Suche nach Name funktioniert
- [ ] Suche nach Telefonnummer funktioniert
- [ ] Suche nach E-Mail funktioniert
- [ ] Filter nach Erstellungsdatum
- [ ] Sortierung nach verschiedenen Spalten
- [ ] Pagination funktioniert
- [ ] Export funktioniert

### Kundendetails
- [ ] Kundenprofil zeigt alle Informationen
- [ ] Kontaktdaten können bearbeitet werden
- [ ] Terminhistorie wird angezeigt
- [ ] Anrufhistorie sichtbar
- [ ] Notizen können hinzugefügt/bearbeitet werden
- [ ] Tags können zugewiesen werden
- [ ] Kommunikationspräferenzen einstellbar

### Kunden-Aktionen
- [ ] Neuen Kunden anlegen
- [ ] Validierung verhindert Duplikate
- [ ] Duplikate zusammenführen
- [ ] Kunden exportieren (DSGVO-konform)
- [ ] Kunden anonymisieren
- [ ] Daten-Export für Kunde (DSGVO)

---

## 6. Team (/business/team) - wenn Berechtigung vorhanden

### Team-Übersicht
- [ ] Alle Team-Mitglieder werden angezeigt
- [ ] Rollen werden korrekt angezeigt
- [ ] Status (Aktiv/Inaktiv) sichtbar
- [ ] Letzte Aktivität wird angezeigt
- [ ] Sortierung funktioniert

### Team-Verwaltung
- [ ] Neues Mitglied einladen
- [ ] E-Mail-Einladung wird versendet
- [ ] Rollen können zugewiesen werden
- [ ] Berechtigungen können angepasst werden
- [ ] Mitglied deaktivieren/aktivieren
- [ ] Mitglied löschen (mit Bestätigung)

### Arbeitszeiten
- [ ] Arbeitszeiten können definiert werden
- [ ] Urlaubszeiten einstellbar
- [ ] Verfügbarkeit wird in Kalender reflektiert

---

## 7. Abrechnung (/business/billing) - wenn Berechtigung vorhanden

### Rechnungsübersicht
- [ ] Alle Rechnungen werden angezeigt
- [ ] Status wird korrekt angezeigt
- [ ] Filter nach Status funktioniert
- [ ] Filter nach Zeitraum funktioniert
- [ ] PDF-Download funktioniert
- [ ] Druckansicht funktioniert

### Zahlungsmethoden
- [ ] Aktuelle Zahlungsmethode wird angezeigt
- [ ] Neue Zahlungsmethode hinzufügen
- [ ] Zahlungsmethode ändern
- [ ] Zahlungsmethode löschen

### Kosten-Dashboard
- [ ] Aktuelle Kosten werden angezeigt
- [ ] Kostenaufschlüsselung sichtbar
- [ ] Verbrauchsstatistiken korrekt
- [ ] Prognose für nächste Rechnung

### Subscription
- [ ] Aktueller Plan wird angezeigt
- [ ] Upgrade/Downgrade möglich
- [ ] Kündigungsoption vorhanden
- [ ] Add-Ons verwaltbar

---

## 8. Einstellungen (/business/settings)

### Unternehmenseinstellungen
- [ ] Firmenname kann geändert werden
- [ ] Logo-Upload funktioniert
- [ ] Kontaktdaten speicherbar
- [ ] Impressum/Datenschutz editierbar
- [ ] Zeitzone einstellbar
- [ ] Sprache wählbar

### Profil-Einstellungen
- [ ] Name änderbar
- [ ] E-Mail änderbar (mit Bestätigung)
- [ ] Passwort-Änderung funktioniert
- [ ] Avatar-Upload funktioniert
- [ ] Benachrichtigungseinstellungen

### Sicherheit
- [ ] 2FA aktivieren/deaktivieren
- [ ] QR-Code wird angezeigt
- [ ] Backup-Codes werden generiert
- [ ] Session-Übersicht verfügbar
- [ ] Aktive Sessions beendbar

### API & Integrationen
- [ ] API-Keys anzeigen/generieren
- [ ] Webhook-URLs konfigurierbar
- [ ] Externe Integrationen verwaltbar
- [ ] Test-Funktionen verfügbar

---

## 9. Responsive Design & Accessibility

### Mobile (< 768px)
- [ ] Navigation-Hamburger funktioniert
- [ ] Sidebar schließt nach Navigation
- [ ] Tabellen sind horizontal scrollbar
- [ ] Modals passen auf Bildschirm
- [ ] Touch-Gesten funktionieren
- [ ] Buttons sind groß genug (min. 44x44px)
- [ ] Text ist lesbar (min. 16px)

### Tablet (768px - 1024px)
- [ ] Layout passt sich an
- [ ] Keine überlappenden Elemente
- [ ] Sidebar kann ein-/ausgeklappt werden
- [ ] Optimale Spaltenbreiten

### Desktop (> 1024px)
- [ ] Volle Funktionalität
- [ ] Optimale Platznutzung
- [ ] Multi-Column Layouts korrekt
- [ ] Hover-States funktionieren

### Accessibility
- [ ] Keyboard-Navigation funktioniert
- [ ] Tab-Reihenfolge ist logisch
- [ ] Focus-States sichtbar
- [ ] Alt-Texte für Bilder
- [ ] ARIA-Labels vorhanden
- [ ] Kontraste ausreichend (WCAG AA)
- [ ] Screenreader-kompatibel

---

## 10. Browser-Kompatibilität

### Chrome (v90+)
- [ ] Keine Darstellungsfehler
- [ ] Alle Features funktionieren
- [ ] Console ohne Fehler

### Firefox (v88+)
- [ ] Keine Darstellungsfehler
- [ ] Alle Features funktionieren
- [ ] Console ohne Fehler

### Safari (v14+)
- [ ] Keine Darstellungsfehler
- [ ] Alle Features funktionieren
- [ ] Console ohne Fehler

### Edge (v90+)
- [ ] Keine Darstellungsfehler
- [ ] Alle Features funktionieren
- [ ] Console ohne Fehler

---

## 11. Performance & Errors

### Ladezeiten
- [ ] Dashboard < 2 Sekunden
- [ ] Listen < 1.5 Sekunden
- [ ] Detail-Seiten < 1 Sekunde
- [ ] API-Responses < 500ms
- [ ] Bilder optimiert (WebP/lazy loading)

### Console Checks
- [ ] Keine JavaScript-Fehler
- [ ] Keine 404-Fehler für Assets
- [ ] Keine CORS-Fehler
- [ ] Keine Mixed-Content Warnungen
- [ ] Keine Deprecation Warnings

### Network
- [ ] Alle API-Calls erfolgreich (200/201)
- [ ] Keine fehlgeschlagenen Requests
- [ ] Requests haben Auth-Header
- [ ] GZIP-Kompression aktiv

### Alpine.js Specific
- [ ] Alle x-data Komponenten initialisiert
- [ ] Keine Expression-Errors
- [ ] x-show/x-if Bedingungen funktionieren
- [ ] Event-Handler reagieren
- [ ] Keine Memory Leaks bei Navigation

### Error Handling
- [ ] 404-Seite wird angezeigt
- [ ] 500-Fehler zeigen Nutzer-freundliche Meldung
- [ ] API-Fehler werden abgefangen
- [ ] Timeout-Handling funktioniert
- [ ] Offline-Meldung bei Verbindungsverlust

---

## 12. Sicherheitstests

### Authentication
- [ ] SQL-Injection in Login nicht möglich
- [ ] XSS in Eingabefeldern verhindert
- [ ] CSRF-Token wird validiert
- [ ] Session-Hijacking verhindert
- [ ] Brute-Force Schutz aktiv

### Authorization
- [ ] Direkte URL-Zugriffe geschützt
- [ ] API-Endpoints validieren Berechtigungen
- [ ] Keine sensiblen Daten in HTML/JS
- [ ] Rate-Limiting funktioniert

### Data Protection
- [ ] HTTPS überall erzwungen
- [ ] Sensible Daten sind verschlüsselt
- [ ] Keine Klartext-Passwörter
- [ ] PII wird korrekt gehandhabt

---

## 13. Lokalisierung & Internationalisierung

### Deutsche Version
- [ ] Alle Texte auf Deutsch
- [ ] Datum-Format: DD.MM.YYYY
- [ ] Zeit-Format: HH:MM
- [ ] Währung: EUR (X.XXX,XX €)
- [ ] Telefonnummern: +49 Format

### Übersetzungsfunktion
- [ ] Sprach-Switcher funktioniert
- [ ] Übersetzungen werden geladen
- [ ] Keine fehlenden Übersetzungen
- [ ] RTL-Support (falls benötigt)

---

## 🐛 Gefundene Probleme

### Kritische Fehler (Blocker)
| ID | Seite | Problem | Schritte zur Reproduktion | Status |
|----|-------|---------|---------------------------|---------|
| 1  |       |         |                           |         |
| 2  |       |         |                           |         |

### Wichtige Fehler (Major)
| ID | Seite | Problem | Schritte zur Reproduktion | Status |
|----|-------|---------|---------------------------|---------|
| 1  |       |         |                           |         |
| 2  |       |         |                           |         |

### Kleinere Fehler (Minor)
| ID | Seite | Problem | Schritte zur Reproduktion | Status |
|----|-------|---------|---------------------------|---------|
| 1  |       |         |                           |         |
| 2  |       |         |                           |         |

### UI/UX Verbesserungen
| ID | Seite | Vorschlag | Priorität |
|----|-------|-----------|-----------|
| 1  |       |           |           |
| 2  |       |           |           |

---

## 📝 Test-Notizen & Beobachtungen

### Positive Aspekte
- 
- 
- 

### Verbesserungspotential
- 
- 
- 

### Spezielle Anmerkungen
- 
- 
- 

---

## 📊 Performance-Metriken

| Metrik | Zielwert | Gemessen | Status |
|--------|----------|----------|---------|
| First Contentful Paint | < 1.8s | ___s | [ ] OK [ ] NOK |
| Time to Interactive | < 3.8s | ___s | [ ] OK [ ] NOK |
| Total Blocking Time | < 300ms | ___ms | [ ] OK [ ] NOK |
| Cumulative Layout Shift | < 0.1 | ___ | [ ] OK [ ] NOK |
| Largest Contentful Paint | < 2.5s | ___s | [ ] OK [ ] NOK |

---

## ✅ Test-Zusammenfassung

### Test-Statistiken
- **Getestete Features**: ___ / ___
- **Gefundene Fehler**: 
  - Kritisch: ___
  - Wichtig: ___
  - Klein: ___
- **Bestehende Tests**: ___ %

### Empfehlung
[ ] **Bereit für Production** - Keine kritischen Fehler gefunden  
[ ] **Bereit mit Einschränkungen** - Kleine Fehler, können später behoben werden  
[ ] **Nicht bereit** - Kritische Fehler müssen zuerst behoben werden  

### Sign-Off

**Getestet von**:  
Name: _________________  
Datum: _________________  
Unterschrift: _________________  

**Freigegeben von**:  
Name: _________________  
Datum: _________________  
Unterschrift: _________________  

---

## 📎 Anhänge

- [ ] Screenshots von Fehlern
- [ ] Browser Console Logs
- [ ] Network HAR-Dateien
- [ ] Performance Reports
- [ ] Video-Aufzeichnungen (bei komplexen Bugs)

---

**Test-Checkliste Version**: 1.0  
**Letzte Aktualisierung**: {{ date('d.m.Y') }} 