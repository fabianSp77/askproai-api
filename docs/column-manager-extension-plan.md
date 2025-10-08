# Column Manager Extension Plan
## Complete Implementation Guide for All Filament Resources

### ✅ Current Status
- **Completed**: Company Resource with full column management functionality
- **Components Ready**:
  - `HasColumnOrdering` trait
  - `columnManager` Alpine.js component
  - `UserPreference` model and API endpoints
  - Modal blade templates

---

## 📋 Implementation Phases

### Phase 1: High-Priority Resources (2 hours)
These resources are frequently used and will benefit most from column management.

#### 1.1 StaffResource (Mitarbeiter) - 30 minutes
**File**: `/app/Filament/Resources/StaffResource.php`

**Columns to manage**:
- name (Name)
- email (E-Mail)
- role (Rolle)
- department (Abteilung)
- phone (Telefon)
- company.name (Unternehmen)
- branch.name (Filiale)
- is_active (Aktiv)
- created_at (Erstellt)
- updated_at (Aktualisiert)

**Implementation Steps**:
```php
// 1. Add trait to StaffResource
use App\Filament\Traits\HasColumnOrdering;

class StaffResource extends Resource
{
    use HasColumnOrdering;

// 2. In ListStaff.php, add the manage columns action
protected function getHeaderActions(): array
{
    return [
        Actions\CreateAction::make(),
        Actions\Action::make('manageColumns')
            ->label('Spalten verwalten')
            ->icon('heroicon-o-view-columns')
            ->color('gray')
            ->modalHeading('Spalten verwalten')
            ->modalContent(function () {
                $columns = [
                    ['key' => 'name', 'label' => 'Name', 'visible' => true],
                    ['key' => 'email', 'label' => 'E-Mail', 'visible' => true],
                    ['key' => 'role', 'label' => 'Rolle', 'visible' => true],
                    // ... all columns
                ];
                return view('filament.modals.column-manager-simple', [
                    'resource' => 'staff',
                    'columns' => $columns,
                ]);
            })
            ->modalWidth('lg'),
    ];
}

// 3. Wrap table definition with applyColumnOrdering
public static function table(Table $table): Table
{
    $table = Table::make()
        ->columns([
            // existing columns
        ]);

    return static::applyColumnOrdering($table, 'staff');
}
```

#### 1.2 CustomerResource (Kunden) - 30 minutes
**File**: `/app/Filament/Resources/CustomerResource.php`

**Columns to manage**:
- name (Name)
- company.name (Unternehmen)
- email (E-Mail)
- phone (Telefon)
- status (Status)
- last_contact (Letzter Kontakt)
- total_appointments (Termine gesamt)
- total_revenue (Umsatz)
- created_at (Erstellt)

#### 1.3 CallResource (Anrufe) - 45 minutes
**File**: `/app/Filament/Resources/CallResource.php`

**Special Considerations**:
- Large dataset - needs performance optimization
- Recording links - handle sensitive data
- Real-time updates via WebSocket

**Columns to manage**:
- id (ID)
- caller_number (Anrufer)
- agent.name (Agent)
- duration (Dauer)
- status (Status)
- recording_url (Aufnahme)
- sentiment (Stimmung)
- created_at (Zeitpunkt)

---

### Phase 2: Business-Critical Resources (2 hours)

#### 2.1 AppointmentResource (Termine) - 45 minutes
**File**: `/app/Filament/Resources/AppointmentResource.php`

**Special Considerations**:
- Calendar view integration
- Time-sensitive data
- Multiple relationships

**Columns to manage**:
- id (ID)
- customer.name (Kunde)
- service.name (Service)
- staff.name (Mitarbeiter)
- branch.name (Filiale)
- appointment_date (Datum)
- appointment_time (Zeit)
- status (Status)
- notes (Notizen)

#### 2.2 BranchResource (Filialen) - 35 minutes
**File**: `/app/Filament/Resources/BranchResource.php`

**Columns to manage**:
- name (Name)
- company.name (Unternehmen)
- address (Adresse)
- phone (Telefon)
- manager.name (Filialleiter)
- staff_count (Mitarbeiter)
- services_count (Services)
- is_active (Aktiv)

#### 2.3 ServiceResource (Dienstleistungen) - 30 minutes
**File**: `/app/Filament/Resources/ServiceResource.php`

**Columns to manage**:
- name (Name)
- category (Kategorie)
- price (Preis)
- duration (Dauer)
- status (Status)
- bookings_count (Buchungen)
- revenue_total (Gesamtumsatz)

---

### Phase 3: Administrative Resources (1.5 hours)

#### 3.1 PhoneNumberResource - 25 minutes
#### 3.2 RetellAgentResource - 25 minutes
#### 3.3 InvoiceResource - 25 minutes
#### 3.4 BalanceTopupResource - 20 minutes

---

### Phase 4: System Resources (1 hour)

