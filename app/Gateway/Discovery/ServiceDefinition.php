<?php

namespace App\Gateway\Discovery;

class ServiceDefinition
{
    /**
     * Service name
     *
     * @var string
     */
    protected string $name;

    /**
     * Service version
     *
     * @var string
     */
    protected string $version;

    /**
     * Service endpoints
     *
     * @var array
     */
    protected array $endpoints;

    /**
     * Health check configuration
     *
     * @var array
     */
    protected array $healthChecks;

    /**
     * Load balancing configuration
     *
     * @var array
     */
    protected array $loadBalancingConfig;

    /**
     * Circuit breaker configuration
     *
     * @var array
     */
    protected array $circuitBreakerConfig;

    /**
     * Create a new service definition
     *
     * @param string $name
     * @param string $version
     * @param array $endpoints
     * @param array $healthChecks
     * @param array $loadBalancingConfig
     * @param array $circuitBreakerConfig
     */
    public function __construct(
        string $name,
        string $version = 'v1',
        array $endpoints = [],
        array $healthChecks = [],
        array $loadBalancingConfig = [],
        array $circuitBreakerConfig = []
    ) {
        $this->name = $name;
        $this->version = $version;
        $this->endpoints = $endpoints;
        $this->healthChecks = $healthChecks;
        $this->loadBalancingConfig = $loadBalancingConfig;
        $this->circuitBreakerConfig = $circuitBreakerConfig;
    }

    /**
     * Get service name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get service version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get service endpoints
     *
     * @return array
     */
    public function getEndpoints(): array
    {
        return $this->endpoints;
    }

    /**
     * Get health checks
     *
     * @return array
     */
    public function getHealthChecks(): array
    {
        return $this->healthChecks;
    }

    /**
     * Get load balancing configuration
     *
     * @return array
     */
    public function getLoadBalancingConfig(): array
    {
        return $this->loadBalancingConfig;
    }

    /**
     * Get circuit breaker configuration
     *
     * @return array
     */
    public function getCircuitBreakerConfig(): array
    {
        return $this->circuitBreakerConfig;
    }
}