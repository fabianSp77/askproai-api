<?php

namespace App\Services\MCP;

use Illuminate\Support\Str;

/**
 * MCP Request object - Encapsulates all request data for MCP services
 */
class MCPRequest
{
    protected string $id;
    protected string $service;
    protected string $operation;
    protected array $params;
    protected int $tenantId;
    protected array $metadata;
    protected ?string $correlationId;
    
    public function __construct(
        string $service,
        string $operation,
        array $params = [],
        int $tenantId = null,
        array $metadata = [],
        string $correlationId = null
    ) {
        $this->id = Str::uuid()->toString();
        $this->service = $service;
        $this->operation = $operation;
        $this->params = $params;
        $this->tenantId = $tenantId ?? $this->resolveTenantId();
        $this->metadata = $metadata;
        $this->correlationId = $correlationId ?? Str::uuid()->toString();
    }
    
    /**
     * Create request from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            service: $data['service'] ?? throw new \InvalidArgumentException('Service is required'),
            operation: $data['operation'] ?? throw new \InvalidArgumentException('Operation is required'),
            params: $data['params'] ?? [],
            tenantId: $data['tenant_id'] ?? null,
            metadata: $data['metadata'] ?? [],
            correlationId: $data['correlation_id'] ?? null
        );
    }
    
    /**
     * Resolve tenant ID from various sources
     */
    protected function resolveTenantId(): int
    {
        // Try app context first
        if (app()->bound('current_company_id')) {
            return app('current_company_id');
        }
        
        // Try auth user
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }
        
        // Try session
        if (session()->has('current_company_id')) {
            return session('current_company_id');
        }
        
        // Try request header
        if (request()->hasHeader('X-Company-ID')) {
            return (int) request()->header('X-Company-ID');
        }
        
        throw new \RuntimeException('Unable to resolve tenant ID for MCP request');
    }
    
    // Getters
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getService(): string
    {
        return $this->service;
    }
    
    public function getOperation(): string
    {
        return $this->operation;
    }
    
    public function getParams(): array
    {
        return $this->params;
    }
    
    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }
    
    public function getTenantId(): int
    {
        return $this->tenantId;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }
    
    // Setters
    
    public function setParam(string $key, $value): self
    {
        $this->params[$key] = $value;
        return $this;
    }
    
    public function setMetadata(string $key, $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }
    
    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'service' => $this->service,
            'operation' => $this->operation,
            'params' => $this->params,
            'tenant_id' => $this->tenantId,
            'metadata' => $this->metadata,
            'correlation_id' => $this->correlationId,
            'created_at' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Create a new request with modified params
     */
    public function withParams(array $params): self
    {
        return new self(
            $this->service,
            $this->operation,
            array_merge($this->params, $params),
            $this->tenantId,
            $this->metadata,
            $this->correlationId
        );
    }
    
    /**
     * Create a new request for a different operation
     */
    public function forOperation(string $operation): self
    {
        return new self(
            $this->service,
            $operation,
            $this->params,
            $this->tenantId,
            $this->metadata,
            $this->correlationId
        );
    }
}