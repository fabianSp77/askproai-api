# Implementierungsplan: Optimierte Navigation für AskProAI

## ✅ Bereits umgesetzt

### 1. Navigation Groups in AdminPanelProvider
- 5 Hauptgruppen definiert: Täglicher Betrieb, Verwaltung, Einrichtung, Auswertungen, System

### 2. Resources aktualisiert mit navigationGroup und navigationSort
- ✅ AppointmentResource → "Täglicher Betrieb" (Sort: 1)
- ✅ CallResource → "Täglicher Betrieb" (Sort: 2) 
- ✅ CustomerResource → "Täglicher Betrieb" (Sort: 3)
- ✅ StaffResource → "Verwaltung" (Sort: 10)
- ✅ ServiceResource → "Verwaltung" (Sort: 11)
- ✅ BranchResource → "Verwaltung" (Sort: 12)
- ✅ PhoneNumberResource → "Verwaltung" (Sort: 14)
- ✅ CompanyResource → "Einrichtung" (Sort: 20)
- ✅ CalcomEventTypeResource → "Einrichtung" (Sort: 21)
- ✅ InvoiceResource → "Auswertungen" (Sort: 30)
- ✅ UserResource → "System" (Sort: 40)

### 3. Pages aktualisiert mit getNavigationGroup() und getNavigationSort()
- ✅ Dashboard → "Täglicher Betrieb" (Sort: 0)
- ✅ OperationalDashboard → "Täglicher Betrieb" (Sort: 4)
- ✅ QuickSetupWizard → "Einrichtung" (Sort: 23)
- ✅ RetellUltimateControlCenter → "Einrichtung" (Sort: 24)
- ✅ SystemStatus → "System" (Sort: 43)

## 📋 Noch zu erledigen

### 1. Weitere Resources aktualisieren
```php
// WorkingHourResource.php
protected static ?string $navigationGroup = 'Verwaltung';
protected static ?int $navigationSort = 13;

// IntegrationResource.php  
protected static ?string $navigationGroup = 'Einrichtung';
protected static ?int $navigationSort = 22;

// CompanyPricingResource.php
protected static ?string $navigationGroup = 'Auswertungen';
protected static ?int $navigationSort = 31;

// TenantResource.php
protected static ?string $navigationGroup = 'System';
protected static ?int $navigationSort = 41;

// GdprRequestResource.php
protected static ?string $navigationGroup = 'System';
protected static ?int $navigationSort = 42;
```

### 2. Weitere Pages aktualisieren
```php
// CustomerPortalManagement.php
public static function getNavigationGroup(): ?string { return 'Verwaltung'; }
public static function getNavigationSort(): ?int { return 15; }

// KnowledgeBaseManager.php
public static function getNavigationGroup(): ?string { return 'Verwaltung'; }
public static function getNavigationSort(): ?int { return 16; }

// EventTypeSetupWizard.php
public static function getNavigationGroup(): ?string { return 'Einrichtung'; }
public static function getNavigationSort(): ?int { return 25; }

// StaffEventAssignment.php
public static function getNavigationGroup(): ?string { return 'Einrichtung'; }
public static function getNavigationSort(): ?int { return 26; }

// CompanyIntegrationPortal.php
public static function getNavigationGroup(): ?string { return 'Einrichtung'; }
public static function getNavigationSort(): ?int { return 27; }

// ReportsAndAnalytics.php
public static function getNavigationGroup(): ?string { return 'Auswertungen'; }
public static function getNavigationSort(): ?int { return 32; }

// EventAnalyticsDashboard.php
public static function getNavigationGroup(): ?string { return 'Auswertungen'; }
public static function getNavigationSort(): ?int { return 33; }

// WebhookAnalysis.php
public static function getNavigationGroup(): ?string { return 'Auswertungen'; }
public static function getNavigationSort(): ?int { return 34; }

// MLTrainingDashboard.php
public static function getNavigationGroup(): ?string { return 'Auswertungen'; }
public static function getNavigationSort(): ?int { return 35; }

// ApiHealthMonitor.php
public static function getNavigationGroup(): ?string { return 'System'; }
public static function getNavigationSort(): ?int { return 44; }

// FeatureFlagManager.php
public static function getNavigationGroup(): ?string { return 'System'; }
public static function getNavigationSort(): ?int { return 45; }

// MCPControlCenter.php
public static function getNavigationGroup(): ?string { return 'System'; }
public static function getNavigationSort(): ?int { return 46; }

// WebhookMonitor.php
public static function getNavigationGroup(): ?string { return 'System'; }
public static function getNavigationSort(): ?int { return 47; }

// DataSync.php
public static function getNavigationGroup(): ?string { return 'System'; }
public static function getNavigationSort(): ?int { return 48; }
public static function getNavigationLabel(): ?string { return 'Daten-Synchronisation'; }

// EventTypeImportWizard.php
public static function getNavigationGroup(): ?string { return 'Einrichtung'; }
public static function getNavigationSort(): ?int { return 28; }
public static function getNavigationLabel(): ?string { return 'Event-Typ Import'; }
```

### 3. Redundante Pages löschen
```bash
# Script ausführen
bash cleanup-redundant-pages.sh

# Cache leeren
php artisan optimize:clear
php artisan filament:cache-components
```

### 4. Favoriten-System implementieren
```php
// app/Models/UserPreferences.php
class UserPreferences extends Model {
    protected $casts = [
        'favorite_pages' => 'array',
    ];
}

// In Filament Resources/Pages
public static function getNavigationBadge(): ?string
{
    return auth()->user()->isFavorite(static::class) ? '⭐' : null;
}
```

### 5. Rollenbasierte Navigation
```php
// In Resources/Pages
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    
    return match(static::getNavigationGroup()) {
        'System' => $user->hasRole('super-admin'),
        'Einrichtung' => $user->hasAnyRole(['super-admin', 'company-admin']),
        'Auswertungen' => $user->hasAnyRole(['super-admin', 'company-admin', 'manager']),
        default => true
    };
}
```

### 6. Such-Integration
```php
// app/Providers/Filament/AdminPanelProvider.php
->globalSearch()
->globalSearchKeyBindings(['command+k', 'ctrl+k'])
```

## 🚀 Deployment-Checkliste

1. **Backup erstellen**
   ```bash
   php artisan backup:run
   ```

2. **Redundante Pages entfernen**
   ```bash
   bash cleanup-redundant-pages.sh
   ```

3. **Cache leeren**
   ```bash
   php artisan optimize:clear
   php artisan filament:cache-components
   ```

4. **Testen**
   - [ ] Navigation in allen Gruppen sichtbar
   - [ ] Sortierung korrekt
   - [ ] Deutsche Labels überall
   - [ ] Keine 404-Fehler bei entfernten Pages
   - [ ] Rollenbasierte Sichtbarkeit funktioniert

5. **Monitoring**
   - [ ] Fehler-Logs beobachten
   - [ ] User-Feedback einholen
   - [ ] Performance prüfen