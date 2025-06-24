<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class CaptureScreenshotJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $url;
    protected string $savePath;
    protected array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(string $url, string $savePath, array $options = [])
    {
        $this->url = $url;
        $this->savePath = $savePath;
        $this->options = array_merge([
            'width' => 1920,
            'height' => 1080,
            'fullPage' => true,
            'waitUntilNetworkIdle' => true,
            'deviceScaleFactor' => 2,
            'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 AskProAI-Screenshot-Bot/1.0',
        ], $options);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting screenshot capture', [
                'url' => $this->url,
                'savePath' => $this->savePath,
                'options' => $this->options,
            ]);

            // Ensure directory exists
            $directory = dirname($this->savePath);
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Create screenshot with authentication if needed
            $screenshot = Browsershot::url($this->url)
                ->windowSize($this->options['width'], $this->options['height'])
                ->deviceScaleFactor($this->options['deviceScaleFactor'])
                ->userAgent($this->options['userAgent'])
                ->waitUntilNetworkIdle()
                ->noSandbox(); // Required when running as root

            // Set full page if requested
            if ($this->options['fullPage']) {
                $screenshot->fullPage();
            }

            // Add authentication if provided
            if (isset($this->options['auth'])) {
                $screenshot->authenticate(
                    $this->options['auth']['username'],
                    $this->options['auth']['password']
                );
            }

            // Add cookies if provided (for session authentication)
            if (isset($this->options['cookies'])) {
                foreach ($this->options['cookies'] as $cookie) {
                    $screenshot->useCookies($cookie);
                }
            }

            // Add custom headers if provided
            if (isset($this->options['headers'])) {
                $screenshot->setExtraHttpHeaders($this->options['headers']);
            }

            // Wait for specific element if provided
            if (isset($this->options['waitForSelector'])) {
                $screenshot->waitForSelector($this->options['waitForSelector']);
            }

            // Add custom JavaScript if provided
            if (isset($this->options['javascript'])) {
                $screenshot->evaluate($this->options['javascript']);
            }

            // Set viewport if mobile
            if (isset($this->options['mobile']) && $this->options['mobile']) {
                $screenshot->mobile();
            }

            // Set specific device if provided
            if (isset($this->options['device'])) {
                $screenshot->device($this->options['device']);
            }

            // Take screenshot
            $screenshot->save($this->savePath);

            // Create thumbnail if requested
            if (isset($this->options['thumbnail'])) {
                $thumbnailPath = str_replace('.png', '_thumb.png', $this->savePath);
                Browsershot::url($this->url)
                    ->windowSize(320, 240)
                    ->save($thumbnailPath);
            }

            Log::info('Screenshot captured successfully', [
                'url' => $this->url,
                'savePath' => $this->savePath,
                'fileSize' => filesize($this->savePath),
            ]);

            // Dispatch event for post-processing if needed
            event('screenshot.captured', [
                'url' => $this->url,
                'path' => $this->savePath,
                'options' => $this->options,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to capture screenshot', [
                'url' => $this->url,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Screenshot job failed', [
            'url' => $this->url,
            'savePath' => $this->savePath,
            'error' => $exception->getMessage(),
        ]);
    }
}