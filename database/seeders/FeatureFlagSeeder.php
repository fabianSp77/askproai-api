<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\FeatureFlagService;

class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $featureFlags = app(FeatureFlagService::class);
        
        // Service consolidation flags
        $featureFlags->createOrUpdate([
            'key' => 'use_calcom_v2_api',
            'name' => 'Use Cal.com V2 API',
            'description' => 'Switch to consolidated Cal.com V2 API service',
            'enabled' => false,
            'rollout_percentage' => '0'
        ]);
        
        $featureFlags->createOrUpdate([
            'key' => 'use_unified_retell_service',
            'name' => 'Use Unified Retell Service',
            'description' => 'Use consolidated Retell service instead of multiple versions',
            'enabled' => false,
            'rollout_percentage' => '0'
        ]);
        
        $featureFlags->createOrUpdate([
            'key' => 'use_unified_event_parser',
            'name' => 'Use Unified Event Parser',
            'description' => 'Use single configurable event type parser',
            'enabled' => false,
            'rollout_percentage' => '0'
        ]);
        
        // Performance flags
        $featureFlags->createOrUpdate([
            'key' => 'enable_service_tracking',
            'name' => 'Enable Service Usage Tracking',
            'description' => 'Track all service method calls for analysis',
            'enabled' => true,
            'rollout_percentage' => '100'
        ]);
        
        $featureFlags->createOrUpdate([
            'key' => 'enable_response_caching',
            'name' => 'Enable API Response Caching',
            'description' => 'Cache API responses for better performance',
            'enabled' => false,
            'rollout_percentage' => '0'
        ]);
        
        // Security flags
        $featureFlags->createOrUpdate([
            'key' => 'enforce_webhook_signatures',
            'name' => 'Enforce Webhook Signatures',
            'description' => 'Require valid signatures for all webhooks',
            'enabled' => true,
            'rollout_percentage' => '100'
        ]);
        
        $featureFlags->createOrUpdate([
            'key' => 'enable_sql_injection_protection',
            'name' => 'SQL Injection Protection',
            'description' => 'Enable enhanced SQL injection protection',
            'enabled' => true,
            'rollout_percentage' => '100'
        ]);
        
        // Feature flags
        $featureFlags->createOrUpdate([
            'key' => 'enable_mcp_servers',
            'name' => 'Enable MCP Servers',
            'description' => 'Enable Model Context Protocol servers',
            'enabled' => true,
            'rollout_percentage' => '100'
        ]);
        
        $featureFlags->createOrUpdate([
            'key' => 'enable_phone_agent_assignment',
            'name' => 'Phone-Level Agent Assignment',
            'description' => 'Allow agent assignment at phone number level',
            'enabled' => true,
            'rollout_percentage' => '100'
        ]);
        
        // Monitoring flags
        $featureFlags->createOrUpdate([
            'key' => 'enable_performance_monitoring',
            'name' => 'Performance Monitoring',
            'description' => 'Track performance metrics and slow queries',
            'enabled' => false,
            'rollout_percentage' => '0'
        ]);
        
        echo "Feature flags seeded successfully!\n";
    }
}