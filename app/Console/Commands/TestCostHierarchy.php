<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Models\User;
use App\Services\CostCalculator;

class TestCostHierarchy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:cost-hierarchy {--call-id= : Specific call ID to test} {--user-id= : Specific user ID to test as}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the cost hierarchy system with different user roles';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Testing Cost Hierarchy System');
        $this->info('==============================');

        // Get a call to test with
        $callId = $this->option('call-id');
        $call = $callId ? Call::find($callId) : Call::latest()->first();

        if (!$call) {
            $this->error('No calls found to test with');
            return 1;
        }

        $this->info("\nTesting with Call ID: {$call->id}");
        $this->info("Call Duration: {$call->duration_sec} seconds");
        $this->info("Call Company: " . ($call->company->name ?? 'N/A'));

        // Calculate costs
        $calculator = new CostCalculator();
        $costs = $calculator->calculateCallCosts($call);

        $this->info("\n📊 Calculated Costs:");
        $this->table(
            ['Type', 'Amount (€)', 'Details'],
            [
                ['Base Cost', number_format($costs['base_cost'] / 100, 2, ',', '.'), 'Our infrastructure cost'],
                ['Reseller Cost', number_format($costs['reseller_cost'] / 100, 2, ',', '.'), 'What reseller pays us'],
                ['Customer Cost', number_format($costs['customer_cost'] / 100, 2, ',', '.'), 'What customer pays'],
            ]
        );

        $this->info("\n💵 Cost Breakdown:");
        if (isset($costs['cost_breakdown']['base'])) {
            $this->info("  Base Costs:");
            foreach ($costs['cost_breakdown']['base'] as $key => $value) {
                $this->info("    - {$key}: €" . number_format($value / 100, 2, ',', '.'));
            }
        }

        // Test different user roles
        $this->info("\n👥 Testing User Role Views:");

        $testUsers = [];

        // Get specific user or test different roles
        $userId = $this->option('user-id');
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                $testUsers[] = $user;
            }
        } else {
            // Get sample users with different roles
            $testRoles = [
                'super-admin' => 'Super Admin',
                'reseller' => 'reseller_admin',
                'company' => 'company_admin',
            ];
            foreach ($testRoles as $displayName => $actualRole) {
                $user = User::role($actualRole)->first();
                if ($user) {
                    $this->info("  Found user with role: {$actualRole}");
                    $testUsers[] = $user;
                } else {
                    $this->info("  No users found with role: {$actualRole}");
                }
            }
        }

        foreach ($testUsers as $user) {
            $displayCost = $calculator->getDisplayCost($call, $user);
            $roles = $user->roles->pluck('name')->implode(', ');

            $this->info("\n  User: {$user->name} (ID: {$user->id})");
            $this->info("  Roles: {$roles}");
            $this->info("  Company: " . ($user->company->name ?? 'N/A'));
            $this->info("  Sees Cost: €" . number_format($displayCost / 100, 2, ',', '.'));

            // Show what cost type they see
            if ($user->hasRole(['super-admin', 'super_admin', 'Super Admin'])) {
                $this->info("  Cost Type: Customer Cost (Full visibility)");
            } elseif ($user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support'])) {
                $this->info("  Cost Type: Reseller Cost");
            } else {
                $this->info("  Cost Type: Customer Cost (Their cost)");
            }
        }

        // Update the call with calculated costs
        $this->info("\n📝 Updating call with calculated costs...");
        $calculator->updateCallCosts($call);
        $call->refresh();

        $this->info("\nUpdated Call Costs:");
        $this->table(
            ['Field', 'Value'],
            [
                ['base_cost', $call->base_cost ? '€' . number_format($call->base_cost / 100, 2, ',', '.') : 'null'],
                ['reseller_cost', $call->reseller_cost ? '€' . number_format($call->reseller_cost / 100, 2, ',', '.') : 'null'],
                ['customer_cost', $call->customer_cost ? '€' . number_format($call->customer_cost / 100, 2, ',', '.') : 'null'],
                ['cost (original)', $call->cost ? '€' . number_format($call->cost / 100, 2, ',', '.') : 'null'],
                ['cost_calculation_method', $call->cost_calculation_method ?? 'null'],
            ]
        );

        $this->info("\n✅ Cost hierarchy test completed!");

        return 0;
    }
}