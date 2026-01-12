<?php

namespace App\Console\Commands;

use App\Models\AggregateInvoice;
use App\Models\Company;
use App\Services\Billing\MonthlyBillingAggregator;
use App\Services\Billing\StripeInvoicingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate-monthly-invoices
        {--partner= : Generate for specific partner ID only}
        {--month= : Billing month in YYYY-MM format (default: previous month)}
        {--dry-run : Preview charges without creating invoices}
        {--send : Finalize and send invoices to Stripe (default: create draft only)}
        {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly aggregate invoices for all partners';

    private MonthlyBillingAggregator $aggregator;
    private StripeInvoicingService $stripeService;

    public function __construct(
        MonthlyBillingAggregator $aggregator,
        StripeInvoicingService $stripeService
    ) {
        parent::__construct();
        $this->aggregator = $aggregator;
        $this->stripeService = $stripeService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Parse billing period
        $month = $this->option('month');
        if ($month) {
            $periodStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } else {
            // Default to previous month
            $periodStart = now()->subMonth()->startOfMonth();
        }
        $periodEnd = $periodStart->copy()->endOfMonth();

        $this->info("Billing Period: {$periodStart->format('d.m.Y')} - {$periodEnd->format('d.m.Y')}");
        $this->newLine();

        // Get partners to process
        $partnerId = $this->option('partner');
        if ($partnerId) {
            $partners = Company::where('id', $partnerId)->where('is_partner', true)->get();
            if ($partners->isEmpty()) {
                $this->error("Partner ID {$partnerId} not found or is not a partner.");
                return Command::FAILURE;
            }
        } else {
            $partners = Company::where('is_partner', true)->where('is_active', true)->get();
        }

        if ($partners->isEmpty()) {
            $this->warn("No active partners found.");
            return Command::SUCCESS;
        }

        $this->info("Processing {$partners->count()} partner(s)...");
        $this->newLine();

        // Dry run mode - just show preview
        if ($this->option('dry-run')) {
            return $this->dryRun($partners, $periodStart, $periodEnd);
        }

        // Confirmation for production
        if (!$this->option('force') && !$this->confirm('This will create invoices. Continue?')) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        // Process each partner
        $successCount = 0;
        $errorCount = 0;

        foreach ($partners as $partner) {
            try {
                $result = $this->processPartner($partner, $periodStart, $periodEnd);
                if ($result) {
                    $successCount++;
                }
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error processing {$partner->name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Complete: {$successCount} invoices created, {$errorCount} errors.");

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Dry run - show preview without creating invoices.
     */
    private function dryRun($partners, Carbon $periodStart, Carbon $periodEnd): int
    {
        $this->comment("=== DRY RUN - No invoices will be created ===");
        $this->newLine();

        $grandTotal = 0;

        foreach ($partners as $partner) {
            $summary = $this->aggregator->getChargesSummary($partner, $periodStart, $periodEnd);

            if (empty($summary['companies'])) {
                $this->line("  <fg=yellow>{$partner->name}</>: No charges");
                continue;
            }

            $this->line("  <fg=green>{$partner->name}</>");

            foreach ($summary['companies'] as $company) {
                $this->line("    └─ {$company['company_name']}");

                foreach ($company['charges'] as $charge) {
                    $amount = number_format($charge['amount_cents'] / 100, 2, ',', '.');
                    $this->line("       • {$charge['description']}: {$amount} €");
                }

                $subtotal = number_format($company['total_cents'] / 100, 2, ',', '.');
                $this->line("       <fg=cyan>Subtotal: {$subtotal} €</>");
            }

            $partnerTotal = number_format($summary['total_cents'] / 100, 2, ',', '.');
            $tax = number_format($summary['total_cents'] * 0.19 / 100, 2, ',', '.');
            $total = number_format($summary['total_cents'] * 1.19 / 100, 2, ',', '.');

            $this->newLine();
            $this->line("    <fg=white;bg=blue> Netto: {$partnerTotal} € | MwSt: {$tax} € | Brutto: {$total} € </>");
            $this->newLine();

            $grandTotal += $summary['total_cents'];
        }

        $grandTotalFormatted = number_format($grandTotal * 1.19 / 100, 2, ',', '.');
        $this->newLine();
        $this->info("Grand Total (all partners): {$grandTotalFormatted} € (brutto)");

        return Command::SUCCESS;
    }

    /**
     * Process a single partner.
     */
    private function processPartner(
        Company $partner,
        Carbon $periodStart,
        Carbon $periodEnd
    ): ?AggregateInvoice {
        $this->line("Processing: {$partner->name}");

        // Check for existing invoice
        $existing = AggregateInvoice::where('partner_company_id', $partner->id)
            ->forPeriod($periodStart, $periodEnd)
            ->whereNotIn('status', [AggregateInvoice::STATUS_VOID])
            ->first();

        if ($existing) {
            $this->warn("  ⚠ Invoice already exists for this period (ID: {$existing->id})");
            return null;
        }

        // Get summary first to check if there are charges
        $summary = $this->aggregator->getChargesSummary($partner, $periodStart, $periodEnd);

        if ($summary['total_cents'] === 0) {
            $this->info("  → No charges for this period");
            return null;
        }

        try {
            DB::beginTransaction();

            // Create invoice
            $invoice = $this->stripeService->createMonthlyInvoice(
                $partner,
                $periodStart,
                $periodEnd
            );

            // Populate with items
            $this->aggregator->populateInvoice($invoice, $partner, $periodStart, $periodEnd);

            // Should we send immediately?
            if ($this->option('send')) {
                $invoice = $this->stripeService->finalizeAndSend($invoice);
                $this->info("  ✓ Invoice #{$invoice->invoice_number} created and sent ({$invoice->formatted_total})");
            } else {
                $this->info("  ✓ Invoice #{$invoice->invoice_number} created as draft ({$invoice->formatted_total})");
            }

            DB::commit();
            return $invoice;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
