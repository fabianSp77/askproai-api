<?php

namespace App\Services\MCP;

use App\Exceptions\MCPException;
use App\Exceptions\RateLimitExceededException;
use App\Models\Call;
use App\Services\AgentSelectionService;
use App\Services\CircuitBreaker\CircuitBreakerManager;
use App\Services\PhoneNumberResolver;
use App\Services\RetellMCPServer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Enhanced Bridge MCP Server with Circuit Breaker, Rate Limiting, and Advanced Error Handling.
 */
class RetellAIBridgeMCPServerEnhanced extends RetellAIBridgeMCPServer
{
    protected CircuitBreakerManager $circuitBreaker;

    protected string $serviceName = 'retell-mcp';

    protected array $metrics = [];

    public function __construct(
        RetellMCPServer $retellMCPServer,
        PhoneNumberResolver $phoneResolver,
        AgentSelectionService $agentSelector,
        CircuitBreakerManager $circuitBreaker
    ) {
        parent::__construct($retellMCPServer, $phoneResolver, $agentSelector);
        $this->circuitBreaker = $circuitBreaker;
        $this->initializeMetrics();
    }

    /**
     * Create an outbound AI call with enhanced error handling.
     *
     * @throws MCPException
     * @throws RateLimitExceededException
     */
    public function createOutboundCall(array $params): array
    {
        // Check rate limits first
        $this->checkRateLimits($params['company_id']);

        // Track metric
        $this->trackMetric('calls.initiated');

        try {
            // Use circuit breaker for external call
            $result = $this->circuitBreaker->call(
                $this->serviceName,
                function () use ($params) {
                    return parent::createOutboundCall($params);
                }
            );

            $this->trackMetric('calls.successful');

            return $result;
        } catch (RequestException $e) {
            $this->handleRequestException($e, 'createOutboundCall', $params);
        } catch (ConnectionException $e) {
            $this->handleConnectionException($e, 'createOutboundCall', $params);
        } catch (\Exception $e) {
            $this->handleGenericException($e, 'createOutboundCall', $params);
        }
    }

    /**
     * Call external MCP server with circuit breaker protection.
     *
     * @throws MCPException
     */
    protected function callExternalMCP(string $tool, array $params): array
    {
        $attempt = 0;
        $maxAttempts = config('retell-mcp.server.retry_times', 3);
        $retryDelay = config('retell-mcp.server.retry_delay', 1000);

        while ($attempt < $maxAttempts) {
            try {
                return $this->circuitBreaker->call(
                    $this->serviceName,
                    function () use ($tool, $params) {
                        $headers = [
                            'Content-Type' => 'application/json',
                            'X-Request-ID' => Str::uuid()->toString(),
                            'X-Correlation-ID' => request()->header('X-Correlation-ID', Str::uuid()->toString()),
                        ];

                        if ($this->externalMCPToken) {
                            $headers['Authorization'] = 'Bearer ' . $this->externalMCPToken;
                        }

                        $response = Http::withHeaders($headers)
                            ->timeout(config('retell-mcp.server.timeout', 30))
                            ->retry(
                                times: 0, // We handle retry ourselves
                                sleepMilliseconds: 0
                            )
                            ->post($this->externalMCPUrl . '/mcp/execute', [
                                'tool' => $tool,
                                'params' => $params,
                                'metadata' => [
                                    'source' => 'laravel_bridge',
                                    'user_id' => auth()->user()->id ?? null,
                                    'company_id' => $params['company_id'] ?? null,
                                    'request_id' => $headers['X-Request-ID'],
                                ],
                            ]);

                        if (! $response->successful()) {
                            $this->logMCPError($tool, $params, $response->status(), $response->body());

                            // Check if it's a retryable error
                            if ($this->isRetryableError($response->status())) {
                                throw new MCPException(
                                    'MCP server returned retryable error: ' . $response->status(),
                                    $response->status()
                                );
                            }

                            throw new MCPException(
                                'MCP server error: ' . $response->body(),
                                $response->status()
                            );
                        }

                        $data = $response->json();

                        // Validate response structure
                        if (! isset($data['success']) || ! $data['success']) {
                            throw new MCPException(
                                'Invalid MCP response: ' . ($data['error'] ?? 'Unknown error'),
                                MCPException::INVALID_RESPONSE
                            );
                        }

                        return $data;
                    }
                );
            } catch (ConnectionException $e) {
                $attempt++;

                if ($attempt >= $maxAttempts) {
                    $this->logConnectionFailure($tool, $params, $e);

                    throw new MCPException(
                        'Failed to connect to MCP server after ' . $maxAttempts . ' attempts',
                        MCPException::CONNECTION_FAILED
                    );
                }

                // Wait before retry with exponential backoff
                usleep($retryDelay * pow(2, $attempt - 1) * 1000);
            } catch (MCPException $e) {
                // Re-throw MCP exceptions without retry
                throw $e;
            } catch (\Exception $e) {
                $this->logUnexpectedError($tool, $params, $e);

                throw new MCPException(
                    'Unexpected error calling MCP server: ' . $e->getMessage(),
                    MCPException::UNKNOWN_ERROR
                );
            }
        }

        throw new MCPException(
            'Failed to call MCP server',
            MCPException::MAX_RETRIES_EXCEEDED
        );
    }

