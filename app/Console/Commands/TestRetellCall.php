<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PhoneNumber;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TestRetellCall extends Command
{
    protected $signature = 'retell:test-call 
                            {phone : The phone number to test (or last digits)}
                            {--from= : The number to call from}
                            {--duration=30 : Call duration in seconds}';

    protected $description = 'Initiate a test call through Retell API';

    public function handle()
    {
        $phoneInput = $this->argument('phone');
        $fromNumber = $this->option('from') ?? '+491234567890'; // Default test number
        $duration = (int) $this->option('duration');

        // Find phone number
        $phone = PhoneNumber::where('number', 'LIKE', "%{$phoneInput}")
                           ->orWhere('number', $phoneInput)
                           ->first();

        if (!$phone) {
            $this->error("Phone number not found: {$phoneInput}");
            return 1;
        }

        if (!$phone->is_active) {
            $this->warn("Phone number is not active: {$phone->number}");
            if (!$this->confirm('Continue anyway?')) {
                return 1;
            }
        }

        if (!$phone->retell_agent_id) {
            $this->error("Phone number has no agent assigned: {$phone->number}");
            return 1;
        }

        // Get company API key
        $company = Company::find($phone->company_id);
        if (!$company || !$company->retell_api_key) {
            $this->error('Company API key not found');
            return 1;
        }

        $this->info("Testing call to: {$phone->number}");
        $this->info("Using agent: {$phone->retell_agent_id}");
        $this->info("From number: {$fromNumber}");

        try {
            // First, check if agent exists and is active
            $agentResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->get("https://api.retellai.com/get-agent/{$phone->retell_agent_id}");

            if (!$agentResponse->successful()) {
                $this->error("Agent not found or error: " . $agentResponse->status());
                return 1;
            }

            $agent = $agentResponse->json();
            if (isset($agent['deleted']) && $agent['deleted']) {
                $this->error("Agent is deleted in Retell!");
                return 1;
            }

            $this->info("Agent found: " . ($agent['agent_name'] ?? 'Unnamed'));

            // Create a test call
            $callData = [
                'agent_id' => $phone->retell_agent_id,
                'from_number' => $fromNumber,
                'to_number' => $phone->number,
                'metadata' => [
                    'test_call' => true,
                    'initiated_by' => 'console_command',
                    'timestamp' => now()->toIso8601String(),
                ],
            ];

            $this->info('Creating call...');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->post('https://api.retellai.com/create-call', $callData);

            if ($response->successful()) {
                $result = $response->json();
                $this->info('✓ Call created successfully!');
                $this->info('Call ID: ' . ($result['call_id'] ?? 'N/A'));
                
                if (isset($result['access_token'])) {
                    $this->info('Access Token: ' . substr($result['access_token'], 0, 20) . '...');
                }

                // Log the test call
                Log::info('Test call initiated', [
                    'phone_number' => $phone->number,
                    'agent_id' => $phone->retell_agent_id,
                    'call_id' => $result['call_id'] ?? null,
                    'response' => $result,
                ]);

                $this->info("\nMonitor the call in:");
                $this->info("- Laravel logs: tail -f storage/logs/laravel.log");
                $this->info("- Webhook events table");
                $this->info("- Calls table");
                
                // Wait and check for webhook
                if ($this->confirm('Wait for webhook confirmation?', true)) {
                    $this->info('Waiting 10 seconds for webhook...');
                    sleep(10);
                    
                    // Check if webhook arrived
                    $webhook = \DB::table('webhook_events')
                        ->where('created_at', '>', now()->subSeconds(15))
                        ->where('event_type', 'LIKE', '%call%')
                        ->orderBy('created_at', 'desc')
                        ->first();
                        
                    if ($webhook) {
                        $this->info('✓ Webhook received: ' . $webhook->event_type);
                    } else {
                        $this->warn('⚠ No webhook received yet');
                    }
                }

            } else {
                $this->error('Failed to create call: ' . $response->status());
                $this->error('Response: ' . $response->body());
                
                // Common error explanations
                if ($response->status() === 400) {
                    $this->warn('Bad Request - Check agent configuration and phone numbers');
                } elseif ($response->status() === 401) {
                    $this->error('Unauthorized - Check API key');
                } elseif ($response->status() === 404) {
                    $this->error('Not Found - Agent might not exist');
                }
            }

        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Test call failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }

        return 0;
    }
}