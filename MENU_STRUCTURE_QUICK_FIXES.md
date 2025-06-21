# AskProAI Menu Structure - Quick Fixes Needed

## Update nach genauerer Analyse

### ✅ Bereits korrekt (keine Änderung nötig)
- InvoiceResource: Hat bereits `navigationLabel = 'Rechnungen'`
- ServiceResource: Hat bereits `navigationLabel = 'Leistungen'`

### ❌ Tatsächliche Probleme die behoben werden müssen

#### 1. **Fehlende Navigation Groups bei Hauptressourcen**
```php
// AppointmentResource.php - FEHLT navigationGroup
protected static ?string $navigationGroup = 'Täglicher Betrieb';
protected static ?int $navigationSort = 10;

// CallResource.php - FEHLT navigationGroup  
protected static ?string $navigationGroup = 'Täglicher Betrieb';
protected static ?int $navigationSort = 20;

// CustomerResource.php - FEHLT navigationGroup
protected static ?string $navigationGroup = 'Täglicher Betrieb';
protected static ?int $navigationSort = 30;

// CompanyResource.php - FEHLT navigationGroup
protected static ?string $navigationGroup = 'Unternehmensstruktur';
protected static ?int $navigationSort = 10;

// InvoiceResource.php - FEHLT navigationGroup
protected static ?string $navigationGroup = 'Abrechnung';
protected static ?int $navigationSort = 10;

// ServiceResource.php - FEHLT navigationGroup
protected static ?string $navigationGroup = 'Unternehmensstruktur';
protected static ?int $navigationSort = 30;
```

#### 2. **Redundante Dashboard-Einträge**
- `Dashboard.php` leitet auf `/admin/dashboard` weiter
- `OperationalDashboard.php` hat slug = 'dashboard'  
- `OperationsDashboard.php` hat slug = '' und navigationGroup = 'Täglicher Betrieb'

**Lösung**: Nur einen Dashboard behalten!

#### 3. **Test-Seiten ausblenden**
Diese Seiten sollten nicht in der Navigation erscheinen:
- MCPTestPage.php
- TestLivewireDropdown.php
- TestMinimalPage.php
- TableDebug.php
- SystemHealthMonitorDebug.php
- EventTypeSetupWizardDebug.php

**Fix**: `protected static bool $shouldRegisterNavigation = false;` hinzufügen

#### 4. **Zu viele System-Monitoring Seiten**
Folgende könnten konsolidiert werden:
- SystemCockpit.php
- SystemCockpitSimple.php
- UltimateSystemCockpitOptimized.php
- UltimateSystemCockpitMinimal.php
- SystemHealthSimple.php
- SystemHealthBasic.php
- BasicSystemStatus.php
- SystemStatus.php

**Empfehlung**: Nur 1-2 behalten, Rest deaktivieren

#### 5. **Doppelte Einträge**
- StaffEventAssignment.php
- StaffEventAssignmentModern.php
→ Nur eine Version behalten

- QuickSetupWizard.php
- QuickSetupWizardV2.php  
→ Nur V2 behalten

## Priorisierte Aktionsliste

### Priorität 1 (Sofort)
1. Navigation Groups bei Hauptressourcen ergänzen
2. Test-Seiten aus Navigation entfernen
3. Dashboard-Chaos bereinigen

### Priorität 2 (Diese Woche)
1. Doppelte Einträge entfernen
2. System-Monitoring konsolidieren
3. Navigation Sort Order optimieren

### Priorität 3 (Später)
1. Rollen-basierte Navigation
2. Weitere UI/UX Verbesserungen

## Geschätzter Aufwand
- Priorität 1: 30 Minuten
- Priorität 2: 1 Stunde  
- Priorität 3: 2-3 Stunden