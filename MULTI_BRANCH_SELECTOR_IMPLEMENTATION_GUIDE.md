# Multi-Branch Selector Implementation Guide

## 🎯 Überblick

AskProAI unterstützt Multi-Branch (Mehrfilial) Betrieb, bei dem ein Unternehmen mehrere Standorte/Filialen verwalten kann. Jede Filiale hat eigene Mitarbeiter, Services, Arbeitszeiten und Termine. Der Branch Selector ermöglicht es Benutzern, zwischen Filialen zu wechseln.

## 🏗️ Architektur

### Datenmodell
```
Company (Mandant)
├── Branch 1 (Hauptfiliale)
│   ├── Staff (Mitarbeiter)
│   ├── Services
│   ├── Working Hours
│   └── Appointments
└── Branch 2 (Zweigstelle)
    ├── Staff
    ├── Services
    ├── Working Hours
    └── Appointments
```

### Scope-System
- **BranchScope**: Filtert automatisch alle Daten nach aktueller Filiale
- **TenantScope**: Filtert nach Company (übergeordnet)
- Session-basierte Speicherung der gewählten Filiale

## 📋 Aktuelle Implementierung

### 1. BranchContextManager Service
```php
// app/Services/BranchContextManager.php
class BranchContextManager
{
    public function getCurrentBranch(): ?Branch
    public function setCurrentBranch(?string $branchId): bool
    public function getBranchesForUser(?User $user = null): Collection
    public function canAccessBranch(?User $user, string $branchId): bool
}
```

**Verwendung:**
```php
// Aktuelle Filiale abrufen
$branch = app(BranchContextManager::class)->getCurrentBranch();

// Filiale wechseln
app(BranchContextManager::class)->setCurrentBranch($branchId);
```

### 2. BranchScope
```php
// app/Models/Scopes/BranchScope.php
// Automatische Filterung aller Models mit branch_id
```

### 3. Branch Selector Page (Workaround)
```php
// app/Filament/Admin/Pages/BranchSelector.php
// Separate Seite für Filialauswahl
// URL: /admin/branch-selector
```

## 🚨 Bekannte Probleme

### 1. Global Dropdown funktioniert nicht
**Problem**: Livewire Component in Filament Render Hook verursacht Fehler
- GET/POST Request Mismatch
- Alpine.js Dropdown schließt nicht
- Property Initialization Errors

**Temporärer Workaround**: Link zur Branch Selector Page im Header

### 2. Livewire Lifecycle Issues
```php
// FEHLER:
Typed property $branchContext must not be accessed before initialization

// LÖSUNG:
public function switchBranch($branchId): void
{
    if (!isset($this->branchContext)) {
        $this->branchContext = app(BranchContextManager::class);
    }
    // ...
}
```

## 🛠️ Implementierungs-Optionen

### Option 1: Filament Global Action (Empfohlen)
```php
// In AdminPanelProvider.php
->globalActions([
    \Filament\Actions\Action::make('switch-branch')
        ->label(fn () => app(BranchContextManager::class)->getCurrentBranch()?->name ?? 'Wähle Filiale')
        ->icon('heroicon-o-building-office')
        ->form([
            Forms\Components\Select::make('branch_id')
                ->label('Filiale')
                ->options(function () {
                    return app(BranchContextManager::class)
                        ->getBranchesForUser()
                        ->pluck('name', 'id');
                })
                ->required()
        ])
        ->action(function (array $data) {
            app(BranchContextManager::class)->setCurrentBranch($data['branch_id']);
            
            Notification::make()
                ->title('Filiale gewechselt')
                ->success()
                ->send();
                
            return redirect()->to(request()->header('referer', '/admin'));
        })
])
```

### Option 2: Navigation Item mit Modal
```php
// In navigation array
NavigationItem::make('Filiale wechseln')
    ->icon('heroicon-o-building-office')
    ->label(fn () => 'Filiale: ' . (app(BranchContextManager::class)->getCurrentBranch()?->name ?? 'Keine'))
    ->url('#')
    ->sort(100)
    ->isActiveWhen(fn () => false)
    ->badge(fn () => app(BranchContextManager::class)->getBranchesForUser()->count())
```

### Option 3: Custom Widget
```php
// app/Filament/Widgets/BranchSelectorWidget.php
class BranchSelectorWidget extends Widget
{
    protected static string $view = 'filament.widgets.branch-selector';
    
    public function getCurrentBranch(): ?Branch
    {
        return app(BranchContextManager::class)->getCurrentBranch();
    }
    
    public function switchBranch($branchId): void
    {
        app(BranchContextManager::class)->setCurrentBranch($branchId);
        $this->redirect(request()->header('referer', '/admin'));
    }
}
```

### Option 4: JavaScript-basierte Lösung
```javascript
// resources/js/branch-selector.js
Alpine.data('branchSelector', () => ({
    open: false,
    currentBranch: null,
    branches: [],
    
    init() {
        this.loadBranches();
    },
    
    async loadBranches() {
        const response = await fetch('/api/branches');
        const data = await response.json();
        this.branches = data.branches;
        this.currentBranch = data.current;
    },
    
    async switchBranch(branchId) {
        const response = await fetch('/api/branches/switch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ branch_id: branchId })
        });
        
        if (response.ok) {
            window.location.reload();
        }
    }
}));
```

