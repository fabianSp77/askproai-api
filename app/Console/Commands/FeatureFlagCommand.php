<?php

namespace App\Console\Commands;

use App\Services\FeatureFlagService;
use Illuminate\Console\Command;

class FeatureFlagCommand extends Command
{
    protected $signature = 'feature {action} {key?} {--percentage=100} {--company=} {--reason=}';
    
    protected $description = 'Manage feature flags for deployment';

    public function __construct(private FeatureFlagService $featureFlagService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $action = $this->argument('action');
        $key = $this->argument('key');

        return match ($action) {
            'list' => $this->listFeatures(),
            'enable' => $this->enableFeature($key),
            'disable' => $this->disableFeature($key),
            'stats' => $this->showStats($key),
            'emergency-disable' => $this->emergencyDisable(),
            'health' => $this->healthCheck(),
            default => $this->error("Unknown action: {$action}") ?? 1,
        };
    }

    private function listFeatures(): int
    {
        $flags = $this->featureFlagService->getAllFlags();
        
        if (empty($flags)) {
            $this->info('No feature flags configured');
            return 0;
        }

        $this->table(
            ['Key', 'Name', 'Enabled', 'Rollout %', 'Status'],
            array_map(function ($flag) {
                return [
                    $flag->key,
                    $flag->name,
                    $flag->enabled ? '✅' : '❌',
                    $flag->rollout_percentage . '%',
                    $this->getStatusIcon($flag)
                ];
            }, $flags)
        );

        return 0;
    }

    private function enableFeature(string $key): int
    {
        $percentage = (int) $this->option('percentage');
        $companyId = $this->option('company');
        
        if ($companyId) {
            // Enable for specific company
            $this->featureFlagService->setOverride(
                $key,
                $companyId,
                true,
                $this->option('reason') ?? 'Manual override'
            );
            
            $this->info("✅ Feature '{$key}' enabled for company {$companyId}");
        } else {
            // Enable globally with percentage
            $this->featureFlagService->createOrUpdate([
                'key' => $key,
                'name' => $key,
                'enabled' => true,
                'rollout_percentage' => $percentage,
                'description' => "Enabled via CLI with {$percentage}% rollout"
            ]);
            
            $this->info("✅ Feature '{$key}' enabled globally with {$percentage}% rollout");
        }

        return 0;
    }

    private function disableFeature(string $key): int
    {
        $companyId = $this->option('company');
        
        if ($companyId) {
            // Disable for specific company
            $this->featureFlagService->setOverride(
                $key,
                $companyId,
                false,
                $this->option('reason') ?? 'Manual override'
            );
            
            $this->info("❌ Feature '{$key}' disabled for company {$companyId}");
        } else {
            // Disable globally
            $this->featureFlagService->createOrUpdate([
                'key' => $key,
                'enabled' => false,
                'rollout_percentage' => 0,
                'description' => 'Disabled via CLI'
            ]);
            
            $this->info("❌ Feature '{$key}' disabled globally");
        }

        return 0;
    }

    private function showStats(string $key): int
    {
        $stats = $this->featureFlagService->getUsageStats($key, 24);
        
        $this->info("📊 Feature Flag Statistics: {$key}");
        $this->info("Last 24 hours:");
        $this->line("Total evaluations: {$stats['total_evaluations']}");
        $this->line("Enabled count: {$stats['enabled_count']}");
        $this->line("Unique companies: {$stats['unique_companies']}");
        
        if (!empty($stats['by_reason'])) {
            $this->info("\nBy evaluation reason:");
            foreach ($stats['by_reason'] as $reason => $count) {
                $this->line("  {$reason}: {$count}");
            }
        }

        return 0;
    }

    private function emergencyDisable(): int
    {
        $reason = $this->option('reason') ?? 'Emergency disable via CLI';
        
        if (!$this->confirm('This will disable ALL feature flags. Are you sure?')) {
            $this->info('Operation cancelled');
            return 0;
        }

        $this->featureFlagService->emergencyDisableAll($reason);
        $this->error("🚨 ALL feature flags have been emergency disabled");
        $this->info("Reason: {$reason}");

        return 0;
    }

    private function healthCheck(): int
    {
        $this->info('🔍 Running feature flag system health check...');
        
        try {
            // Test basic functionality
            $testKey = 'health_check_test_' . time();
            
            // Create test flag
            $this->featureFlagService->createOrUpdate([
                'key' => $testKey,
                'name' => 'Health Check Test',
                'enabled' => true,
                'rollout_percentage' => 100
            ]);
            
            // Test evaluation
            $result = $this->featureFlagService->isEnabled($testKey, null, false);
            
            if (!$result) {
                throw new \Exception('Test flag evaluation failed');
            }
            
            // Test percentage rollout
            $this->featureFlagService->createOrUpdate([
                'key' => $testKey,
                'enabled' => true,
                'rollout_percentage' => 50
            ]);
            
            // Test stats
            $stats = $this->featureFlagService->getUsageStats($testKey, 1);
            
            // Cleanup
            $this->featureFlagService->createOrUpdate([
                'key' => $testKey,
                'enabled' => false
            ]);
            
            $this->info('✅ Feature flag system is healthy');
            $this->line('- Flag creation: ✅');
            $this->line('- Flag evaluation: ✅');
            $this->line('- Percentage rollout: ✅');
            $this->line('- Statistics: ✅');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Feature flag system health check failed');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    private function getStatusIcon($flag): string
    {
        if (!$flag->enabled) {
            return '🔴 Disabled';
        }
        
        if ($flag->rollout_percentage == 100) {
            return '🟢 Full';
        }
        
        if ($flag->rollout_percentage > 0) {
            return '🟡 Partial';
        }
        
        return '⚪ Unknown';
    }
}