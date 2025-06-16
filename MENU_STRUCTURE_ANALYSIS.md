# AskProAI Admin Menu Struktur Analyse

## Aktuell registrierte Menügruppen:

### 1. **Geschäftsvorgänge**
- ✅ Anrufe (CallResource) - navigationSort: 20
- ✅ Kunden (CustomerResource) - navigationSort: 30
- ❓ Termine (AppointmentResource) - navigationSort: 10 - **SOLLTE SICHTBAR SEIN**

### 2. **Unternehmensstruktur**
- ✅ Unternehmen (CompanyResource) - navigationSort: 1
- ✅ Filialen (BranchResource) - navigationSort: 2
- ✅ Mitarbeiter (StaffResource) - navigationSort: 3
- ✅ Dienstleistungen (ServiceResource) - navigationSort: 4

### 3. **Kalender & Events**
- ✅ Cal.com Event-Types (CalcomEventTypeResource)
- ✅ Unified Event Types (UnifiedEventTypeResource)

### 4. **Konfiguration**
- ✅ Benutzer (UserResource)
- ✅ Tenants (TenantResource)
- ✅ Integrationen (IntegrationResource)
- ✅ Arbeitszeiten (WorkingHourResource)

### 5. **System & Monitoring**
- ✅ Validation Dashboard (ValidationDashboardResource)

### 6. **Sonstige** (ohne Gruppe)
- ❓ Telefonnummern (PhoneNumberResource) - navigationGroup: null

## Potenziell fehlende Ressourcen:

### Geschäftsvorgänge:
- **Buchungen/Bookings** - Im Backup vorhanden, könnte mit Appointments identisch sein
- **Rechnungen/Invoices** - Kein Model gefunden
- **Zahlungen/Payments** - Kein Model gefunden
- **Berichte/Reports** - Könnte als Page statt Resource implementiert sein

### System & Monitoring:
- **Dashboard** - Als Page implementiert (SimpleDashboard)
- **Logs/Protokolle** - Könnte fehlen
- **API Status** - Könnte fehlen

## Probleme gefunden:

1. **AppointmentResource** sollte im Menü "Geschäftsvorgänge" sichtbar sein mit navigationSort: 10
2. **Namenskonflikte**: 
   - AppointmentResource vs AppointmentResourceSimple (disabled)
   - CustomerResource vs CustomerResourceFixed (disabled)

## Empfehlungen:

1. Prüfen Sie im Admin-Panel, ob "Termine" jetzt unter "Geschäftsvorgänge" sichtbar ist
2. Falls nicht, könnte es an Berechtigungen liegen
3. Überlegen Sie, ob BookingResource benötigt wird oder ob AppointmentResource ausreicht
4. Für Rechnungen/Zahlungen: Diese könnten für Phase 2 geplant sein