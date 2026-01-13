<?php

namespace Tests\Unit\Models;

use App\Models\AggregateInvoice;
use App\Models\AggregateInvoiceItem;
use App\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit tests for AggregateInvoice model.
 *
 * Tests: Status transitions, calculateTotals, accessors, discount handling.
 */
class AggregateInvoiceModelTest extends TestCase
{
    use DatabaseTransactions;

    private Company $partner;
    private AggregateInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Company::factory()->create([
            'name' => 'Test Partner GmbH',
            'is_partner' => true,
            'partner_billing_email' => 'billing@test-partner.de',
            'partner_payment_terms_days' => 14,
        ]);

        $this->invoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_DRAFT,
            'subtotal_cents' => 10000, // 100 EUR
            'tax_rate' => 19.00,
            'tax_cents' => 1900, // 19 EUR
            'total_cents' => 11900, // 119 EUR
        ]);
    }

    // ========================================
    // STATUS TRANSITIONS
    // ========================================

    /** @test */
    public function draft_invoice_can_be_finalized(): void
    {
        $this->assertEquals(AggregateInvoice::STATUS_DRAFT, $this->invoice->status);

        $this->invoice->finalize();

        $this->assertEquals(AggregateInvoice::STATUS_OPEN, $this->invoice->fresh()->status);
        $this->assertNotNull($this->invoice->fresh()->finalized_at);
        $this->assertNotNull($this->invoice->fresh()->due_at);
    }

    /** @test */
    public function open_invoice_can_be_marked_as_paid(): void
    {
        $this->invoice->finalize();
        $this->assertEquals(AggregateInvoice::STATUS_OPEN, $this->invoice->fresh()->status);

        $this->invoice->markAsPaid();

        $this->assertEquals(AggregateInvoice::STATUS_PAID, $this->invoice->fresh()->status);
        $this->assertNotNull($this->invoice->fresh()->paid_at);
    }

    /** @test */
    public function open_invoice_can_be_marked_as_uncollectible(): void
    {
        $this->invoice->finalize();

        $this->invoice->markAsUncollectible();

        $this->assertEquals(AggregateInvoice::STATUS_UNCOLLECTIBLE, $this->invoice->fresh()->status);
    }

    /** @test */
    public function draft_invoice_can_be_voided(): void
    {
        $this->invoice->void();

        $this->assertEquals(AggregateInvoice::STATUS_VOID, $this->invoice->fresh()->status);
    }

    /** @test */
    public function paid_invoice_cannot_be_voided(): void
    {
        $this->invoice->finalize();
        $this->invoice->markAsPaid();

        $this->expectException(\Exception::class);
        $this->invoice->void();
    }

    /** @test */
    public function already_finalized_invoice_cannot_be_finalized_again(): void
    {
        $this->invoice->finalize();

        $this->expectException(\Exception::class);
        $this->invoice->finalize();
    }

    // ========================================
    // CALCULATE TOTALS
    // ========================================

    /** @test */
    public function calculate_totals_sums_items_correctly(): void
    {
        // Clear existing totals
        $this->invoice->update([
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
        ]);

        // Create items
        AggregateInvoiceItem::factory()->create([
            'aggregate_invoice_id' => $this->invoice->id,
            'amount_cents' => 5000, // 50 EUR
        ]);

        AggregateInvoiceItem::factory()->create([
            'aggregate_invoice_id' => $this->invoice->id,
            'amount_cents' => 3000, // 30 EUR
        ]);

        $this->invoice->calculateTotals();

        $this->assertEquals(8000, $this->invoice->fresh()->subtotal_cents); // 80 EUR
        $this->assertEquals(1520, $this->invoice->fresh()->tax_cents); // 15.20 EUR (19%)
        $this->assertEquals(9520, $this->invoice->fresh()->total_cents); // 95.20 EUR
    }

    /** @test */
    public function calculate_totals_applies_discount_before_tax(): void
    {
        // German VAT compliance: discount must be applied before tax calculation

        // Set up invoice with items
        $this->invoice->update([
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'discount_cents' => 1000, // 10 EUR discount
            'discount_description' => 'Neukundenrabatt',
        ]);

        // Create item worth 100 EUR
        AggregateInvoiceItem::factory()->create([
            'aggregate_invoice_id' => $this->invoice->id,
            'amount_cents' => 10000, // 100 EUR
        ]);

        $this->invoice->calculateTotals();

        // Subtotal: 100 EUR
        $this->assertEquals(10000, $this->invoice->fresh()->subtotal_cents);

        // Taxable amount: 100 - 10 = 90 EUR
        // Tax: 90 * 0.19 = 17.10 EUR
        $this->assertEquals(1710, $this->invoice->fresh()->tax_cents);

        // Total: 90 + 17.10 = 107.10 EUR
        $this->assertEquals(10710, $this->invoice->fresh()->total_cents);
    }

    /** @test */
    public function discount_cannot_exceed_subtotal(): void
    {
        $this->invoice->update([
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'discount_cents' => 20000, // 200 EUR discount (more than subtotal)
        ]);

        AggregateInvoiceItem::factory()->create([
            'aggregate_invoice_id' => $this->invoice->id,
            'amount_cents' => 10000, // 100 EUR
        ]);

        $this->invoice->calculateTotals();

        // Taxable amount should be 0 (not negative)
        $this->assertEquals(0, $this->invoice->fresh()->tax_cents);
        $this->assertEquals(0, $this->invoice->fresh()->total_cents);
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /** @test */
    public function subtotal_accessor_converts_cents_to_euros(): void
    {
        $this->invoice->update(['subtotal_cents' => 12345]);

        $this->assertEquals(123.45, $this->invoice->fresh()->subtotal);
    }

    /** @test */
    public function tax_accessor_converts_cents_to_euros(): void
    {
        $this->invoice->update(['tax_cents' => 1900]);

        $this->assertEquals(19.00, $this->invoice->fresh()->tax);
    }

    /** @test */
    public function total_accessor_converts_cents_to_euros(): void
    {
        $this->invoice->update(['total_cents' => 11900]);

        $this->assertEquals(119.00, $this->invoice->fresh()->total);
    }

    /** @test */
    public function discount_accessor_converts_cents_to_euros(): void
    {
        $this->invoice->update(['discount_cents' => 500]);

        $this->assertEquals(5.00, $this->invoice->fresh()->discount);
    }

    /** @test */
    public function discount_accessor_returns_zero_when_zero(): void
    {
        $this->invoice->update(['discount_cents' => 0]);

        $this->assertEquals(0.0, $this->invoice->fresh()->discount);
    }

    /** @test */
    public function discount_mutator_converts_euros_to_cents(): void
    {
        $this->invoice->discount = 15.50;
        $this->invoice->save();

        $this->assertEquals(1550, $this->invoice->fresh()->discount_cents);
    }

    /** @test */
    public function formatted_total_displays_german_format(): void
    {
        $this->invoice->update(['total_cents' => 123456]); // 1234.56 EUR

        $this->assertEquals('1.234,56 €', $this->invoice->fresh()->formatted_total);
    }

    /** @test */
    public function is_editable_returns_true_only_for_drafts(): void
    {
        $this->assertTrue($this->invoice->is_editable);

        $this->invoice->finalize();
        $this->assertFalse($this->invoice->fresh()->is_editable);

        $this->invoice->markAsPaid();
        $this->assertFalse($this->invoice->fresh()->is_editable);
    }

    /** @test */
    public function is_overdue_returns_true_for_past_due_open_invoices(): void
    {
        $this->invoice->finalize();
        $this->invoice->update(['due_at' => now()->subDays(5)]);

        $this->assertTrue($this->invoice->fresh()->is_overdue);
    }

    /** @test */
    public function is_overdue_returns_false_for_paid_invoices(): void
    {
        $this->invoice->finalize();
        $this->invoice->update(['due_at' => now()->subDays(5)]);
        $this->invoice->markAsPaid();

        $this->assertFalse($this->invoice->fresh()->is_overdue);
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /** @test */
    public function invoice_belongs_to_partner_company(): void
    {
        $this->assertInstanceOf(Company::class, $this->invoice->partnerCompany);
        $this->assertEquals($this->partner->id, $this->invoice->partnerCompany->id);
    }

    /** @test */
    public function invoice_has_many_items(): void
    {
        AggregateInvoiceItem::factory()->count(3)->create([
            'aggregate_invoice_id' => $this->invoice->id,
        ]);

        $this->assertCount(3, $this->invoice->fresh()->items);
    }

    // ========================================
    // STATUS LABELS & COLORS
    // ========================================

    /** @test */
    public function status_label_returns_german_labels(): void
    {
        $this->assertEquals('Entwurf', $this->invoice->getStatusLabel());

        $this->invoice->finalize();
        $this->assertEquals('Offen', $this->invoice->fresh()->getStatusLabel());

        $this->invoice->update(['due_at' => now()->subDays(5)]);
        $this->assertEquals('Überfällig', $this->invoice->fresh()->getStatusLabel());

        $this->invoice->markAsPaid();
        $this->assertEquals('Bezahlt', $this->invoice->fresh()->getStatusLabel());
    }

    /** @test */
    public function status_color_returns_correct_filament_colors(): void
    {
        $this->assertEquals('gray', $this->invoice->getStatusColor());

        $this->invoice->finalize();
        $this->assertEquals('warning', $this->invoice->fresh()->getStatusColor());

        $this->invoice->update(['due_at' => now()->subDays(5)]);
        $this->assertEquals('danger', $this->invoice->fresh()->getStatusColor());

        $this->invoice->markAsPaid();
        $this->assertEquals('success', $this->invoice->fresh()->getStatusColor());
    }
}
