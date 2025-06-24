<?php

namespace App\Services;

use App\Jobs\CaptureScreenshotJob;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class ScreenshotService
{
    /**
     * Capture a screenshot of a given URL or route
     */
    public function capture(string $urlOrRoute, array $options = []): string
    {
        // Determine if it's a route or full URL
        $url = filter_var($urlOrRoute, FILTER_VALIDATE_URL) 
            ? $urlOrRoute 
            : config('app.url') . $urlOrRoute;
        
        // Generate filename
        $filename = $this->generateFilename($urlOrRoute, $options);
        $savePath = $this->getScreenshotPath($filename);
        
        // Merge default options
        $options = array_merge([
            'width' => 1920,
            'height' => 1080,
            'fullPage' => true,
            'waitUntilNetworkIdle' => true,
            'deviceScaleFactor' => 2,
        ], $options);
        
        // Queue or execute directly based on configuration
        if (config('queue.default') !== 'sync' && !($options['sync'] ?? false)) {
            CaptureScreenshotJob::dispatch($url, $savePath, $options);
            return $savePath;
        }
        
        // Execute synchronously
        return $this->captureSync($url, $savePath, $options);
    }
    
    /**
     * Capture screenshot synchronously
     */
    public function captureSync(string $url, string $savePath, array $options = []): string
    {
        $screenshot = Browsershot::url($url)
            ->windowSize($options['width'], $options['height'])
            ->deviceScaleFactor($options['deviceScaleFactor'] ?? 2)
            ->waitUntilNetworkIdle()
            ->noSandbox(); // Required when running as root
        
        if ($options['fullPage'] ?? true) {
            $screenshot->fullPage();
        }
        
        if (isset($options['selector'])) {
            $screenshot->select($options['selector']);
        }
        
        if (isset($options['javascript'])) {
            $screenshot->evaluate($options['javascript']);
        }
        
        $screenshot->save($savePath);
        
        return $savePath;
    }
    
    /**
     * Capture multiple screenshots in batch
     */
    public function captureBatch(array $routes, array $options = []): array
    {
        $results = [];
        
        foreach ($routes as $name => $route) {
            $results[$name] = $this->capture($route, array_merge($options, ['name' => $name]));
        }
        
        return $results;
    }
    
    /**
     * Get the latest screenshot for a route
     */
    public function getLatest(string $route): ?string
    {
        $pattern = 'screenshots/metadata/' . str_replace('/', '_', $route) . '_latest.json';
        
        if (Storage::exists($pattern)) {
            $metadata = json_decode(Storage::get($pattern), true);
            return $metadata['path'] ?? null;
        }
        
        return null;
    }
    
    /**
     * Clean up old screenshots
     */
    public function cleanup(int $daysToKeep = 7): int
    {
        $deleted = 0;
        $cutoffDate = now()->subDays($daysToKeep);
        
        $files = Storage::files('screenshots');
        foreach ($files as $file) {
            if (Storage::lastModified($file) < $cutoffDate->timestamp) {
                Storage::delete($file);
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Generate a unique filename for the screenshot
     */
    protected function generateFilename(string $urlOrRoute, array $options): string
    {
        $name = $options['name'] ?? str_replace(['/', ':', '?', '&'], '_', $urlOrRoute);
        $timestamp = now()->format('Ymd_His');
        
        return "screenshot_{$name}_{$timestamp}.png";
    }
    
    /**
     * Get the full path for storing a screenshot
     */
    protected function getScreenshotPath(string $filename): string
    {
        $dir = storage_path('app/screenshots');
        
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        
        return $dir . '/' . $filename;
    }
}