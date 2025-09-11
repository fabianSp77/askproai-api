<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\BalanceTopup;
use App\Models\PricingPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\Exception\ApiConnectionException;

class BillingHealthCheck extends Command
{
    protected $signature = 'billing:health-check
                            {--email : E-Mail-Benachrichtigung senden}
                            {--slack : Slack-Benachrichtigung senden}
                            {--verbose : Detaillierte Ausgabe}';

    protected $description = 'Überprüft die Gesundheit des Abrechnungssystems';

    private array $issues = [];
    private array $warnings = [];
    private array $metrics = [];

    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info(' Abrechnungssystem Health Check');
        $this->info(' ' . now()->format('d.m.Y H:i:s'));
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Führe alle Checks durch
        $this->checkDatabaseIntegrity();
        $this->checkBalanceSync();
        $this->checkTransactionIntegrity();
        $this->checkStripeConnection();
        $this->checkLowBalances();
        $this->checkResellerPayouts();
        $this->checkSystemPerformance();
        $this->checkAnomalies();

        // Zeige Zusammenfassung
        $this->displaySummary();

        // Sende Benachrichtigungen bei Problemen
        if (count($this->issues) > 0) {
            $this->sendAlerts();
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Überprüft Datenbank-Integrität
     */
    private function checkDatabaseIntegrity()
    {
        $this->task('Überprüfe Datenbank-Integrität', function () {
            try {
                // Prüfe kritische Tabellen
                $tables = [
                    'tenants' => 'Mandanten',
                    'transactions' => 'Transaktionen',
                    'balance_topups' => 'Aufladungen',
                    'pricing_plans' => 'Preismodelle',
                    'commission_ledger' => 'Provisionen',
                ];

                foreach ($tables as $table => $name) {
                    if (!DB::getSchemaBuilder()->hasTable($table)) {
                        $this->issues[] = "Kritische Tabelle fehlt: {$table} ({$name})";
                        return false;
                    }
                }

                // Prüfe Foreign Key Constraints
                $constraints = DB::select("
                    SELECT 
                        TABLE_NAME,
                        CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME IN ('transactions', 'balance_topups', 'commission_ledger')
                ");

                if (count($constraints) < 3) {
                    $this->warnings[] = "Fehlende Foreign Key Constraints erkannt";
                }

                $this->metrics['database_tables'] = count($tables);
                $this->metrics['foreign_keys'] = count($constraints);

                return true;
            } catch (\Exception $e) {
                $this->issues[] = "Datenbank-Fehler: " . $e->getMessage();
                return false;
            }
        });
    }

    /**
     * Überprüft Guthaben-Synchronisation
     */
    private function checkBalanceSync()
    {
        $this->task('Überprüfe Guthaben-Synchronisation', function () {
            $mismatches = 0;

            // Prüfe alle Mandanten
            $tenants = Tenant::all();
            
            foreach ($tenants as $tenant) {
                // Berechne Guthaben aus Transaktionen
                $calculatedBalance = Transaction::where('tenant_id', $tenant->id)
                    ->sum('amount_cents');

                // Vergleiche mit gespeichertem Guthaben
                if (abs($tenant->balance_cents - $calculatedBalance) > 1) {
                    $this->issues[] = sprintf(
                        "Guthaben-Abweichung bei %s: Gespeichert=%s, Berechnet=%s",
                        $tenant->name,
                        number_format($tenant->balance_cents / 100, 2) . '€',
                        number_format($calculatedBalance / 100, 2) . '€'
                    );
                    $mismatches++;
                }
            }

            $this->metrics['tenants_checked'] = $tenants->count();
            $this->metrics['balance_mismatches'] = $mismatches;

            return $mismatches === 0;
        });
    }

    /**
     * Überprüft Transaktions-Integrität
     */
    private function checkTransactionIntegrity()
    {
        $this->task('Überprüfe Transaktions-Integrität', function () {
            // Prüfe auf unterbrochene Transaktionsketten
            $orphanedTransactions = Transaction::whereNotNull('parent_transaction_id')
                ->whereDoesntHave('parentTransaction')
                ->count();

            if ($orphanedTransactions > 0) {
                $this->issues[] = "Verwaiste Transaktionen gefunden: {$orphanedTransactions}";
            }

            // Prüfe auf negative Endguthaben
            $negativeBalances = Transaction::where('balance_after_cents', '<', 0)->count();
            
            if ($negativeBalances > 0) {
                $this->warnings[] = "Transaktionen mit negativem Endguthaben: {$negativeBalances}";
            }

            // Prüfe heutige Transaktionen
            $todayTransactions = Transaction::whereDate('created_at', Carbon::today())->count();
            $yesterdayTransactions = Transaction::whereDate('created_at', Carbon::yesterday())->count();

            if ($yesterdayTransactions > 0 && $todayTransactions < ($yesterdayTransactions * 0.1)) {
                $this->warnings[] = "Ungewöhnlich niedrige Transaktionsanzahl heute";
            }

            $this->metrics['orphaned_transactions'] = $orphanedTransactions;
            $this->metrics['negative_balances'] = $negativeBalances;
            $this->metrics['transactions_today'] = $todayTransactions;

            return $orphanedTransactions === 0;
        });
    }

    /**
     * Überprüft Stripe-Verbindung
     */
    private function checkStripeConnection()
    {
        $this->task('Überprüfe Stripe-Verbindung', function () {
            if (!config('billing.stripe.secret')) {
                $this->issues[] = "Stripe API-Schlüssel nicht konfiguriert";
                return false;
            }

            try {
                Stripe::setApiKey(config('billing.stripe.secret'));
                
                // Teste Verbindung mit Balance-Abfrage
                $balance = \Stripe\Balance::retrieve();
                
                $this->metrics['stripe_balance_eur'] = ($balance->available[0]->amount ?? 0) / 100;
                $this->metrics['stripe_pending_eur'] = ($balance->pending[0]->amount ?? 0) / 100;

                return true;
            } catch (ApiConnectionException $e) {
                $this->issues[] = "Stripe-Verbindung fehlgeschlagen: " . $e->getMessage();
                return false;
            } catch (\Exception $e) {
                $this->warnings[] = "Stripe-Fehler: " . $e->getMessage();
                return false;
            }
        });
    }

    /**
     * Überprüft niedrige Guthaben
     */
    private function checkLowBalances()
    {
        $this->task('Überprüfe niedrige Guthaben', function () {
            $criticalBalance = config('billing.pricing.minimums.balance_critical', 500);
            $warningBalance = config('billing.pricing.minimums.balance_warning', 1000);

            $criticalTenants = Tenant::where('balance_cents', '<', $criticalBalance)
                ->where('is_active', true)
                ->get();

            $warningTenants = Tenant::whereBetween('balance_cents', [$criticalBalance, $warningBalance])
                ->where('is_active', true)
                ->get();

            foreach ($criticalTenants as $tenant) {
                $this->issues[] = sprintf(
                    "KRITISCH: %s hat nur noch %s Guthaben",
                    $tenant->name,
                    number_format($tenant->balance_cents / 100, 2) . '€'
                );
            }

            foreach ($warningTenants as $tenant) {
                $this->warnings[] = sprintf(
                    "Niedriges Guthaben: %s (%s)",
                    $tenant->name,
                    number_format($tenant->balance_cents / 100, 2) . '€'
                );
            }

            $this->metrics['critical_balances'] = $criticalTenants->count();
            $this->metrics['warning_balances'] = $warningTenants->count();

            return true;
        });
    }

    /**
     * Überprüft fällige Reseller-Auszahlungen
     */
    private function checkResellerPayouts()
    {
        $this->task('Überprüfe Reseller-Auszahlungen', function () {
            // Prüfe anstehende Provisionen
            $pendingCommissions = DB::table('commission_ledger')
                ->where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays(30))
                ->sum('amount_cents');

            if ($pendingCommissions > 10000) { // Mehr als 100€ ausstehend
                $this->warnings[] = sprintf(
                    "Ausstehende Provisionen: %s",
                    number_format($pendingCommissions / 100, 2) . '€'
                );
            }

            // Prüfe überfällige Auszahlungen
            $overduePayouts = DB::table('reseller_payouts')
                ->where('status', 'pending')
                ->where('scheduled_date', '<', Carbon::now())
                ->count();

            if ($overduePayouts > 0) {
                $this->issues[] = "Überfällige Auszahlungen: {$overduePayouts}";
            }

            $this->metrics['pending_commissions_eur'] = $pendingCommissions / 100;
            $this->metrics['overdue_payouts'] = $overduePayouts;

            return $overduePayouts === 0;
        });
    }

    /**
     * Überprüft System-Performance
     */
    private function checkSystemPerformance()
    {
        $this->task('Überprüfe System-Performance', function () {
            // Prüfe durchschnittliche Transaktionszeit
            $avgProcessingTime = DB::table('transactions')
                ->whereDate('created_at', Carbon::today())
                ->selectRaw('AVG(TIMESTAMPDIFF(MICROSECOND, created_at, updated_at)) as avg_time')
                ->first();

            $avgTimeMs = ($avgProcessingTime->avg_time ?? 0) / 1000;

            if ($avgTimeMs > 1000) { // Mehr als 1 Sekunde
                $this->warnings[] = sprintf(
                    "Langsame Transaktionsverarbeitung: %.2fms",
                    $avgTimeMs
                );
            }

            // Prüfe Webhook-Fehlerrate
            $webhookFailures = DB::table('jobs_failed')
                ->where('queue', 'webhooks')
                ->whereDate('failed_at', Carbon::today())
                ->count();

            if ($webhookFailures > config('billing.monitoring.webhook_failure_threshold', 3)) {
                $this->issues[] = "Hohe Webhook-Fehlerrate: {$webhookFailures} Fehler heute";
            }

            $this->metrics['avg_processing_ms'] = $avgTimeMs;
            $this->metrics['webhook_failures_today'] = $webhookFailures;

            return true;
        });
    }

    /**
     * Überprüft auf Anomalien
     */
    private function checkAnomalies()
    {
        $this->task('Überprüfe Anomalien', function () {
            // Prüfe auf ungewöhnlich hohe Einzeltransaktionen
            $highValueTransactions = Transaction::where('amount_cents', '>', 50000) // Über 500€
                ->whereDate('created_at', Carbon::today())
                ->count();

            if ($highValueTransactions > 0) {
                $this->warnings[] = "Ungewöhnlich hohe Transaktionen: {$highValueTransactions}";
            }

            // Prüfe auf verdächtige Aktivitätsmuster
            $suspiciousPatterns = DB::table('transactions')
                ->select('tenant_id', DB::raw('COUNT(*) as count'))
                ->whereDate('created_at', Carbon::today())
                ->groupBy('tenant_id')
                ->having('count', '>', 1000) // Mehr als 1000 Transaktionen pro Tag
                ->count();

            if ($suspiciousPatterns > 0) {
                $this->warnings[] = "Verdächtige Aktivitätsmuster bei {$suspiciousPatterns} Mandanten";
            }

            $this->metrics['high_value_transactions'] = $highValueTransactions;
            $this->metrics['suspicious_patterns'] = $suspiciousPatterns;

            return true;
        });
    }

    /**
     * Zeigt Zusammenfassung
     */
    private function displaySummary()
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info(' ZUSAMMENFASSUNG');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Status
        if (count($this->issues) === 0 && count($this->warnings) === 0) {
            $this->info('✅ System-Status: GESUND');
        } elseif (count($this->issues) === 0) {
            $this->warn('⚠️  System-Status: WARNUNGEN ({count($this->warnings)})');
        } else {
            $this->error('❌ System-Status: KRITISCH ({count($this->issues)} Probleme)');
        }

        // Kritische Probleme
        if (count($this->issues) > 0) {
            $this->newLine();
            $this->error('Kritische Probleme:');
            foreach ($this->issues as $issue) {
                $this->error(" • {$issue}");
            }
        }

        // Warnungen
        if (count($this->warnings) > 0) {
            $this->newLine();
            $this->warn('Warnungen:');
            foreach ($this->warnings as $warning) {
                $this->warn(" • {$warning}");
            }
        }

        // Metriken
        if ($this->option('verbose') && count($this->metrics) > 0) {
            $this->newLine();
            $this->info('Metriken:');
            $this->table(
                ['Metrik', 'Wert'],
                collect($this->metrics)->map(function ($value, $key) {
                    return [
                        str_replace('_', ' ', ucfirst($key)),
                        is_numeric($value) ? number_format($value, 2) : $value
                    ];
                })->toArray()
            );
        }

        $this->newLine();
        $this->info('Health Check abgeschlossen: ' . now()->format('H:i:s'));
    }

    /**
     * Sendet Benachrichtigungen bei Problemen
     */
    private function sendAlerts()
    {
        if ($this->option('email')) {
            // E-Mail-Benachrichtigung implementieren
            Log::critical('Billing Health Check Fehler', [
                'issues' => $this->issues,
                'warnings' => $this->warnings,
                'metrics' => $this->metrics
            ]);
        }

        if ($this->option('slack')) {
            // Slack-Benachrichtigung implementieren
            Log::channel('slack')->critical('Billing System kritisch!', [
                'issues' => $this->issues
            ]);
        }
    }
}
