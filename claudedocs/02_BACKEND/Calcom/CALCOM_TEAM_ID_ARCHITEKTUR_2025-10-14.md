# Cal.com Team ID Architektur - Analyse & Empfehlung

**Datum:** 2025-10-14
**Status:** 📋 ANALYSE KOMPLETT
**User Frage:** Wie ist Cal.com Team ID Zuordnung geregelt? Company-Level? Branch-Level?

---

## 🎯 USER ANFORDERUNG

**Gewünschte Struktur:**
```
Unternehmen (Company)
  └── Mindestens 1 Filiale (Branch)
        └── Team ID (Cal.com)
              └── Dienstleistungen (Services) können je Filiale unterschiedlich sein
```

**Grund:** Jede Filiale könnte unterschiedliche Dienstleistungen anbieten.

---

## 📊 AKTUELLE ARCHITEKTUR

### Datenbank-Struktur

```
companies
  ├── id
  ├── calcom_team_id (unsignedInteger, nullable)      ← COMPANY-LEVEL
  ├── calcom_team_name
  ├── calcom_team_slug
  └── team_sync_status

branches
  ├── id
  ├── company_id (FK)
  ├── name, city, phone_number
  └── NO calcom_team_id                                ← BRANCH hat KEINE Team ID

services
  ├── id
  ├── company_id (FK)
  ├── name, duration_minutes, price
  ├── calcom_event_type_id (unsignedInteger, nullable) ← SERVICE-LEVEL
  └── is_active

branch_service (Pivot)
  ├── branch_id (FK)
  ├── service_id (FK)
  ├── is_active
  ├── duration_override_minutes
  ├── price_override
  └── custom_segments
```

---

## 🏗️ AKTUELLE IMPLEMENTIERUNG

### 1. Company Model (Lines 163-211)

```php
class Company extends Model
{
    /**
     * Check if company has a Cal.com team assigned
     */
    public function hasTeam(): bool
    {
        return !empty($this->calcom_team_id);  // ← COMPANY-LEVEL
    }

    /**
     * Get services that belong to this company's team
     */
    public function teamServices()
    {
        return $this->services()->whereNotNull('calcom_event_type_id');
    }

    /**
     * Validate that a service belongs to this company's team
     */
    public function ownsService(int $calcomEventTypeId): bool
    {
        if (!$this->hasTeam()) {
            return false;
        }

        $calcomService = new \App\Services\CalcomV2Service($this);
        return $calcomService->validateTeamAccess($this->calcom_team_id, $calcomEventTypeId);
    }
}
```

**➡️ Company ist der Haupt-Container für Team ID**

---

### 2. Branch Model (Lines 75-92)

```php
class Branch extends Model
{
    protected $fillable = [
        'company_id', 'name', 'city', 'phone_number',
        // NOTE: calcom_event_type_id removed - branches link to services (which have event_type_ids)
        'calcom_api_key', 'retell_agent_id', 'integration_status',
        // ...
    ];

    /**
     * Many-to-Many: Branch can have multiple Services
     * Service can be assigned to multiple Branches
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'branch_service')
            ->withPivot([
                'duration_override_minutes',
                'price_override',
                'is_active'  // ← Branch kann Service aktivieren/deaktivieren
            ])
            ->withTimestamps();
    }

    public function activeServices(): BelongsToMany
    {
        return $this->services()->wherePivot('is_active', true);
    }
}
```

**➡️ Branch hat KEINE direkte Team ID**
**➡️ Branch linkt zu Services über Pivot-Tabelle**
**➡️ Branch kann Services pro Filiale aktivieren/deaktivieren**

---

### 3. Service Model

```php
class Service extends Model
{
    protected $fillable = [
        // ...
        'calcom_event_type_id',  // ← SERVICE-LEVEL: Cal.com Event Type
        'is_active',
        // ...
    ];

    /**
     * Service belongs to Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Service can be assigned to multiple Branches
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_service')
            ->withPivot(['is_active', 'duration_override_minutes', 'price_override']);
    }
}
```

**➡️ Service gehört zu Company (nicht zu Branch)**
**➡️ Service hat `calcom_event_type_id`**
**➡️ Service kann mehreren Branches zugeordnet werden**

---

### 4. Settings Dashboard

