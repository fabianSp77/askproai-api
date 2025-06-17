<?php

namespace App\Services\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\ConnectionException;

trait RetryableHttpClient
{
    /**
     * Get HTTP client with retry logic and timeout
     */
    protected function httpWithRetry(): PendingRequest
    {
        return Http::timeout(10)
            ->retry(3, 100, function ($exception) {
                // Log retry attempts
                Log::warning('HTTP request retry', [
                    'error' => $exception->getMessage(),
                    'service' => static::class,
                ]);
                
                // Retry on connection errors and 5xx responses
                return $exception instanceof ConnectionException ||
                       ($exception->response && $exception->response->serverError());
            })
            ->throw();
    }
    
    /**
     * Get HTTP client with custom timeout and retries
     */
    protected function httpWithCustomRetry(int $timeout = 10, int $retries = 3, int $sleep = 100): PendingRequest
    {
        return Http::timeout($timeout)
            ->retry($retries, $sleep, function ($exception) {
                return $exception instanceof ConnectionException ||
                       ($exception->response && $exception->response->serverError());
            })
            ->throw();
    }
    
    /**
     * Make GET request with retry logic
     */
    protected function getWithRetry(string $url, array $query = []): array
    {
        $startTime = microtime(true);
        
        try {
            $response = $this->httpWithRetry()->get($url, $query);
            
            $this->logSuccessfulRequest('GET', $url, microtime(true) - $startTime);
            
            return $response->json() ?? [];
        } catch (\Exception $e) {
            $this->logFailedRequest('GET', $url, $e, microtime(true) - $startTime);
            throw $e;
        }
    }
    
    /**
     * Make POST request with retry logic
     */
    protected function postWithRetry(string $url, array $data = []): array
    {
        $startTime = microtime(true);
        
        try {
            $response = $this->httpWithRetry()->post($url, $data);
            
            $this->logSuccessfulRequest('POST', $url, microtime(true) - $startTime);
            
            return $response->json() ?? [];
        } catch (\Exception $e) {
            $this->logFailedRequest('POST', $url, $e, microtime(true) - $startTime);
            throw $e;
        }
    }
    
    /**
     * Make PUT request with retry logic
     */
    protected function putWithRetry(string $url, array $data = []): array
    {
        $startTime = microtime(true);
        
        try {
            $response = $this->httpWithRetry()->put($url, $data);
            
            $this->logSuccessfulRequest('PUT', $url, microtime(true) - $startTime);
            
            return $response->json() ?? [];
        } catch (\Exception $e) {
            $this->logFailedRequest('PUT', $url, $e, microtime(true) - $startTime);
            throw $e;
        }
    }
    
    /**
     * Make DELETE request with retry logic
     */
    protected function deleteWithRetry(string $url): bool
    {
        $startTime = microtime(true);
        
        try {
            $response = $this->httpWithRetry()->delete($url);
            
            $this->logSuccessfulRequest('DELETE', $url, microtime(true) - $startTime);
            
            return $response->successful();
        } catch (\Exception $e) {
            $this->logFailedRequest('DELETE', $url, $e, microtime(true) - $startTime);
            throw $e;
        }
    }
    
    /**
     * Log successful API request
     */
    private function logSuccessfulRequest(string $method, string $url, float $duration): void
    {
        Log::info('API request successful', [
            'service' => static::class,
            'method' => $method,
            'url' => $url,
            'duration_ms' => round($duration * 1000, 2),
            'trace_id' => request()->header('X-Trace-ID', uniqid()),
        ]);
    }
    
    /**
     * Log failed API request
     */
    private function logFailedRequest(string $method, string $url, \Exception $e, float $duration): void
    {
        Log::error('API request failed', [
            'service' => static::class,
            'method' => $method,
            'url' => $url,
            'duration_ms' => round($duration * 1000, 2),
            'error' => $e->getMessage(),
            'trace_id' => request()->header('X-Trace-ID', uniqid()),
        ]);
    }
}