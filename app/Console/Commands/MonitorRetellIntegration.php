<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RetellV2Service;
use App\Models\Call;
use App\Models\WebhookEvent;
use App\Models\Company;
use Carbon\Carbon;

class MonitorRetellIntegration extends Command
{
    protected $signature = 'retell:monitor 
                            {--live : Show live updates}
                            {--test : Test the integration}';
    
    protected $description = 'Monitor Retell.ai integration status and health';

    private RetellV2Service $retellService;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->retellService = app(RetellV2Service::class);
        
        // Set company context for tenant scoping in console command
        $company = Company::first();
        if ($company) {
            // Set trusted job context for console command
            app()->instance('current_company_id', $company->id);
            app()->instance('company_context_source', 'trusted_job');
            app()->instance('trusted_job_class', 'App\Console\Commands\MonitorRetellIntegration');
        }
        
        $this->info('🤖 Retell.ai Integration Monitor');
        $this->info('================================');
        
        // Check configuration
        $this->checkConfiguration();
        
        // Check webhook status
        $this->checkWebhookStatus();
        
        // Check recent calls
        $this->checkRecentCalls();
        
        // Check queue status
        $this->checkQueueStatus();
        
        if ($this->option('test')) {
            $this->testIntegration();
        }
        
        if ($this->option('live')) {
            $this->liveMonitoring();
        }
        