    /**
     * Check rate limits for outbound calls.
     *
     * @throws RateLimitExceededException
     */
    protected function checkRateLimits(int $companyId): void
    {
        $limits = [
            'per_minute' => config('retell-mcp.rate_limits.calls_per_minute', 30),
            'per_hour' => config('retell-mcp.rate_limits.calls_per_hour', 500),
            'per_day' => config('retell-mcp.rate_limits.calls_per_day', 5000),
        ];

        $multiplier = config('retell-mcp.rate_limits.per_company_multiplier', 0.5);

        foreach ($limits as $period => $limit) {
            $key = "retell-mcp:rate-limit:{$companyId}:{$period}";
            $companyLimit = (int) ($limit * $multiplier);

            if (! RateLimiter::attempt($key, $companyLimit, function () {}, $this->getRateLimitWindow($period))) {
                $this->trackMetric('rate_limit.exceeded');

                throw new RateLimitExceededException(
                    "Rate limit exceeded for {$period}. Limit: {$companyLimit}",
                    $this->getRateLimitWindow($period)
                );
            }
        }
    }

    /**
     * Get rate limit window in seconds.
     */
    protected function getRateLimitWindow(string $period): int
    {
        return match ($period) {
            'per_minute' => 60,
            'per_hour' => 3600,
            'per_day' => 86400,
            default => 60,
        };
    }

    /**
     * Check if HTTP status code is retryable.
     */
    protected function isRetryableError(int $status): bool
    {
        // Retry on 5xx errors and specific 4xx errors
        return $status >= 500 || in_array($status, [408, 425, 429]);
    }

    /**
     * Handle request exceptions.
     *
     * @throws MCPException
     */
    protected function handleRequestException(RequestException $e, string $operation, array $context): void
    {
        $this->trackMetric('errors.request');

        Log::error('MCP Request Exception', [
            'operation' => $operation,
            'status' => $e->response->status() ?? null,
            'body' => $e->response->body() ?? null,
            'context' => $context,
        ]);

        throw new MCPException(
            'Request failed: ' . $e->getMessage(),
            $e->response->status() ?? 500
        );
    }

    /**
     * Handle connection exceptions.
     *
     * @throws MCPException
     */
    protected function handleConnectionException(ConnectionException $e, string $operation, array $context): void
    {
        $this->trackMetric('errors.connection');

        Log::error('MCP Connection Exception', [
            'operation' => $operation,
            'message' => $e->getMessage(),
            'context' => $context,
        ]);

        // Mark circuit breaker as failed
        $this->circuitBreaker->recordFailure($this->serviceName);

        throw new MCPException(
            'Connection failed: ' . $e->getMessage(),
            MCPException::CONNECTION_FAILED
        );
    }

