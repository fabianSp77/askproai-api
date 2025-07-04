<?php

namespace App\Console\Commands;

use App\Services\Billing\BillingAlertService;
use Illuminate\Console\Command;

class CheckBillingAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:check-alerts 
                            {--company= : Check alerts for specific company ID}
                            {--type= : Check only specific alert type}
                            {--dry-run : Run without sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and send billing alerts for all companies';

    private BillingAlertService $alertService;

    /**
     * Create a new command instance.
     */
    public function __construct(BillingAlertService $alertService)
    {
        parent::__construct();
        $this->alertService = $alertService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting billing alert check...');

        $startTime = microtime(true);
        $alertCount = 0;

        try {
            if ($companyId = $this->option('company')) {
                // Check specific company
                $company = \App\Models\Company::find($companyId);
                
                if (!$company) {
                    $this->error("Company with ID {$companyId} not found.");
                    return 1;
                }

                $this->info("Checking alerts for company: {$company->name}");
                
                if ($this->option('dry-run')) {
                    $this->warn('DRY RUN MODE - No notifications will be sent');
                }

                $this->alertService->checkCompanyAlerts($company);
                $alertCount++;
            } else {
                // Check all companies
                $this->info('Checking alerts for all active companies...');
                
                if ($this->option('dry-run')) {
                    $this->warn('DRY RUN MODE - No notifications will be sent');
                }

                $this->alertService->checkAllAlerts();
            }

            $duration = round(microtime(true) - $startTime, 2);
            
            $this->info("✓ Alert check completed in {$duration} seconds");
            
            // Show recent alerts
            $this->showRecentAlerts();

            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to check alerts: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Show recently created alerts.
     */
    private function showRecentAlerts(): void
    {
        $recentAlerts = \App\Models\BillingAlert::with('company')
            ->where('created_at', '>=', now()->subHour())
            ->orderBy('created_at', 'desc')
            ->get();

        if ($recentAlerts->isEmpty()) {
            $this->info('No alerts generated in the last hour.');
            return;
        }

        $this->info("\nRecent alerts (last hour):");
        
        $headers = ['Company', 'Type', 'Severity', 'Title', 'Status', 'Created'];
        $rows = [];

        foreach ($recentAlerts as $alert) {
            $rows[] = [
                $alert->company->name,
                $alert->alert_type,
                $alert->severity,
                \Str::limit($alert->title, 40),
                $alert->status,
                $alert->created_at->diffForHumans(),
            ];
        }

        $this->table($headers, $rows);
    }
}