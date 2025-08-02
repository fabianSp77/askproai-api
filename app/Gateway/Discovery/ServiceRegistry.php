<?php

namespace App\Gateway\Discovery;

class ServiceRegistry
{
    /**
     * Registered services
     *
     * @var array<string, ServiceDefinition>
     */
    protected array $services = [];

    /**
     * Register a service
     *
     * @param string $name
     * @param ServiceDefinition $definition
     */
    public function register(string $name, ServiceDefinition $definition): void
    {
        $this->services[$name] = $definition;
    }

    /**
     * Get a service definition
     *
     * @param string $name
     * @return ServiceDefinition|null
     */
    public function get(string $name): ?ServiceDefinition
    {
        return $this->services[$name] ?? null;
    }

    /**
     * Get all registered services
     *
     * @return array<string, ServiceDefinition>
     */
    public function all(): array
    {
        return $this->services;
    }

    /**
     * Check if a service is registered
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]);
    }

    /**
     * Remove a service
     *
     * @param string $name
     */
    public function remove(string $name): void
    {
        unset($this->services[$name]);
    }
}