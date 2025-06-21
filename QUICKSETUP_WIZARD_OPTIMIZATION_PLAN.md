# QuickSetupWizard Optimization Plan

## Kritische Probleme & Sofortmaßnahmen

### 1. Performance-Optimierung (Priorität: HOCH)

#### Problem: N+1 Queries & ineffiziente Datenbankzugriffe
```php
// AKTUELL - Lädt ALLE Companies
$companies = Company::all();

// OPTIMIERT - Nur benötigte Felder
$companies = Company::select('id', 'name')
    ->where('is_active', true)
    ->orderBy('name')
    ->limit(100)
    ->get();
```

#### Problem: Mehrere einzelne Branch-Inserts
```php
// AKTUELL - Loop mit einzelnen Inserts
foreach ($branchesData as $branch) {
    Branch::create($branch);
}

// OPTIMIERT - Bulk Insert
Branch::insert(
    collect($branchesData)->map(function($branch) use ($company) {
        return array_merge($branch, [
            'company_id' => $company->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    })->toArray()
);
```

### 2. Code-Struktur Refactoring

#### Service-Klassen erstellen:
```php
// app/Services/Setup/CompanySetupService.php
class CompanySetupService
{
    public function createCompany(array $data): Company
    {
        return DB::transaction(function() use ($data) {
            $company = Company::create([
                'name' => $data['company_name'],
                'industry' => $data['industry'],
                'settings' => [
                    'wizard_completed' => true,
                    'setup_date' => now(),
                ]
            ]);
            
            event(new CompanyCreated($company));
            return $company;
        });
    }
}

// app/Services/Setup/BranchSetupService.php  
class BranchSetupService
{
    public function createBranches(Company $company, array $branchesData): Collection
    {
        $branches = collect($branchesData)->map(function($data) use ($company) {
            return new Branch([
                'company_id' => $company->id,
                'name' => $data['name'],
                'city' => $data['city'],
                'address' => $data['address'],
                'phone_number' => $data['phone_number'],
                'is_active' => true,
                'business_hours' => $this->getDefaultBusinessHours(),
                'features' => $data['features'] ?? [],
            ]);
        });
        
        // Bulk save
        $company->branches()->saveMany($branches);
        
        return $branches;
    }
}
```

### 3. Form Validation verbessern

```php
// app/Http/Requests/QuickSetupRequest.php
class QuickSetupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'company_name' => 'required|string|max:255|unique:companies,name',
            'industry' => 'required|in:medical,beauty,handwerk,legal',
            'branches' => 'required|array|min:1',
            'branches.*.name' => 'required|string|max:255',
            'branches.*.city' => 'required|string|max:100',
            'branches.*.phone_number' => [
                'required',
                'string',
                'regex:/^\+49[0-9\s]{10,15}$/'
            ],
        ];
    }
}
```

### 4. Error Handling verbessern

```php
// Spezifische Exception-Klassen
class CompanySetupException extends Exception {}
class BranchCreationException extends Exception {}
class IntegrationException extends Exception {}

// Im Wizard
try {
    $company = $this->companySetupService->createCompany($data);
} catch (CompanySetupException $e) {
    Notification::make()
        ->title('Firma konnte nicht angelegt werden')
        ->body($e->getMessage())
        ->danger()
        ->send();
    return;
} catch (\Exception $e) {
    Log::error('Unexpected error in company setup', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    throw $e;
}
```

### 5. Memory-Optimierung

```php
// Industry Templates in Config-Datei verschieben
// config/askproai.php
return [
    'industry_templates' => [
        'medical' => [
            'name' => '🏥 Medizin & Gesundheit',
            // ...
        ],
    ],
];

// Im Wizard
protected function getIndustryTemplates(): array
{
    return config('askproai.industry_templates', []);
}
```

## Unit Tests

### 1. CompanySetupServiceTest
```php
class CompanySetupServiceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_creates_company_with_correct_data()
    {
        $service = new CompanySetupService();
        
        $data = [
            'company_name' => 'Test Company',
            'industry' => 'medical',
        ];
        
        $company = $service->createCompany($data);
        
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'industry' => 'medical',
        ]);
        
        $this->assertTrue($company->settings['wizard_completed']);
    }
    
    public function test_rollback_on_failure()
    {
        // Test transaction rollback
    }
}
```

### 2. BranchSetupServiceTest
```php
class BranchSetupServiceTest extends TestCase
{
    public function test_bulk_creates_branches()
    {
        $company = Company::factory()->create();
        $service = new BranchSetupService();
        
        $branchesData = [
            ['name' => 'Branch 1', 'city' => 'Berlin'],
            ['name' => 'Branch 2', 'city' => 'Munich'],
        ];
        
        $branches = $service->createBranches($company, $branchesData);
        
        $this->assertCount(2, $branches);
        $this->assertEquals(2, $company->branches()->count());
    }
}
```

### 3. QuickSetupWizardTest (Feature Test)
```php
class QuickSetupWizardTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_complete_wizard_flow()
    {
        $user = User::factory()->create();
        
        Livewire::actingAs($user)
            ->test(QuickSetupWizard::class)
            ->set('data.company_name', 'Test Company')
            ->set('data.industry', 'medical')
            ->set('data.branches', [
                ['name' => 'Main', 'city' => 'Berlin', 'phone_number' => '+49 30 12345678']
            ])
            ->call('completeSetup')
            ->assertHasNoErrors()
            ->assertNotified('Setup erfolgreich abgeschlossen!');
            
        $this->assertDatabaseHas('companies', ['name' => 'Test Company']);
        $this->assertDatabaseHas('branches', ['name' => 'Main']);
    }
    
    public function test_validation_errors()
    {
        // Test verschiedene Validierungsfehler
    }
}
```

## Implementierungs-Prioritäten

### Phase 1: Sofortmaßnahmen (1-2 Tage)
1. ✅ Query-Optimierungen
2. ✅ Bulk-Inserts für Branches
3. ✅ Basis-Validation

### Phase 2: Service-Extraktion (2-3 Tage)
1. CompanySetupService
2. BranchSetupService
3. PhoneNumberService

### Phase 3: Testing & Monitoring (1-2 Tage)
1. Unit Tests schreiben
2. Performance-Monitoring einbauen
3. Error-Tracking verbessern

### Phase 4: UI-Optimierungen (optional)
1. Lazy Loading für große Forms
2. Progress-Indicators
3. Auto-Save Funktion

## Erwartete Verbesserungen
- **Performance**: 40-60% schnellere Ladezeiten
- **Memory**: 30% weniger Speicherverbrauch
- **Code-Qualität**: Bessere Testbarkeit und Wartbarkeit
- **Fehlerbehandlung**: Klare, spezifische Fehlermeldungen