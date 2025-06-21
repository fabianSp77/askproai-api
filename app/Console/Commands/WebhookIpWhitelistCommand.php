<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookIpWhitelistCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:ip-whitelist 
                            {action : list|add|remove|test}
                            {ip? : IP address to add/remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage webhook IP whitelist';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $ip = $this->argument('ip');

        switch ($action) {
            case 'list':
                $this->listWhitelistedIps();
                break;
                
            case 'add':
                if (!$ip) {
                    $this->error('IP address is required for add action');
                    return 1;
                }
                $this->addIpToWhitelist($ip);
                break;
                
            case 'remove':
                if (!$ip) {
                    $this->error('IP address is required for remove action');
                    return 1;
                }
                $this->removeIpFromWhitelist($ip);
                break;
                
            case 'test':
                $this->testCurrentIp();
                break;
                
            default:
                $this->error("Invalid action. Use: list, add, remove, or test");
                return 1;
        }

        return 0;
    }

    /**
     * List all whitelisted IPs
     */
    private function listWhitelistedIps()
    {
        $this->info('=== Webhook IP Whitelist ===');
        
        // Get configured IPs from environment
        $envIps = array_filter(array_map('trim', explode(',', env('WEBHOOK_IP_WHITELIST', ''))));
        
        if (!empty($envIps)) {
            $this->info("\nEnvironment configured IPs:");
            foreach ($envIps as $ip) {
                $this->line("  • $ip");
            }
        }
        
        // Get Retell known IPs
        $retellIps = config('webhook.retell.known_ips', []);
        if (!empty($retellIps)) {
            $this->info("\nRetell.ai known IPs:");
            foreach ($retellIps as $ip) {
                $this->line("  • $ip");
            }
        }
        
        // Get Cal.com known IPs
        $calcomIps = config('webhook.calcom.known_ips', []);
        if (!empty($calcomIps)) {
            $this->info("\nCal.com known IPs:");
            foreach ($calcomIps as $ip) {
                $this->line("  • $ip");
            }
        }
        
        $total = count($envIps) + count($retellIps) + count($calcomIps);
        $this->info("\nTotal whitelisted IPs: $total");
    }

    /**
     * Add IP to whitelist
     */
    private function addIpToWhitelist($ip)
    {
        // Validate IP format
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->error("Invalid IP address format: $ip");
            return;
        }
        
        // Get current whitelist from env
        $currentIps = array_filter(array_map('trim', explode(',', env('WEBHOOK_IP_WHITELIST', ''))));
        
        // Check if already exists
        if (in_array($ip, $currentIps)) {
            $this->warn("IP $ip is already whitelisted");
            return;
        }
        
        // Add to list
        $currentIps[] = $ip;
        $newValue = implode(',', $currentIps);
        
        $this->info("Added $ip to whitelist");
        $this->warn("Update your .env file:");
        $this->line("WEBHOOK_IP_WHITELIST=$newValue");
        
        // Log the addition
        Log::info('IP added to webhook whitelist', [
            'ip' => $ip,
            'by' => 'console command'
        ]);
    }

    /**
     * Remove IP from whitelist
     */
    private function removeIpFromWhitelist($ip)
    {
        // Get current whitelist from env
        $currentIps = array_filter(array_map('trim', explode(',', env('WEBHOOK_IP_WHITELIST', ''))));
        
        // Check if exists
        if (!in_array($ip, $currentIps)) {
            $this->warn("IP $ip is not in the whitelist");
            return;
        }
        
        // Remove from list
        $currentIps = array_diff($currentIps, [$ip]);
        $newValue = implode(',', $currentIps);
        
        $this->info("Removed $ip from whitelist");
        $this->warn("Update your .env file:");
        $this->line("WEBHOOK_IP_WHITELIST=$newValue");
        
        // Log the removal
        Log::info('IP removed from webhook whitelist', [
            'ip' => $ip,
            'by' => 'console command'
        ]);
    }

    /**
     * Test current server's external IP
     */
    private function testCurrentIp()
    {
        $this->info('Testing current server IP...');
        
        try {
            // Get external IP
            $response = Http::get('https://api.ipify.org?format=json');
            $externalIp = $response->json()['ip'] ?? 'unknown';
            
            $this->info("Server's external IP: $externalIp");
            
            // Check if it's whitelisted
            $allIps = array_merge(
                array_filter(array_map('trim', explode(',', env('WEBHOOK_IP_WHITELIST', '')))),
                config('webhook.retell.known_ips', []),
                config('webhook.calcom.known_ips', [])
            );
            
            if (in_array($externalIp, $allIps)) {
                $this->info("✓ This IP is whitelisted");
            } else {
                $this->warn("✗ This IP is NOT whitelisted");
                $this->line("To whitelist this server, run:");
                $this->line("php artisan webhook:ip-whitelist add $externalIp");
            }
            
        } catch (\Exception $e) {
            $this->error("Failed to determine external IP: " . $e->getMessage());
        }
    }
}