# Admin Portal Funktionen - Analyse für React Migration

## 🎯 Hauptziel
Das bestehende Filament Admin Portal auf React umstellen (wie das Business Portal), um Session-Konflikte und Login-Probleme zu lösen.

## 📊 Bestehende Admin-Funktionen (Filament)

### 1. **Resources (Datenverwaltung)**
- **Companies** - Mandantenverwaltung
- **Branches** - Filialen/Standorte
- **Users** - Systembenutzer (Admins)
- **PortalUsers** - Business Portal Benutzer
- **Staff** - Mitarbeiter
- **Customers** - Kunden
- **Appointments** - Termine
- **Calls** - Anrufe
- **Services** - Dienstleistungen
- **CalcomEventTypes** - Kalender-Ereignistypen
- **PhoneNumbers** - Telefonnummern
- **Invoices** - Rechnungen
- **Subscriptions** - Abonnements
- **PrepaidBalances** - Prepaid-Guthaben

### 2. **Spezial-Seiten**
- **Dashboard** - Übersicht mit Statistiken
- **RetellConfigurationCenter** - AI-Telefonie Konfiguration
- **CalcomSyncStatus** - Kalender-Synchronisation
- **EventTypeImportWizard** - Event-Import
- **QuickSetupWizard** - Schnell-Einrichtung
- **WebhookMonitor** - Webhook-Überwachung
- **SystemMonitoringDashboard** - System-Monitoring
- **BillingAlertsManagement** - Abrechnungs-Warnungen
- **LanguageSettings** - Spracheinstellungen
- **MCPControlCenter** - MCP-Server Verwaltung

### 3. **Kern-Features die migriert werden müssen**
1. **Authentication & Permissions**
   - Multi-Tenant mit Company-Filterung
   - Role-based Access Control
   - 2FA Support

2. **CRUD Operations**
   - Tabellen mit Suche, Filter, Sortierung
   - Inline-Editing
   - Bulk-Actions
   - Export (CSV, Excel)

3. **Dashboards & Analytics**
   - Real-time Statistiken
   - Charts und Graphen
   - Performance Metriken

4. **Integrations**
   - Retell.ai Management
   - Cal.com Synchronisation
   - Webhook Handling
   - API Monitoring

## 🔄 Migration Strategie

### Phase 1: Backend API (1-2 Tage)
1. **Bestehende APIs erweitern**
   - `/api/v2/admin/*` Endpoints erstellen
   - JWT Authentication für Admin
   - Alle CRUD Operations als REST APIs

2. **Neue Admin-spezifische APIs**
   ```
   GET    /api/v2/admin/dashboard/stats
   GET    /api/v2/admin/companies
   GET    /api/v2/admin/users
   GET    /api/v2/admin/system/health
   POST   /api/v2/admin/retell/sync
   ```

### Phase 2: React Admin App (3-5 Tage)
1. **Setup (wie Business Portal)**
   - Vite + React
   - React Router
   - Ant Design oder Material-UI
   - Axios für API Calls
   - Redux/Zustand für State

2. **Core Components**
   - Layout mit Sidebar/Navigation
   - DataTable Component
   - Forms mit Validation
   - Dashboard Widgets
   - Charts/Graphs

3. **Seiten nachbauen**
   - Login/Auth
   - Dashboard
   - Alle Resource-Listen
   - Settings
   - Monitoring

### Phase 3: Feature Parity (2-3 Tage)
- Alle Filament-Features in React
- Testing & Bug Fixes
- Performance Optimierung
- Migration der Benutzer

## 🎯 Vorteile nach Migration

1. **Keine Session-Konflikte mehr**
   - Beide Portale nutzen JWT Tokens
   - Keine Server-Side Sessions
   - Saubere API-First Architektur

2. **Bessere Performance**
   - SPA mit schneller Navigation
   - Lazy Loading
   - Optimierte Bundle-Größe

3. **Moderne Entwicklung**
   - Hot Module Replacement
   - Component-basiert
   - Wiederverwendbare UI-Elemente

4. **Einheitliche Codebasis**
   - Shared Components zwischen Admin & Business
   - Gleiche Build-Pipeline
   - Konsistentes Design

## 📝 Nächste Schritte

1. Admin API Endpoints implementieren
2. React Admin App initialisieren
3. Authentication/JWT Setup
4. Dashboard als erste Seite
5. Schrittweise alle Resources migrieren