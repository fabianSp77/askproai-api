<?php

namespace App\Services\Traits;

use App\Services\Logging\StructuredLogger;

trait LogsStructured
{
    /**
     * The structured logger instance
     */
    protected ?StructuredLogger $logger = null;

    /**
     * Get the structured logger instance
     */
    protected function logger(): StructuredLogger
    {
        if (!$this->logger) {
            $this->logger = app(StructuredLogger::class);
            
            // Add service-specific context
            $this->logger->withContext([
                'service' => static::class,
                'service_name' => class_basename(static::class),
            ]);
        }
        
        return $this->logger;
    }

    /**
     * Log API call with timing
     */
    protected function logApiCall(string $endpoint, string $method = 'POST', array $requestData = []): ApiCallLogger
    {
        return new ApiCallLogger($this->logger(), $this->getServiceName(), $endpoint, $method, $requestData);
    }

    /**
     * Log a booking flow step
     */
    protected function logBookingStep(string $step, array $context = []): void
    {
        $this->logger()->logBookingFlow($step, array_merge([
            'service' => static::class,
        ], $context));
    }

    /**
     * Log success with context
     */
    protected function logSuccess(string $message, array $context = []): void
    {
        $this->logger()->success($message, $context);
    }

    /**
     * Log failure with context
     */
    protected function logFailure(string $message, array $context = []): void
    {
        $this->logger()->failure($message, $context);
    }

    /**
     * Log warning with context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->logger()->warning($message, $context);
    }

    /**
     * Log info with context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->logger()->info($message, $context);
    }

    /**
     * Log debug with context
     */
    protected function logDebug(string $message, array $context = []): void
    {
        $this->logger()->debug($message, $context);
    }

    /**
     * Log exception with context
     */
    protected function logError(\Throwable $exception, array $context = []): void
    {
        $this->logger()->logError($exception, array_merge([
            'service' => static::class,
        ], $context));
    }

    /**
     * Get service name for logging
     */
    private function getServiceName(): string
    {
        // Extract service name from class name
        $className = class_basename(static::class);
        
        // Map common service names
        $serviceMap = [
            'CalcomService' => 'calcom',
            'CalcomV2Service' => 'calcom',
            'RetellService' => 'retell',
            'RetellV2Service' => 'retell',
            'StripeService' => 'stripe',
        ];
        
        return $serviceMap[$className] ?? strtolower(str_replace('Service', '', $className));
    }
}

/**
 * Helper class for timing API calls
 */
class ApiCallLogger
{
    private StructuredLogger $logger;
    private string $service;
    private string $endpoint;
    private string $method;
    private array $requestData;
    private float $startTime;

    public function __construct(StructuredLogger $logger, string $service, string $endpoint, string $method, array $requestData)
    {
        $this->logger = $logger;
        $this->service = $service;
        $this->endpoint = $endpoint;
        $this->method = $method;
        $this->requestData = $requestData;
        $this->startTime = microtime(true);
        
        // Store start time in request data for the logger
        $this->requestData['start_time'] = now();
    }

    /**
     * Log successful response
     */
    public function success($response): void
    {
        $duration = microtime(true) - $this->startTime;
        
        $this->logger->logApiCall(
            $this->service,
            $this->endpoint,
            $this->method,
            $this->requestData,
            $response,
            $duration
        );
    }

    /**
     * Log failed response
     */
    public function failure($response, string $error): void
    {
        $duration = microtime(true) - $this->startTime;
        
        $this->logger->logApiCall(
            $this->service,
            $this->endpoint,
            $this->method,
            $this->requestData,
            $response,
            $duration,
            $error
        );
    }

    /**
     * Log exception
     */
    public function exception(\Throwable $exception): void
    {
        $duration = microtime(true) - $this->startTime;
        
        $this->logger->logApiCall(
            $this->service,
            $this->endpoint,
            $this->method,
            $this->requestData,
            null,
            $duration,
            $exception->getMessage()
        );
        
        // Also log the full exception
        $this->logger->logError($exception, [
            'api_call' => [
                'service' => $this->service,
                'endpoint' => $this->endpoint,
                'method' => $this->method,
            ]
        ]);
    }
}