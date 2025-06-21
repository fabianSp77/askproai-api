<?php

namespace App\Services\MCP;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * MCP Response object - Standardized response format for all MCP services
 */
class MCPResponse implements Arrayable, Jsonable
{
    protected bool $success;
    protected $data;
    protected ?string $error;
    protected array $metadata;
    protected float $timestamp;
    
    public function __construct(
        bool $success = true,
        $data = null,
        string $error = null,
        array $metadata = []
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->error = $error;
        $this->metadata = $metadata;
        $this->timestamp = microtime(true);
    }
    
    /**
     * Create successful response
     */
    public static function success($data = null, array $metadata = []): self
    {
        return new self(
            success: true,
            data: $data,
            metadata: $metadata
        );
    }
    
    /**
     * Create error response
     */
    public static function error(string $error, array $metadata = []): self
    {
        return new self(
            success: false,
            error: $error,
            metadata: $metadata
        );
    }
    
    /**
     * Create response from exception
     */
    public static function fromException(\Exception $exception, array $metadata = []): self
    {
        return new self(
            success: false,
            error: $exception->getMessage(),
            metadata: array_merge($metadata, [
                'exception_class' => get_class($exception),
                'exception_code' => $exception->getCode(),
            ])
        );
    }
    
    // Getters
    
    public function isSuccess(): bool
    {
        return $this->success;
    }
    
    public function getData()
    {
        return $this->data;
    }
    
    public function getError(): ?string
    {
        return $this->error;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
    
    /**
     * Get specific metadata value
     */
    public function getMeta(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }
    
    /**
     * Add metadata to response
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }
    
    /**
     * Get execution time in milliseconds
     */
    public function getExecutionTime(): float
    {
        return $this->metadata['duration_ms'] ?? 0;
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $array = [
            'success' => $this->success,
            'timestamp' => $this->timestamp,
        ];
        
        if ($this->success) {
            $array['data'] = $this->data;
        } else {
            $array['error'] = $this->error;
        }
        
        if (!empty($this->metadata)) {
            $array['metadata'] = $this->metadata;
        }
        
        return $array;
    }
    
    /**
     * Convert to JSON
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }
    
    /**
     * Convert to HTTP response
     */
    public function toHttpResponse(int $successCode = 200, int $errorCode = 400): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $this->toArray(),
            $this->success ? $successCode : $errorCode
        );
    }
    
    /**
     * Throw exception if response is an error
     */
    public function throwIfError(): self
    {
        if (!$this->success) {
            throw new \RuntimeException($this->error ?? 'Unknown MCP error');
        }
        
        return $this;
    }
    
    /**
     * Map data if successful
     */
    public function map(callable $callback): self
    {
        if ($this->success && $this->data !== null) {
            $this->data = $callback($this->data);
        }
        
        return $this;
    }
    
    /**
     * Get data or default value
     */
    public function getDataOr($default)
    {
        return $this->success ? $this->data : $default;
    }
}