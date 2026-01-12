<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ServiceCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

/**
 * ServiceCase Billing State Machine Tests
 *
 * Tests the billing status state transitions and guards:
 * - unbilled → billed (allowed)
 * - unbilled → waived (allowed)
 * - billed → billed (NOT allowed - state guard)
 * - waived → billed (NOT allowed - state guard)
 * - billed → waived (NOT allowed - state guard)
 */
class ServiceCaseBillingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up SQLite in-memory database for faster unit tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Use SQLite in-memory for unit tests (faster and no DB permissions required)
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
    }

    /**
     * Test: unbilled → billed transition
     *
     * Verifies that an unbilled case can be marked as billed
     * with correct invoice item linkage and amount tracking.
     */
    public function test_unbilled_case_can_be_marked_as_billed(): void
    {
        // Arrange: Create an unbilled service case
        $serviceCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_UNBILLED,
            'billed_at' => null,
            'invoice_item_id' => null,
            'billed_amount_cents' => null,
        ]);

        $invoiceItemId = 123;
        $amountCents = 15000; // 150.00 EUR

        // Act: Mark the case as billed
        $serviceCase->markAsBilled($invoiceItemId, $amountCents);

        // Assert: Case is now billed with correct metadata
        $this->assertEquals(ServiceCase::BILLING_BILLED, $serviceCase->fresh()->billing_status);
        $this->assertNotNull($serviceCase->fresh()->billed_at);
        $this->assertEquals($invoiceItemId, $serviceCase->fresh()->invoice_item_id);
        $this->assertEquals($amountCents, $serviceCase->fresh()->billed_amount_cents);

        // Assert: isBillable() returns false after billing
        $this->assertFalse($serviceCase->fresh()->isBillable());
    }

    /**
     * Test: unbilled → waived transition
     *
     * Verifies that an unbilled case can be waived with a reason
     * stored in the ai_metadata field.
     */
    public function test_unbilled_case_can_be_marked_as_waived(): void
    {
        // Arrange: Create an unbilled service case
        $serviceCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_UNBILLED,
            'ai_metadata' => ['initial' => 'data'],
        ]);

        $waiverReason = 'Support case - internal testing';

        // Act: Mark the case as waived
        $serviceCase->markAsWaived($waiverReason);

        // Assert: Case is now waived with reason in metadata
        $this->assertEquals(ServiceCase::BILLING_WAIVED, $serviceCase->fresh()->billing_status);

        $metadata = $serviceCase->fresh()->ai_metadata;
        $this->assertArrayHasKey('billing_waived_at', $metadata);
        $this->assertArrayHasKey('billing_waived_reason', $metadata);
        $this->assertEquals($waiverReason, $metadata['billing_waived_reason']);

        // Assert: Original metadata preserved
        $this->assertEquals('data', $metadata['initial']);

        // Assert: isBillable() returns false after waiving
        $this->assertFalse($serviceCase->fresh()->isBillable());
    }

    /**
     * Test: billed cases cannot be re-billed (state guard)
     *
     * Verifies that attempting to bill an already-billed case
     * throws a LogicException and does NOT modify the database.
     */
    public function test_billed_case_cannot_be_rebilled(): void
    {
        // Arrange: Create a case that is already billed
        $serviceCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_BILLED,
            'billed_at' => now()->subDays(5),
            'invoice_item_id' => 999,
            'billed_amount_cents' => 10000,
        ]);

        // Store original values
        $originalBilledAt = $serviceCase->billed_at;
        $originalInvoiceItemId = $serviceCase->invoice_item_id;
        $originalAmountCents = $serviceCase->billed_amount_cents;

        // Act & Assert: Attempting to re-bill throws LogicException
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot bill ServiceCase {$serviceCase->id}: Current billing_status is 'billed', expected 'unbilled'");

        $serviceCase->markAsBilled(123, 15000);

        // Assert: Database NOT modified (exception thrown before update)
        $fresh = $serviceCase->fresh();
        $this->assertEquals(ServiceCase::BILLING_BILLED, $fresh->billing_status);
        $this->assertEquals($originalBilledAt, $fresh->billed_at);
        $this->assertEquals($originalInvoiceItemId, $fresh->invoice_item_id);
        $this->assertEquals($originalAmountCents, $fresh->billed_amount_cents);
    }

    /**
     * Test: waived cases cannot be billed
     *
     * Verifies that attempting to bill a waived case
     * throws a LogicException and does NOT modify the database.
     */
    public function test_waived_case_cannot_be_billed(): void
    {
        // Arrange: Create a waived case
        $serviceCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_WAIVED,
            'ai_metadata' => [
                'billing_waived_at' => now()->subDays(3)->toISOString(),
                'billing_waived_reason' => 'Customer support case',
            ],
        ]);

        // Act & Assert: Attempting to bill waived case throws LogicException
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot bill ServiceCase {$serviceCase->id}: Current billing_status is 'waived', expected 'unbilled'");

        $serviceCase->markAsBilled(123, 15000);

        // Assert: Case remains waived (exception thrown before update)
        $this->assertEquals(ServiceCase::BILLING_WAIVED, $serviceCase->fresh()->billing_status);
        $this->assertNull($serviceCase->fresh()->invoice_item_id);
        $this->assertNull($serviceCase->fresh()->billed_amount_cents);
    }

    /**
     * Test: billed cases cannot be waived (state guard)
     *
     * Verifies that attempting to waive an already-billed case
     * throws a LogicException and preserves the billing linkage.
     */
    public function test_billed_case_cannot_be_waived(): void
    {
        // Arrange: Create a case that is already billed
        $serviceCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_BILLED,
            'billed_at' => now()->subDays(5),
            'invoice_item_id' => 999,
            'billed_amount_cents' => 10000,
        ]);

        // Act & Assert: Attempting to waive billed case throws LogicException
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Cannot waive ServiceCase {$serviceCase->id}: Case is already billed (invoice_item_id: 999)");

        $serviceCase->markAsWaived('Trying to waive after billing');

        // Assert: Case remains billed (exception thrown before update)
        $this->assertEquals(ServiceCase::BILLING_BILLED, $serviceCase->fresh()->billing_status);
        $this->assertEquals(999, $serviceCase->fresh()->invoice_item_id);
    }

    /**
     * Test: isBillable() returns true only for unbilled cases
     *
     * Verifies the isBillable() method correctly identifies
     * which cases are eligible for billing.
     */
    public function test_is_billable_returns_true_only_for_unbilled_cases(): void
    {
        // Unbilled case - should be billable
        $unbilledCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_UNBILLED,
        ]);
        $this->assertTrue($unbilledCase->isBillable());

        // Billed case - should NOT be billable
        $billedCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_BILLED,
        ]);
        $this->assertFalse($billedCase->isBillable());

        // Waived case - should NOT be billable
        $waivedCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_WAIVED,
        ]);
        $this->assertFalse($waivedCase->isBillable());
    }

    /**
     * Test: Query scopes work correctly
     *
     * Verifies that scopeUnbilled, scopeBilled, and scopeWaived
     * correctly filter cases by billing status.
     */
    public function test_billing_status_query_scopes(): void
    {
        // Arrange: Create cases in different billing states
        $unbilledCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_UNBILLED,
        ]);

        $billedCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_BILLED,
            'invoice_item_id' => 123,
        ]);

        $waivedCase = ServiceCase::factory()->create([
            'billing_status' => ServiceCase::BILLING_WAIVED,
        ]);

        // Act & Assert: scopeUnbilled
        $unbilledCases = ServiceCase::unbilled()->get();
        $this->assertCount(1, $unbilledCases);
        $this->assertTrue($unbilledCases->contains($unbilledCase));

        // Act & Assert: scopeBilled
        $billedCases = ServiceCase::billed()->get();
        $this->assertCount(1, $billedCases);
        $this->assertTrue($billedCases->contains($billedCase));

        // Act & Assert: scopeWaived
        $waivedCases = ServiceCase::waived()->get();
        $this->assertCount(1, $waivedCases);
        $this->assertTrue($waivedCases->contains($waivedCase));
    }
}
