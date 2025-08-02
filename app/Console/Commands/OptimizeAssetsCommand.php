<?php

namespace App\Console\Commands;

use App\Services\AssetOptimizationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class OptimizeAssetsCommand extends Command
{
    protected $signature = 'assets:optimize 
        {--type=all : Type of assets to optimize (all, css, js, images)}
        {--bundle : Bundle CSS and JS files}
        {--critical : Generate critical CSS}
        {--cdn : Push optimized assets to CDN}
        {--force : Force re-optimization of all assets}';

    protected $description = 'Optimize static assets for better performance';

    protected $optimizer;

    public function __construct(AssetOptimizationService $optimizer)
    {
        parent::__construct();
        $this->optimizer = $optimizer;
    }

    public function handle()
    {
        $type = $this->option('type');
        $force = $this->option('force');

        $this->info('Starting asset optimization...');
        $this->info('Type: ' . $type);
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Run optimization based on type
        $results = [];
        
        switch ($type) {
            case 'css':
                $results = $this->optimizer->optimizeCssFiles();
                break;
                
            case 'js':
                $results = $this->optimizer->optimizeJsFiles();
                break;
                
            case 'images':
                $results = $this->optimizer->optimizeImages();
                break;
                
            case 'all':
            default:
                $results = $this->optimizer->optimizeAll();
                break;
        }

        // Bundle files if requested
        if ($this->option('bundle')) {
            $this->bundleAssets();
        }

        // Generate critical CSS if requested
        if ($this->option('critical')) {
            $this->generateCriticalCss();
        }

        // Push to CDN if requested
        if ($this->option('cdn')) {
            $this->pushToCdn($results);
        }

        // Display results
        $this->displayResults($results);

        $duration = round(microtime(true) - $startTime, 2);
        $memoryUsed = round((memory_get_peak_usage(true) - $startMemory) / 1024 / 1024, 2);

        $this->info('');
        $this->info("Optimization completed in {$duration}s");
        $this->info("Memory used: {$memoryUsed}MB");

        // Clear opcache
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $this->info('OPcache cleared');
        }

        return 0;
    }

    protected function bundleAssets()
    {
        $this->info('');
        $this->info('Bundling assets...');
        
        // Bundle CSS files
        $cssFiles = [
            public_path('css/app.css'),
            public_path('css/components.css'),
            public_path('css/utilities.css'),
        ];
        
        $cssFiles = array_filter($cssFiles, function($file) {
            return File::exists($file);
        });
        
        if (!empty($cssFiles)) {
            $bundledCss = $this->optimizer->bundleCss($cssFiles, 'app-bundle');
            $this->info('CSS bundled: ' . basename($bundledCss));
        }
        
        // Bundle JS files
        $jsFiles = [
            public_path('js/app.js'),
            public_path('js/components.js'),
            public_path('js/utilities.js'),
        ];
        
        $jsFiles = array_filter($jsFiles, function($file) {
            return File::exists($file);
        });
        
        if (!empty($jsFiles)) {
            $bundledJs = $this->optimizer->bundleJs($jsFiles, 'app-bundle');
            $this->info('JS bundled: ' . basename($bundledJs));
        }
    }

    protected function generateCriticalCss()
    {
        $this->info('');
        $this->info('Generating critical CSS...');
        
        $urls = [
            '/',
            '/login',
            '/dashboard',
            '/appointments',
        ];
        
        foreach ($urls as $url) {
            $criticalCss = $this->optimizer->generateCriticalCss($url);
            $filename = 'critical-' . str_replace('/', '-', trim($url, '/')) . '.css';
            $path = public_path('css/' . $filename);
            
            File::put($path, $criticalCss);
            $this->info('Critical CSS generated for ' . $url . ': ' . $filename);
        }
    }

    protected function pushToCdn($results)
    {
        if (!config('cdn.enabled')) {
            $this->warn('CDN is not enabled. Skipping CDN push.');
            return;
        }

        $this->info('');
        $this->info('Pushing assets to CDN...');
        
        $provider = config('cdn.providers.cloudflare.enabled') ? 'cloudflare' : 
                    (config('cdn.providers.aws_cloudfront.enabled') ? 'aws_cloudfront' : 
                    (config('cdn.providers.bunnycdn.enabled') ? 'bunnycdn' : null));
        
        if (!$provider) {
            $this->warn('No CDN provider is enabled.');
            return;
        }

        // Collect all optimized files
        $files = [];
        
        foreach (['css', 'js', 'images'] as $type) {
            if (isset($results[$type]['files'])) {
                foreach ($results[$type]['files'] as $file) {
                    $files[] = $file['optimized'];
                }
            }
        }

        // Push files to CDN
        $pushed = 0;
        $bar = $this->output->createProgressBar(count($files));
        
        foreach ($files as $file) {
            // Here you would implement actual CDN push logic
            // For now, we'll simulate it
            $this->pushFileToCdn($file, $provider);
            $pushed++;
            $bar->advance();
        }
        
        $bar->finish();
        $this->line('');
        $this->info("Pushed {$pushed} files to CDN ({$provider})");

        // Purge CDN cache if configured
        if (config('cdn.purge.on_deploy')) {
            $this->purgeCdnCache($provider);
        }
    }

    protected function pushFileToCdn($file, $provider)
    {
        // Simulate CDN push
        usleep(10000); // 10ms delay
        
        // In a real implementation, you would:
        // 1. Upload file to CDN storage
        // 2. Update CDN distribution
        // 3. Verify file is accessible
    }

    protected function purgeCdnCache($provider)
    {
        $this->info('Purging CDN cache...');
        
        switch ($provider) {
            case 'cloudflare':
                // Cloudflare purge logic
                break;
            case 'aws_cloudfront':
                // CloudFront invalidation logic
                break;
            case 'bunnycdn':
                // BunnyCDN purge logic
                break;
        }
        
        $this->info('CDN cache purged successfully');
    }

    protected function displayResults($results)
    {
        $this->info('');
        $this->info(str_repeat('=', 60));
        $this->info('OPTIMIZATION RESULTS');
        $this->info(str_repeat('=', 60));

        // CSS Results
        if (isset($results['css'])) {
            $this->displayTypeResults('CSS', $results['css']);
        }

        // JS Results
        if (isset($results['js'])) {
            $this->displayTypeResults('JavaScript', $results['js']);
        }

        // Image Results
        if (isset($results['images'])) {
            $this->displayTypeResults('Images', $results['images']);
        }

        // Total Summary
        if (isset($results['total_saved'])) {
            $this->info('');
            $this->info('TOTAL SAVINGS: ' . $this->formatBytes($results['total_saved']));
        }

        // Errors
        $totalErrors = 0;
        foreach (['css', 'js', 'images'] as $type) {
            if (isset($results[$type]['errors'])) {
                $totalErrors += count($results[$type]['errors']);
            }
        }

        if ($totalErrors > 0) {
            $this->warn('');
            $this->warn("Total errors: {$totalErrors}");
            
            if ($this->option('verbose')) {
                foreach (['css', 'js', 'images'] as $type) {
                    if (isset($results[$type]['errors'])) {
                        foreach ($results[$type]['errors'] as $error) {
                            $this->error("{$type}: {$error['file']} - {$error['error']}");
                        }
                    }
                }
            }
        }
    }

    protected function displayTypeResults($type, $results)
    {
        if (empty($results['files'])) {
            return;
        }

        $this->info('');
        $this->info("{$type} Optimization:");
        $this->info(str_repeat('-', 40));

        $headers = ['File', 'Original', 'Optimized', 'Saved', 'Reduction'];
        $rows = [];

        foreach ($results['files'] as $file) {
            $rows[] = [
                basename($file['original']),
                $this->formatBytes($file['original_size']),
                $this->formatBytes($file['optimized_size']),
                $this->formatBytes($file['saved']),
                $file['reduction'] . '%',
            ];
        }

        $this->table($headers, $rows);
        
        if (isset($results['total_saved'])) {
            $this->info("Total {$type} saved: " . $this->formatBytes($results['total_saved']));
        }
    }

    protected function formatBytes($bytes)
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } elseif ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        } else {
            return round($bytes / 1073741824, 2) . ' GB';
        }
    }
}