<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BalanceMonitoringService;

class CheckLowBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balances:check-low 
                            {--company= : Check specific company by ID}
                            {--dry-run : Run without sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for companies with low prepaid balance and send warnings';

    /**
     * Execute the console command.
     */
    public function handle(BalanceMonitoringService $monitoringService): int
    {
        $this->info('Checking for low balances...');

        if ($companyId = $this->option('company')) {
            // Check specific company
            $company = \App\Models\Company::find($companyId);
            
            if (!$company) {
                $this->error("Company with ID {$companyId} not found.");
                return 1;
            }

            if (!$company->prepaid_billing_enabled) {
                $this->warn("Company {$company->name} does not have prepaid billing enabled.");
                return 0;
            }

            $status = $monitoringService->getBalanceStatus($company);
            
            $this->info("Company: {$company->name}");
            $this->info("Balance: {$status['balance']} €");
            $this->info("Effective Balance: {$status['effective_balance']} €");
            $this->info("Reserved: {$status['reserved_balance']} €");
            $this->info("Status: {$status['status']}");
            $this->info("Estimated Minutes: {$status['estimated_minutes']}");

            if (!$this->option('dry-run') && $status['is_low']) {
                if ($monitoringService->checkAndNotifyLowBalance($company)) {
                    $this->info('Low balance warning sent.');
                } else {
                    $this->info('Warning already sent recently or not needed.');
                }
            }
        } else {
            // Check all companies
            $results = $monitoringService->checkAllCompaniesForLowBalance();
            
            $this->info("Companies checked: {$results['checked']}");
            $this->info("Warnings sent: {$results['warnings_sent']}");
            
            if ($results['errors'] > 0) {
                $this->error("Errors encountered: {$results['errors']}");
            }
        }

        return 0;
    }
}