**Settings Speicherung (Lines 938-960):**
```php
public function save(): void
{
    $data = $this->form->getState();

    // Save each setting as key-value in system_settings table
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            continue;  // Arrays handled separately
        }

        SystemSetting::updateOrCreate(
            [
                'company_id' => $this->selectedCompanyId,
                'key' => $key,  // e.g. 'calcom_team_id'
            ],
            [
                'value' => $value,
                'group' => $groupMapping[$key] ?? 'general',
                // ...
            ]
        );
    }

    // Save Branches, Services, Staff separately
    $this->saveBranches($data);
    $this->saveServices($data);
    $this->saveStaff($data);
}
```

**Form Definition (Lines 314-333):**
```php
protected function getCalcomTab(): Tabs\Tab
{
    return Tabs\Tab::make('Cal.com')->schema([
        // ...

        Grid::make(2)->schema([
            TextInput::make('calcom_team_id')
                ->label('Team ID')
                ->numeric()
                ->helperText('Cal.com Team ID für diese Company'),  // ← COMPANY-LEVEL

            TextInput::make('calcom_team_slug')
                ->label('Team Slug')
                ->helperText('Cal.com Team Slug (z.B. "askproai")'),
        ]),

        TextInput::make('calcom_event_type_id')
            ->label('Event Type ID (Standard)')
            ->numeric()
            ->helperText('Optional: Standard Event Type ID für Terminbuchungen'),

        // ...
    ]);
}
```

**➡️ Team ID wird auf Company-Level im Settings Dashboard gesetzt**
**➡️ Gespeichert in `system_settings` Tabelle**

---

## 🔍 WIE ES AKTUELL FUNKTIONIERT

### Datenfluss: Company → Service → Branch

```
1. COMPANY hat Team ID
   └── companies.calcom_team_id = 123

2. SERVICES gehören zu Company
   ├── Service A (calcom_event_type_id = 456)
   ├── Service B (calcom_event_type_id = 789)
   └── Service C (calcom_event_type_id = 101)

3. BRANCHES können Services zuordnen
   Branch Hamburg:
   ├── Service A (aktiv, custom price)
   ├── Service B (aktiv)
   └── Service C (inaktiv)

   Branch Berlin:
   ├── Service A (aktiv)
   ├── Service B (inaktiv)
   └── Service C (aktiv, custom duration)
```

**Logik:**
- **Company** definiert welches **Cal.com Team** verwendet wird
- **Services** sind company-weit definiert (mit Event Type IDs)
- **Branches** können Services selektiv aktivieren/deaktivieren
- **Branches** können Service-Parameter überschreiben (Preis, Dauer)

---

## ⚖️ VERGLEICH: IST vs. SOLL

### IST-Zustand

```
Company
  ├── calcom_team_id: 123          ← Eine Team ID für gesamte Company
  └── Services
        ├── Service A (event_type_id: 456)
        ├── Service B (event_type_id: 789)
        └── Service C (event_type_id: 101)

Branch Hamburg
  └── Services (via Pivot)
        ├── Service A ✅ aktiv
        ├── Service B ✅ aktiv
        └── Service C ❌ inaktiv

Branch Berlin
  └── Services (via Pivot)
        ├── Service A ✅ aktiv
        ├── Service B ❌ inaktiv
        └── Service C ✅ aktiv
```

**Einschränkung:**
- ❌ Alle Branches müssen **dieselben Cal.com Event Types** verwenden (vom Company Team)
- ❌ Branches können NICHT **eigene Team IDs** haben
- ✅ Branches können Services ein/ausschalten
- ✅ Branches können Preise/Dauer überschreiben

---

### SOLL-Zustand (User-Anforderung)

```
Company
  └── Branches
        ├── Branch Hamburg
        │     ├── calcom_team_id: 123        ← Eigene Team ID
        │     └── Services (Hamburg-spezifisch)
        │           ├── Herrenhaarschnitt
        │           ├── Damenhaarschnitt
        │           └── Färben
        │
        └── Branch Berlin
              ├── calcom_team_id: 456        ← Eigene Team ID
              └── Services (Berlin-spezifisch)
                    ├── Herrenhaarschnitt
                    ├── Rasur
                    └── Bartpflege
```

