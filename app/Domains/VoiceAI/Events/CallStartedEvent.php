<?php

namespace App\Domains\VoiceAI\Events;

use App\Shared\Events\DomainEvent;

/**
 * CallStartedEvent
 *
 * Fired when a voice AI call starts.
 * Used for logging, tracking, and call analytics.
 *
 * LISTENERS:
 * - CallTrackingListener
 * - AnalyticsListener
 * - AuditListener
 *
 * @author Phase 5 Architecture Refactoring
 * @date 2025-10-18
 */
class CallStartedEvent extends DomainEvent
{
    public function __construct(
        public string $callId,
        public string $fromNumber,
        public string $toNumber,
        public string $agentId,
        public \DateTime $startTime,
        string $correlationId = null,
        string $causedBy = null,
        array $metadata = []
    ) {
        parent::__construct(
            aggregateId: $callId,
            correlationId: $correlationId,
            causedBy: $causedBy,
            metadata: $metadata
        );
    }

    protected function getPayload(): array
    {
        return [
            'callId' => $this->callId,
            'fromNumber' => $this->fromNumber,
            'toNumber' => $this->toNumber,
            'agentId' => $this->agentId,
            'startTime' => $this->startTime->toIso8601String(),
        ];
    }
}
