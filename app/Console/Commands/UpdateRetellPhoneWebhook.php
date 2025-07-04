<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PhoneNumber;
use App\Models\Company;
use Illuminate\Support\Facades\Http;

class UpdateRetellPhoneWebhook extends Command
{
    protected $signature = 'retell:update-phone-webhook 
                            {phone? : The phone number to update (or last digits)}
                            {--all : Update all phone numbers}
                            {--webhook-url= : Custom webhook URL (default: system webhook)}';

    protected $description = 'Update webhook URL for Retell phone numbers';

    public function handle()
    {
        $phoneInput = $this->argument('phone');
        $updateAll = $this->option('all');
        $customWebhookUrl = $this->option('webhook-url');
        
        // Default webhook URL
        $webhookUrl = $customWebhookUrl ?? 'https://api.askproai.de/api/retell/webhook';
        
        $this->info("Webhook URL to set: {$webhookUrl}");

        if ($updateAll) {
            $phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                                      ->where('is_active', true)
                                      ->whereNotNull('retell_agent_id')
                                      ->get();
        } elseif ($phoneInput) {
            $phoneNumbers = PhoneNumber::withoutGlobalScope(\App\Scopes\TenantScope::class)
                                      ->where('number', 'LIKE', "%{$phoneInput}")
                                      ->orWhere('number', $phoneInput)
                                      ->get();
        } else {
            $this->error('Please specify a phone number or use --all flag');
            return 1;
        }

        if ($phoneNumbers->isEmpty()) {
            $this->error('No phone numbers found');
            return 1;
        }

        $this->info("Found {$phoneNumbers->count()} phone number(s) to update");

        foreach ($phoneNumbers as $phone) {
            $this->updatePhoneWebhook($phone, $webhookUrl);
        }

        return 0;
    }

    private function updatePhoneWebhook(PhoneNumber $phone, string $webhookUrl)
    {
        $this->info("\nUpdating phone: {$phone->number}");
        
        $company = Company::find($phone->company_id);
        if (!$company || !$company->retell_api_key) {
            $this->error('Company API key not found');
            return;
        }

        try {
            // First, get current phone configuration from Retell
            $listResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $company->retell_api_key,
            ])->get('https://api.retellai.com/list-phone-numbers');

            if (!$listResponse->successful()) {
                $this->error("Failed to list phone numbers: " . $listResponse->status());
                return;
            }

            $phoneNumbers = $listResponse->json();
            $retellPhone = null;
            
            // Find this phone number in Retell's list
            foreach ($phoneNumbers as $p) {
                if (isset($p['phone_number']) && $p['phone_number'] === $phone->number) {
                    $retellPhone = $p;
                    break;
                }
            }

            if (!$retellPhone) {
                $this->error("Phone number not found in Retell: {$phone->number}");
                return;
            }

            $this->info("Current configuration:");
            $this->line("- Agent ID: " . ($retellPhone['inbound_agent_id'] ?? 'None'));
            $this->line("- Current Webhook: " . ($retellPhone['inbound_webhook_url'] ?? 'None'));
            
            // Check if we need to use a different API endpoint
            // Retell API might require updating via phone number ID
            if (isset($retellPhone['phone_number_id'])) {
                // Try to update the phone number configuration
                $updateData = [
                    'inbound_webhook_url' => $webhookUrl,
                ];
                
                // If agent is not set, also set it
                if (!isset($retellPhone['inbound_agent_id']) && $phone->retell_agent_id) {
                    $updateData['inbound_agent_id'] = $phone->retell_agent_id;
                    $this->info("Also setting agent ID: {$phone->retell_agent_id}");
                }
                
                $phoneId = $retellPhone['phone_number_id'];
                $this->info("Updating phone number ID: {$phoneId}");
                
                // Update phone number
                $updateResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $company->retell_api_key,
                ])->patch("https://api.retellai.com/update-phone-number/{$phoneId}", $updateData);
                
                if ($updateResponse->successful()) {
                    $this->info("✓ Successfully updated webhook URL!");
                    
                    // Verify the update
                    $this->verifyUpdate($company, $phone->number, $webhookUrl);
                } else {
                    $this->error("Failed to update: " . $updateResponse->status());
                    $this->error("Response: " . $updateResponse->body());
                }
            } else {
                $this->warn("Phone number ID not found, cannot update via API");
                $this->warn("You may need to update this manually in Retell dashboard");
            }
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
    
    private function verifyUpdate($company, $phoneNumber, $expectedWebhook)
    {
        $this->info("\nVerifying update...");
        
        // Wait a moment for changes to propagate
        sleep(2);
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $company->retell_api_key,
        ])->get('https://api.retellai.com/list-phone-numbers');
        
        if ($response->successful()) {
            $phoneNumbers = $response->json();
            
            foreach ($phoneNumbers as $p) {
                if (isset($p['phone_number']) && $p['phone_number'] === $phoneNumber) {
                    $currentWebhook = $p['inbound_webhook_url'] ?? 'None';
                    
                    if ($currentWebhook === $expectedWebhook) {
                        $this->info("✓ Webhook URL verified: {$currentWebhook}");
                    } else {
                        $this->error("✗ Webhook URL mismatch!");
                        $this->error("Expected: {$expectedWebhook}");
                        $this->error("Actual: {$currentWebhook}");
                    }
                    
                    return;
                }
            }
            
            $this->error("Phone number not found during verification");
        }
    }
}