# Navigation Overview - AskProAI Admin Panel

## Menüstruktur

### Dashboard
- **Dashboard** - `/admin` (Startseite)

### Stammdaten
1. **Unternehmen** - `/admin/companies` (Sort: 1)
2. **Filialen** - `/admin/branches` (Sort: 12)
3. **Leistungen** - `/admin/master-services` (Sort: 4)
4. **Mitarbeiter** - `/admin/staff` (Sort: 30)
5. **Kunden** - `/admin/customers`
6. **Leistungen** - `/admin/services`
7. **Arbeitszeiten** - `/admin/working-hours`

### Event Management
1. **Event Types** - `/admin/calcom-event-types` (Sort: 1)
2. **Mitarbeiter-Zuordnung** - `/admin/staff-event-assignment` (Sort: 2)
3. **Smart Zuordnung** - `/admin/staff-event-assignment-modern` (Sort: 3)
4. **Event-Type Import** - `/admin/event-type-import-wizard` (Sort: 3)
5. **Analytics Dashboard** - `/admin/event-analytics-dashboard` (Sort: 5)

### Buchungen
- **Termine** - `/admin/appointments` (Sort: 10)

### Kommunikation
- **Anrufe** - `/admin/calls`

### Verwaltung
- **Benutzer** - `/admin/users`
- **Mandanten** - `/admin/tenants`

### Integrationen
- **Integrationen** - `/admin/integrations`

### System Control
- **Validation Dashboard** - `/admin/validation-dashboards` (Sort: 1)

### Monitoring & Status
- **System Cockpit** - `/admin/system-cockpit` (Sort: 0)

### System
1. **Security Dashboard** - `/admin/security-dashboard` (Sort: 1)
2. **Systemstatus** - `/admin/system-status` (Sort: 2)
3. **Debug** - `/admin/debug-dashboard` (Sort: 999) - *Nur im Debug-Modus oder für Super-Admins*
4. **Debug Data** - `/admin/debug-data` (Sort: 999) - *Nur im Debug-Modus oder für Super-Admins*

### Sonstige (ohne Gruppe)
- **Phone Numbers** - `/admin/phone-numbers`
- **Event Type Management** - `/admin/unified-event-types` (Sort: 50)
- **Einrichtungsassistent** - `/admin/onboarding-wizard` - *Nur wenn Onboarding nicht abgeschlossen*

## Besondere Navigationsbedingungen

### Bedingte Anzeige
1. **OnboardingWizard** - Wird nur angezeigt, wenn das Onboarding noch nicht abgeschlossen ist
2. **Debug Pages** - Werden nur im Debug-Modus oder für Super-Admins angezeigt

### Bekannte Probleme
1. Doppelte "Leistungen" Einträge in Stammdaten (MasterServiceResource und ServiceResource)
2. Einige Resources haben keine navigationGroup und erscheinen daher außerhalb der Gruppen

## Routen-Übersicht

Alle Hauptrouten:
- `/admin` - Dashboard
- `/admin/appointments` - Termine
- `/admin/branches` - Filialen  
- `/admin/calcom-event-types` - Event Types
- `/admin/calls` - Anrufe
- `/admin/companies` - Unternehmen
- `/admin/customers` - Kunden
- `/admin/debug` - Debug
- `/admin/debug-dashboard` - Debug Dashboard
- `/admin/debug-data` - Debug Data
- `/admin/event-analytics-dashboard` - Analytics Dashboard
- `/admin/event-type-import-wizard` - Event-Type Import
- `/admin/integrations` - Integrationen
- `/admin/master-services` - Leistungen (Master)
- `/admin/onboarding-wizard` - Einrichtungsassistent
- `/admin/phone-numbers` - Phone Numbers
- `/admin/security-dashboard` - Security Dashboard
- `/admin/services` - Leistungen
- `/admin/staff` - Mitarbeiter
- `/admin/staff-event-assignment` - Mitarbeiter-Zuordnung
- `/admin/staff-event-assignment-modern` - Smart Zuordnung
- `/admin/system-cockpit` - System Cockpit
- `/admin/system-status` - Systemstatus
- `/admin/tenants` - Mandanten
- `/admin/unified-event-types` - Event Type Management
- `/admin/users` - Benutzer
- `/admin/validation-dashboards` - Validation Dashboard
- `/admin/working-hours` - Arbeitszeiten