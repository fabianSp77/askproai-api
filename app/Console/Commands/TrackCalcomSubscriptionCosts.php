<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\PlatformCostService;
use Carbon\Carbon;

class TrackCalcomSubscriptionCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'costs:track-calcom
                            {--company= : Specific company ID to track}
                            {--month= : Month to track (1-12)}
                            {--year= : Year to track}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Track Cal.com subscription costs for companies';

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
        $month = $this->option('month') ?? now()->month;
        $year = $this->option('year') ?? now()->year;

        // Calculate period
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $this->info("Tracking Cal.com costs for {$periodStart->format('F Y')}");

        // Get companies to process
        if ($companyId) {
            $companies = Company::where('id', $companyId)->get();
        } else {
            $companies = Company::where('is_active', true)->get();
        }

        $totalCompanies = 0;
        $totalUsers = 0;
        $totalCost = 0;

        foreach ($companies as $company) {
            // Count Cal.com users for this company
            // This is a simplified count - adjust based on your actual Cal.com user tracking
            $userCount = $company->users()->where('is_active', true)->count();

            if ($userCount > 0) {
                try {
                    $this->platformCostService->trackCalcomCost(
                        $company,
                        $userCount,
                        $periodStart,
                        $periodEnd
                    );

                    $costUsd = $userCount * 15; // $15 per user
                    $totalCompanies++;
                    $totalUsers += $userCount;
                    $totalCost += $costUsd;

                    $this->line("✅ Company: {$company->name} - {$userCount} users - \${$costUsd} USD");
                } catch (\Exception $e) {
                    $this->error("❌ Failed to track costs for {$company->name}: {$e->getMessage()}");
                }
            }
        }

        $this->info("");
        $this->info("Summary:");
        $this->info("- Companies processed: {$totalCompanies}");
        $this->info("- Total users: {$totalUsers}");
        $this->info("- Total cost: \${$totalCost} USD");

        return Command::SUCCESS;
    }
}