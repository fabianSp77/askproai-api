<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class WebhookSignatureMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhook:signature-mode 
                            {provider : The webhook provider (retell, calcom, stripe)}
                            {mode : The verification mode (strict, bypass, debug)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Switch webhook signature verification mode';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $provider = $this->argument('provider');
        $mode = $this->argument('mode');
        
        // Validate provider
        if (!in_array($provider, ['retell', 'calcom', 'stripe'])) {
            $this->error("Invalid provider. Choose from: retell, calcom, stripe");
            return 1;
        }
        
        // Validate mode
        if (!in_array($mode, ['strict', 'bypass', 'debug'])) {
            $this->error("Invalid mode. Choose from: strict, bypass, debug");
            return 1;
        }
        
        // Update routes file
        $routesFile = base_path('routes/api.php');
        $routes = File::get($routesFile);
        
        switch ($provider) {
            case 'retell':
                $middlewareMap = [
                    'strict' => 'verify.retell.signature',
                    'bypass' => 'verify.retell.signature.bypass',
                    'debug' => 'verify.retell.signature.debug'
                ];
                
                // Update the main webhook route
                $pattern = '/->middleware\(\[\'verify\.retell\.signature[^\']*\'\]\)/';
                $replacement = "->middleware(['{$middlewareMap[$mode]}'])";
                $routes = preg_replace($pattern, $replacement, $routes);
                
                break;
                
            case 'calcom':
                if ($mode === 'bypass') {
                    // Comment out the middleware
                    $routes = str_replace(
                        "->middleware(['calcom.signature']);",
                        "/*->middleware(['calcom.signature']);*/ // Bypassed"
                    );
                } else {
                    // Restore the middleware
                    $routes = str_replace(
                        "/*->middleware(['calcom.signature']);*/ // Bypassed",
                        "->middleware(['calcom.signature']);"
                    );
                }
                break;
                
            case 'stripe':
                if ($mode === 'bypass') {
                    // Remove middleware
                    $routes = str_replace(
                        "->middleware(['verify.stripe.signature', 'webhook.replay.protection'])",
                        "// ->middleware(['verify.stripe.signature', 'webhook.replay.protection']) // Bypassed"
                    );
                } else {
                    // Restore middleware
                    $routes = str_replace(
                        "// ->middleware(['verify.stripe.signature', 'webhook.replay.protection']) // Bypassed",
                        "->middleware(['verify.stripe.signature', 'webhook.replay.protection'])"
                    );
                }
                break;
        }
        
        // Save the updated routes file
        File::put($routesFile, $routes);
        
        // Clear route cache
        $this->call('route:clear');
        $this->call('config:clear');
        
        $this->info("✅ Webhook signature verification for {$provider} set to: {$mode} mode");
        
        // Show warning for non-strict modes
        if ($mode !== 'strict') {
            $this->warn("⚠️  WARNING: {$mode} mode is not secure for production use!");
            $this->warn("   Remember to switch back to 'strict' mode before deploying.");
        }
        
        // Show current status
        $this->info("\nCurrent webhook verification modes:");
        $this->table(
            ['Provider', 'Mode', 'Security'],
            [
                ['Retell', $this->getCurrentMode('retell', $routes), $this->getSecurityLevel($this->getCurrentMode('retell', $routes))],
                ['Cal.com', $this->getCurrentMode('calcom', $routes), $this->getSecurityLevel($this->getCurrentMode('calcom', $routes))],
                ['Stripe', $this->getCurrentMode('stripe', $routes), $this->getSecurityLevel($this->getCurrentMode('stripe', $routes))],
            ]
        );
        
        return 0;
    }
    
    private function getCurrentMode(string $provider, string $routes): string
    {
        switch ($provider) {
            case 'retell':
                if (str_contains($routes, 'verify.retell.signature.bypass')) {
                    return 'bypass';
                } elseif (str_contains($routes, 'verify.retell.signature.debug')) {
                    return 'debug';
                } else {
                    return 'strict';
                }
                
            case 'calcom':
                return str_contains($routes, '/*->middleware') ? 'bypass' : 'strict';
                
            case 'stripe':
                return str_contains($routes, '// ->middleware') ? 'bypass' : 'strict';
                
            default:
                return 'unknown';
        }
    }
    
    private function getSecurityLevel(string $mode): string
    {
        return match($mode) {
            'strict' => '🟢 Secure',
            'debug' => '🟡 Debug (logs enabled)',
            'bypass' => '🔴 Insecure',
            default => '❓ Unknown'
        };
    }
}