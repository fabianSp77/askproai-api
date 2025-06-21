<?php

namespace App\Services\Setup;

use App\Models\Company;
use App\Models\Branch;
use App\Services\Provisioning\RetellAgentProvisioner;
use App\Services\PromptTemplateService;
use Illuminate\Support\Facades\Log;

class RetellSetupService
{
    protected RetellAgentProvisioner $provisioner;
    protected PromptTemplateService $promptService;

    public function __construct()
    {
        $this->provisioner = app(RetellAgentProvisioner::class);
        $this->promptService = app(PromptTemplateService::class);
    }

    /**
     * Provision Retell AI agent for company
     */
    public function provisionAgent(Company $company, array $branches, array $config): void
    {
        try {
            // Set API key
            if (isset($config['api_key'])) {
                $company->update([
                    'retell_api_key' => encrypt($config['api_key']),
                    'retell_integration_active' => true,
                ]);
            }

            // Create agent for each branch
            foreach ($branches as $branch) {
                $this->provisionBranchAgent($branch, $config);
            }

            Log::info('Retell agents provisioned successfully', [
                'company_id' => $company->id,
                'branches_count' => count($branches)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to provision Retell agents', [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Provision agent for specific branch
     */
    protected function provisionBranchAgent(Branch $branch, array $config): void
    {
        // Generate prompt from template
        $prompt = $this->promptService->renderPrompt(
            $branch,
            $config['industry'] ?? 'generic',
            [
                'agent_name' => $config['agent_name'] ?? $branch->name . ' Assistant',
                'voice_id' => $config['voice_id'] ?? 'sarah',
                'language' => $config['language'] ?? 'de-DE',
            ]
        );

        // Provision agent
        $agentConfig = [
            'agent_name' => $config['agent_name'] ?? $branch->name . ' AI',
            'voice_id' => $config['voice_id'] ?? 'sarah',
            'language' => $config['language'] ?? 'de-DE',
            'prompt' => $prompt,
            'webhook_url' => config('app.url') . '/api/retell/webhook',
            'max_call_duration' => 1800, // 30 minutes
            'response_delay' => 500,
            'llm_model' => 'gpt-4',
        ];

        $result = $this->provisioner->provisionAgent($branch, $agentConfig);

        // Save agent ID to branch
        $branch->update([
            'retell_agent_id' => $result['agent_id'] ?? null,
            'retell_phone_number_id' => $result['phone_number_id'] ?? null,
            'retell_integration_active' => true,
        ]);

        Log::info('Retell agent provisioned for branch', [
            'branch_id' => $branch->id,
            'agent_id' => $result['agent_id'] ?? 'unknown'
        ]);
    }
}