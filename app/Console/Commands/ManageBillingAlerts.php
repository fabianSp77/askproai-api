<?php

namespace App\Console\Commands;

use App\Models\BillingAlert;
use App\Models\BillingAlertConfig;
use App\Models\Company;
use Illuminate\Console\Command;

class ManageBillingAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:manage-alerts 
                            {action : Action to perform (list|acknowledge|suppress|enable|disable)}
                            {--company= : Company ID to manage}
                            {--type= : Alert type to manage}
                            {--alert= : Specific alert ID for acknowledge action}
                            {--days= : Days for suppression (default: 7)}
                            {--reason= : Reason for suppression}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage billing alerts and configurations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listAlerts(),
            'acknowledge' => $this->acknowledgeAlert(),
            'suppress' => $this->suppressAlerts(),
            'enable' => $this->toggleAlerts(true),
            'disable' => $this->toggleAlerts(false),
            default => $this->invalidAction(),
        };
    }

    /**
     * List alerts.
     */
    private function listAlerts(): int
    {
        $query = BillingAlert::with('company');

        if ($companyId = $this->option('company')) {
            $query->where('company_id', $companyId);
        }

        if ($type = $this->option('type')) {
            $query->where('alert_type', $type);
        }

        $alerts = $query->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        if ($alerts->isEmpty()) {
            $this->info('No alerts found.');
            return 0;
        }

        $headers = ['ID', 'Company', 'Type', 'Severity', 'Title', 'Status', 'Created'];
        $rows = [];

        foreach ($alerts as $alert) {
            $rows[] = [
                $alert->id,
                $alert->company->name,
                $alert->alert_type,
                $alert->severity,
                \Str::limit($alert->title, 40),
                $alert->status,
                $alert->created_at->format('Y-m-d H:i'),
            ];
        }

        $this->table($headers, $rows);

        // Show summary
        $summary = $alerts->groupBy('status')->map->count();
        $this->info("\nSummary:");
        foreach ($summary as $status => $count) {
            $this->info("  {$status}: {$count}");
        }

        return 0;
    }

    /**
     * Acknowledge an alert.
     */
    private function acknowledgeAlert(): int
    {
        $alertId = $this->option('alert');
        
        if (!$alertId) {
            $this->error('Alert ID is required for acknowledge action.');
            return 1;
        }

        $alert = BillingAlert::find($alertId);
        
        if (!$alert) {
            $this->error("Alert with ID {$alertId} not found.");
            return 1;
        }

        if ($alert->status === BillingAlert::STATUS_ACKNOWLEDGED) {
            $this->warn('Alert is already acknowledged.');
            return 0;
        }

        // For command line, use system user or first admin
        $user = \App\Models\User::where('email', 'system@askproai.de')->first()
            ?? \App\Models\User::first();

        $alert->acknowledge($user);

        $this->info("✓ Alert {$alertId} acknowledged successfully.");
        $this->info("  Type: {$alert->alert_type}");
        $this->info("  Title: {$alert->title}");

        return 0;
    }

    /**
     * Suppress alerts.
     */
    private function suppressAlerts(): int
    {
        $companyId = $this->option('company');
        $type = $this->option('type') ?? 'all';
        $days = $this->option('days') ?? 7;
        $reason = $this->option('reason') ?? 'Manual suppression via CLI';

        if (!$companyId) {
            $this->error('Company ID is required for suppress action.');
            return 1;
        }

        $company = Company::find($companyId);
        
        if (!$company) {
            $this->error("Company with ID {$companyId} not found.");
            return 1;
        }

        // Create suppression
        \DB::table('billing_alert_suppressions')->insert([
            'company_id' => $company->id,
            'alert_type' => $type,
            'starts_at' => now(),
            'ends_at' => now()->addDays($days),
            'reason' => $reason,
            'created_by' => \App\Models\User::first()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("✓ Alerts suppressed for {$company->name}");
        $this->info("  Type: {$type}");
        $this->info("  Duration: {$days} days");
        $this->info("  Reason: {$reason}");

        return 0;
    }

    /**
     * Enable or disable alerts.
     */
    private function toggleAlerts(bool $enable): int
    {
        $companyId = $this->option('company');
        $type = $this->option('type');

        if ($companyId) {
            // Toggle for specific company
            $company = Company::find($companyId);
            
            if (!$company) {
                $this->error("Company with ID {$companyId} not found.");
                return 1;
            }

            if ($type) {
                // Toggle specific alert type
                $config = BillingAlertConfig::where('company_id', $company->id)
                    ->where('alert_type', $type)
                    ->first();

                if (!$config) {
                    $this->error("Alert configuration not found for type: {$type}");
                    return 1;
                }

                $config->update(['is_enabled' => $enable]);
                $this->info("✓ {$type} alerts " . ($enable ? 'enabled' : 'disabled') . " for {$company->name}");
            } else {
                // Toggle all alerts for company
                $company->update(['alerts_enabled' => $enable]);
                $this->info("✓ All alerts " . ($enable ? 'enabled' : 'disabled') . " for {$company->name}");
            }
        } else {
            // Toggle globally (be careful!)
            $this->warn('This will ' . ($enable ? 'enable' : 'disable') . ' alerts for ALL companies!');
            
            if (!$this->confirm('Do you want to continue?')) {
                return 0;
            }

            Company::query()->update(['alerts_enabled' => $enable]);
            $this->info("✓ Alerts " . ($enable ? 'enabled' : 'disabled') . " globally for all companies");
        }

        return 0;
    }

    /**
     * Handle invalid action.
     */
    private function invalidAction(): int
    {
        $this->error('Invalid action. Valid actions are: list, acknowledge, suppress, enable, disable');
        return 1;
    }
}