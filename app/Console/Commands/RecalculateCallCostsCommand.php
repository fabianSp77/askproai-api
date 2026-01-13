<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use App\Services\CostCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RecalculateCallCostsCommand extends Command
{
    protected $signature = 'billing:recalculate-calls
        {--company= : Nur für eine bestimmte Company (ID)}
        {--from= : Ab Datum (YYYY-MM-DD)}
        {--until= : Bis Datum (YYYY-MM-DD)}
        {--dry-run : Nur Vorschau, keine Änderungen}
        {--force : Keine Bestätigung erforderlich}
        {--billing-mode=per_second : Billing-Modus für Neuberechnung (per_second oder per_minute)}';

    protected $description = 'Berechnet Call-Kosten neu (z.B. für Umstellung auf Per-Second Billing)';

    private CostCalculator $costCalculator;
    private int $processedCount = 0;
    private int $changedCount = 0;
    private int $totalSavings = 0;
    private int $totalIncrease = 0;

    public function __construct(CostCalculator $costCalculator)
    {
        parent::__construct();
        $this->costCalculator = $costCalculator;
    }

    public function handle(): int
    {
        $companyId = $this->option('company');
        $fromDate = $this->option('from');
        $untilDate = $this->option('until');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $billingMode = $this->option('billing-mode');

        // Validate billing mode
        if (!in_array($billingMode, [CompanyFeeSchedule::BILLING_MODE_PER_SECOND, CompanyFeeSchedule::BILLING_MODE_PER_MINUTE])) {
            $this->error("Ungültiger Billing-Modus: {$billingMode}");
            return Command::FAILURE;
        }

        // Build query
        $query = Call::query()
            ->whereNotNull('duration_sec')
            ->where('duration_sec', '>', 0);

        if ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company mit ID {$companyId} nicht gefunden.");
                return Command::FAILURE;
            }
            $query->where('company_id', $companyId);
            $this->info("Filtere nach Company: {$company->name} (ID: {$companyId})");
        }

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
            $this->info("Filtere ab Datum: {$fromDate}");
        }

        if ($untilDate) {
            $query->where('created_at', '<=', $untilDate . ' 23:59:59');
            $this->info("Filtere bis Datum: {$untilDate}");
        }

        $totalCalls = $query->count();

        if ($totalCalls === 0) {
            $this->warn('Keine Calls gefunden, die den Filterkriterien entsprechen.');
            return Command::SUCCESS;
        }

        $this->info("Gefundene Calls: {$totalCalls}");
        $this->info("Billing-Modus: {$billingMode}");

        if ($dryRun) {
            $this->warn('DRY-RUN Modus: Keine Änderungen werden gespeichert.');
        }

        if (!$force && !$dryRun) {
            if (!$this->confirm("Möchten Sie {$totalCalls} Calls neu berechnen?")) {
                $this->info('Abgebrochen.');
                return Command::SUCCESS;
            }
        }

        $this->output->newLine();
        $progressBar = $this->output->createProgressBar($totalCalls);
        $progressBar->start();

        // Process in chunks
        $query->chunk(100, function ($calls) use ($dryRun, $billingMode, $progressBar) {
            foreach ($calls as $call) {
                $this->processCall($call, $dryRun, $billingMode);
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->output->newLine(2);

        // Summary
        $this->displaySummary($dryRun);

        return Command::SUCCESS;
    }

    private function processCall(Call $call, bool $dryRun, string $billingMode): void
    {
        $this->processedCount++;

        $oldCustomerCost = $call->customer_cost ?? 0;
        $oldResellerCost = $call->reseller_cost ?? 0;
        $oldCostBreakdown = $call->cost_breakdown ?? [];

        // Calculate new costs using the specified billing mode
        $newCosts = $this->calculateNewCosts($call, $billingMode);

        $newCustomerCost = $newCosts['customer_cost'];
        $newResellerCost = $newCosts['reseller_cost'];

        $customerDiff = $newCustomerCost - $oldCustomerCost;
        $resellerDiff = $newResellerCost - $oldResellerCost;

        // Track changes
        if ($customerDiff !== 0 || $resellerDiff !== 0) {
            $this->changedCount++;

            if ($customerDiff < 0) {
                $this->totalSavings += abs($customerDiff);
            } else {
                $this->totalIncrease += $customerDiff;
            }

            // Verbose output for significant changes
            if ($this->output->isVerbose() && abs($customerDiff) >= 5) {
                $this->line(sprintf(
                    "\n  Call #%d: %ds → Alt: %d ct, Neu: %d ct (Diff: %+d ct)",
                    $call->id,
                    $call->duration_sec,
                    $oldCustomerCost,
                    $newCustomerCost,
                    $customerDiff
                ));
            }

            if (!$dryRun) {
                $this->updateCall($call, $newCosts, $oldCustomerCost, $oldResellerCost, $oldCostBreakdown, $billingMode);
            }
        }
    }

    private function calculateNewCosts(Call $call, string $billingMode): array
    {
        $company = $call->company;
        if (!$company) {
            return [
                'customer_cost' => $call->customer_cost ?? 0,
                'reseller_cost' => $call->reseller_cost ?? 0,
            ];
        }

        // Get pricing plan
        $pricingPlan = $company->pricingPlan;
        $perMinuteRate = $pricingPlan?->price_per_minute ?? 0.12;

        // Check for company-specific override
        $feeSchedule = $company->feeSchedule;
        if ($feeSchedule && $feeSchedule->override_per_minute_rate) {
            $perMinuteRate = $feeSchedule->override_per_minute_rate;
        }

        // Calculate based on billing mode
        if ($billingMode === CompanyFeeSchedule::BILLING_MODE_PER_SECOND) {
            // Per-second: exact calculation
            $minutes = $call->duration_sec / 60;
        } else {
            // Per-minute: ceiling
            $minutes = ceil($call->duration_sec / 60);
        }

        $customerCost = (int) round($perMinuteRate * $minutes * 100);

        // Apply discount if applicable
        $discountPercentage = $feeSchedule?->override_discount_percentage
            ?? $pricingPlan?->discount_percentage
            ?? 0;

        if ($discountPercentage > 0) {
            $discount = (int) round($customerCost * ($discountPercentage / 100));
            $customerCost -= $discount;
        }

        // Calculate reseller cost (if applicable)
        $resellerCost = 0;
        $resellerCompany = $company->resellerCompany;
        if ($resellerCompany) {
            $resellerPlan = $resellerCompany->pricingPlan;
            $resellerRate = $resellerPlan?->price_per_minute ?? $perMinuteRate;

            if ($billingMode === CompanyFeeSchedule::BILLING_MODE_PER_SECOND) {
                $resellerCost = (int) round($resellerRate * ($call->duration_sec / 60) * 100);
            } else {
                $resellerCost = (int) round($resellerRate * ceil($call->duration_sec / 60) * 100);
            }
        }

        return [
            'customer_cost' => $customerCost,
            'reseller_cost' => $resellerCost,
        ];
    }

    private function updateCall(
        Call $call,
        array $newCosts,
        int $oldCustomerCost,
        int $oldResellerCost,
        array $oldCostBreakdown,
        string $billingMode
    ): void {
        $costBreakdown = $call->cost_breakdown ?? [];

        // Add recalculation history
        $history = $costBreakdown['recalculation_history'] ?? [];
        $history[] = [
            'timestamp' => now()->toIso8601String(),
            'old_customer_cost' => $oldCustomerCost,
            'old_reseller_cost' => $oldResellerCost,
            'new_customer_cost' => $newCosts['customer_cost'],
            'new_reseller_cost' => $newCosts['reseller_cost'],
            'billing_mode' => $billingMode,
            'reason' => 'billing:recalculate-calls command',
        ];

        $costBreakdown['recalculation_history'] = $history;
        $costBreakdown['last_recalculated_at'] = now()->toIso8601String();
        $costBreakdown['billing_mode'] = $billingMode;

        try {
            DB::transaction(function () use ($call, $newCosts, $costBreakdown) {
                $call->update([
                    'customer_cost' => $newCosts['customer_cost'],
                    'reseller_cost' => $newCosts['reseller_cost'],
                    'cost_breakdown' => $costBreakdown,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to update call costs', [
                'call_id' => $call->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function displaySummary(bool $dryRun): void
    {
        $this->info('═══════════════════════════════════════════════════');
        $this->info('               ZUSAMMENFASSUNG');
        $this->info('═══════════════════════════════════════════════════');

        $this->table(
            ['Metrik', 'Wert'],
            [
                ['Verarbeitete Calls', number_format($this->processedCount)],
                ['Geänderte Calls', number_format($this->changedCount)],
                ['Gesamtersparnis (Kunden)', $this->formatCents($this->totalSavings)],
                ['Gesamterhöhung (Kunden)', $this->formatCents($this->totalIncrease)],
                ['Netto-Differenz', $this->formatCents($this->totalIncrease - $this->totalSavings)],
            ]
        );

        if ($dryRun) {
            $this->warn('Dies war ein DRY-RUN. Führen Sie den Befehl ohne --dry-run aus, um die Änderungen anzuwenden.');
        } else {
            $this->info('Alle Änderungen wurden erfolgreich gespeichert.');
        }

        // Log the recalculation
        Log::info('Call costs recalculation completed', [
            'processed' => $this->processedCount,
            'changed' => $this->changedCount,
            'total_savings_cents' => $this->totalSavings,
            'total_increase_cents' => $this->totalIncrease,
            'dry_run' => $dryRun,
        ]);
    }

    private function formatCents(int $cents): string
    {
        $euros = $cents / 100;
        return number_format($euros, 2, ',', '.') . ' EUR';
    }
}
