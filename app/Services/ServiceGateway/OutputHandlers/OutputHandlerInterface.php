<?php

namespace App\Services\ServiceGateway\OutputHandlers;

use App\Models\ServiceCase;

/**
 * OutputHandlerInterface
 *
 * Contract for service case output handlers.
 * Defines standard methods for delivering case information
 * to external systems (email, webhook, etc.).
 *
 * @package App\Services\ServiceGateway\OutputHandlers
 */
interface OutputHandlerInterface
{
    /**
     * Deliver service case to configured output destination.
     *
     * @param \App\Models\ServiceCase $case Service case to deliver
     * @return bool True if delivery succeeded or was queued
     */
    public function deliver(ServiceCase $case): bool;

    /**
     * Test delivery configuration without sending.
     *
     * Validates configuration and returns diagnostic information
     * about readiness to deliver cases.
     *
     * @param \App\Models\ServiceCase $case Service case to test with
     * @return array Test results with status and diagnostic info
     */
    public function test(ServiceCase $case): array;

    /**
     * Get the output handler type identifier.
     *
     * @return string Handler type (e.g., 'email', 'webhook', 'hybrid')
     */
    public function getType(): string;
}
