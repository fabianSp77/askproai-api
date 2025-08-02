<?php

namespace App\Gateway;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pipeline\Pipeline;
use App\Gateway\Discovery\ServiceRegistry;
use App\Gateway\RateLimit\AdvancedRateLimiter;
use App\Gateway\Cache\GatewayCacheManager;
use App\Gateway\CircuitBreaker\CircuitBreaker;
use App\Gateway\Auth\AuthenticationGateway;
use App\Gateway\Monitoring\GatewayMetrics;
use Exception;

class ApiGatewayManager
{
    private ServiceRegistry $serviceRegistry;
    private AdvancedRateLimiter $rateLimiter;
    private GatewayCacheManager $cache;
    private CircuitBreaker $circuitBreaker;
    private AuthenticationGateway $auth;
    private GatewayMetrics $metrics;
    private array $config;

    public function __construct(
        ServiceRegistry $serviceRegistry,
        AdvancedRateLimiter $rateLimiter,
        GatewayCacheManager $cache,
        CircuitBreaker $circuitBreaker,
        AuthenticationGateway $auth,
        GatewayMetrics $metrics,
        array $config = []
    ) {
        $this->serviceRegistry = $serviceRegistry;
        $this->rateLimiter = $rateLimiter;
        $this->cache = $cache;
        $this->circuitBreaker = $circuitBreaker;
        $this->auth = $auth;
        $this->metrics = $metrics;
        $this->config = $config;
    }

    /**
     * Handle incoming API request through the gateway pipeline
     */
    public function handle(Request $request): Response
    {
        $startTime = microtime(true);
        
        try {
            // Apply gateway pipeline
            $response = app(Pipeline::class)
                ->send($request)
                ->through($this->getPipelineMiddlewares())
                ->then(function ($request) {
                    return $this->processRequest($request);
                });

            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->recordRequest($request, $response, $duration);

            return $response;

        } catch (Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $errorResponse = $this->handleError($e, $request);
            $this->metrics->recordRequest($request, $errorResponse, $duration);
            
            return $errorResponse;
        }
    }

    /**
     * Get the pipeline middlewares for request processing
     */
    private function getPipelineMiddlewares(): array
    {
        return $this->config['pipeline']['middlewares'] ?? [
            \App\Gateway\Middleware\AuthenticationGateway::class,
            \App\Gateway\Middleware\AuthorizationGateway::class,
            \App\Gateway\Middleware\RateLimitingGateway::class,
            \App\Gateway\Middleware\CachingGateway::class,
            \App\Gateway\Middleware\CircuitBreakerGateway::class,
            \App\Gateway\Middleware\MetricsGateway::class,
        ];
    }

    /**
     * Process the request after it passes through all middleware
     */
    private function processRequest(Request $request): Response
    {
        // Resolve service from registry
        $service = $this->serviceRegistry->resolve($request->path(), $request->method());
        
        if (!$service) {
            return response()->json([
                'error' => 'Service not found',
                'message' => 'No service registered for this endpoint'
            ], 404);
        }

        // Execute with circuit breaker protection
        return $this->circuitBreaker->call($service->getName(), function () use ($request, $service) {
            return $service->handle($request);
        });
    }

    /**
     * Handle gateway errors with appropriate responses
     */
    private function handleError(Exception $e, Request $request): Response
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        $errorResponse = [
            'error' => class_basename($e),
            'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            'gateway' => true,
        ];

        // Add debug information in development
        if (config('app.debug')) {
            $errorResponse['trace'] = $e->getTraceAsString();
            $errorResponse['file'] = $e->getFile();
            $errorResponse['line'] = $e->getLine();
        }

        return response()->json($errorResponse, $statusCode);
    }

    /**
     * Get gateway health status
     */
    public function getHealthStatus(): array
    {
        return [
            'gateway' => 'healthy',
            'timestamp' => now()->toISOString(),
            'services' => $this->serviceRegistry->getServicesHealth(),
            'cache' => $this->cache->getHealthStatus(),
            'circuit_breakers' => $this->circuitBreaker->getStatus(),
        ];
    }

    /**
     * Get gateway metrics
     */
    public function getMetrics(): array
    {
        return $this->metrics->getMetrics();
    }
}