# Business Portal React Migration - Umfassender Testplan

## 🎯 Übersicht
Dieser Testplan deckt alle migrierten React-Module des Business Portals ab.

## ✅ Status der Migration
- **Abgeschlossen**: Alle Hauptmodule wurden erfolgreich zu React mit shadcn/ui migriert
- **Build Status**: ✅ Erfolgreich (npm run build ohne Fehler)
- **Design System**: Konsistent mit modernem Billing-Modul

## 📋 Module zum Testen

### 1. Dashboard (`/business/dashboard`)
**Komponenten zu testen:**
- [ ] Statistik-Karten (Anrufe, Termine, Kunden, Umsatz)
- [ ] Trend-Anzeigen (Pfeile hoch/runter mit Prozentsätzen)
- [ ] Charts:
  - [ ] Anruf-Trend (LineChart)
  - [ ] Termin-Trend (AreaChart)
  - [ ] Umsatzentwicklung (BarChart)
  - [ ] Service-Verteilung (PieChart)
- [ ] Aktivitäts-Timeline
- [ ] Quick Actions
- [ ] Responsive Design (Mobile/Tablet/Desktop)

### 2. Anrufe (`/business/calls`)
**Listenansicht testen:**
- [ ] Suche funktioniert
- [ ] Filter (Status, Datum, Dauer)
- [ ] Sortierung
- [ ] Pagination
- [ ] CSV Export
- [ ] PDF Export einzelner Anrufe

**Detailansicht testen:**
- [ ] Anrufdetails werden korrekt angezeigt
- [ ] Audio-Player funktioniert
- [ ] Transkript wird angezeigt
- [ ] Zusammenfassung sichtbar
- [ ] Notizen hinzufügen
- [ ] Status ändern
- [ ] Kunde zuweisen

### 3. Termine (`/business/appointments`)
**Funktionen testen:**
- [ ] Terminliste wird geladen
- [ ] Filter (Status, Filiale, Mitarbeiter, Service)
- [ ] Suche nach Kunde/Service
- [ ] Neuen Termin erstellen (Dialog)
- [ ] Termindetails anzeigen (Sheet)
- [ ] Status ändern (Bestätigen, Absagen, etc.)
- [ ] Termin löschen
- [ ] Statistik-Karten korrekt

### 4. Kunden (`/business/customers`)
**Funktionen testen:**
- [ ] Kundenliste wird angezeigt
- [ ] Suche funktioniert
- [ ] Tags werden angezeigt
- [ ] Neuen Kunden anlegen
- [ ] Kunde bearbeiten
- [ ] Kunde löschen
- [ ] Kundendetails (Sheet)
- [ ] Anruf-Historie
- [ ] Termin-Historie
- [ ] CSV Export

### 5. Team (`/business/team`)
**Funktionen testen:**
- [ ] Teammitglieder werden angezeigt
- [ ] Suche funktioniert
- [ ] Filter nach Filiale
- [ ] Neues Mitglied einladen
- [ ] Mitglied bearbeiten
- [ ] Status toggle (Aktiv/Inaktiv)
- [ ] Berechtigungen verwalten
- [ ] Mitarbeiterdetails (Sheet)

### 6. Analytics (`/business/analytics`)
**Tabs testen:**
- [ ] Übersicht mit Statistik-Karten
- [ ] Anruf-Analytics (Stoßzeiten, Dauer)
- [ ] Termin-Analytics (Status-Verteilung)
- [ ] Mitarbeiter-Performance
- [ ] Konversions-Funnel
- [ ] Zeitraum-Filter
- [ ] Filial-Filter
- [ ] Export (CSV/PDF)

### 7. Einstellungen (`/business/settings`)
**Tabs testen:**
- [ ] Profil bearbeiten
- [ ] Passwort ändern
- [ ] 2FA aktivieren/deaktivieren
- [ ] Firmeneinstellungen
- [ ] Benachrichtigungseinstellungen
- [ ] Erscheinungsbild (Theme)
- [ ] Speichern funktioniert

## 🔍 Allgemeine Tests

### Navigation & Layout
- [ ] Hauptnavigation funktioniert
- [ ] Mobile Menu (Hamburger)
- [ ] User Dropdown Menu
- [ ] Benachrichtigungs-Icon
- [ ] Breadcrumbs
- [ ] Dark Mode Toggle (wenn implementiert)

### Performance
- [ ] Schnelle Ladezeiten (< 3s)
- [ ] Keine JavaScript-Fehler in Console
- [ ] Smooth Scrolling
- [ ] Animationen flüssig

### Responsive Design
- [ ] Mobile (< 640px)
- [ ] Tablet (640px - 1024px)
- [ ] Desktop (> 1024px)
- [ ] Alle Komponenten passen sich an

### Browser-Kompatibilität
- [ ] Chrome/Edge
- [ ] Firefox
- [ ] Safari
- [ ] Mobile Browser

### API Integration
- [ ] CSRF Token wird korrekt gesendet
- [ ] API Fehler werden behandelt
- [ ] Loading States funktionieren
- [ ] Error States werden angezeigt

## 🐛 Bekannte Issues
- Kalenderansicht in Appointments noch nicht implementiert (Placeholder vorhanden)
- Dark Mode noch nicht vollständig implementiert

## 📝 Test-Durchführung

### Voraussetzungen:
1. Build erfolgreich: `npm run build`
2. Server läuft: `php artisan serve`
3. Queue Worker läuft: `php artisan horizon`
4. Test-Account mit Portal-Zugang

### Test-URLs:
- Dashboard: https://api.askproai.de/business/dashboard
- Anrufe: https://api.askproai.de/business/calls
- Termine: https://api.askproai.de/business/appointments  
- Kunden: https://api.askproai.de/business/customers
- Team: https://api.askproai.de/business/team
- Analytics: https://api.askproai.de/business/analytics
- Einstellungen: https://api.askproai.de/business/settings

### Test-Schritte:
1. In Business Portal einloggen
2. Jeden Menüpunkt durchgehen
3. Alle Funktionen gemäß Checkliste testen
4. Auf verschiedenen Geräten/Browsern testen
5. Performance und Fehler überwachen

## ✅ Abschluss-Kriterien
- [ ] Alle Module funktionieren fehlerfrei
- [ ] Keine JavaScript-Fehler in Console
- [ ] Responsive Design auf allen Geräten
- [ ] API-Calls funktionieren korrekt
- [ ] Performance akzeptabel
- [ ] UX konsistent mit Design-System

## 🚀 Deployment-Bereitschaft
Nach erfolgreichem Test aller Punkte ist das neue React Business Portal bereit für Production Deployment.