## 📋 Schritt-für-Schritt Implementierung

### 1. Backend vorbereiten
```bash
# Migration für branch_id in relevanten Tabellen
php artisan make:migration add_branch_id_to_tables

# In der Migration:
Schema::table('appointments', function (Blueprint $table) {
    $table->foreignUuid('branch_id')->nullable()->after('company_id');
});
```

### 2. Models anpassen
```php
// In Models mit branch_id
use App\Models\Scopes\BranchScope;

protected static function booted()
{
    static::addGlobalScope(new BranchScope);
}
```

### 3. API Routes hinzufügen
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/branches', [BranchController::class, 'index']);
    Route::post('/branches/switch', [BranchController::class, 'switch']);
});
```

### 4. Controller erstellen
```php
// app/Http/Controllers/Api/BranchController.php
class BranchController extends Controller
{
    public function index(BranchContextManager $branchContext)
    {
        return response()->json([
            'current' => $branchContext->getCurrentBranch(),
            'branches' => $branchContext->getBranchesForUser()
        ]);
    }
    
    public function switch(Request $request, BranchContextManager $branchContext)
    {
        $request->validate([
            'branch_id' => 'required|uuid'
        ]);
        
        if ($branchContext->setCurrentBranch($request->branch_id)) {
            return response()->json(['success' => true]);
        }
        
        return response()->json(['error' => 'Unauthorized'], 403);
    }
}
```

## 🧪 Testing

### Test-Szenario 1: Branch wechseln
```php
test('user can switch branches', function () {
    $user = User::factory()->create();
    $branch1 = Branch::factory()->create();
    $branch2 = Branch::factory()->create();
    
    $this->actingAs($user)
        ->post('/api/branches/switch', ['branch_id' => $branch2->id])
        ->assertOk();
        
    expect(session('current_branch_id'))->toBe($branch2->id);
});
```

### Test-Szenario 2: Daten-Filterung
```php
test('data is filtered by current branch', function () {
    $branch1 = Branch::factory()->create();
    $branch2 = Branch::factory()->create();
    
    Appointment::factory()->count(5)->create(['branch_id' => $branch1->id]);
    Appointment::factory()->count(3)->create(['branch_id' => $branch2->id]);
    
    app(BranchContextManager::class)->setCurrentBranch($branch1->id);
    
    expect(Appointment::count())->toBe(5);
});
```

## 🚀 Deployment

### 1. Migrations ausführen
```bash
php artisan migrate
```

### 2. Cache leeren
```bash
php artisan optimize:clear
```

### 3. Assets kompilieren
```bash
npm run build
```

### 4. Permissions prüfen
```php
// Sicherstellen dass User Branches sehen können
Gate::define('view-branches', function ($user) {
    return $user->hasRole(['super_admin', 'admin', 'manager']);
});
```

## 📊 Monitoring

### Metriken
- Branch-Wechsel pro User
- Häufigste Filialen
- Performance der Scope-Filterung

### Logs
```php
Log::info('Branch switched', [
    'user_id' => auth()->id(),
    'from_branch' => $oldBranch?->id,
    'to_branch' => $newBranch->id,
    'ip' => request()->ip()
]);
```

## 🐛 Debugging

### Debug-Befehle
```bash
# Aktuelle Branch in Session prüfen
php artisan tinker
>>> session('current_branch_id')

# Alle Branches für User
>>> app(BranchContextManager::class)->getBranchesForUser()->pluck('name', 'id')

# Scope testen
>>> \App\Models\Appointment::withoutGlobalScope(\App\Models\Scopes\BranchScope::class)->count()
>>> \App\Models\Appointment::count()
```

### Häufige Fehler

**1. "No branch selected"**
- User hat keine Filiale gewählt
- Lösung: Default-Branch setzen oder zur Auswahl zwingen

**2. "Cannot access branch"**
- User hat keine Berechtigung für diese Filiale
- Lösung: Permissions prüfen

**3. "Data not filtered"**
- BranchScope nicht aktiviert
- Lösung: Global Scope in Model hinzufügen

## 📝 Best Practices

1. **Immer Default-Branch setzen**
   ```php
   if (!$branchContext->getCurrentBranch() && $user->branches->count() === 1) {
       $branchContext->setCurrentBranch($user->branches->first()->id);
   }
   ```

2. **Branch-Kontext in Jobs berücksichtigen**
   ```php
   class ProcessAppointment implements ShouldQueue
   {
       public $branchId;
       
       public function __construct($branchId)
       {
           $this->branchId = $branchId;
       }
       
       public function handle()
       {
           app(BranchContextManager::class)->setCurrentBranch($this->branchId);
           // ...
       }
   }
   ```

3. **API-Responses mit Branch-Info**
   ```php
   return response()->json([
       'data' => $appointments,
       'meta' => [
           'current_branch' => app(BranchContextManager::class)->getCurrentBranch()
       ]
   ]);
   ```

## 🎯 Nächste Schritte

1. **Kurzfristig**: Branch Selector Page als Workaround nutzen
2. **Mittelfristig**: Filament Global Action implementieren
3. **Langfristig**: Native Filament Component entwickeln

---

**Status**: 🟡 Teilweise implementiert (Workaround verfügbar)
**Priorität**: Mittel (Funktionalität vorhanden, UX verbesserungswürdig)