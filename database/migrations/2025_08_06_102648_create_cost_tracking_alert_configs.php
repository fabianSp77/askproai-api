<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\BillingAlertConfig;
use App\Models\Company;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration ensures all companies have the new cost tracking alert configurations
        $companies = Company::with('billingAlertConfigs')->get();
        
        foreach ($companies as $company) {
            // Create default cost tracking alert configurations if they don't exist
            $this->createCostTrackingConfigs($company);
        }
    }

    /**
     * Create cost tracking alert configurations for a company
     */
    protected function createCostTrackingConfigs(Company $company): void
    {
        $defaultConfigs = [
            [
                'alert_type' => 'low_balance',
                'thresholds' => [25, 10, 5], // 25%, 10%, 5% of threshold
                'notification_channels' => ['email'],
                'is_enabled' => true,
            ],
            [
                'alert_type' => 'zero_balance', 
                'notification_channels' => ['email'],
                'is_enabled' => true,
            ],
            [
                'alert_type' => 'usage_spike',
                'amount_threshold' => 200, // 200% of average usage
                'notification_channels' => ['email'],
                'is_enabled' => true,
            ],
            [
                'alert_type' => 'cost_anomaly',
                'amount_threshold' => 3.0, // 3x average daily cost
                'notification_channels' => ['email'], 
                'is_enabled' => true,
            ],
            [
                'alert_type' => 'budget_exceeded',
                'thresholds' => [80, 90, 100, 110], // % of budget
                'notification_channels' => ['email'],
                'is_enabled' => false, // Disabled by default since not all companies have budgets
            ]
        ];

        foreach ($defaultConfigs as $config) {
            BillingAlertConfig::firstOrCreate(
                [
                    'company_id' => $company->id,
                    'alert_type' => $config['alert_type'],
                ],
                array_merge($config, [
                    'notify_primary_contact' => true,
                    'notify_billing_contact' => true,
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove cost tracking alert configurations
        BillingAlertConfig::whereIn('alert_type', [
            'low_balance',
            'zero_balance', 
            'usage_spike',
            'cost_anomaly',
            'budget_exceeded'
        ])->delete();
    }
};