**Vorteil:**
- ✅ Jede Branch kann **eigenes Cal.com Team** haben
- ✅ Branches können **komplett unterschiedliche Services** anbieten
- ✅ Flexible, unabhängige Verwaltung pro Standort

---

## 🚧 WAS MÜSSTE GEÄNDERT WERDEN?

### Option 1: Branch-Level Team IDs (EMPFOHLEN für User-Anforderung)

#### 1.1 Database Migration

```php
// Add to branches table
Schema::table('branches', function (Blueprint $table) {
    $table->unsignedInteger('calcom_team_id')->nullable()->after('company_id');
    $table->string('calcom_team_slug')->nullable()->after('calcom_team_id');
    $table->enum('team_sync_status', ['pending', 'syncing', 'synced', 'error'])
          ->default('pending')
          ->after('calcom_team_slug');
    $table->timestamp('last_team_sync')->nullable()->after('team_sync_status');
    $table->text('team_sync_error')->nullable()->after('last_team_sync');
});
```

#### 1.2 Branch Model Update

```php
class Branch extends Model
{
    protected $fillable = [
        // ...
        'calcom_team_id',          // NEW
        'calcom_team_slug',        // NEW
        'team_sync_status',        // NEW
        'last_team_sync',          // NEW
        'team_sync_error',         // NEW
    ];

    /**
     * Check if branch has a Cal.com team assigned
     */
    public function hasTeam(): bool
    {
        return !empty($this->calcom_team_id);
    }

    /**
     * Get team services (branch-specific)
     */
    public function teamServices()
    {
        return $this->services()->whereNotNull('calcom_event_type_id');
    }

    /**
     * Fallback to company team if branch has no team
     */
    public function getEffectiveTeamId(): ?int
    {
        return $this->calcom_team_id ?? $this->company->calcom_team_id;
    }
}
```

#### 1.3 Settings Dashboard Update

```php
// Add to getBranchesTab()
Repeater::make('branches')
    ->schema([
        TextInput::make('name')->required(),
        TextInput::make('city'),

        // NEW: Branch-specific Team ID
        Section::make('Cal.com Team (Optional)')
            ->description('Leer lassen um Company Team zu verwenden')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('calcom_team_id')
                        ->label('Team ID')
                        ->numeric()
                        ->helperText('Optional: Eigene Team ID für diese Filiale'),

                    TextInput::make('calcom_team_slug')
                        ->label('Team Slug')
                        ->helperText('Optional: Team Slug für diese Filiale'),
                ]),
            ])
            ->collapsible()
            ->collapsed(),

        // ... rest of fields
    ]);
```

#### 1.4 Service Assignment Logic Update

```php
// When creating appointments, use branch team if available
class AppointmentService
{
    public function createAppointment(Branch $branch, Service $service)
    {
        $teamId = $branch->getEffectiveTeamId();  // Branch Team OR Company Team

        $calcomService = new CalcomService($branch->company);
        $calcomService->createBooking([
            'teamId' => $teamId,
            'eventTypeId' => $service->calcom_event_type_id,
            // ...
        ]);
    }
}
```

---

### Option 2: Hierarchie beibehalten (Company-Level) - AKTUELL

**Vorteile:**
- ✅ Einfacher zu verwalten (ein Team pro Company)
- ✅ Weniger Cal.com API Calls
- ✅ Konsistente Event Types über alle Branches
- ✅ Keine Datenbank-Migration nötig

**Nachteile:**
- ❌ Alle Branches teilen sich dieselbe Team ID
- ❌ Services müssen company-weit definiert sein
- ❌ Branches können nur Services ein/ausschalten, nicht eigene erstellen

**Workaround für unterschiedliche Services pro Branch:**
```
Company definiert ALLE möglichen Services:
  ├── Herrenhaarschnitt (Hamburg + Berlin)
  ├── Damenhaarschnitt (Hamburg + Berlin)
  ├── Färben (nur Hamburg)
  ├── Rasur (nur Berlin)
  └── Bartpflege (nur Berlin)

Branch Hamburg aktiviert:
  ├── Herrenhaarschnitt ✅
  ├── Damenhaarschnitt ✅
  ├── Färben ✅
  ├── Rasur ❌
  └── Bartpflege ❌

Branch Berlin aktiviert:
  ├── Herrenhaarschnitt ✅
  ├── Damenhaarschnitt ✅
  ├── Färben ❌
  ├── Rasur ✅
  └── Bartpflege ✅
```

