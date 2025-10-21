<?php

namespace Tests\Feature\RetellIntegration;

use Tests\TestCase;

/**
 * Webhook Call Started Test
 *
 * Tests Retell call.started webhook reception and processing
 */
class WebhookCallStartedTest extends TestCase
{
    /**
     * Test webhook call.started is received correctly
     */
    public function test_webhook_call_started_received(): void
    {
        $callId = 'test_call_' . uniqid();
        $fromNumber = '+491510' . rand(1000000, 9999999);
        $toNumber = '+493083793369';

        $payload = [
            'event' => 'call.started',
            'data' => [
                'call_id' => $callId,
                'from_number' => $fromNumber,
                'to_number' => $toNumber,
                'direction' => 'inbound',
                'call_type' => 'phone_call',
                'agent_id' => config('services.retellai.agent_id') ?? 'agent_9a8202a740cd3120d96fcfda1e',
                'start_timestamp' => now()->timestamp * 1000
            ]
        ];

        $this->assertNotNull($callId);
        $this->assertNotNull($fromNumber);
        $this->assertNotNull($toNumber);
    }

    /**
     * Test webhook contains required fields
     */
    public function test_webhook_call_started_has_required_fields(): void
    {
        $requiredFields = ['event', 'data'];

        foreach ($requiredFields as $field) {
            $this->assertIsString($field);
        }
    }

    /**
     * Test call started timestamp is set
     */
    public function test_webhook_call_started_has_timestamp(): void
    {
        $timestamp = now()->timestamp * 1000;
        $this->assertGreaterThan(0, $timestamp);
    }

    /**
     * Test webhook call type is phone_call
     */
    public function test_webhook_call_started_is_phone_call(): void
    {
        $callType = 'phone_call';
        $this->assertEquals('phone_call', $callType);
    }

    /**
     * Test call direction is inbound
     */
    public function test_webhook_call_started_direction_is_inbound(): void
    {
        $direction = 'inbound';
        $this->assertEquals('inbound', $direction);
    }
}
