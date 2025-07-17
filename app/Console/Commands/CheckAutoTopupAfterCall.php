<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\AutoTopupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckAutoTopupAfterCall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:check-auto-topup {companyId? : The company ID to check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and execute auto-topup for companies after calls';

    protected AutoTopupService $autoTopupService;

    /**
     * Create a new command instance.
     */
    public function __construct(AutoTopupService $autoTopupService)
    {
        parent::__construct();
        $this->autoTopupService = $autoTopupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->argument('companyId');
        
        if ($companyId) {
            // Check specific company
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Company with ID {$companyId} not found");
                return 1;
            }
            
            $this->checkCompany($company);
        } else {
            // Check all companies with auto-topup enabled
            $companies = Company::whereHas('prepaidBalance', function ($query) {
                $query->where('auto_topup_enabled', true)
                      ->whereNotNull('stripe_payment_method_id');
            })->get();
            
            $this->info("Checking {$companies->count()} companies for auto-topup...");
            
            foreach ($companies as $company) {
                $this->checkCompany($company);
            }
        }
        
        return 0;
    }

    /**
     * Check and execute auto-topup for a company
     */
    protected function checkCompany(Company $company): void
    {
        $this->info("Checking company: {$company->name} (ID: {$company->id})");
        
        $result = $this->autoTopupService->checkAndExecuteAutoTopup($company);
        
        if ($result === null) {
            $this->line("  - No auto-topup needed or not configured");
        } elseif ($result['success']) {
            $this->info("  ✓ Auto-topup successful: €{$result['amount']}");
            $this->info("  - New balance: €{$result['new_balance']}");
        } else {
            $this->error("  ✗ Auto-topup failed: {$result['error']}");
        }
    }
}