**Das funktioniert aktuell bereits!**

---

## 💡 EMPFEHLUNG

### Für User-Anforderung: Option 1 (Branch-Level Team IDs)

**Wenn:**
- ✅ Jede Filiale **echte autonome** Dienstleistungen braucht
- ✅ Branches haben **komplett unterschiedliche** Geschäftsmodelle
- ✅ Cal.com Teams sind bereits pro Branch organisiert
- ✅ Zukünftig: Multi-Franchise mit unabhängigen Standorten

**Aufwand:**
- 📅 **1-2 Tage Entwicklung**
- Database Migration
- Model Updates
- Settings Dashboard Anpassung
- Cal.com Integration Logic Update
- Testing aller Appointment-Flows

---

### Alternative: Aktuelle Struktur optimieren (Company-Level)

**Wenn:**
- ✅ Services sind **ähnlich** über alle Branches
- ✅ Nur kleine **Anpassungen** pro Branch nötig (Preis, Dauer)
- ✅ Schnelle Implementierung gewünscht
- ✅ Weniger Komplexität gewünscht

**Bereits vorhanden:**
- ✅ Branch kann Services aktivieren/deaktivieren
- ✅ Branch kann Preise überschreiben
- ✅ Branch kann Dauer überschreiben
- ✅ Funktioniert mit aktuellem Code

**Beispiel:**
```php
// Bereits implementiert in branch_service Pivot:
Branch Hamburg:
  - Herrenhaarschnitt (active, price: 25€, duration: 30min)
  - Färben (active, price: 80€, duration: 120min)
  - Rasur (inactive)

Branch Berlin:
  - Herrenhaarschnitt (active, price: 30€, duration: 45min)
  - Färben (inactive)
  - Rasur (active, price: 20€, duration: 20min)
```

---

## 📋 NÄCHSTE SCHRITTE

### Entscheidung benötigt:

**Frage an User:**

1. **Brauchen Branches wirklich eigene Cal.com Team IDs?**
   - Oder reicht es, wenn Branches Services aktivieren/deaktivieren können?

2. **Sind die Services pro Branch komplett unterschiedlich?**
   - Oder sind es ähnliche Services mit kleinen Anpassungen?

3. **Wie ist Cal.com aktuell organisiert?**
   - Ein Team pro Company?
   - Oder bereits ein Team pro Branch?

4. **Zeitrahmen?**
   - Schnelle Lösung (aktuelle Struktur nutzen)
   - Oder strukturelle Änderung (1-2 Tage Entwicklung)

---

## 📊 ZUSAMMENFASSUNG

### Aktueller Stand:
```
✅ Company hat calcom_team_id
✅ Services gehören zu Company (haben event_type_id)
✅ Branches können Services via Pivot zuordnen
✅ Branches können Services aktivieren/deaktivieren
✅ Branches können Preis/Dauer überschreiben
❌ Branches haben KEINE eigene Team ID
```

### Was funktioniert JETZT schon:
```
✅ Hamburg: Herrenhaarschnitt, Damenhaarschnitt, Färben
✅ Berlin: Herrenhaarschnitt, Rasur, Bartpflege
✅ Services werden über Pivot pro Branch gesteuert
✅ Settings Dashboard erlaubt Branch-Service Zuordnung
```

### Was User möchte (verstanden):
```
📋 Jede Branch könnte eigene Cal.com Team ID haben
📋 Jede Branch könnte komplett eigene Services haben
📋 Flexiblere, unabhängigere Verwaltung
```

### Empfehlung:
**Testen Sie zunächst die aktuelle Struktur:**
- Legen Sie alle möglichen Services auf Company-Level an
- Aktivieren Sie Services pro Branch selektiv
- Überschreiben Sie Preise/Dauer pro Branch
- **Falls das nicht ausreicht** → Option 1 implementieren (Branch-Level Team IDs)

---

**Developer:** Claude Code
**Date:** 2025-10-14
**Status:** Analyse komplett, Entscheidung offen

**Wir besprechen morgen wie Sie weiter vorgehen möchten!** 🎯
