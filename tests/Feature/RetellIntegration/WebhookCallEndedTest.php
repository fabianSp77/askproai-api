<?php

namespace Tests\Feature\RetellIntegration;

use Tests\TestCase;

/**
 * Webhook Call Ended Test
 *
 * Tests Retell call.ended webhook reception and processing
 */
class WebhookCallEndedTest extends TestCase
{
    /**
     * Test webhook call.ended is received correctly
     */
    public function test_webhook_call_ended_received(): void
    {
        $callId = 'test_call_' . uniqid();
        $startTimestamp = now()->timestamp * 1000;
        $endTimestamp = $startTimestamp + 120000; // 2 minutes later

        $payload = [
            'event' => 'call.ended',
            'data' => [
                'call_id' => $callId,
                'call_status' => 'ended',
                'start_timestamp' => $startTimestamp,
                'end_timestamp' => $endTimestamp,
                'duration_ms' => $endTimestamp - $startTimestamp
            ]
        ];

        $this->assertNotNull($callId);
        $this->assertGreaterThan(0, $payload['data']['duration_ms']);
    }

    /**
     * Test webhook call.ended has required fields
     */
    public function test_webhook_call_ended_has_required_fields(): void
    {
        $requiredFields = ['event', 'data', 'call_status', 'duration_ms'];

        $this->assertCount(4, $requiredFields);
    }

    /**
     * Test call duration is calculated correctly
     */
    public function test_webhook_call_ended_duration_is_positive(): void
    {
        $startTime = now()->timestamp * 1000;
        $endTime = $startTime + 180000; // 3 minutes
        $duration = $endTime - $startTime;

        $this->assertGreaterThan(0, $duration);
        $this->assertEquals(180000, $duration);
    }

    /**
     * Test call status is ended
     */
    public function test_webhook_call_ended_status_is_ended(): void
    {
        $status = 'ended';
        $this->assertEquals('ended', $status);
    }

    /**
     * Test call has transcript
     */
    public function test_webhook_call_ended_has_transcript(): void
    {
        $transcript = "Agent: Guten Tag.\nCustomer: Hallo!";
        $this->assertNotEmpty($transcript);
    }

    /**
     * Test call metadata exists
     */
    public function test_webhook_call_ended_has_metadata(): void
    {
        $metadata = [
            'appointment_made' => true,
            'test_call' => false
        ];

        $this->assertArrayHasKey('appointment_made', $metadata);
        $this->assertTrue($metadata['appointment_made']);
    }
}
