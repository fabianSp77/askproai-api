<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Illuminate\Support\Facades\Blade;

class LivewirePerformanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Apply performance optimizations to Livewire components
        $this->optimizeLivewirePolling();
        $this->injectPerformanceMonitoring();
        $this->registerPerformanceDirectives();
    }
    
    protected function optimizeLivewirePolling(): void
    {
        // Hook into Livewire component rendering
        Livewire::listen('component.dehydrate', function ($component, $response) {
            $config = config('livewire-performance.component_settings');
            $componentName = class_basename($component);
            
            // Check if polling should be disabled
            if (in_array($componentName, $config['disable_polling'] ?? [])) {
                // Remove any wire:poll attributes from the response
                if (isset($response->effects['html'])) {
                    $response->effects['html'] = preg_replace(
                        '/wire:poll(?:\.[^"\']*)?=["\'][^"\']*["\']/', 
                        '', 
                        $response->effects['html']
                    );
                }
            }
            
            // Force specific intervals
            if (isset($config['force_intervals'][$componentName])) {
                $interval = $config['force_intervals'][$componentName];
                if (isset($response->effects['html'])) {
                    $response->effects['html'] = preg_replace(
                        '/wire:poll\.?\d*s?=["\'][^"\']*["\']/', 
                        'wire:poll.' . $interval . 's=""', 
                        $response->effects['html']
                    );
                }
            }
        });
    }
    
    protected function injectPerformanceMonitoring(): void
    {
        // Add performance monitoring to admin pages
        if (app()->environment('local', 'development')) {
            Blade::directive('performanceMonitor', function () {
                return <<<'BLADE'
                <script>
                (function() {
                    const startTime = performance.now();
                    let lastInteractionTime = Date.now();
                    
                    // Monitor page performance
                    window.addEventListener('load', function() {
                        const loadTime = performance.now() - startTime;
                        console.log(`[Performance] Page load time: ${loadTime.toFixed(2)}ms`);
                        
                        // Check DOM size
                        const nodeCount = document.getElementsByTagName('*').length;
                        if (nodeCount > <?= config('livewire-performance.dom.max_nodes_warning', 5000) ?>) {
                            console.warn(`[Performance] High DOM node count: ${nodeCount}`);
                        }
                        
                        // Monitor memory if available
                        if (performance.memory) {
                            const mb = Math.round(performance.memory.usedJSHeapSize / 1024 / 1024);
                            if (mb > <?= config('livewire-performance.memory.warning_threshold_mb', 256) ?>) {
                                console.warn(`[Performance] High memory usage: ${mb}MB`);
                            }
                        }
                    });
                    
                    // Monitor Livewire requests
                    document.addEventListener('livewire:load', function () {
                        let activeRequests = 0;
                        
                        Livewire.hook('message.sent', (message, component) => {
                            activeRequests++;
                            if (activeRequests > <?= config('livewire-performance.polling.max_concurrent_requests', 3) ?>) {
                                console.warn(`[Performance] Too many concurrent Livewire requests: ${activeRequests}`);
                            }
                        });
                        
                        Livewire.hook('message.processed', (message, component) => {
                            activeRequests--;
                        });
                        
                        Livewire.hook('message.failed', (message, component) => {
                            activeRequests--;
                            console.error('[Performance] Livewire request failed', message);
                        });
                    });
                    
                    // Auto-disable polling after inactivity
                    document.addEventListener('mousemove', () => lastInteractionTime = Date.now());
                    document.addEventListener('keypress', () => lastInteractionTime = Date.now());
                    
                    setInterval(() => {
                        const inactiveMinutes = (Date.now() - lastInteractionTime) / 1000 / 60;
                        if (inactiveMinutes > 5) {
                            // Pause Livewire polling after 5 minutes of inactivity
                            document.querySelectorAll('[wire\\:poll]').forEach(el => {
                                el.setAttribute('wire:poll.pause', '');
                            });
                        }
                    }, 60000); // Check every minute
                })();
                </script>
                BLADE;
            });
        }
    }
    
    protected function registerPerformanceDirectives(): void
    {
        // Blade directive for lazy loading
        Blade::directive('lazyLoad', function ($expression) {
            return <<<BLADE
            <?php if (!isset(\$__lazyLoadIndex)) \$__lazyLoadIndex = 0; ?>
            <?php if (\$__lazyLoadIndex++ < config('livewire-performance.dom.lazy_load_threshold', 50)): ?>
                {$expression}
            <?php else: ?>
                <div wire:init="loadMore">
                    <div class="text-center py-4">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="ml-2">Loading more...</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            BLADE;
        });
        
        // Directive for performance-safe polling
        Blade::directive('safePoll', function ($seconds = 30) {
            $minInterval = config('livewire-performance.polling.min_interval', 10);
            $safeInterval = max($seconds, $minInterval);
            
            return <<<BLADE
            wire:poll.{$safeInterval}s
            BLADE;
        });
    }
}