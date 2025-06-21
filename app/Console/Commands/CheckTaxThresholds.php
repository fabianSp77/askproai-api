<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\TaxService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckTaxThresholds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tax:check-thresholds {--company=all : Company ID or "all" for all companies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Überprüft Kleinunternehmer-Schwellenwerte und sendet Warnungen';

    protected TaxService $taxService;

    public function __construct(TaxService $taxService)
    {
        parent::__construct();
        $this->taxService = $taxService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyOption = $this->option('company');
        
        if ($companyOption === 'all') {
            $companies = Company::where('is_active', true)->get();
            $this->info("Prüfe Schwellenwerte für {$companies->count()} Unternehmen...");
        } else {
            $company = Company::find($companyOption);
            if (!$company) {
                $this->error("Unternehmen mit ID {$companyOption} nicht gefunden.");
                return 1;
            }
            $companies = collect([$company]);
        }

        $warnings = 0;
        $exceeded = 0;

        foreach ($companies as $company) {
            $this->info("\nPrüfe {$company->name} (ID: {$company->id})...");
            
            $result = $this->taxService->checkSmallBusinessThreshold($company);
            
            $this->info("  Aktueller Jahresumsatz: " . number_format($result['current_revenue'], 2, ',', '.') . ' €');
            $this->info("  Vorjahresumsatz: " . number_format($result['previous_revenue'], 2, ',', '.') . ' €');
            $this->info("  Kleinunternehmer-Status: " . ($result['is_small_business'] ? 'Ja' : 'Nein'));
            
            if ($result['threshold_status'] === 'exceeded') {
                $this->error("  ⚠️  " . $result['message']);
                $exceeded++;
                
                // Sende E-Mail Benachrichtigung
                $this->sendThresholdExceededNotification($company, $result);
                
            } elseif ($result['threshold_status'] === 'warning') {
                $this->warn("  ⚠️  " . $result['message']);
                $warnings++;
                
                // Sende Warn-E-Mail
                $this->sendThresholdWarningNotification($company, $result);
                
            } elseif ($result['message']) {
                $this->comment("  ℹ️  " . $result['message']);
            } else {
                $this->info("  ✓ Schwellenwerte OK");
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Zusammenfassung:");
        $this->info("  Geprüfte Unternehmen: {$companies->count()}");
        $this->info("  Warnungen: {$warnings}");
        $this->info("  Überschreitungen: {$exceeded}");

        Log::info('Kleinunternehmer-Schwellenwerte geprüft', [
            'companies_checked' => $companies->count(),
            'warnings' => $warnings,
            'exceeded' => $exceeded,
        ]);

        return 0;
    }

    /**
     * Sendet E-Mail bei Schwellenwertüberschreitung
     */
    protected function sendThresholdExceededNotification(Company $company, array $result): void
    {
        if (!$company->email) {
            $this->warn("    Keine E-Mail-Adresse für Benachrichtigung hinterlegt.");
            return;
        }

        try {
            Mail::send('emails.tax-threshold-exceeded', [
                'company' => $company,
                'current_revenue' => $result['current_revenue'],
                'previous_revenue' => $result['previous_revenue'],
            ], function ($message) use ($company) {
                $message->to($company->email)
                    ->subject('Wichtig: Kleinunternehmerregelung entfällt - ' . $company->name);
            });

            $this->info("    E-Mail-Benachrichtigung gesendet an: {$company->email}");
        } catch (\Exception $e) {
            $this->error("    Fehler beim E-Mail-Versand: " . $e->getMessage());
            Log::error('Failed to send tax threshold notification', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sendet Warn-E-Mail bei Annäherung an Schwellenwert
     */
    protected function sendThresholdWarningNotification(Company $company, array $result): void
    {
        if (!$company->email) {
            return;
        }

        $percentage = ($result['current_revenue'] / 22000) * 100;

        // Nur bei 80% und 90% warnen
        if ($percentage < 80 || $percentage >= 100) {
            return;
        }

        try {
            Mail::send('emails.tax-threshold-warning', [
                'company' => $company,
                'current_revenue' => $result['current_revenue'],
                'percentage' => $percentage,
                'remaining' => 22000 - $result['current_revenue'],
            ], function ($message) use ($company) {
                $message->to($company->email)
                    ->subject('Warnung: Annäherung an Kleinunternehmergrenze - ' . $company->name);
            });

            $this->info("    Warn-E-Mail gesendet an: {$company->email}");
        } catch (\Exception $e) {
            Log::error('Failed to send tax threshold warning', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}