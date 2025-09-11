<?php

namespace App\Console\Commands;

use App\Services\ViewCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class WarmViewCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm 
                            {--views : Warm up all view templates}
                            {--routes : Warm up route cache}
                            {--config : Warm up config cache}
                            {--all : Warm up everything}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up various caches to prevent cold start issues';

    /**
     * Execute the console command.
     */
    public function handle(ViewCacheService $cacheService): int
    {
        $this->info('🔥 Starting cache warming process...');
        
        $warmViews = $this->option('views') || $this->option('all');
        $warmRoutes = $this->option('routes') || $this->option('all');
        $warmConfig = $this->option('config') || $this->option('all');
        
        // If no specific option, warm everything
        if (!$warmViews && !$warmRoutes && !$warmConfig) {
            $warmViews = $warmRoutes = $warmConfig = true;
        }
        
        $results = [];
        
        if ($warmConfig) {
            $this->line('');
            $this->info('Warming config cache...');
            $results['config'] = $this->warmConfigCache();
        }
        
        if ($warmRoutes) {
            $this->line('');
            $this->info('Warming route cache...');
            $results['routes'] = $this->warmRouteCache();
        }
        
        if ($warmViews) {
            $this->line('');
            $this->info('Warming view cache...');
            $results['views'] = $this->warmViewCache();
        }
        
        $this->line('');
        $this->displayResults($results);
        
        return Command::SUCCESS;
    }
    
    /**
     * Warm up configuration cache
     */
    private function warmConfigCache(): array
    {
        $start = microtime(true);
        
        try {
            $this->call('config:cache', [], $this->output);
            
            return [
                'status' => 'success',
                'time' => round((microtime(true) - $start) * 1000, 2),
                'message' => 'Config cache warmed successfully'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'time' => round((microtime(true) - $start) * 1000, 2),
                'message' => 'Failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Warm up route cache
     */
    private function warmRouteCache(): array
    {
        $start = microtime(true);
        
        try {
            $this->call('route:cache', [], $this->output);
            
            return [
                'status' => 'success',
                'time' => round((microtime(true) - $start) * 1000, 2),
                'message' => 'Route cache warmed successfully'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'time' => round((microtime(true) - $start) * 1000, 2),
                'message' => 'Failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Warm up view cache by compiling all views
     */
    private function warmViewCache(): array
    {
        $start = microtime(true);
        $compiled = 0;
        $failed = 0;
        $views = [];
        
        try {
            // First, clear existing view cache
            $this->call('view:clear', [], $this->output);
            
            // Find all blade templates
            $viewPaths = View::getFinder()->getPaths();
            
            foreach ($viewPaths as $path) {
                $bladeFiles = File::allFiles($path);
                
                foreach ($bladeFiles as $file) {
                    if ($file->getExtension() === 'php' && str_contains($file->getFilename(), '.blade.')) {
                        $relativePath = str_replace($path . '/', '', $file->getPath());
                        $viewName = str_replace('/', '.', $relativePath);
                        
                        if ($relativePath) {
                            $viewName .= '.';
                        }
                        
                        $viewName .= str_replace('.blade.php', '', $file->getFilename());
                        
                        try {
                            // Attempt to compile the view
                            view($viewName)->render();
                            $compiled++;
                            $this->output->write('.');
                            
                            if ($compiled % 50 === 0) {
                                $this->output->write(" {$compiled}");
                                $this->line('');
                            }
                        } catch (\Exception $e) {
                            $failed++;
                            $views[] = [
                                'view' => $viewName,
                                'error' => $e->getMessage()
                            ];
                        }
                    }
                }
            }
            
            $this->line('');
            
            // Cache the views using Artisan command
            $this->call('view:cache', [], $this->output);
            
            $result = [
                'status' => $failed === 0 ? 'success' : 'partial',
                'time' => round((microtime(true) - $start) * 1000, 2),
                'message' => "Compiled {$compiled} views",
                'compiled' => $compiled,
                'failed' => $failed
            ];
            
            if ($failed > 0) {
                $result['failed_views'] = array_slice($views, 0, 5); // Show first 5 failures
            }
            
            return $result;
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'time' => round((microtime(true) - $start) * 1000, 2),
                'message' => 'Failed: ' . $e->getMessage(),
                'compiled' => $compiled,
                'failed' => $failed
            ];
        }
    }
    
    /**
     * Display warming results
     */
    private function displayResults(array $results): void
    {
        $this->info('=== Cache Warming Results ===');
        $this->line('');
        
        foreach ($results as $type => $result) {
            $icon = $result['status'] === 'success' ? '✅' : 
                   ($result['status'] === 'partial' ? '⚠️' : '❌');
            
            $this->line("{$icon} {$type}: {$result['message']} ({$result['time']}ms)");
            
            if ($type === 'views' && isset($result['compiled'])) {
                $this->line("   Compiled: {$result['compiled']} | Failed: {$result['failed']}");
                
                if (isset($result['failed_views']) && count($result['failed_views']) > 0) {
                    $this->warn('   Failed views (showing first 5):');
                    foreach ($result['failed_views'] as $view) {
                        $this->line("   - {$view['view']}");
                    }
                }
            }
        }
        
        $this->line('');
        $totalTime = array_sum(array_column($results, 'time'));
        $this->info("Total warming time: {$totalTime}ms");
    }
}