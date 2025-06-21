# 🚀 Stripe Integration Plan für AskProAI
**Stand**: 2025-06-19  
**Status**: In Planung  
**Erstellt von**: Claude mit 3 Sub-Agenten Analyse

## 📋 Zusammenfassung der Anforderungen

### Geschäftliche Anforderungen
- **Zielmarkt**: Deutsche Service-Unternehmen (Ärzte, Salons, Handwerker)
- **Geschäftsmodell**: AI-Telefonie (Retell.ai) + Kalenderbuchung (Cal.com)
- **Besonderheit**: Kleinunternehmerregelung (§19 UStG) - keine Umsatzsteuer

### Technische Anforderungen
1. **Flexible Steuereinstellungen**
   - Kleinunternehmer: 0% MwSt mit Pflichthinweis
   - Regulär: 7% oder 19% MwSt
   - Company-level Konfiguration

2. **Manuelle Rechnungsbearbeitung**
   - Zeitraum-basierte Abrechnung (von-bis)
   - Individuelle Preise pro Zeitraum
   - Zusätzliche Positionen hinzufügen
   - Nachträgliche Bearbeitung

3. **Proberechnungen**
   - Rechnungsvorschau vor Erstellung
   - "Was-wäre-wenn" Szenarien
   - Kostenvoranschläge

## 🏗️ Aktuelle Systemanalyse

### Vorhandene Komponenten
- ✅ Stripe SDK installiert (`stripe/stripe-php: ^14.0`)
- ✅ Basis-Models (Invoice, Payment, InvoiceItem)
- ✅ StripeInvoiceService mit Grundfunktionen
- ✅ Webhook-Infrastructure
- ✅ Pricing-Tabellen (company_pricing, billing_periods)

### Fehlende Komponenten
- ❌ Kleinunternehmer-Support
- ❌ Flexible Steuerberechnung
- ❌ Rechnungs-Editor UI
- ❌ Proberechnung-Feature
- ❌ Subscription Management
- ❌ Customer Portal Integration

## 🎯 Implementierungsplan

### Phase 1: Datenbank-Erweiterungen
```sql
-- Companies Tabelle erweitern
ALTER TABLE companies ADD COLUMN is_small_business BOOLEAN DEFAULT FALSE;
ALTER TABLE companies ADD COLUMN tax_id VARCHAR(255);
ALTER TABLE companies ADD COLUMN invoice_prefix VARCHAR(10);
ALTER TABLE companies ADD COLUMN next_invoice_number INT DEFAULT 1;

-- Flexible Steuer-Tabelle
CREATE TABLE tax_rates (
    id BIGINT PRIMARY KEY,
    company_id BIGINT,
    name VARCHAR(255),
    rate DECIMAL(5,2),
    is_default BOOLEAN DEFAULT FALSE,
    description TEXT,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

-- Manuelle Rechnungspositionen
CREATE TABLE manual_invoice_items (
    id BIGINT PRIMARY KEY,
    invoice_id BIGINT,
    description TEXT,
    quantity DECIMAL(10,2),
    unit_price DECIMAL(10,2),
    tax_rate_id BIGINT,
    period_start DATE,
    period_end DATE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    FOREIGN KEY (tax_rate_id) REFERENCES tax_rates(id)
);
```

### Phase 2: Service Layer

#### 2.1 EnhancedStripeInvoiceService
```php
class EnhancedStripeInvoiceService extends StripeInvoiceService
{
    public function createDraftInvoice(Company $company, array $options = []): Invoice
    {
        // Erstelle Entwurf ohne Stripe-Finalisierung
        // Ermöglicht manuelle Bearbeitung
    }
    
    public function previewInvoice(Company $company, array $items): array
    {
        // Berechne Vorschau ohne zu speichern
        // Retourniert Summen und Positionen
    }
    
    public function applyTaxRate(Invoice $invoice): void
    {
        if ($invoice->company->is_small_business) {
            // Keine MwSt, aber Hinweis hinzufügen
            $invoice->tax_note = "Umsatzsteuerbefreit nach §19 UStG";
            $invoice->tax_rate = 0;
        } else {
            // Normale Steuerberechnung
        }
    }
}
```

