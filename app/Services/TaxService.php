<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TaxService
{
    // Steuersätze in Deutschland
    const TAX_RATE_STANDARD = 19.0;
    const TAX_RATE_REDUCED = 7.0;
    const TAX_RATE_ZERO = 0.0;
    
    // Kleinunternehmer Schwellenwerte nach §19 UStG
    const SMALL_BUSINESS_THRESHOLD_CURRENT = 22000; // Aktuelles Jahr
    const SMALL_BUSINESS_THRESHOLD_PREVIOUS = 50000; // Vorjahr
    
    /**
     * Ermittelt den anzuwendenden Steuersatz basierend auf Unternehmenskonfiguration
     */
    public function getDeterminedTaxRate(Company $company, ?string $taxType = 'standard'): array
    {
        // Kleinunternehmer zahlen keine Umsatzsteuer
        if ($company->is_small_business) {
            return [
                'rate' => 0.0,
                'rate_id' => $this->getOrCreateTaxRate($company, 'Kleinunternehmer', 0.0)->id,
                'name' => 'Kleinunternehmer',
                'note' => 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.',
                'is_tax_exempt' => true
            ];
        }

        // Standard Steuersätze abrufen
        $taxRate = DB::table('tax_rates')
            ->where('company_id', $company->id)
            ->where('is_default', true)
            ->where(function($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->where('valid_from', '<=', now())
            ->first();

        if (!$taxRate) {
            // Fallback auf Standard-Steuersatz
            $rate = $taxType === 'reduced' ? self::TAX_RATE_REDUCED : self::TAX_RATE_STANDARD;
            $taxRate = $this->getOrCreateTaxRate($company, 'Standard', $rate);
        }

        return [
            'rate' => $taxRate->rate,
            'rate_id' => $taxRate->id,
            'name' => $taxRate->name,
            'note' => null,
            'is_tax_exempt' => false
        ];
    }

    /**
     * Erstellt oder holt einen Steuersatz
     */
    public function getOrCreateTaxRate(Company $company, string $name, float $rate)
    {
        $taxRate = DB::table('tax_rates')
            ->where('company_id', $company->id)
            ->where('name', $name)
            ->where('rate', $rate)
            ->first();
            
        if (!$taxRate) {
            $id = DB::table('tax_rates')->insertGetId([
                'company_id' => $company->id,
                'name' => $name,
                'rate' => $rate,
                'is_default' => $name === 'Standard',
                'is_system' => false,
                'description' => $rate === 0 ? 'Kleinunternehmer - keine Umsatzsteuer' : null,
                'valid_from' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $taxRate = DB::table('tax_rates')->find($id);
        }
        
        return $taxRate;
    }

    /**
     * Überprüft und aktualisiert Kleinunternehmer-Status
     */
    public function checkSmallBusinessThreshold(Company $company): array
    {
        $currentYear = now()->year;
        $previousYear = $currentYear - 1;

        // Umsatz des aktuellen Jahres
        $currentRevenue = Invoice::where('company_id', $company->id)
            ->whereYear('invoice_date', $currentYear)
            ->where('status', Invoice::STATUS_PAID)
            ->sum('subtotal');

        // Umsatz des Vorjahres
        $previousRevenue = Invoice::where('company_id', $company->id)
            ->whereYear('invoice_date', $previousYear)
            ->where('status', Invoice::STATUS_PAID)
            ->sum('subtotal');

        // Monitoring-Eintrag aktualisieren
        DB::table('tax_threshold_monitoring')->updateOrInsert(
            [
                'company_id' => $company->id,
                'year' => $currentYear,
            ],
            [
                'annual_revenue' => $currentRevenue,
                'threshold_exceeded' => false,
                'updated_at' => now(),
            ]
        );

        $result = [
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'is_small_business' => $company->is_small_business,
            'threshold_status' => 'ok',
            'action_required' => false,
            'message' => null,
        ];

        // Prüfung ob Schwellenwerte überschritten wurden
        if ($company->is_small_business) {
            // Prüfung für Kleinunternehmer
            if ($previousRevenue > self::SMALL_BUSINESS_THRESHOLD_PREVIOUS) {
                $result['threshold_status'] = 'exceeded';
                $result['action_required'] = true;
                $result['message'] = 'Vorjahresumsatz überschreitet 50.000€ - Kleinunternehmerregelung entfällt!';
                
                // Automatisch Status ändern
                $company->update([
                    'is_small_business' => false,
                    'small_business_threshold_date' => now(),
                ]);
                
                $this->logThresholdExceeded($company, $previousRevenue, 'previous_year');
                
            } elseif ($currentRevenue > self::SMALL_BUSINESS_THRESHOLD_CURRENT) {
                $result['threshold_status'] = 'warning';
                $result['message'] = 'Umsatz überschreitet 22.000€ - Kleinunternehmerregelung entfällt nächstes Jahr!';
                
                $this->sendThresholdWarning($company, $currentRevenue);
            }
        } else {
            // Prüfung ob wieder Kleinunternehmer werden kann
            if ($previousRevenue <= self::SMALL_BUSINESS_THRESHOLD_CURRENT && 
                $currentRevenue <= self::SMALL_BUSINESS_THRESHOLD_CURRENT) {
                $result['message'] = 'Umsätze unter Kleinunternehmergrenze - Option zur Regelung möglich';
            }
        }

        return $result;
    }

    /**
     * Validiert eine USt-ID über VIES
     */
    public function validateVatId(string $vatId, string $countryCode = 'DE'): array
    {
        try {
            // VIES SOAP Service der EU
            $response = Http::asForm()->post('https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number', [
                'countryCode' => strtoupper($countryCode),
                'vatNumber' => str_replace($countryCode, '', strtoupper($vatId)),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'valid' => $data['valid'] ?? false,
                    'name' => $data['name'] ?? null,
                    'address' => $data['address'] ?? null,
                    'request_date' => now()->toDateTimeString(),
                    'error' => null,
                ];
            }

            return [
                'valid' => false,
                'error' => 'VIES Service nicht erreichbar',
            ];

        } catch (\Exception $e) {
            Log::error('VAT ID validation failed', [
                'vat_id' => $vatId,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'error' => 'Validierung fehlgeschlagen: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Prüft ob Reverse-Charge anzuwenden ist
     */
    public function shouldApplyReverseCharge(Company $company, ?string $customerVatId, string $customerCountry): bool
    {
        // Kein Reverse-Charge für Kleinunternehmer
        if ($company->is_small_business) {
            return false;
        }

        // Nur für B2B mit gültiger USt-ID
        if (empty($customerVatId)) {
            return false;
        }

        // Nur für EU-Länder außer Deutschland
        $euCountries = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'ES'];
        
        return $customerCountry !== 'DE' && in_array($customerCountry, $euCountries);
    }

    /**
     * Generiert Steuerhinweis für Rechnung
     */
    public function generateTaxNote(Company $company, Invoice $invoice): ?string
    {
        if ($company->is_small_business) {
            return 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.';
        }

        if ($invoice->is_reverse_charge) {
            return 'Steuerschuldnerschaft des Leistungsempfängers (Reverse Charge).';
        }

        if ($invoice->is_tax_exempt) {
            return 'Steuerfreie Leistung gemäß § 4 UStG.';
        }

        return null;
    }

    /**
     * Berechnet Steuern für eine Rechnung
     */
    public function calculateInvoiceTaxes(Invoice $invoice): array
    {
        $company = $invoice->company;
        $taxBreakdown = [];
        $totalTax = 0;
        $subtotal = 0;

        foreach ($invoice->items as $item) {
            $taxInfo = $this->getDeterminedTaxRate($company);
            
            if ($invoice->is_reverse_charge || $invoice->is_tax_exempt || $company->is_small_business) {
                $taxAmount = 0;
                $taxRate = 0;
            } else {
                $taxRate = $item->tax_rate_id 
                    ? DB::table('tax_rates')->find($item->tax_rate_id)->rate 
                    : $taxInfo['rate'];
                $taxAmount = $item->amount * ($taxRate / 100);
            }

            $item->update([
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'tax_rate_id' => $taxInfo['rate_id'] ?? null,
            ]);

            // Gruppiere nach Steuersatz
            $key = $taxRate . '%';
            if (!isset($taxBreakdown[$key])) {
                $taxBreakdown[$key] = [
                    'rate' => $taxRate,
                    'base_amount' => 0,
                    'tax_amount' => 0,
                ];
            }

            $taxBreakdown[$key]['base_amount'] += $item->amount;
            $taxBreakdown[$key]['tax_amount'] += $taxAmount;
            
            $subtotal += $item->amount;
            $totalTax += $taxAmount;
        }

        // Aktualisiere Rechnung
        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'total' => $subtotal + $totalTax,
            'tax_note' => $this->generateTaxNote($company, $invoice),
            'is_tax_exempt' => $company->is_small_business || $invoice->is_reverse_charge,
        ]);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'total' => $subtotal + $totalTax,
            'tax_breakdown' => $taxBreakdown,
        ];
    }

    /**
     * Loggt Schwellenwertüberschreitung
     */
    private function logThresholdExceeded(Company $company, float $revenue, string $type): void
    {
        Log::warning('Kleinunternehmer Schwellenwert überschritten', [
            'company_id' => $company->id,
            'revenue' => $revenue,
            'type' => $type,
            'threshold' => $type === 'previous_year' ? self::SMALL_BUSINESS_THRESHOLD_PREVIOUS : self::SMALL_BUSINESS_THRESHOLD_CURRENT,
        ]);

        DB::table('tax_threshold_monitoring')
            ->where('company_id', $company->id)
            ->where('year', now()->year)
            ->update([
                'threshold_exceeded' => true,
                'notification_sent_at' => now(),
            ]);
    }

    /**
     * Sendet Warnung bei Annäherung an Schwellenwert
     */
    private function sendThresholdWarning(Company $company, float $currentRevenue): void
    {
        $percentage = ($currentRevenue / self::SMALL_BUSINESS_THRESHOLD_CURRENT) * 100;
        
        if ($percentage >= 80) {
            // TODO: Email-Benachrichtigung implementieren
            Log::info('Kleinunternehmer nähert sich Schwellenwert', [
                'company_id' => $company->id,
                'current_revenue' => $currentRevenue,
                'percentage' => $percentage,
            ]);
        }
    }

    /**
     * Erstellt Stripe Tax Rate
     */
    public function syncStripeEUtaxRate(Company $company, float $rate, string $name): ?string
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            
            $stripeTaxRate = $stripe->taxRates->create([
                'display_name' => $name,
                'inclusive' => false,
                'percentage' => $rate,
                'country' => 'DE',
                'description' => "Tax rate for {$company->name}",
                'metadata' => [
                    'company_id' => $company->id,
                    'created_by' => 'askproai',
                ],
            ]);

            return $stripeTaxRate->id;

        } catch (\Exception $e) {
            Log::error('Failed to create Stripe tax rate', [
                'company_id' => $company->id,
                'rate' => $rate,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }
}