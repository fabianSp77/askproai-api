# 📋 STRIPE INTEGRATION PLAN - WIZARD INTEGRATION

## 🎯 BEWERTUNG DES STRIPE-PLANS

Der Plan vom anderen Claude-Agenten ist **SEHR GUT DURCHDACHT** für deutsche Steuer-Compliance:

### ✅ Stärken des Plans:
1. **Kleinunternehmerregelung** - Kritisch für deutsche Startups
2. **Variable Steuersätze** statt hardcoded 19%
3. **GoBD-Konformität** - Rechtlich notwendig
4. **USt-ID Validierung** - Wichtig für B2B
5. **DATEV-Export** - Standard für Steuerberater

### 🔧 INTEGRATION IN DEN QUICK SETUP WIZARD

## NEUER WIZARD STEP 8: "Zahlungen & Steuern"

### Position im Wizard:
1. Company & Branch ✅
2. Phone Configuration ✅
3. Cal.com Setup ✅
4. Retell AI Setup ✅
5. Integration Check ✅
6. Services & Staff ✅
7. Review & Health Check ✅
8. **NEU: Zahlungen & Steuern** 💰

### Wizard Step Implementation:

```php
protected function getPaymentAndTaxFields(): array
{
    return [
        // Stripe Connection
        Section::make('Stripe Zahlungsanbindung')
            ->description('Verbinden Sie Ihr Stripe-Konto für automatische Zahlungen')
            ->schema([
                Toggle::make('enable_stripe')
                    ->label('Stripe aktivieren')
                    ->default(false)
                    ->reactive()
                    ->helperText('Automatische Rechnungsstellung und Zahlungsabwicklung'),
                    
                TextInput::make('stripe_publishable_key')
                    ->label('Stripe Publishable Key')
                    ->visible(fn($get) => $get('enable_stripe'))
                    ->required(fn($get) => $get('enable_stripe'))
                    ->helperText('Beginnt mit pk_live_ oder pk_test_'),
                    
                TextInput::make('stripe_secret_key')
                    ->label('Stripe Secret Key')
                    ->password()
                    ->visible(fn($get) => $get('enable_stripe'))
                    ->required(fn($get) => $get('enable_stripe'))
                    ->helperText('Beginnt mit sk_live_ oder sk_test_'),
                    
                // Test Connection Button
                Actions::make([
                    Action::make('test_stripe')
                        ->label('Verbindung testen')
                        ->icon('heroicon-o-bolt')
                        ->visible(fn($get) => $get('enable_stripe'))
                        ->action(fn() => $this->testStripeConnection())
                ]),
            ]),
            
        // German Tax Configuration
        Section::make('Deutsche Steuer-Einstellungen')
            ->description('Konfigurieren Sie Ihre Steuereinstellungen für Deutschland')
            ->visible(fn($get) => $get('enable_stripe'))
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('tax_number')
                        ->label('Steuernummer')
                        ->placeholder('12/345/67890')
                        ->helperText('Ihre deutsche Steuernummer'),
                        
                    TextInput::make('vat_id')
                        ->label('USt-IdNr.')
                        ->placeholder('DE123456789')
                        ->helperText('Für innergemeinschaftliche Lieferungen'),
                ]),
                
                // Kleinunternehmer Toggle
                Toggle::make('is_small_business')
                    ->label('Kleinunternehmerregelung (§19 UStG)')
                    ->default(true)
                    ->reactive()
                    ->helperText('Keine Umsatzsteuer bei Jahresumsatz < 22.000€')
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state) {
                            $set('tax_rates', [
                                ['name' => 'Kleinunternehmer', 'rate' => 0]
                            ]);
                        } else {
                            $set('tax_rates', [
                                ['name' => 'Standard', 'rate' => 19],
                                ['name' => 'Ermäßigt', 'rate' => 7]
                            ]);
                        }
                    }),
                    
                // Warning for Kleinunternehmer
                Placeholder::make('small_business_info')
                    ->content(new HtmlString('
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <strong>Hinweis:</strong> Als Kleinunternehmer dürfen Sie keine Umsatzsteuer ausweisen. 
                                Auf Ihren Rechnungen erscheint automatisch der Hinweis: 
                                "Gemäß § 19 UStG wird keine Umsatzsteuer berechnet."
                            </p>
                        </div>
                    '))
                    ->visible(fn($get) => $get('is_small_business')),
                    
                // Tax Rates Configuration
                Repeater::make('tax_rates')
                    ->label('Steuersätze')
                    ->visible(fn($get) => !$get('is_small_business'))
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('name')
                                ->label('Bezeichnung')
                                ->required(),
                                
                            TextInput::make('rate')
                                ->label('Satz (%)')
                                ->numeric()
                                ->suffix('%')
                                ->required(),
                                
                            Toggle::make('is_default')
                                ->label('Standard')
                                ->afterStateUpdated(function ($state, $set, $livewire, $component) {
                                    if ($state) {
                                        // Ensure only one default
                                        foreach ($component->getState() as $index => $item) {
                                            if ($index !== $component->getItemKey()) {
                                                $set("tax_rates.{$index}.is_default", false);
                                            }
                                        }
                                    }
                                }),
                        ])
                    ])
                    ->defaultItems(2)
                    ->minItems(1),
            ]),
            
        // Invoice Settings
        Section::make('Rechnungseinstellungen')
            ->description('Konfigurieren Sie Ihre Rechnungsnummern und -texte')
            ->visible(fn($get) => $get('enable_stripe'))
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('invoice_prefix')
                        ->label('Rechnungsnummer-Präfix')
                        ->default('RE')
                        ->helperText('z.B. RE-2024-00001'),
                        
                    TextInput::make('invoice_start_number')
                        ->label('Startnummer')
                        ->numeric()
                        ->default(1)
                        ->helperText('Erste Rechnungsnummer'),
                ]),
                
                Textarea::make('invoice_footer_text')
                    ->label('Rechnungs-Fußtext')
                    ->rows(3)
                    ->default(fn($get) => $get('is_small_business') 
                        ? 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.'
                        : 'Zahlbar innerhalb von 14 Tagen nach Rechnungsdatum.'),
                        
                Toggle::make('enable_datev_export')
                    ->label('DATEV-Export aktivieren')
                    ->helperText('Für Ihren Steuerberater'),
            ]),
    ];
}
```

