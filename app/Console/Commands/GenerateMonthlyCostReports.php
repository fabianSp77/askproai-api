<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\PlatformCostService;
use Carbon\Carbon;

class GenerateMonthlyCostReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'costs:generate-monthly-report
                            {--company= : Specific company ID}
                            {--month= : Month to generate report for (1-12)}
                            {--year= : Year to generate report for}
                            {--finalize : Finalize the reports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate monthly cost reports for companies';

    private PlatformCostService $platformCostService;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->platformCostService = new PlatformCostService();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');
        $month = $this->option('month') ?? now()->subMonth()->month; // Default to last month
        $year = $this->option('year') ?? now()->year;
        $finalize = $this->option('finalize');

        $this->info("Generating monthly cost reports for {$month}/{$year}");

        // Get companies to process
        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::where('is_active', true)->get();
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($companies as $company) {
            try {
                $this->line("Processing: {$company->name}...");

                $report = $this->platformCostService->generateMonthlyReport($company, $month, $year);

                if ($finalize) {
                    $report->finalize();
                    $this->info("✅ Report generated and finalized for {$company->name}");
                } else {
                    $this->info("✅ Report generated for {$company->name}");
                }

                // Display summary
                $this->line("  - Calls: {$report->call_count}");
                $this->line("  - Total minutes: " . number_format($report->total_minutes, 2));
                $this->line("  - Retell costs: €" . number_format($report->retell_cost_cents / 100, 2));
                $this->line("  - Twilio costs: €" . number_format($report->twilio_cost_cents / 100, 2));
                $this->line("  - Cal.com costs: €" . number_format($report->calcom_cost_cents / 100, 2));
                $this->line("  - Total external costs: €" . number_format($report->total_external_costs_cents / 100, 2));
                $this->line("  - Revenue: €" . number_format($report->total_revenue_cents / 100, 2));
                $this->line("  - Gross profit: €" . number_format($report->gross_profit_cents / 100, 2));
                $this->line("  - Profit margin: " . number_format($report->profit_margin, 1) . "%");
                $this->line("");

                $successCount++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to generate report for {$company->name}: {$e->getMessage()}");
                $errorCount++;
            }
        }

        $this->info("");
        $this->info("Summary:");
        $this->info("- Reports generated: {$successCount}");
        if ($errorCount > 0) {
            $this->warn("- Errors: {$errorCount}");
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}