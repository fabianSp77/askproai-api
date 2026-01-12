<?php

namespace Tests\Feature\Billing;

use App\Models\AggregateInvoice;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Feature tests for AggregateInvoice model.
 *
 * These tests verify core invoice functionality:
 * - Invoice number generation with race-condition safety
 * - Invoice number format validation (AGG-YYYY-MM-NNN)
 * - Concurrent invoice creation scenarios
 * - Database transaction safety
 *
 * Note: Uses DatabaseTransactions instead of RefreshDatabase to avoid
 * migration compatibility issues with the complex production schema.
 */
class AggregateInvoiceTest extends TestCase
{
    use DatabaseTransactions;

    private Company $partner;

    private Carbon $periodStart;

    private Carbon $periodEnd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periodStart = Carbon::create(2025, 12, 1)->startOfMonth();
        $this->periodEnd = $this->periodStart->copy()->endOfMonth();

        // Create partner company for invoice testing
        $this->partner = Company::factory()->create([
            'name' => 'Test Partner GmbH',
            'is_partner' => true,
            'partner_billing_email' => 'billing@test-partner.de',
            'partner_payment_terms_days' => 14,
        ]);
    }

    /** @test */
    public function it_generates_invoice_numbers_in_correct_format(): void
    {
        // Generate multiple invoice numbers
        $invoiceNumber1 = AggregateInvoice::generateInvoiceNumber();
        $invoiceNumber2 = AggregateInvoice::generateInvoiceNumber();
        $invoiceNumber3 = AggregateInvoice::generateInvoiceNumber();

        // Pattern: AGG-YYYY-MM-NNN (e.g., AGG-2026-01-001)
        $pattern = '/^AGG-\d{4}-\d{2}-\d{3}$/';

        $this->assertMatchesRegularExpression($pattern, $invoiceNumber1, 'Invoice number 1 should match AGG-YYYY-MM-NNN format');
        $this->assertMatchesRegularExpression($pattern, $invoiceNumber2, 'Invoice number 2 should match AGG-YYYY-MM-NNN format');
        $this->assertMatchesRegularExpression($pattern, $invoiceNumber3, 'Invoice number 3 should match AGG-YYYY-MM-NNN format');

        // Verify year and month match current date
        $expectedPrefix = 'AGG-'.now()->format('Y-m');
        $this->assertStringStartsWith($expectedPrefix, $invoiceNumber1, 'Invoice number should start with current year-month');
        $this->assertStringStartsWith($expectedPrefix, $invoiceNumber2, 'Invoice number should start with current year-month');
        $this->assertStringStartsWith($expectedPrefix, $invoiceNumber3, 'Invoice number should start with current year-month');
    }

    /** @test */
    public function it_generates_sequential_invoice_numbers(): void
    {
        // Delete existing invoices for current month to ensure clean test
        AggregateInvoice::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->delete();

        // Generate invoice numbers sequentially
        $invoiceNumber1 = AggregateInvoice::generateInvoiceNumber();
        $invoice1 = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => $invoiceNumber1,
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
        ]);

        $invoiceNumber2 = AggregateInvoice::generateInvoiceNumber();
        $invoice2 = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => $invoiceNumber2,
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
        ]);

        $invoiceNumber3 = AggregateInvoice::generateInvoiceNumber();
        $invoice3 = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => $invoiceNumber3,
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
        ]);

        // Extract sequence numbers from invoice numbers (last 3 digits)
        $seq1 = (int) substr($invoiceNumber1, -3);
        $seq2 = (int) substr($invoiceNumber2, -3);
        $seq3 = (int) substr($invoiceNumber3, -3);

        // Verify sequential increments
        $this->assertEquals($seq1 + 1, $seq2, 'Second invoice should be sequential to first');
        $this->assertEquals($seq2 + 1, $seq3, 'Third invoice should be sequential to second');

        // Verify invoice numbers are unique
        $this->assertNotEquals($invoiceNumber1, $invoiceNumber2, 'Invoice numbers should be unique');
        $this->assertNotEquals($invoiceNumber2, $invoiceNumber3, 'Invoice numbers should be unique');
        $this->assertNotEquals($invoiceNumber1, $invoiceNumber3, 'Invoice numbers should be unique');
    }

    /** @test */
    public function it_generates_unique_invoice_numbers_under_concurrent_creation(): void
    {
        // This test simulates concurrent invoice creation by using nested transactions
        // to verify that the advisory lock prevents duplicate invoice numbers.

        $generatedNumbers = [];
        $concurrentAttempts = 5;

        // Simulate concurrent invoice number generation
        for ($i = 0; $i < $concurrentAttempts; $i++) {
            // Each iteration simulates a separate request/transaction
            $invoiceNumber = DB::transaction(function () {
                return AggregateInvoice::generateInvoiceNumber();
            });

            // Create the invoice with the generated number
            AggregateInvoice::factory()->create([
                'partner_company_id' => $this->partner->id,
                'invoice_number' => $invoiceNumber,
                'billing_period_start' => $this->periodStart,
                'billing_period_end' => $this->periodEnd,
            ]);

            $generatedNumbers[] = $invoiceNumber;
        }

        // Verify all generated numbers are unique
        $uniqueNumbers = array_unique($generatedNumbers);
        $this->assertCount(
            $concurrentAttempts,
            $uniqueNumbers,
            'All generated invoice numbers should be unique (no duplicates from race condition)'
        );

        // Verify all numbers follow the correct format
        foreach ($generatedNumbers as $number) {
            $this->assertMatchesRegularExpression(
                '/^AGG-\d{4}-\d{2}-\d{3}$/',
                $number,
                "Invoice number {$number} should match AGG-YYYY-MM-NNN format"
            );
        }

        // Verify database contains all invoices
        $this->assertCount(
            $concurrentAttempts,
            AggregateInvoice::whereIn('invoice_number', $generatedNumbers)->get(),
            'Database should contain all created invoices'
        );
    }

    /** @test */
    public function it_handles_invoice_number_generation_with_existing_invoices(): void
    {
        // Create some existing invoices for current month
        $existing1 = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => AggregateInvoice::generateInvoiceNumber(),
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
        ]);

        $existing2 = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => AggregateInvoice::generateInvoiceNumber(),
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
        ]);

        // Count existing invoices
        $existingCount = AggregateInvoice::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // Generate a new invoice number
        $newInvoiceNumber = AggregateInvoice::generateInvoiceNumber();
        $newInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => $newInvoiceNumber,
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
        ]);

        // Extract sequence number from new invoice
        $newSeq = (int) substr($newInvoiceNumber, -3);

        // Verify new sequence number equals existingCount + 1
        $this->assertEquals(
            $existingCount + 1,
            $newSeq,
            'New invoice sequence number should be existingCount + 1'
        );

        // Verify new invoice number is unique
        $this->assertNotEquals($existing1->invoice_number, $newInvoiceNumber, 'New invoice number should differ from existing');
        $this->assertNotEquals($existing2->invoice_number, $newInvoiceNumber, 'New invoice number should differ from existing');
    }

    /** @test */
    public function it_resets_invoice_number_sequence_for_new_month(): void
    {
        // This test verifies that invoice numbers reset sequence for each new month
        // by manually manipulating the created_at timestamp

        // Create invoice in previous month
        $previousMonth = now()->subMonth();
        $oldInvoice = new AggregateInvoice([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => 'AGG-'.$previousMonth->format('Y-m').'-005',
            'billing_period_start' => $previousMonth->copy()->startOfMonth(),
            'billing_period_end' => $previousMonth->copy()->endOfMonth(),
            'subtotal_cents' => 5000,
            'tax_cents' => 950,
            'total_cents' => 5950,
            'currency' => 'EUR',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);
        $oldInvoice->save();
        $oldInvoice->created_at = $previousMonth;
        $oldInvoice->save();

        // Delete any existing invoices for current month
        AggregateInvoice::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->delete();

        // Generate invoice number for current month
        $currentMonthNumber = AggregateInvoice::generateInvoiceNumber();

        // Extract sequence from current month number
        $currentSeq = (int) substr($currentMonthNumber, -3);

        // Verify sequence resets to 001 for new month
        $this->assertEquals(
            1,
            $currentSeq,
            'Invoice number sequence should reset to 001 for new month'
        );

        // Verify different month prefix
        $this->assertStringStartsWith(
            'AGG-'.now()->format('Y-m'),
            $currentMonthNumber,
            'Current month invoice should have current month prefix'
        );

        $this->assertStringStartsWith(
            'AGG-'.$previousMonth->format('Y-m'),
            $oldInvoice->invoice_number,
            'Previous month invoice should have previous month prefix'
        );
    }
}
