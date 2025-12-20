<?php

namespace App\Services\ServiceGateway;

use App\Models\ServiceCase;
use App\Services\ServiceGateway\OutputHandlers\EmailOutputHandler;
use App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler;
use App\Services\ServiceGateway\OutputHandlers\HybridOutputHandler;
use App\Services\ServiceGateway\OutputHandlers\OutputHandlerInterface;
use InvalidArgumentException;

/**
 * Output Handler Factory
 *
 * Strategy Pattern factory for routing case outputs to appropriate handlers.
 * Supports progressive enhancement: Email (Phase 2) → Webhook (Phase 3) → Hybrid (Phase 3)
 *
 * Phase 3 Complete:
 * - Email delivery (Phase 2)
 * - Webhook delivery with HMAC signatures (Phase 3)
 * - Hybrid delivery for redundancy (Phase 3)
 */
class OutputHandlerFactory
{
    /**
     * Create factory with handler dependencies.
     *
     * @param \App\Services\ServiceGateway\OutputHandlers\EmailOutputHandler $emailHandler
     * @param \App\Services\ServiceGateway\OutputHandlers\WebhookOutputHandler $webhookHandler
     */
    public function __construct(
        private EmailOutputHandler $emailHandler,
        private WebhookOutputHandler $webhookHandler,
    ) {}

    /**
     * Create handler by type
     *
     * @param string $type Handler type (email|webhook|hybrid)
     * @return \App\Services\ServiceGateway\OutputHandlerInterface
     * @throws InvalidArgumentException If type is unknown
     */
    public function make(string $type): OutputHandlerInterface
    {
        return match($type) {
            'email' => $this->emailHandler,
            'webhook' => $this->webhookHandler,
            'hybrid' => $this->createHybridHandler(),
            default => throw new InvalidArgumentException("Unknown output type: {$type}"),
        };
    }

    /**
     * Create handler based on case configuration
     *
     * @param \App\Models\ServiceCase $case Service case with configuration
     * @return \App\Services\ServiceGateway\OutputHandlerInterface
     * @throws \Exception If case has no output configuration
     */
    public function makeForCase(ServiceCase $case): OutputHandlerInterface
    {
        $config = $case->category?->outputConfiguration;

        if (!$config) {
            throw new \Exception("No output configuration for case {$case->id}");
        }

        return $this->make($config->output_type);
    }

    /**
     * Get list of currently available handler types
     *
     * @return array<string> Available types
     */
    public function getAvailableTypes(): array
    {
        return ['email', 'webhook', 'hybrid'];
    }

    /**
     * Create hybrid handler instance.
     *
     * Hybrid handler combines email and webhook for redundant delivery.
     *
     * @return \App\Services\ServiceGateway\OutputHandlers\HybridOutputHandler
     */
    private function createHybridHandler(): HybridOutputHandler
    {
        return new HybridOutputHandler(
            $this->emailHandler,
            $this->webhookHandler
        );
    }
}
