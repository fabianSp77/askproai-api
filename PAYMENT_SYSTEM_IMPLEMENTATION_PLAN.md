# Bezahlsystem Implementierungsplan

## Aktueller Status

### ✅ Vorhanden:
- `balance_cents` Feld in Tenants-Tabelle
- BillingController mit Stripe-Integration (50€ Aufladung)
- RevenueAnalyticsWidget zeigt Guthaben
- TenantResource mit Balance-Anzeige

### ❌ Fehlt:
1. Preismodelle/Tarife
2. Auflade-UI für Kunden
3. Transaktionshistorie
4. Automatische Verbrauchsabrechnung
5. Verschiedene Auflade-Beträge

## Implementierung

### Phase 1: Datenbank-Schema erweitern

#### 1.1 Pricing Plans Tabelle
```sql
CREATE TABLE pricing_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    
    -- Preise pro Service in Cents
    price_per_call_cents INT DEFAULT 10,        -- Standard API Call
    price_per_minute_cents INT DEFAULT 50,      -- Telefon-Minuten
    price_per_appointment_cents INT DEFAULT 100, -- Terminbuchung
    
    -- Mengenrabatte
    volume_discount_percentage INT DEFAULT 0,
    volume_threshold INT DEFAULT 1000,
    
    -- Features
    features JSON,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

#### 1.2 Tenant-Preismodell Zuordnung
```sql
ALTER TABLE tenants ADD COLUMN pricing_plan_id BIGINT UNSIGNED;
ALTER TABLE tenants ADD FOREIGN KEY (pricing_plan_id) REFERENCES pricing_plans(id);
```

#### 1.3 Transaktions-Historie
```sql
CREATE TABLE transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    type ENUM('topup', 'usage', 'refund', 'adjustment') NOT NULL,
    amount_cents INT NOT NULL,
    balance_before_cents INT NOT NULL,
    balance_after_cents INT NOT NULL,
    description VARCHAR(255),
    metadata JSON,
    stripe_payment_intent_id VARCHAR(255),
    created_at TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    INDEX idx_tenant_created (tenant_id, created_at)
);
```

### Phase 2: Models und Relationships

#### 2.1 PricingPlan Model
```php
// app/Models/PricingPlan.php
class PricingPlan extends Model {
    protected $fillable = [
        'name', 'slug', 'description',
        'price_per_call_cents', 'price_per_minute_cents',
        'price_per_appointment_cents', 'features'
    ];
    
    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean'
    ];
    
    public function tenants() {
        return $this->hasMany(Tenant::class);
    }
}
```

#### 2.2 Transaction Model
```php
// app/Models/Transaction.php
class Transaction extends Model {
    protected $fillable = [
        'tenant_id', 'type', 'amount_cents',
        'balance_before_cents', 'balance_after_cents',
        'description', 'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array'
    ];
    
    public function tenant() {
        return $this->belongsTo(Tenant::class);
    }
}
```

#### 2.3 Tenant Model erweitern
```php
// In app/Models/Tenant.php hinzufügen:

public function pricingPlan() {
    return $this->belongsTo(PricingPlan::class);
}

public function transactions() {
    return $this->hasMany(Transaction::class);
}

public function addCredit($cents, $description = null) {
    $balanceBefore = $this->balance_cents;
    $this->increment('balance_cents', $cents);
    
    Transaction::create([
        'tenant_id' => $this->id,
        'type' => 'topup',
        'amount_cents' => $cents,
        'balance_before_cents' => $balanceBefore,
        'balance_after_cents' => $balanceBefore + $cents,
        'description' => $description
    ]);
}

public function deductBalance($cents, $description = null) {
    if ($this->balance_cents < $cents) {
        throw new InsufficientBalanceException();
    }
    
    $balanceBefore = $this->balance_cents;
    $this->decrement('balance_cents', $cents);
    
    Transaction::create([
        'tenant_id' => $this->id,
        'type' => 'usage',
        'amount_cents' => -$cents,
        'balance_before_cents' => $balanceBefore,
        'balance_after_cents' => $balanceBefore - $cents,
        'description' => $description
    ]);
}
```

### Phase 3: Filament Resources

#### 3.1 PricingPlanResource
```php
// app/Filament/Admin/Resources/PricingPlanResource.php
- Liste aller Preismodelle
- Bearbeitung der Preise
- Aktivierung/Deaktivierung
```

#### 3.2 TransactionResource
```php
// app/Filament/Admin/Resources/TransactionResource.php
- Transaktionshistorie
- Filter nach Tenant, Typ, Datum
- Export-Funktion
```

#### 3.3 TenantResource erweitern
```php
// Neue Actions hinzufügen:
- "Add Credit" Action
- "View Transactions" Relation Manager
- "Change Pricing Plan" Action
```

### Phase 4: Auflade-UI

#### 4.1 Customer Portal Routes
```php
// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/topup', [BillingController::class, 'topup'])->name('billing.topup');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
    Route::get('/billing/transactions', [BillingController::class, 'transactions'])->name('billing.transactions');
});
```

#### 4.2 Blade Views
```
resources/views/billing/
├── index.blade.php         # Übersicht: Guthaben, Verbrauch, Plan
├── topup.blade.php         # Auflade-Formular mit Betragsauswahl
├── transactions.blade.php  # Transaktionshistorie
└── pricing-plans.blade.php # Verfügbare Tarife
```

### Phase 5: Usage Tracking

#### 5.1 Middleware für API-Calls
```php
// app/Http/Middleware/DeductApiUsage.php
class DeductApiUsage {
    public function handle($request, Closure $next) {
        $response = $next($request);
        
        if ($tenant = $request->user()->tenant) {
            $plan = $tenant->pricingPlan;
            $cost = $plan->price_per_call_cents;
            
            try {
                $tenant->deductBalance($cost, 'API Call: ' . $request->path());
            } catch (InsufficientBalanceException $e) {
                return response()->json(['error' => 'Insufficient balance'], 402);
            }
        }
        
        return $response;
    }
}
```

### Phase 6: Dashboard Widgets

#### 6.1 Balance Widget (erweitern)
- Aktuelles Guthaben
- Verbrauch heute/Woche/Monat
- Quick-Topup Button

#### 6.2 Usage Statistics Widget
- API Calls nach Typ
- Kosten-Breakdown
- Trend-Grafik

#### 6.3 Low Balance Alert
- Warnung bei Guthaben < 10€
- Email-Benachrichtigung Option

## Prioritäten

1. **SOFORT**: Routen für BillingController aktivieren
2. **HOCH**: Preismodelle implementieren
3. **MITTEL**: Transaktionshistorie
4. **NIEDRIG**: Erweiterte Features (Rabatte, Reports)

## Geschätzter Aufwand

- Phase 1-2: 4 Stunden (Datenbank + Models)
- Phase 3: 3 Stunden (Admin Resources)
- Phase 4: 4 Stunden (Customer UI)
- Phase 5: 2 Stunden (Usage Tracking)
- Phase 6: 2 Stunden (Widgets)

**Gesamt: ~15 Stunden für vollständige Implementation**

## Nächste Schritte

1. Migration für Datenbank-Tabellen erstellen
2. Models implementieren
3. Admin-Interface erweitern
4. Customer Portal aktivieren
5. Testing & Deployment