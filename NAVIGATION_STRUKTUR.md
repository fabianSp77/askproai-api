# AskProAI Navigationsstruktur

## Optimierte Menüstruktur (5 Hauptgruppen)

### 1. **Täglicher Betrieb** 
*Was Nutzer jeden Tag brauchen*

#### Resources:
- **Termine** (AppointmentResource) - Sort: 1
- **Anrufe** (CallResource) - Sort: 2
- **Kunden** (CustomerResource) - Sort: 3

#### Pages:
- **Dashboard** (Dashboard) - Sort: 0
- **Operational Dashboard** (OperationalDashboard) - Sort: 4
- **Live Call Status** (durch Widget auf Dashboard)

### 2. **Verwaltung**
*Regelmäßige Verwaltungsaufgaben*

#### Resources:
- **Mitarbeiter** (StaffResource) - Sort: 10
- **Dienstleistungen** (ServiceResource) - Sort: 11
- **Filialen** (BranchResource) - Sort: 12
- **Arbeitszeiten** (WorkingHourResource) - Sort: 13
- **Telefonnummern** (PhoneNumberResource) - Sort: 14

#### Pages:
- **Kundenportal-Verwaltung** (CustomerPortalManagement) - Sort: 15
- **Wissens-Datenbank** (KnowledgeBaseManager) - Sort: 16

### 3. **Einrichtung**
*Einmalige oder seltene Konfiguration*

#### Resources:
- **Firmen-Einstellungen** (CompanyResource) - Sort: 20
- **Kalender-Events** (CalcomEventTypeResource) - Sort: 21
- **Integrationen** (IntegrationResource) - Sort: 22

#### Pages:
- **Schnell-Setup Wizard** (QuickSetupWizard) - Sort: 23
- **Retell Konfiguration** (RetellUltimateControlCenter) - Sort: 24
- **Event-Typ Setup** (EventTypeSetupWizard) - Sort: 25
- **Mitarbeiter-Event Zuordnung** (StaffEventAssignment) - Sort: 26
- **Integration Portal** (CompanyIntegrationPortal) - Sort: 27

### 4. **Auswertungen**
*Reports und Analysen*

#### Resources:
- **Rechnungen** (InvoiceResource) - Sort: 30
- **Preisgestaltung** (CompanyPricingResource) - Sort: 31

#### Pages:
- **Reports & Analytics** (ReportsAndAnalytics) - Sort: 32
- **Event Analytics** (EventAnalyticsDashboard) - Sort: 33
- **Webhook Analyse** (WebhookAnalysis) - Sort: 34
- **ML Training Dashboard** (MLTrainingDashboard) - Sort: 35

### 5. **System**
*Nur für Administratoren*

#### Resources:
- **Benutzer** (UserResource) - Sort: 40
- **Mandanten** (TenantResource) - Sort: 41
- **DSGVO-Anfragen** (GdprRequestResource) - Sort: 42

#### Pages:
- **System Status** (SystemStatus) - Sort: 43
- **API Health Monitor** (ApiHealthMonitor) - Sort: 44
- **Feature Flags** (FeatureFlagManager) - Sort: 45
- **MCP Control Center** (MCPControlCenter) - Sort: 46
- **Webhook Monitor** (WebhookMonitor) - Sort: 47

## Zu löschende/zusammenzulegende Pages

### Redundante Pages (können gelöscht werden):
1. **Dashboard-Duplikate:**
   - OperationsDashboard (verwende OperationalDashboard)
   - MCPDashboard (integriert in MCPControlCenter)
   
2. **System-Status Duplikate:**
   - BasicSystemStatus
   - SystemHealthBasic
   - SystemHealthSimple
   - SystemHealthMonitorDebug
   - SystemMonitoring
   - CompanyConfigStatus
   - QuantumSystemMonitoring (zu abstrakt)
   
3. **Setup-Wizard Duplikate:**
   - QuickSetupWizardV2 (verwende V1)
   - SimpleOnboarding
   - SimpleCompanyIntegrationPortal
   - BasicCompanyConfig
   
4. **Sync-Manager Duplikate:**
   - SimpleSyncManager
   - IntelligentSyncManager (verwende DataSync)
   - CalcomSyncStatus (in CompanyIntegrationPortal integriert)
   
5. **Event-Assignment Duplikate:**
   - StaffEventAssignmentModern (verwende StaffEventAssignment)
   
6. **Retell-Duplikate:**
   - RetellConfigurationCenter (verwende RetellUltimateControlCenter)
   - RetellAgentEditor (in RetellUltimateControlCenter integriert)
   - RetellAgentImportWizard (in RetellUltimateControlCenter integriert)
   
7. **Sonstige:**
   - ErrorFallback (nicht benötigt)
   - TableDebug (nur für Entwicklung)
   - SetupSuccessPage (kann durch Notification ersetzt werden)
   - PricingCalculator (in CompanyPricingResource integriert)
   - SystemImprovements (zu vage)
   - MLTrainingDashboardLivewire (verwende MLTrainingDashboard)
   - ListCompanies (Page, verwende CompanyResource)

### Umbenennen für Konsistenz:
- DataSync → "Daten-Synchronisation"
- EventTypeImportWizard → "Event-Typ Import"

## Implementierungs-Features

### 1. **Favoriten/Schnellzugriff**
- User können häufig genutzte Seiten als Favoriten markieren
- Favoriten erscheinen oben im Menü in separater Gruppe
- Gespeichert in User-Preferences

### 2. **Kontextabhängige Menüs**
- Wenn User in Filiale arbeitet: Nur relevante Daten dieser Filiale
- Wenn User bestimmte Rolle hat: Nur erlaubte Menüpunkte

### 3. **Rollenbasierte Sichtbarkeit**
- **Super Admin**: Alle Gruppen sichtbar
- **Company Admin**: Gruppen 1-4 sichtbar
- **Filialleiter**: Gruppen 1-2 + eingeschränkt 3
- **Mitarbeiter**: Nur Gruppe 1 (Täglicher Betrieb)

### 4. **Such-Integration**
- Globale Suche über alle Menüpunkte
- Shortcuts für häufige Aktionen (z.B. "Neuer Termin")

## Implementierungsschritte

1. **Navigation Groups in AdminPanelProvider** ✅
2. **Resources aktualisieren** (navigationGroup & navigationSort)
3. **Pages aktualisieren** (getNavigationGroup() & getNavigationSort())
4. **Redundante Pages löschen**
5. **Favoriten-System implementieren**
6. **Rollen-basierte Filterung**
7. **Deutsche Labels überall sicherstellen**