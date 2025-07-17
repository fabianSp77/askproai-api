<?php

namespace App\Services\MCP\Contracts;

interface ExternalMCPProvider
{
    /**
     * Check if the external server is running
     */
    public function isExternalServerRunning(): bool;

    /**
     * Start the external server
     */
    public function startExternalServer(): bool;

    /**
     * Get server configuration
     */
    public function getConfiguration(): array;
}