        return Command::SUCCESS;
    }
    
    private function checkConfiguration()
    {
        $this->info("\n📋 Configuration Status:");
        
        $configs = [
            'API Key' => config('services.retell.api_key') ? '✅ Set' : '❌ Missing',
            'Webhook Secret' => config('services.retell.webhook_secret') ? '✅ Set' : '❌ Missing',
            'Default Agent ID' => env('DEFAULT_RETELL_AGENT_ID') ? '✅ ' . env('DEFAULT_RETELL_AGENT_ID') : '❌ Missing',
            'Base URL' => config('services.retell.base', 'https://api.retellai.com'),
        ];
        
        foreach ($configs as $key => $value) {
            $this->info("   {$key}: {$value}");
        }
        
        // Test API connection
        try {
            $company = Company::first();
            if ($company) {
                // Test with a simple API call
                $defaultAgentId = env('DEFAULT_RETELL_AGENT_ID');
                if ($defaultAgentId) {
                    $agent = $this->retellService->getAgent($defaultAgentId);
                    if ($agent) {
                        $this->info("   API Connection: ✅ Working (Agent: " . ($agent['agent_name'] ?? 'Unknown') . ")");
                    } else {
                        $this->warn("   API Connection: ⚠️ Working but agent not found");
                    }
                } else {
                    $this->warn("   API Connection: ⚠️ Cannot test - no default agent ID");
                }
            }
        } catch (\Exception $e) {
            $this->error("   API Connection: ❌ Failed - " . $e->getMessage());
        }
    }
    
    private function checkWebhookStatus()
    {
        $this->info("\n🔔 Webhook Status:");
        
        // Check webhook URL in Retell dashboard
        $webhookUrl = config('app.url') . '/api/retell/webhook';
        $this->info("   Webhook URL: {$webhookUrl}");
        
        // Check recent webhook events
        $recentWebhooks = WebhookEvent::where('provider', 'retell')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();
        
        $failedWebhooks = WebhookEvent::where('provider', 'retell')
            ->where('status', 'failed')
            ->where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();
        
        $this->info("   Last 24h Events: {$recentWebhooks} total, {$failedWebhooks} failed");
        
        // Show last webhook
        $lastWebhook = WebhookEvent::where('provider', 'retell')
            ->latest()
            ->first();
        
        if ($lastWebhook) {
            $this->info("   Last Webhook: " . $lastWebhook->created_at->diffForHumans() . 
                       " - " . $lastWebhook->event_type . 
                       " (" . $lastWebhook->status . ")");
        } else {
            $this->warn("   No webhooks received yet");
        }
    }
    
    private function checkRecentCalls()
    {
        $this->info("\n📞 Recent Calls:");
        
        $recentCalls = Call::where('created_at', '>=', Carbon::now()->subHours(24))
            ->count();
        
        $this->info("   Calls in last 24h: {$recentCalls}");
        
        // Show last 5 calls
        $calls = Call::latest()->limit(5)->get();
        
        if ($calls->isEmpty()) {
            $this->warn("   No calls in database");
        } else {
            $this->table(
                ['Time', 'Phone', 'Duration', 'Status'],
                $calls->map(function ($call) {
                    return [
                        $call->created_at->diffForHumans(),
                        substr($call->phone_number ?? 'Unknown', -4),
                        $call->duration_minutes . ' min',
                        $call->status ?? 'unknown'
                    ];
                })
            );
        }
    }
    
    private function checkQueueStatus()
    {
        $this->info("\n⚙️ Queue Status:");
        
        // Check if Horizon is running
        $horizonStatus = trim(shell_exec('php artisan horizon:status 2>&1'));
        $isHorizonRunning = str_contains($horizonStatus, 'running');
        
        $this->info("   Laravel Horizon: " . ($isHorizonRunning ? '✅ Running' : '❌ Not running'));
        
        // Check webhook queue
        $pendingJobs = \DB::table('jobs')
            ->where('queue', 'webhooks')
            ->count();
        
        $failedJobs = \DB::table('failed_jobs')
            ->where('queue', 'webhooks')
            ->where('failed_at', '>=', Carbon::now()->subHours(24))
            ->count();
        
        $this->info("   Webhook Queue: {$pendingJobs} pending, {$failedJobs} failed (24h)");
    }
    
    private function testIntegration()
    {
        $this->info("\n🧪 Testing Integration:");
        
        // Test 1: Fetch agents
        try {
            $company = Company::first();
            if (!$company) {
                $this->error("   No company found in database");
                return;
            }
            
            $defaultAgentId = env('DEFAULT_RETELL_AGENT_ID');
            if ($defaultAgentId) {
                $agent = $this->retellService->getAgent($defaultAgentId);
                if ($agent) {
                    $this->info("   ✅ Agent Fetch: Success");
                    $this->info("      Agent: " . $agent['agent_name'] . " (ID: " . $agent['agent_id'] . ")");
                } else {
                    $this->error("   ❌ Agent Fetch: Agent not found");
                }
            } else {
                $this->error("   ❌ Agent Fetch: No default agent ID configured");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Agent Fetch: Failed - " . $e->getMessage());
        }
        
        // Test 2: Check phone number
        try {
            // Check for phone numbers in database
            $phoneNumbers = \App\Models\PhoneNumber::where('company_id', $company->id)->get();
            $this->info("   ✅ Phone Numbers: " . $phoneNumbers->count() . " found in database");
            
            if ($phoneNumbers->isNotEmpty()) {
                $phone = $phoneNumbers->first();
                $this->info("      Phone: " . $phone->number . " (Agent: " . ($phone->retell_agent_id ?? 'None') . ")");
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Phone Numbers: Failed - " . $e->getMessage());
        }
    }
    
    private function liveMonitoring()
    {
        $this->info("\n📊 Live Monitoring (Press Ctrl+C to stop)...\n");
        
        $lastWebhookId = WebhookEvent::where('provider', 'retell')->max('id') ?? 0;
        $lastCallId = Call::max('id') ?? 0;
        
        while (true) {
            // Check for new webhooks
            $newWebhooks = WebhookEvent::where('provider', 'retell')
                ->where('id', '>', $lastWebhookId)
                ->get();
            
            foreach ($newWebhooks as $webhook) {
                $this->info("[" . now()->format('H:i:s') . "] 🔔 Webhook: " . 
                           $webhook->event_type . " - " . $webhook->status);
                $lastWebhookId = $webhook->id;
            }
            
            // Check for new calls
            $newCalls = Call::where('id', '>', $lastCallId)->get();
            
            foreach ($newCalls as $call) {
                $this->info("[" . now()->format('H:i:s') . "] 📞 New Call: " . 
                           $call->phone_number . " (" . $call->duration_minutes . " min)");
                $lastCallId = $call->id;
            }
            
            sleep(2); // Check every 2 seconds
        }
    }
}