#### 4.1 ActivityLogResource - 20 minutes
#### 4.2 SystemSettingsResource - 20 minutes
#### 4.3 PermissionResource - 20 minutes

---

## 🛠️ Reusable Implementation Components

### 1. Create Base ListPage Class
```php
// app/Filament/Resources/Pages/BaseListRecords.php
abstract class BaseListRecords extends ListRecords
{
    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\CreateAction::make(),
        ];

        if ($this->hasColumnManager()) {
            $actions[] = $this->getColumnManagerAction();
        }

        return $actions;
    }

    protected function hasColumnManager(): bool
    {
        return method_exists(static::getResource(), 'getColumnData');
    }

    protected function getColumnManagerAction(): Actions\Action
    {
        $resource = static::getResource();
        $resourceName = $resource::getSlug();

        return Actions\Action::make('manageColumns')
            ->label('Spalten verwalten')
            ->icon('heroicon-o-view-columns')
            ->color('gray')
            ->modalHeading('Spalten verwalten')
            ->modalContent(function () use ($resource, $resourceName) {
                return view('filament.modals.column-manager-simple', [
                    'resource' => $resourceName,
                    'columns' => $resource::getColumnData(),
                ]);
            })
            ->modalWidth('lg');
    }
}
```

### 2. Create Column Data Helper Method
```php
// Add to each Resource class
public static function getColumnData(): array
{
    return [
        ['key' => 'field1', 'label' => 'Label 1', 'visible' => true],
        ['key' => 'field2', 'label' => 'Label 2', 'visible' => true],
        // ... define all columns
    ];
}
```

### 3. Batch Implementation Script
```bash
#!/bin/bash
# Script to add column management to multiple resources

RESOURCES=(
    "StaffResource"
    "CustomerResource"
    "CallResource"
    "AppointmentResource"
    "BranchResource"
    "ServiceResource"
)

for RESOURCE in "${RESOURCES[@]}"
do
    echo "Adding column management to $RESOURCE..."

    # Add trait to resource
    sed -i '/^class.*Resource/a use App\\Filament\\Traits\\HasColumnOrdering;' \
        /var/www/api-gateway/app/Filament/Resources/$RESOURCE.php

    # Update list page
    # ... additional commands
done
```

---

## ⚡ Performance Optimizations

### 1. Lazy Loading for Large Tables
```php
// For resources with >1000 records
->deferLoading()
->paginationPageOptions([25, 50, 100])
```

### 2. Column Preference Caching
```php
// Cache preferences per session
Cache::remember("user_{$userId}_columns_{$resource}", 3600, function () {
    return UserPreference::getColumnOrder($userId, $resource);
});
```

### 3. Batch Column Updates
```javascript
// Update all columns at once instead of individual API calls
saveAllPreferences() {
    const updates = this.getAllColumnStates();
    return fetch('/api/user-preferences/columns/batch-save', {
        method: 'POST',
        body: JSON.stringify({ resources: updates })
    });
}
```

---

## 📊 Testing Checklist

For each resource implementation:

- [ ] Column manager button appears
- [ ] Modal opens with all columns listed
- [ ] Drag & drop reorders columns
- [ ] Checkbox toggles visibility
- [ ] Preferences persist after refresh
- [ ] Reset button works
- [ ] Table reflects column order changes
- [ ] No JavaScript errors in console
- [ ] Mobile responsive
- [ ] Performance acceptable (<100ms response)

---

## ⏱️ Time Estimates

| Phase | Resources | Time |
|-------|-----------|------|
| Phase 1 | Staff, Customer, Call | 1h 45m |
| Phase 2 | Appointment, Branch, Service | 1h 50m |
| Phase 3 | Phone, Agent, Invoice, Topup | 1h 35m |
| Phase 4 | Logs, Settings, Permissions | 1h |
| **Total** | **All Resources** | **6h 10m** |

---

## 🚀 Quick Start Commands

```bash
# 1. Test column manager on companies page
curl https://api.askproai.de/admin/companies

# 2. Check JavaScript console
window.debugComponents()

# 3. Test API endpoints
curl -X GET /api/user-preferences/columns/companies
curl -X POST /api/user-preferences/columns/save

# 4. Clear all caches after changes
php artisan cache:clear && php artisan view:clear

# 5. Rebuild assets
npm run build
```

---

## 📝 Notes

1. **Consistency**: All resources should use the same modal template and styling
2. **Localization**: Use German labels consistently across all resources
3. **Security**: Ensure column preferences are user-scoped and cannot leak between users
4. **Documentation**: Update CLAUDE.md with column manager usage instructions
5. **Monitoring**: Add logging for preference changes for debugging

---

## ✅ Success Criteria

- All major Filament resources have column management
- User preferences persist across sessions
- Performance remains acceptable even with many columns
- Consistent UX across all tables
- No breaking changes to existing functionality