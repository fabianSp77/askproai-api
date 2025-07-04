<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\WebhookSecurityService;

class VerifyWebhookSecurity extends Command
{
    protected $signature = 'webhooks:verify-security';
    protected $description = 'Verify webhook security configuration';

    public function handle()
    {
        $this->info('🔒 Webhook Security Configuration Check');
        $this->info('=====================================');
        
        // Check environment variables
        $this->checkEnvironmentVariables();
        
        // Check middleware configuration
        $this->checkMiddlewareConfiguration();
        
        // Check route security
        $this->checkRouteSecurity();
        
        // Run configuration verification
        $issues = WebhookSecurityService::verifyConfiguration();
        
        if (empty($issues)) {
            $this->info('✅ All webhook security checks passed!');
            return Command::SUCCESS;
        }
        
        $this->error('❌ Security issues found:');
        foreach ($issues as $issue) {
            $this->error("   - {$issue}");
        }
        
        return Command::FAILURE;
    }
    
    private function checkEnvironmentVariables()
    {
        $this->info("\n📋 Environment Variables:");
        
        $vars = [
            'RETELL_WEBHOOK_SECRET' => env('RETELL_WEBHOOK_SECRET'),
            'CALCOM_WEBHOOK_SECRET' => env('CALCOM_WEBHOOK_SECRET'),
            'STRIPE_WEBHOOK_SECRET' => env('STRIPE_WEBHOOK_SECRET'),
            'WEBHOOK_REPLAY_WINDOW' => env('WEBHOOK_REPLAY_WINDOW', 300),
        ];
        
        foreach ($vars as $key => $value) {
            if (empty($value) && $key !== 'WEBHOOK_REPLAY_WINDOW') {
                $this->error("   ❌ {$key}: Not set");
            } else {
                $this->info("   ✅ {$key}: " . (str_contains($key, 'SECRET') ? '***' : $value));
            }
        }
    }
    
    private function checkMiddlewareConfiguration()
    {
        $this->info("\n🛡️ Middleware Configuration:");
        
        // Read middleware aliases from Kernel class using reflection
        $kernel = app(\App\Http\Kernel::class);
        $reflection = new \ReflectionClass($kernel);
        $property = $reflection->getProperty('middlewareAliases');
        $property->setAccessible(true);
        $middlewareAliases = $property->getValue($kernel);
        
        $required = [
            'verify.retell.signature' => 'Retell signature verification',
            'calcom.signature' => 'Cal.com signature verification',
            'verify.stripe.signature' => 'Stripe signature verification',
            'webhook.replay.protection' => 'Replay attack protection',
        ];
        
        foreach ($required as $alias => $description) {
            if (isset($middlewareAliases[$alias])) {
                $this->info("   ✅ {$description}: {$middlewareAliases[$alias]}");
            } else {
                $this->error("   ❌ {$description}: Not configured");
            }
        }
    }
    
    private function checkRouteSecurity()
    {
        $this->info("\n🚪 Route Security:");
        
        $routes = app('router')->getRoutes();
        $webhookRoutes = [];
        
        foreach ($routes as $route) {
            $uri = $route->uri();
            if (str_contains($uri, 'webhook')) {
                $middleware = $route->middleware();
                $webhookRoutes[] = [
                    'uri' => $uri,
                    'methods' => implode('|', $route->methods()),
                    'middleware' => $middleware,
                ];
            }
        }
        
        $this->table(
            ['URI', 'Methods', 'Security'],
            collect($webhookRoutes)->map(function ($route) {
                $security = [];
                
                // Check for signature verification
                foreach ($route['middleware'] as $middleware) {
                    if (str_contains($middleware, 'signature')) {
                        $security[] = '🔏 Signature';
                    }
                    if (str_contains($middleware, 'throttle')) {
                        $security[] = '⏱️ Rate Limit';
                    }
                    if (str_contains($middleware, 'replay')) {
                        $security[] = '🔄 Replay Protection';
                    }
                }
                
                return [
                    $route['uri'],
                    $route['methods'],
                    empty($security) ? '⚠️ No security' : implode(', ', $security),
                ];
            })
        );
    }
}