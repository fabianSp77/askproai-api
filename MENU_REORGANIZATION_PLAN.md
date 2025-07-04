# 📋 Menü-Reorganisation Plan

## 🎯 Neue vereinfachte Menüstruktur

### 1. **Dashboard** (navigationSort: 0-10)
- ✅ OptimizedOperationalDashboard (Hauptdashboard behalten)
- ❌ Andere Dashboards deaktivieren

### 2. **Täglicher Betrieb** (navigationSort: 20-40)
- Termine
- Anrufe  
- Kunden
- Personal

### 3. **Einrichtung** (navigationSort: 50-70)
- ✅ **Retell Konfiguration** (RetellUltimateControlCenter) - Hauptlösung
- ✅ **Schnell-Setup** (QuickSetupWizard)
- ✅ **Event-Types Import** (EventTypeImportWizard)
- ❌ Redundante Pages deaktivieren

### 4. **Verwaltung** (navigationSort: 80-100)
- Unternehmen
- Filialen
- Telefonnummern
- Dienstleistungen

### 5. **System** (navigationSort: 110-130)
- ✅ **System Status** (SystemHealthSimple) - Eine vereinfachte Ansicht
- ✅ **API Monitor** (ApiHealthMonitor)
- ✅ **Webhook Monitor** (WebhookMonitor)
- ❌ Redundante System Pages deaktivieren

### 6. **Berichte** (navigationSort: 140-160)
- ✅ **Berichte & Analysen** (ReportsAndAnalytics)
- ❌ Andere Analytics deaktivieren

## 🔧 Zu deaktivierende Pages

### Retell (redundant):
- RetellConfigurationCenter (nur read-only)
- RetellAgentImportWizard (in UltimateControlCenter integriert)
- RetellAgentEditor (in UltimateControlCenter integriert)

### System (redundant):
- SystemStatus
- BasicSystemStatus
- SystemHealthBasic
- QuantumSystemMonitoring
- SystemMonitoring
- SystemImprovements

### Company Config (redundant):
- BasicCompanyConfig
- CompanyConfigStatus
- SimpleCompanyIntegrationPortal
- CompanyIntegrationPortal

### Dashboards (redundant):
- Dashboard
- OperationalDashboard
- OperationsDashboard
- EventAnalyticsDashboard

## 📝 Implementierung

Für jede zu deaktivierende Page:
```php
public static function shouldRegisterNavigation(): bool
{
    return false;
}
```