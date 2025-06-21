<?php

namespace App\Contracts;

use App\Services\MCP\MCPRequest;
use App\Services\MCP\MCPResponse;

/**
 * Interface for all MCP services
 */
interface MCPServiceInterface
{
    /**
     * Process an MCP request
     */
    public function process(MCPRequest $request): MCPResponse;
    
    /**
     * Check service health
     */
    public function healthCheck(): array;
    
    /**
     * Get service metrics
     */
    public function getMetrics(): array;
    
    /**
     * Get service capabilities
     */
    public function getCapabilities(): array;
}