## 🔄 WIZARD FLOW ANPASSUNGEN

### Step 7 (Review) erweitern:
```php
// In getReviewSummary() hinzufügen:
if ($this->data['enable_stripe'] ?? false) {
    $sections[] = [
        'title' => 'Zahlungen & Steuern',
        'items' => [
            'Stripe' => $this->data['stripe_connection_status'] ?? 'Nicht verbunden',
            'Steuerstatus' => $this->data['is_small_business'] 
                ? 'Kleinunternehmer (§19 UStG)' 
                : 'Regelbesteuerung',
            'Steuersätze' => count($this->data['tax_rates'] ?? []) . ' konfiguriert',
            'DATEV-Export' => $this->data['enable_datev_export'] ? '✅' : '❌',
        ]
    ];
}
```

### Health Check erweitern:
```php
// Neuer StripeHealthCheck
class StripeHealthCheck implements IntegrationHealthCheck 
{
    public function check(Company $company): HealthCheckResult
    {
        if (!$company->stripe_enabled) {
            return HealthCheckResult::healthy('Stripe nicht aktiviert');
        }
        
        try {
            \Stripe\Stripe::setApiKey($company->stripe_secret_key);
            $account = \Stripe\Account::retrieve();
            
            // Check tax configuration
            $taxIssues = [];
            if (!$company->is_small_business && !$company->tax_rates()->exists()) {
                $taxIssues[] = 'Keine Steuersätze konfiguriert';
            }
            
            if (!$company->tax_number) {
                $taxIssues[] = 'Steuernummer fehlt';
            }
            
            if (!empty($taxIssues)) {
                return HealthCheckResult::degraded(
                    'Stripe verbunden, Steuerkonfiguration unvollständig',
                    $taxIssues
                );
            }
            
            return HealthCheckResult::healthy(
                'Stripe verbunden und konfiguriert',
                ['account_id' => $account->id]
            );
            
        } catch (\Exception $e) {
            return HealthCheckResult::unhealthy(
                'Stripe-Verbindung fehlgeschlagen',
                ['error' => $e->getMessage()]
            );
        }
    }
}
```

