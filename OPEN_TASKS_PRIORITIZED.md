# 📋 OPEN TASKS - PRIORITIZED

## 🔴 P0 - KRITISCHE BLOCKER (Muss sofort)

### 1. Test-Suite reparieren (4h)
**Problem**: SQLite-Migrations sind MySQL-spezifisch
**Lösung**: 
```php
// Erstelle app/Database/Migrations/CompatibleMigration.php
// Alle Migrations davon ableiten
// Check column/index existence vor Creation
```
**Dateien**:
- `database/migrations/2025_06_17_add_performance_critical_indexes.php`
- Alle anderen Migrations mit Indexes

### 2. Pending Migrations ausführen (1h)
**Vorsicht**: Production-Datenbank!
```bash
php artisan migrate --force
# Vorher Backup!
```
**Migrations**:
- 7 pending (siehe COMPACT_STATUS.md)

## 🟡 P1 - WICHTIGE FEATURES (Diese Woche)

### 3. Integration Step implementieren (2h)
**Was**: Neuer Wizard-Step zwischen Cal.com und Services
**Features**:
- Live API-Ping zu Retell & Cal.com
- Direkte Fehlermeldungen
- Retry-Button bei Fehlern

**Code**:
```php
// In QuickSetupWizard.php nach Step 3 einfügen:
Wizard\Step::make('Integration überprüfen')
    ->description('Live-Verbindungstest')
    ->icon('heroicon-o-wifi')
    ->schema($this->getIntegrationCheckFields())
```

### 4. Admin Badge Integration (1h)
**Was**: Health-Status im Admin-Navigation
**Wo**: `app/Providers/Filament/AdminPanelProvider.php`

```php
->globalSearch(true)
->brandName('AskProAI')
->brandLogo(asset('images/logo.svg'))
->favicon(asset('images/favicon.ico'))
->navigationItems([
    NavigationItem::make('health-status')
        ->label(fn() => $this->getHealthBadge())
        ->url('/admin/health')
        ->icon('heroicon-o-shield-check')
        ->sort(999)
])
```

### 5. Performance Timer (1h)
**Was**: Query-Performance-Logging
**Wo**: `app/Services/Booking/StaffSkillMatcher.php`

```php
$start = microtime(true);
$query = $this->buildQuery();
$results = $query->get();
$duration = (microtime(true) - $start) * 1000;

if ($duration > 200) {
    Log::warning('Slow query in StaffSkillMatcher', [
        'duration_ms' => $duration,
        'query' => $query->toSql()
    ]);
}
```

## 🟢 P2 - NICE TO HAVE (Nächster Sprint)

### 6. Prompt Templates (3h)
**Was**: Branchen-spezifische Retell-Prompts
**Struktur**:
```
resources/views/prompts/
├── industries/
│   ├── salon.blade.php
│   ├── fitness.blade.php
│   ├── medical.blade.php
│   └── generic.blade.php
└── components/
    ├── greeting.blade.php
    └── variables.blade.php
```

### 7. E2E Dusk Tests (4h)
**Nach** Test-Suite-Fix!
- Wizard Complete Flow
- Phone Routing Scenarios
- Health Check Validation

### 8. PHPUnit Attribute Migration (2h)
**Von**:
```php
/**
 * @test
 */
public function it_does_something()
```

**Zu**:
```php
#[Test]
public function it_does_something()
```

## 📊 AUFWANDS-SCHÄTZUNG

| Priorität | Tasks | Aufwand | Abhängigkeiten |
|-----------|-------|---------|----------------|
| P0 | 2 | 5h | Keine |
| P1 | 3 | 4h | P0 fertig |
| P2 | 3 | 9h | P0+P1 fertig |
| **TOTAL** | **8** | **18h** | |

## 🎯 QUICK WINS (< 30 Min)

1. **Health Badge Copy**: Code ist schon da, nur einbinden
2. **Performance Timer**: 5 Zeilen Code
3. **Migration Backup Script**: `mysqldump` vor Migration

## ⚠️ RISIKEN

1. **Datenverlust bei Migrations**: Immer Backup!
2. **Breaking Changes**: Wizard-Steps Order matters
3. **Performance**: Health Checks können System belasten (Caching!)

## 🚀 DEPLOYMENT CHECKLIST

- [ ] Alle Tests grün
- [ ] Migrations getestet (Staging)
- [ ] Environment Variables gesetzt
- [ ] Cron Jobs registriert
- [ ] Queue Workers neugestartet
- [ ] Cache geleert
- [ ] Health Checks laufen

**Nächster sinnvoller Schritt**: Test-Suite fixen → Dann alles andere!