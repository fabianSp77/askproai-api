<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\PolicyConfiguration;
use App\Models\RetellAgent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Setup VisionaryData Agent Configuration
 *
 * Idempotent command to configure Company 1658 (IT-Systemhaus Test GmbH)
 * for the Retell v3.6 IT Support Agent.
 *
 * Actions:
 * 1. Register agent in retell_agents table
 * 2. Set gateway_mode policy to 'service_desk'
 * 3. Sync company retell_agent_id
 *
 * @see claudedocs/03_API/VisionaryData/retell-agent-v3.0.json
 */
class SetupVisionaryDataAgent extends Command
{
    protected $signature = 'visionarydata:setup-agent
                            {--company-id=1658 : The company ID to configure}
                            {--agent-id=agent_88ca380b9cb51f22d07d078a3c : The Retell agent ID}
                            {--flow-id=conversation_flow_0b5d4b51c882 : The conversation flow ID}
                            {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Setup VisionaryData IT Support Agent configuration (idempotent)';

    public function handle(): int
    {
        $companyId = (int) $this->option('company-id');
        $agentId = $this->option('agent-id');
        $flowId = $this->option('flow-id');
        $dryRun = $this->option('dry-run');

        $this->info('🔧 VisionaryData Agent Setup');
        $this->info('═══════════════════════════════════════════');
        $this->info("Company ID:  {$companyId}");
        $this->info("Agent ID:    {$agentId}");
        $this->info("Flow ID:     {$flowId}");
        $this->info("Dry Run:     " . ($dryRun ? 'YES' : 'NO'));
        $this->newLine();

        // Validate company exists
        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Company {$companyId} not found!");
            return self::FAILURE;
        }

        $this->info("✅ Company found: {$company->name}");

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be made');
            $this->newLine();
        }

        // Step 1: Register agent in retell_agents table
        $this->info('');
        $this->info('Step 1: Register Retell Agent');
        $this->info('─────────────────────────────────');

        $existingAgent = RetellAgent::where('agent_id', $agentId)
            ->where('company_id', $companyId)
            ->first();

        if ($existingAgent) {
            $this->info("  Agent already registered (ID: {$existingAgent->id})");

            if (!$dryRun) {
                DB::table('retell_agents')
                    ->where('id', $existingAgent->id)
                    ->update([
                        'name' => 'IT-Systemhaus Ticket Support Agent (v3.2)',
                        'is_active' => true,
                        'language' => 'de-DE',
                        'updated_at' => now(),
                    ]);
                $this->info('  Updated agent metadata');
            }
        } else {
            $this->info('  Agent not found — creating...');

            if (!$dryRun) {
                $newId = DB::table('retell_agents')->insertGetId([
                    'company_id' => $companyId,
                    'agent_id' => $agentId,
                    'retell_agent_id' => '',
                    'name' => 'IT-Systemhaus Ticket Support Agent (v3.2)',
                    'is_active' => true,
                    'language' => 'de-DE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $existingAgent = RetellAgent::find($newId);
                $this->info("  Created agent (DB ID: {$newId})");
            }
        }

        // Step 2: Set gateway_mode policy
        $this->newLine();
        $this->info('Step 2: Set Gateway Mode Policy');
        $this->info('─────────────────────────────────');

        $existingPolicy = PolicyConfiguration::where('company_id', $companyId)
            ->where('configurable_type', Company::class)
            ->where('configurable_id', $companyId)
            ->where('policy_type', PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE)
            ->first();

        if ($existingPolicy) {
            $currentMode = $existingPolicy->config['mode'] ?? 'unknown';
            $this->info("  Policy exists (mode: {$currentMode})");

            if ($currentMode !== 'service_desk') {
                $this->warn("  Mode is '{$currentMode}', updating to 'service_desk'");
                if (!$dryRun) {
                    $existingPolicy->update([
                        'config' => ['mode' => 'service_desk'],
                    ]);
                    $this->info('  Updated to service_desk');
                }
            } else {
                $this->info('  Already set to service_desk — no change needed');
            }
        } else {
            $this->info('  Policy not found — creating...');

            if (!$dryRun) {
                PolicyConfiguration::create([
                    'company_id' => $companyId,
                    'configurable_type' => Company::class,
                    'configurable_id' => $companyId,
                    'policy_type' => PolicyConfiguration::POLICY_TYPE_GATEWAY_MODE,
                    'config' => ['mode' => 'service_desk'],
                    'is_override' => false,
                ]);
                $this->info('  Created gateway_mode policy (service_desk)');
            }
        }

        // Step 3: Sync company retell_agent_id
        $this->newLine();
        $this->info('Step 3: Sync Company Agent ID');
        $this->info('─────────────────────────────────');

        $currentAgentId = $company->retell_agent_id;
        $this->info("  Current: {$currentAgentId}");
        $this->info("  Target:  {$agentId}");

        if ($currentAgentId === $agentId) {
            $this->info('  Already correct — no change needed');
        } else {
            if (!$dryRun) {
                $company->update([
                    'retell_agent_id' => $agentId,
                    'retell_conversation_flow_id' => $flowId,
                ]);
                $this->info('  Updated company retell_agent_id and flow_id');
            } else {
                $this->warn("  Would update from '{$currentAgentId}' to '{$agentId}'");
            }
        }

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════════');

        if ($dryRun) {
            $this->warn('DRY RUN complete — no changes were made');
        } else {
            $this->info('✅ VisionaryData agent setup complete!');

            Log::info('[VisionaryData] Agent setup completed', [
                'company_id' => $companyId,
                'agent_id' => $agentId,
                'flow_id' => $flowId,
            ]);
        }

        // Verification hints
        $this->newLine();
        $this->info('Verification:');
        $this->info('  1. Make a test call → Agent should ask triage questions after name');
        $this->info('  2. Check logs: grep "Routing to ServiceDeskHandler" storage/logs/laravel.log');
        $this->info('  3. After call: Check service_cases table for new entry');

        return self::SUCCESS;
    }
}
