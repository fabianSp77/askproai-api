<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CostTrackingAlertService;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class CheckCostAlerts extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cost-alerts:check 
                          {--company= : Check alerts for specific company ID}
                          {--type= : Check specific alert type only}
                          {--dry-run : Show what would be done without actually creating alerts}
                          {--force : Force check even if recently checked}';

    /**
     * The console command description.
     */
    protected $description = 'Check and trigger cost tracking alerts for companies';

    protected CostTrackingAlertService $costTrackingService;

    /**
     * Create a new command instance.
     */
    public function __construct(CostTrackingAlertService $costTrackingService)
    {
        parent::__construct();
        $this->costTrackingService = $costTrackingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $startTime = now();
        $this->info("Starting cost alerts check at {$startTime->format('Y-m-d H:i:s')}");

        try {
            if ($this->option('dry-run')) {
                $this->warn('DRY RUN MODE - No alerts will be created or sent');
            }

            $companyId = $this->option('company');
            $alertType = $this->option('type');
            
            if ($companyId) {
                return $this->checkSingleCompany($companyId);
            }
            
            return $this->checkAllCompanies();
            
        } catch (\Exception $e) {
            $this->error("Critical error during cost alerts check: {$e->getMessage()}");
            Log::error('Cost alerts check command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return self::FAILURE;
        }
    }

    /**
     * Check alerts for all companies
     */
    protected function checkAllCompanies(): int
    {
        $this->info('Checking cost alerts for all active companies...');
        
        $companies = Company::with(['prepaidBalance', 'billingAlertConfigs'])
            ->where('is_active', true)
            ->whereHas('prepaidBalance')
            ->get();

        if ($companies->isEmpty()) {
            $this->warn('No active companies with prepaid balances found.');
            return self::SUCCESS;
        }

        $this->info("Found {$companies->count()} active companies to check");
        
        $progressBar = $this->output->createProgressBar($companies->count());
        $progressBar->start();

        $totalResults = [
            'processed' => 0,
            'alerts_created' => 0,
            'notifications_sent' => 0,
            'errors' => []
        ];

        foreach ($companies as $company) {
            try {
                if ($this->option('dry-run')) {
                    $this->checkCompanyDryRun($company);
                } else {
                    $results = $this->costTrackingService->checkCompanyCostAlerts($company);
                    
                    $totalResults['processed']++;
                    $totalResults['alerts_created'] += $results['alerts_created'];
                    $totalResults['notifications_sent'] += $results['notifications_sent'];
                    
                    if ($results['alerts_created'] > 0) {
                        $this->line("\n  ✓ Company {$company->name} ({$company->id}): {$results['alerts_created']} alerts created");
                    }
                }
                
            } catch (\Exception $e) {
                $totalResults['errors'][] = [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'error' => $e->getMessage()
                ];
                
                $this->line("\n  ✗ Error checking company {$company->name} ({$company->id}): {$e->getMessage()}");
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        return $this->displayResults($totalResults);
    }

    /**
     * Check alerts for a single company
     */
    protected function checkSingleCompany(int $companyId): int
    {
        $this->info("Checking cost alerts for company ID: {$companyId}");
        
        $company = Company::with(['prepaidBalance', 'billingAlertConfigs'])
            ->where('id', $companyId)
            ->first();

        if (!$company) {
            $this->error("Company with ID {$companyId} not found.");
            return self::FAILURE;
        }

        if (!$company->is_active) {
            $this->warn("Company {$company->name} is not active. Skipping...");
            return self::SUCCESS;
        }

        if (!$company->prepaidBalance) {
            $this->warn("Company {$company->name} has no prepaid balance. Skipping...");
            return self::SUCCESS;
        }

        $this->info("Company: {$company->name}");
        $this->info("Balance: €" . number_format($company->prepaidBalance->getEffectiveBalance(), 2));
        $this->info("Low Balance Threshold: €" . number_format($company->prepaidBalance->low_balance_threshold, 2));

        try {
            if ($this->option('dry-run')) {
                $this->checkCompanyDryRun($company);
                return self::SUCCESS;
            }
            
            $results = $this->costTrackingService->checkCompanyCostAlerts($company);
            
            $this->info("Results:");
            $this->info("  - Alerts created: {$results['alerts_created']}");
            $this->info("  - Notifications sent: {$results['notifications_sent']}");
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("Error checking company {$company->name}: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Dry run check for a company (shows what would happen)
     */
    protected function checkCompanyDryRun(Company $company): void
    {
        $this->line("DRY RUN - Company: {$company->name} ({$company->id})");
        
        $balance = $company->prepaidBalance;
        if (!$balance) {
            $this->line("  └─ No prepaid balance");
            return;
        }

        $effectiveBalance = $balance->getEffectiveBalance();
        $threshold = $balance->low_balance_threshold;
        
        $this->line("  └─ Current balance: €" . number_format($effectiveBalance, 2));
        $this->line("  └─ Threshold: €" . number_format($threshold, 2));
        
        // Check what alerts would be triggered
        $alerts = [];
        
        // Low balance check
        if ($threshold > 0) {
            $percentage = ($effectiveBalance / $threshold) * 100;
            $defaultThresholds = [25, 10, 5];
            
            foreach ($defaultThresholds as $t) {
                if ($percentage <= $t) {
                    $alerts[] = "Low balance ({$t}% threshold)";
                    break;
                }
            }
        }
        
        // Zero balance check
        if ($effectiveBalance <= 0) {
            $alerts[] = "Zero balance (CRITICAL)";
        }
        
        if (empty($alerts)) {
            $this->line("  └─ No alerts would be triggered");
        } else {
            foreach ($alerts as $alert) {
                $this->line("  └─ Would trigger: {$alert}");
            }
        }
    }

    /**
     * Display final results
     */
    protected function displayResults(array $results): int
    {
        $this->newLine();
        $this->info('=== COST ALERTS CHECK SUMMARY ===');
        $this->info("Companies processed: {$results['processed']}");
        $this->info("Total alerts created: {$results['alerts_created']}");
        $this->info("Total notifications sent: {$results['notifications_sent']}");
        
        if (!empty($results['errors'])) {
            $this->warn("Errors encountered: " . count($results['errors']));
            
            foreach ($results['errors'] as $error) {
                $this->line("  ✗ {$error['company_name']} ({$error['company_id']}): {$error['error']}");
            }
        }

        $endTime = now();
        $duration = $endTime->diffInSeconds($this->startTime ?? $endTime);
        $this->info("Completed in {$duration} seconds");

        // Return appropriate exit code
        if (!empty($results['errors'])) {
            return self::FAILURE;
        }
        
        return self::SUCCESS;
    }

    /**
     * Get command help text
     */
    public function getHelp(): string
    {
        return <<<'HELP'
This command checks and triggers cost tracking alerts for companies with prepaid balances.

EXAMPLES:
  php artisan cost-alerts:check                    # Check all companies
  php artisan cost-alerts:check --company=123      # Check specific company
  php artisan cost-alerts:check --dry-run          # Preview what would happen
  php artisan cost-alerts:check --force            # Force check even if recently checked

ALERT TYPES CHECKED:
  - Low balance alerts (when balance drops below thresholds)
  - Zero balance alerts (critical - services may be interrupted)
  - Usage spike alerts (unusual hourly cost increases)
  - Budget exceeded alerts (monthly spending over budget)
  - Cost anomaly alerts (unusual daily spending patterns)

The command is designed to be run frequently (every 30 minutes to 1 hour) via cron.
HELP;
    }
}