### Phase 3: Filament Admin UI

#### 3.1 InvoiceResource mit Editor
```php
class InvoiceResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Rechnungsdetails')
                ->schema([
                    Select::make('company_id')
                        ->relationship('company', 'name')
                        ->required(),
                    DatePicker::make('invoice_date'),
                    Toggle::make('is_draft')
                        ->label('Als Entwurf speichern'),
                ]),
            
            Section::make('Positionen')
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->schema([
                            TextInput::make('description'),
                            TextInput::make('quantity'),
                            TextInput::make('unit_price'),
                            DatePicker::make('period_start'),
                            DatePicker::make('period_end'),
                            Select::make('tax_rate_id')
                                ->relationship('taxRate', 'name'),
                        ])
                        ->addActionLabel('Position hinzufügen'),
                ]),
            
            Section::make('Vorschau')
                ->schema([
                    Placeholder::make('preview')
                        ->content(fn ($record) => view('invoices.preview', [
                            'invoice' => $record
                        ])),
                ]),
        ]);
    }
}
```

### Phase 4: Stripe Integration Features

#### 4.1 Subscription Management
- Monatliche/Jährliche Abos
- Upgrade/Downgrade Logic
- Proration bei Planwechsel
- Trial Periods

#### 4.2 Payment Methods
- SEPA Lastschrift (wichtig für DE)
- Kreditkarte
- PayPal Integration
- Rechnung mit Zahlungsziel

#### 4.3 Customer Portal
- Self-Service für Kunden
- Rechnungen einsehen/downloaden
- Zahlungsmethoden verwalten
- Abo-Verwaltung

## 📊 Preismodelle (Vorschlag)

### Starter (49€/Monat)
- 100 Inklusiv-Minuten
- 1 Standort
- Basis-Support
- 0,29€ pro zusätzliche Minute

### Professional (149€/Monat)
- 500 Inklusiv-Minuten
- 3 Standorte
- Priority Support
- 0,19€ pro zusätzliche Minute
- API Zugang

### Enterprise (individuell)
- Unbegrenzte Minuten
- Unbegrenzte Standorte
- Dedicated Support
- Custom Integrationen
- SLA

## 🔧 Technische Spezifikation

### API Endpoints
```
POST   /api/invoices/preview      - Rechnungsvorschau
POST   /api/invoices/draft        - Entwurf erstellen
PUT    /api/invoices/{id}/items   - Positionen bearbeiten
POST   /api/invoices/{id}/finalize - Rechnung finalisieren
GET    /api/tax-settings          - Steuereinstellungen
POST   /api/subscriptions/change  - Abo ändern
```

### Webhook Events
```
invoice.created
invoice.finalized  
invoice.paid
invoice.payment_failed
subscription.created
subscription.updated
subscription.cancelled
```

## 🚦 Nächste Schritte

1. **Sofort**: Diesen Plan mit Team besprechen
2. **Diese Woche**: Datenbank-Migrations erstellen
3. **Nächste Woche**: Service Layer implementieren
4. **In 2 Wochen**: UI Components bauen
5. **In 3 Wochen**: Testing & Go-Live

## 📝 Offene Fragen

1. Sollen Kunden ihre Rechnungen selbst bearbeiten können?
2. Welche Zahlungsziele sind gewünscht? (14, 30, 60 Tage?)
3. Automatische Mahnungen gewünscht?
4. Integration mit Buchhaltungssoftware (DATEV, lexoffice)?
5. Gutschriften/Stornierungen Workflow?

---

**Hinweis**: Dieser Plan wird kontinuierlich erweitert. Die Sub-Agenten Analyse folgt im nächsten Schritt.