## 📊 DATENBANK-ANPASSUNGEN

```sql
-- Erweitere companies Tabelle
ALTER TABLE companies ADD COLUMN stripe_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE companies ADD COLUMN stripe_publishable_key VARCHAR(255);
ALTER TABLE companies ADD COLUMN stripe_secret_key VARCHAR(255);
ALTER TABLE companies ADD COLUMN stripe_account_id VARCHAR(255);
ALTER TABLE companies ADD COLUMN tax_number VARCHAR(50);
ALTER TABLE companies ADD COLUMN vat_id VARCHAR(20);
ALTER TABLE companies ADD COLUMN is_small_business BOOLEAN DEFAULT TRUE;
ALTER TABLE companies ADD COLUMN small_business_threshold_date DATE;
ALTER TABLE companies ADD COLUMN invoice_prefix VARCHAR(10) DEFAULT 'RE';
ALTER TABLE companies ADD COLUMN invoice_counter INT DEFAULT 0;
ALTER TABLE companies ADD COLUMN enable_datev_export BOOLEAN DEFAULT FALSE;

-- Neue tax_rates Tabelle
CREATE TABLE tax_rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    rate DECIMAL(5,2) NOT NULL,
    stripe_tax_rate_id VARCHAR(255),
    is_default BOOLEAN DEFAULT FALSE,
    valid_from DATE,
    valid_until DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

## 🚀 IMPLEMENTIERUNGS-REIHENFOLGE

### Phase 1: Wizard Integration (4 Stunden)
1. ✅ Database Migrations
2. ✅ Wizard Step 8 hinzufügen
3. ✅ Stripe Connection Test
4. ✅ Health Check Integration

### Phase 2: Service Layer (8 Stunden)
1. ✅ TaxService (wie vom anderen Agenten geplant)
2. ✅ InvoiceComplianceService 
3. ✅ StripeInvoiceService Anpassungen
4. ✅ VatValidationService

### Phase 3: Testing & Rollout (4 Stunden)
1. ✅ Kleinunternehmer-Szenarien
2. ✅ Regelbesteuerung Tests
3. ✅ DATEV-Export Tests
4. ✅ Dokumentation

## 📝 EMPFEHLUNG AN DEN ANDEREN AGENTEN

**Bitte berücksichtige folgende Punkte:**

1. **Wizard Integration**: Der Quick Setup Wizard hat bereits 7 Steps. Step 8 "Zahlungen & Steuern" sollte optional sein (nur wenn Toggle `enable_stripe` aktiviert)

2. **Health Check System**: Nutze das bestehende `IntegrationHealthCheck` Interface für Stripe

3. **Existing Services**: 
   - `PromptTemplateService` - Könnte für Rechnungstexte genutzt werden
   - `HealthCheckService` - Für Stripe-Status
   - `DashboardMetricsService` - Für Umsatz-Tracking

4. **Database**: 
   - Nutze die bestehende `CompatibleMigration` Base Class
   - Beachte Multi-Tenancy mit `company_id`

5. **UI/UX**:
   - Filament Forms für Konfiguration
   - Ampel-System für Status-Anzeige
   - Industry-specific defaults (z.B. Fitness oft ermäßigter Steuersatz)

**Der Plan ist grundsätzlich SEHR GUT!** Die Integration in den Wizard macht es benutzerfreundlicher und die deutsche Steuer-Compliance ist durchdacht. 

**Zeitschätzung mit Wizard**: ~28 Stunden (4 zusätzlich für Wizard-Integration)