    /**
     * Handle generic exceptions.
     *
     * @throws MCPException
     */
    protected function handleGenericException(\Exception $e, string $operation, array $context): void
    {
        $this->trackMetric('errors.generic');

        Log::error('MCP Generic Exception', [
            'operation' => $operation,
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'context' => $context,
        ]);

        throw new MCPException(
            'Operation failed: ' . $e->getMessage(),
            MCPException::UNKNOWN_ERROR
        );
    }

    /**
     * Log MCP errors.
     */
    protected function logMCPError(string $tool, array $params, int $status, string $body): void
    {
        Log::channel(config('retell-mcp.monitoring.log_channel', 'retell-mcp'))
            ->error('MCP Server Error', [
                'tool' => $tool,
                'status' => $status,
                'response' => $body,
                'params' => $this->sanitizeParams($params),
            ]);
    }

    /**
     * Log connection failures.
     */
    protected function logConnectionFailure(string $tool, array $params, ConnectionException $e): void
    {
        Log::channel(config('retell-mcp.monitoring.log_channel', 'retell-mcp'))
            ->critical('MCP Connection Failure', [
                'tool' => $tool,
                'error' => $e->getMessage(),
                'params' => $this->sanitizeParams($params),
            ]);
    }

    /**
     * Log unexpected errors.
     */
    protected function logUnexpectedError(string $tool, array $params, \Exception $e): void
    {
        Log::channel(config('retell-mcp.monitoring.log_channel', 'retell-mcp'))
            ->error('MCP Unexpected Error', [
                'tool' => $tool,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'params' => $this->sanitizeParams($params),
                'trace' => $e->getTraceAsString(),
            ]);
    }

    /**
     * Sanitize parameters for logging.
     */
    protected function sanitizeParams(array $params): array
    {
        $sensitive = ['api_key', 'token', 'password', 'secret'];

        return collect($params)->map(function ($value, $key) use ($sensitive) {
            if (in_array(strtolower($key), $sensitive)) {
                return '[REDACTED]';
            }

            if (is_array($value)) {
                return $this->sanitizeParams($value);
            }

            return $value;
        })->toArray();
    }

    /**
     * Initialize metrics tracking.
     */
    protected function initializeMetrics(): void
    {
        $this->metrics = [
            'calls.initiated' => 0,
            'calls.successful' => 0,
            'calls.failed' => 0,
            'errors.request' => 0,
            'errors.connection' => 0,
            'errors.generic' => 0,
            'rate_limit.exceeded' => 0,
        ];
    }

    /**
     * Track a metric.
     */
    protected function trackMetric(string $metric, int $value = 1): void
    {
        if (! config('retell-mcp.monitoring.metrics_enabled', true)) {
            return;
        }

        $this->metrics[$metric] = ($this->metrics[$metric] ?? 0) + $value;

        // Report to monitoring service
        app('monitoring.metrics')->increment("retell_mcp.{$metric}", $value, [
            'company_id' => auth()->user()->company_id ?? 'system',
        ]);
    }

    /**
     * Get current metrics.
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Health check with circuit breaker status.
     */
    public function healthCheck(): array
    {
        $baseHealth = parent::healthCheck();

        // Add circuit breaker status
        $circuitBreakerStatus = $this->circuitBreaker->getStatus($this->serviceName);

        return array_merge($baseHealth, [
            'circuit_breaker' => [
                'status' => $circuitBreakerStatus['state'],
                'failures' => $circuitBreakerStatus['failure_count'],
                'last_failure' => $circuitBreakerStatus['last_failure_time'],
                'will_retry_at' => $circuitBreakerStatus['half_open_time'],
            ],
            'metrics' => $this->metrics,
        ]);
    }
}
