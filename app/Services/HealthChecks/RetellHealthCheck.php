<?php

namespace App\Services\HealthChecks;

use App\Contracts\IntegrationHealthCheck;
use App\Contracts\HealthCheckResult;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Services\RetellService;
use App\Services\RetellV2Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RetellHealthCheck implements IntegrationHealthCheck
{
    protected array $diagnostics = [];
    protected float $startTime;
    
    public function __construct(
        protected ?RetellService $retellService = null,
        protected ?RetellV2Service $retellV2Service = null
    ) {
        // Services will be injected or resolved from container
        $this->retellService = $retellService ?? app(RetellService::class);
        $this->retellV2Service = $retellV2Service ?? app(RetellV2Service::class);
    }
    
    /**
     * Run the health check for a specific company
     */
    public function check(Company $company): HealthCheckResult
    {
        $this->startTime = microtime(true);
        $this->diagnostics = [];
        
        $details = [];
        $issues = [];
        $suggestions = [];
        $metrics = [];
        
        try {
            // 1. Check if company has Retell API key
            $apiKeyCheck = $this->checkApiKey($company);
            $details['api_key'] = $apiKeyCheck;
            if (!$apiKeyCheck['valid']) {
                $issues[] = 'No valid Retell API key configured';
                $suggestions[] = 'Add your Retell API key in company settings';
            }
            
            // 2. Check API connectivity
            if ($apiKeyCheck['valid']) {
                $apiCheck = $this->checkApiConnectivity($company);
                $details['api_connectivity'] = $apiCheck;
                if (!$apiCheck['success']) {
                    $issues[] = 'Cannot connect to Retell API';
                    $suggestions[] = 'Check API key validity and network connectivity';
                }
            }
            
            // 3. Check webhook configuration
            $webhookCheck = $this->checkWebhookConfiguration($company);
            $details['webhook_config'] = $webhookCheck;
            if (!$webhookCheck['all_configured']) {
                $issues[] = "Some agents have incorrect webhook URLs";
                foreach ($webhookCheck['issues'] as $issue) {
                    $suggestions[] = $issue;
                }
            }
            
            // 4. Check active agents
            $agentCheck = $this->checkActiveAgents($company);
            $details['active_agents'] = $agentCheck;
            $metrics['active_agents'] = $agentCheck['count'];
            if ($agentCheck['count'] === 0) {
                $issues[] = 'No active Retell agents configured';
                $suggestions[] = 'Create at least one agent for each branch';
            }
            
            // 5. Check recent call success rate
            $callSuccessRate = $this->checkCallSuccessRate($company);
            $details['call_success_rate'] = $callSuccessRate;
            $metrics['call_success_rate'] = $callSuccessRate['rate'];
            if ($callSuccessRate['rate'] < 0.8 && $callSuccessRate['total'] > 10) {
                $issues[] = sprintf('Low call success rate: %.0f%%', $callSuccessRate['rate'] * 100);
                $suggestions[] = 'Review failed calls and agent configuration';
            }
            
            // 6. Check agent response times
            $responseTimeCheck = $this->checkAgentResponseTimes($company);
            $details['response_times'] = $responseTimeCheck;
            $metrics['avg_response_time'] = $responseTimeCheck['average'];
            if ($responseTimeCheck['average'] > 2000) {
                $issues[] = 'Slow agent response times detected';
                $suggestions[] = 'Optimize agent prompts and reduce complexity';
            }
            
            // Determine overall status
            $status = $this->determineOverallStatus($details, $issues);
            $message = $this->buildStatusMessage($status, $details, $issues);
            
        } catch (\Exception $e) {
            Log::error('Retell health check failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return HealthCheckResult::unhealthy(
                'Health check failed: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
                ['Contact support if this persists']
            );
        }
        
        $responseTime = microtime(true) - $this->startTime;
        
        return new HealthCheckResult(
            status: $status,
            message: $message,
            details: $details,
            metrics: $metrics,
            responseTime: $responseTime,
            issues: $issues,
            suggestions: $suggestions
        );
    }
    
    /**
     * Get service name
     */
    public function getName(): string
    {
        return 'Retell.ai';
    }
    
    /**
     * Get check priority (higher = more important)
     */
    public function getPriority(): int
    {
        return 100; // Very high priority as it's critical for phone functionality
    }
    
    /**
     * Whether this check is critical for operation
     */
    public function isCritical(): bool
    {
        return true; // Phone system is critical
    }
    
    /**
     * Get detailed diagnostics
     */
    public function getDiagnostics(): array
    {
        return $this->diagnostics;
    }
    
    /**
     * Get suggested fixes for common issues
     */
    public function getSuggestedFixes(array $issues): array
    {
        $fixes = [];
        
        foreach ($issues as $issue) {
            $fixes[] = match(true) {
                str_contains($issue, 'API key') => [
                    'issue' => $issue,
                    'fix' => 'Go to Settings → Integrations → Retell.ai and add your API key',
                    'docs' => 'https://docs.retellai.com/api-keys',
                ],
                str_contains($issue, 'webhook') => [
                    'issue' => $issue,
                    'fix' => 'Run: php artisan retell:sync-webhooks',
                    'docs' => 'https://docs.retellai.com/webhooks',
                ],
                str_contains($issue, 'agents') => [
                    'issue' => $issue,
                    'fix' => 'Go to each branch and click "Create AI Agent"',
                    'docs' => '/admin/help/retell-agents',
                ],
                str_contains($issue, 'success rate') => [
                    'issue' => $issue,
                    'fix' => 'Review call logs and adjust agent prompts',
                    'docs' => '/admin/calls?status=failed',
                ],
                default => [
                    'issue' => $issue,
                    'fix' => 'Check system logs for more details',
                    'docs' => '/admin/logs',
                ],
            };
        }
        
        return $fixes;
    }
    
    /**
     * Run automatic fixes if possible
     */
    public function attemptAutoFix(Company $company, array $issues): bool
    {
        $fixed = false;
        
        foreach ($issues as $issue) {
            try {
                if (str_contains($issue, 'webhook')) {
                    $fixed = $this->fixWebhookUrls($company) || $fixed;
                }
                
                if (str_contains($issue, 'agent') && str_contains($issue, 'No active')) {
                    // We can't auto-create agents without more config
                    $this->diagnostics[] = 'Cannot auto-create agents - manual setup required';
                }
                
            } catch (\Exception $e) {
                Log::warning('Auto-fix failed for Retell issue', [
                    'company_id' => $company->id,
                    'issue' => $issue,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $fixed;
    }
    
    /**
     * Check if company has API key configured
     */
    protected function checkApiKey(Company $company): array
    {
        $hasKey = !empty($company->retell_api_key);
        
        $this->diagnostics[] = $hasKey 
            ? 'API key is configured' 
            : 'API key is missing';
        
        return [
            'valid' => $hasKey,
            'configured_at' => $hasKey ? $company->updated_at->toDateTimeString() : null,
        ];
    }
    
    /**
     * Check API connectivity
     */
    protected function checkApiConnectivity(Company $company): array
    {
        try {
            $cacheKey = "retell_api_check_{$company->id}";
            
            return Cache::remember($cacheKey, 300, function() use ($company) {
                $startTime = microtime(true);
                
                // Try to list agents as a connectivity test
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . decrypt($company->retell_api_key),
                    'Accept' => 'application/json',
                ])
                ->timeout(5)
                ->get('https://api.retellai.com/list-agents');
                
                $responseTime = (microtime(true) - $startTime) * 1000; // Convert to ms
                
                $this->diagnostics[] = sprintf(
                    'API connectivity test completed in %.0fms with status %d',
                    $responseTime,
                    $response->status()
                );
                
                return [
                    'success' => $response->successful(),
                    'status_code' => $response->status(),
                    'response_time_ms' => round($responseTime),
                    'error' => $response->failed() ? $response->body() : null,
                ];
            });
            
        } catch (\Exception $e) {
            $this->diagnostics[] = 'API connectivity test failed: ' . $e->getMessage();
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time_ms' => null,
            ];
        }
    }
    
    /**
     * Check webhook configuration for all agents
     */
    protected function checkWebhookConfiguration(Company $company): array
    {
        $branches = $company->branches()
            ->where('is_active', true)
            ->whereNotNull('retell_agent_id')
            ->get();
        
        $totalAgents = $branches->count();
        $configuredCorrectly = 0;
        $issues = [];
        $expectedUrl = config('app.url') . '/api/retell/webhook';
        
        foreach ($branches as $branch) {
            try {
                // Get agent details from Retell
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . decrypt($company->retell_api_key),
                ])
                ->get("https://api.retellai.com/get-agent/{$branch->retell_agent_id}");
                
                if ($response->successful()) {
                    $agent = $response->json();
                    $webhookUrl = $agent['webhook_url'] ?? '';
                    
                    if (empty($webhookUrl)) {
                        $issues[] = "Branch '{$branch->name}': No webhook URL configured";
                    } elseif (!str_starts_with($webhookUrl, config('app.url'))) {
                        $issues[] = "Branch '{$branch->name}': Webhook points to wrong domain";
                    } else {
                        $configuredCorrectly++;
                    }
                } else {
                    $issues[] = "Branch '{$branch->name}': Cannot fetch agent details";
                }
                
            } catch (\Exception $e) {
                $issues[] = "Branch '{$branch->name}': " . $e->getMessage();
            }
        }
        
        $this->diagnostics[] = sprintf(
            'Webhook check: %d/%d agents configured correctly',
            $configuredCorrectly,
            $totalAgents
        );
        
        return [
            'total' => $totalAgents,
            'configured' => $configuredCorrectly,
            'all_configured' => $configuredCorrectly === $totalAgents && $totalAgents > 0,
            'issues' => $issues,
            'expected_url' => $expectedUrl,
        ];
    }
    
    /**
     * Check active agents
     */
    protected function checkActiveAgents(Company $company): array
    {
        $activeBranches = $company->branches()
            ->where('is_active', true)
            ->count();
        
        $branchesWithAgents = $company->branches()
            ->where('is_active', true)
            ->whereNotNull('retell_agent_id')
            ->count();
        
        $this->diagnostics[] = sprintf(
            'Active agents: %d branches with agents out of %d active branches',
            $branchesWithAgents,
            $activeBranches
        );
        
        return [
            'count' => $branchesWithAgents,
            'total_branches' => $activeBranches,
            'coverage_percentage' => $activeBranches > 0 
                ? round(($branchesWithAgents / $activeBranches) * 100) 
                : 0,
        ];
    }
    
    /**
     * Check recent call success rate
     */
    protected function checkCallSuccessRate(Company $company): array
    {
        $since = now()->subDays(7);
        
        $totalCalls = Call::where('company_id', $company->id)
            ->where('created_at', '>=', $since)
            ->count();
        
        $successfulCalls = Call::where('company_id', $company->id)
            ->where('created_at', '>=', $since)
            ->whereIn('call_status', ['completed', 'transferred'])
            ->count();
        
        $failedCalls = Call::where('company_id', $company->id)
            ->where('created_at', '>=', $since)
            ->whereIn('call_status', ['failed', 'error', 'no_answer'])
            ->count();
        
        $rate = $totalCalls > 0 ? $successfulCalls / $totalCalls : 1.0;
        
        $this->diagnostics[] = sprintf(
            'Call success rate (7 days): %.1f%% (%d successful, %d failed, %d total)',
            $rate * 100,
            $successfulCalls,
            $failedCalls,
            $totalCalls
        );
        
        return [
            'rate' => $rate,
            'successful' => $successfulCalls,
            'failed' => $failedCalls,
            'total' => $totalCalls,
            'period_days' => 7,
        ];
    }
    
    /**
     * Check agent response times
     */
    protected function checkAgentResponseTimes(Company $company): array
    {
        $recentCalls = Call::where('company_id', $company->id)
            ->where('created_at', '>=', now()->subDays(1))
            ->whereNotNull('metadata->latency')
            ->get();
        
        if ($recentCalls->isEmpty()) {
            return [
                'average' => 0,
                'min' => 0,
                'max' => 0,
                'sample_size' => 0,
            ];
        }
        
        $latencies = $recentCalls->map(fn($call) => $call->metadata['latency'] ?? 0)
            ->filter(fn($l) => $l > 0);
        
        $average = $latencies->average();
        $min = $latencies->min();
        $max = $latencies->max();
        
        $this->diagnostics[] = sprintf(
            'Agent response times (24h): avg=%.0fms, min=%.0fms, max=%.0fms (n=%d)',
            $average,
            $min,
            $max,
            $latencies->count()
        );
        
        return [
            'average' => round($average),
            'min' => round($min),
            'max' => round($max),
            'sample_size' => $latencies->count(),
        ];
    }
    
    /**
     * Determine overall status based on check results
     */
    protected function determineOverallStatus(array $details, array $issues): string
    {
        // Critical failures
        if (!($details['api_key']['valid'] ?? false)) {
            return HealthCheckResult::STATUS_UNHEALTHY;
        }
        
        if (!($details['api_connectivity']['success'] ?? false)) {
            return HealthCheckResult::STATUS_UNHEALTHY;
        }
        
        if (($details['active_agents']['count'] ?? 0) === 0) {
            return HealthCheckResult::STATUS_UNHEALTHY;
        }
        
        // Degraded conditions
        if (!($details['webhook_config']['all_configured'] ?? true)) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        if (($details['call_success_rate']['rate'] ?? 1) < 0.8) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        if (($details['response_times']['average'] ?? 0) > 3000) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        // Check if there are any issues
        if (count($issues) > 0) {
            return HealthCheckResult::STATUS_DEGRADED;
        }
        
        return HealthCheckResult::STATUS_HEALTHY;
    }
    
    /**
     * Build status message
     */
    protected function buildStatusMessage(string $status, array $details, array $issues): string
    {
        if ($status === HealthCheckResult::STATUS_HEALTHY) {
            $agentCount = $details['active_agents']['count'] ?? 0;
            $successRate = ($details['call_success_rate']['rate'] ?? 1) * 100;
            
            return sprintf(
                'Retell.ai is healthy: %d active agents, %.0f%% call success rate',
                $agentCount,
                $successRate
            );
        }
        
        if ($status === HealthCheckResult::STATUS_DEGRADED) {
            return 'Retell.ai is operational but needs attention: ' . 
                   implode(', ', array_slice($issues, 0, 2));
        }
        
        return 'Retell.ai integration is not functioning: ' . 
               ($issues[0] ?? 'Unknown error');
    }
    
    /**
     * Fix webhook URLs for all agents
     */
    protected function fixWebhookUrls(Company $company): bool
    {
        $fixed = false;
        $expectedUrl = config('app.url') . '/api/retell/webhook';
        
        $branches = $company->branches()
            ->where('is_active', true)
            ->whereNotNull('retell_agent_id')
            ->get();
        
        foreach ($branches as $branch) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . decrypt($company->retell_api_key),
                ])
                ->patch("https://api.retellai.com/update-agent/{$branch->retell_agent_id}", [
                    'webhook_url' => $expectedUrl,
                ]);
                
                if ($response->successful()) {
                    $fixed = true;
                    $this->diagnostics[] = "Fixed webhook URL for branch: {$branch->name}";
                }
                
            } catch (\Exception $e) {
                Log::warning('Failed to fix webhook URL', [
                    'branch_id' => $branch->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $fixed;
    }
}