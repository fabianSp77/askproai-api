<?php

namespace Tests\Feature\Billing;

use App\Models\StripeEvent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Feature tests for StripeEvent model.
 *
 * Tests webhook idempotency functionality that prevents
 * duplicate processing of Stripe webhook events.
 */
class StripeEventTest extends TestCase
{
    use DatabaseTransactions;

    // =========================================================================
    // isDuplicate() Tests
    // =========================================================================

    /** @test */
    public function is_duplicate_returns_false_for_new_event(): void
    {
        $eventId = 'evt_test_' . uniqid();

        $this->assertFalse(StripeEvent::isDuplicate($eventId));
    }

    /** @test */
    public function is_duplicate_returns_true_for_already_processed_event(): void
    {
        $eventId = 'evt_test_duplicate_check';

        // Process the event first
        StripeEvent::markAsProcessed($eventId, 'invoice.paid');

        // Now it should be detected as duplicate
        $this->assertTrue(StripeEvent::isDuplicate($eventId));
    }

    /** @test */
    public function is_duplicate_distinguishes_between_different_event_ids(): void
    {
        $eventId1 = 'evt_test_event_1';
        $eventId2 = 'evt_test_event_2';

        StripeEvent::markAsProcessed($eventId1, 'invoice.paid');

        $this->assertTrue(StripeEvent::isDuplicate($eventId1));
        $this->assertFalse(StripeEvent::isDuplicate($eventId2));
    }

    // =========================================================================
    // markAsProcessed() Tests
    // =========================================================================

    /** @test */
    public function mark_as_processed_creates_record_with_correct_data(): void
    {
        Carbon::setTestNow('2026-01-12 18:00:00');

        $eventId = 'evt_test_mark_processed';
        $eventType = 'invoice.paid';

        $record = StripeEvent::markAsProcessed($eventId, $eventType);

        $this->assertDatabaseHas('stripe_events', [
            'event_id' => $eventId,
            'event_type' => $eventType,
        ]);

        $this->assertEquals($eventId, $record->event_id);
        $this->assertEquals($eventType, $record->event_type);
        $this->assertEquals('2026-01-12 18:00:00', $record->processed_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    /** @test */
    public function mark_as_processed_returns_stripe_event_model(): void
    {
        $eventId = 'evt_test_return_type';
        $eventType = 'invoice.payment_failed';

        $result = StripeEvent::markAsProcessed($eventId, $eventType);

        $this->assertInstanceOf(StripeEvent::class, $result);
        $this->assertTrue($result->exists);
        $this->assertNotNull($result->id);
    }

    /** @test */
    public function mark_as_processed_handles_different_event_types(): void
    {
        $eventTypes = [
            'invoice.paid',
            'invoice.payment_failed',
            'invoice.finalized',
            'invoice.voided',
        ];

        foreach ($eventTypes as $index => $eventType) {
            $eventId = "evt_test_type_{$index}";

            $record = StripeEvent::markAsProcessed($eventId, $eventType);

            $this->assertEquals($eventType, $record->event_type);
        }

        $this->assertCount(4, StripeEvent::all());
    }

    // =========================================================================
    // Database Constraint Tests
    // =========================================================================

    /** @test */
    public function duplicate_event_id_throws_exception(): void
    {
        $eventId = 'evt_test_unique_constraint';

        StripeEvent::markAsProcessed($eventId, 'invoice.paid');

        $this->expectException(\Illuminate\Database\QueryException::class);

        // This should fail due to unique constraint on event_id
        StripeEvent::markAsProcessed($eventId, 'invoice.paid');
    }

    /** @test */
    public function same_event_type_with_different_ids_is_allowed(): void
    {
        $eventType = 'invoice.paid';

        StripeEvent::markAsProcessed('evt_unique_1', $eventType);
        StripeEvent::markAsProcessed('evt_unique_2', $eventType);
        StripeEvent::markAsProcessed('evt_unique_3', $eventType);

        $count = StripeEvent::where('event_type', $eventType)->count();

        $this->assertEquals(3, $count);
    }

    // =========================================================================
    // Idempotency Workflow Tests
    // =========================================================================

    /** @test */
    public function typical_idempotency_workflow(): void
    {
        $eventId = 'evt_webhook_idempotency_test';
        $eventType = 'invoice.paid';

        // First webhook call: Check and process
        $this->assertFalse(StripeEvent::isDuplicate($eventId));
        StripeEvent::markAsProcessed($eventId, $eventType);

        // Stripe retry: Should be detected as duplicate
        $this->assertTrue(StripeEvent::isDuplicate($eventId));

        // Third retry: Still duplicate
        $this->assertTrue(StripeEvent::isDuplicate($eventId));
    }

    /** @test */
    public function processed_at_is_cast_to_carbon(): void
    {
        $record = StripeEvent::markAsProcessed('evt_carbon_test', 'invoice.paid');

        $this->assertInstanceOf(Carbon::class, $record->processed_at);
    }
}
