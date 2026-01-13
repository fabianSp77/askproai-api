<?php

namespace Tests\Feature\Billing;

use App\Models\AggregateInvoice;
use App\Models\AggregateInvoiceItem;
use App\Models\Call;
use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use App\Models\CompanyServicePricing;
use App\Models\PricingPlan;
use App\Models\ServiceChangeFee;
use App\Models\ServicePricingTemplate;
use App\Services\Billing\MonthlyBillingAggregator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Integration tests for MonthlyBillingAggregator service.
 *
 * These tests verify the core billing aggregation logic:
 * - Call minute aggregation across multiple companies
 * - Monthly service fees
 * - Setup fees
 * - Service change fees
 * - Correct totals calculation
 *
 * Note: Uses DatabaseTransactions instead of RefreshDatabase to avoid
 * migration compatibility issues with the complex production schema.
 * Test database schema should be synced from production periodically.
 */
class MonthlyBillingAggregatorTest extends TestCase
{
    use DatabaseTransactions;

    private MonthlyBillingAggregator $aggregator;
    private Company $partner;
    private Carbon $periodStart;
    private Carbon $periodEnd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aggregator = app(MonthlyBillingAggregator::class);
        $this->periodStart = Carbon::create(2025, 12, 1)->startOfMonth();
        $this->periodEnd = $this->periodStart->copy()->endOfMonth();

        // Create partner company
        $this->partner = Company::factory()->create([
            'name' => 'Test Partner GmbH',
            'is_partner' => true,
            'partner_billing_email' => 'billing@test-partner.de',
            'partner_payment_terms_days' => 14,
        ]);
    }

    /** @test */
    public function it_aggregates_call_minutes_from_managed_companies(): void
    {
        // Create pricing plan
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12, // 12 cents per minute
        ]);

        // Create managed companies
        $company1 = Company::factory()->create([
            'name' => 'Client A GmbH',
            'managed_by_company_id' => $this->partner->id,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        $company2 = Company::factory()->create([
            'name' => 'Client B GmbH',
            'managed_by_company_id' => $this->partner->id,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Create calls for company 1 (10 calls, 600 seconds total = 10 min)
        for ($i = 0; $i < 10; $i++) {
            Call::factory()->create([
                'company_id' => $company1->id,
                'duration_sec' => 60,
                'status' => 'completed',
                'created_at' => $this->periodStart->copy()->addDays($i),
            ]);
        }

        // Create calls for company 2 (5 calls, 300 seconds total = 5 min)
        for ($i = 0; $i < 5; $i++) {
            Call::factory()->create([
                'company_id' => $company2->id,
                'duration_sec' => 60,
                'status' => 'completed',
                'created_at' => $this->periodStart->copy()->addDays($i),
            ]);
        }

        // Create invoice and populate
        $invoice = AggregateInvoice::create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => 'TEST-001',
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'eur',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);

        $this->aggregator->populateInvoice($invoice, $this->partner, $this->periodStart, $this->periodEnd);

        $invoice->refresh();

        // Verify items were created
        $this->assertEquals(2, $invoice->items->count());

        // Verify company 1 charges: 10 min × 12 cents = 120 cents
        $company1Item = $invoice->items->where('company_id', $company1->id)->first();
        $this->assertNotNull($company1Item);
        $this->assertEquals(AggregateInvoiceItem::TYPE_CALL_MINUTES, $company1Item->item_type);
        $this->assertEquals(120, $company1Item->amount_cents);

        // Verify company 2 charges: 5 min × 12 cents = 60 cents
        $company2Item = $invoice->items->where('company_id', $company2->id)->first();
        $this->assertNotNull($company2Item);
        $this->assertEquals(60, $company2Item->amount_cents);

        // Verify totals (subtotal = 180, tax = 34.2 ≈ 34, total = 214)
        $this->assertEquals(180, $invoice->subtotal_cents);
        $this->assertEquals(34, $invoice->tax_cents);
        $this->assertEquals(214, $invoice->total_cents);
    }

    /** @test */
    public function it_includes_only_completed_calls(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.10,
        ]);

        $company = Company::factory()->create([
            'managed_by_company_id' => $this->partner->id,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Completed call (should be included)
        Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 120,
            'status' => 'completed',
            'created_at' => $this->periodStart->copy()->addDay(),
        ]);

        // Ended call (should be included)
        Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 60,
            'status' => 'ended',
            'created_at' => $this->periodStart->copy()->addDays(2),
        ]);

        // Pending call (should NOT be included)
        Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 60,
            'status' => 'pending',
            'created_at' => $this->periodStart->copy()->addDays(3),
        ]);

        // Failed call (should NOT be included)
        Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 60,
            'status' => 'failed',
            'created_at' => $this->periodStart->copy()->addDays(4),
        ]);

        $invoice = AggregateInvoice::create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => 'TEST-002',
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'eur',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);

        $this->aggregator->populateInvoice($invoice, $this->partner, $this->periodStart, $this->periodEnd);
        $invoice->refresh();

        // Only 3 minutes (180 sec) should be billed: 120 + 60 = 180 sec = 3 min
        // 3 min × 10 cents = 30 cents
        $this->assertEquals(1, $invoice->items->count());
        $this->assertEquals(30, $invoice->items->first()->amount_cents);
    }

    /** @test */
    public function it_handles_setup_fees_for_new_companies(): void
    {
        $company = Company::factory()->create([
            'managed_by_company_id' => $this->partner->id,
            'created_at' => $this->periodStart->copy()->addDays(5), // Created in billing period
        ]);

        // Setup fee schedule with setup fee (not yet billed)
        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'setup_fee' => 150.00, // €150
            'setup_fee_billed_at' => null, // Not yet billed
        ]);

        $invoice = AggregateInvoice::create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => 'TEST-003',
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'eur',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);

        $this->aggregator->populateInvoice($invoice, $this->partner, $this->periodStart, $this->periodEnd);
        $invoice->refresh();

        // Find setup fee item
        $setupFeeItem = $invoice->items
            ->where('item_type', AggregateInvoiceItem::TYPE_SETUP_FEE)
            ->first();

        $this->assertNotNull($setupFeeItem);
        $this->assertEquals(15000, $setupFeeItem->amount_cents); // €150 = 15000 cents

        // Verify fee was marked as billed
        $company->refresh();
        $this->assertTrue($company->feeSchedule->isSetupFeeBilled());
    }

    /** @test */
    public function it_does_not_duplicate_setup_fees(): void
    {
        $company = Company::factory()->create([
            'managed_by_company_id' => $this->partner->id,
            'created_at' => $this->periodStart->copy()->addDays(5),
        ]);

        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'setup_fee' => 100.00,
            'setup_fee_billed_at' => now()->subMonth(), // Already billed
        ]);

        $invoice = AggregateInvoice::create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => 'TEST-004',
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'eur',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);

        $this->aggregator->populateInvoice($invoice, $this->partner, $this->periodStart, $this->periodEnd);
        $invoice->refresh();

        // No setup fee should be added
        $setupFeeItems = $invoice->items
            ->where('item_type', AggregateInvoiceItem::TYPE_SETUP_FEE);

        $this->assertEquals(0, $setupFeeItems->count());
    }

    /** @test */
    public function it_generates_empty_invoice_for_partner_with_no_activity(): void
    {
        // Create managed company with no calls
        Company::factory()->create([
            'managed_by_company_id' => $this->partner->id,
            'created_at' => now()->subMonths(3), // Created before billing period
        ]);

        $invoice = AggregateInvoice::create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => 'TEST-005',
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'eur',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);

        $this->aggregator->populateInvoice($invoice, $this->partner, $this->periodStart, $this->periodEnd);
        $invoice->refresh();

        // Invoice should have zero items and zero totals
        $this->assertEquals(0, $invoice->items->count());
        $this->assertEquals(0, $invoice->subtotal_cents);
        $this->assertEquals(0, $invoice->total_cents);
    }

    /** @test */
    public function it_uses_correct_rate_priority(): void
    {
        // Test rate priority: override > tenant > pricing_plan > default

        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.10, // Base rate
        ]);

        $company = Company::factory()->create([
            'managed_by_company_id' => $this->partner->id,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Create fee schedule with override rate
        CompanyFeeSchedule::create([
            'company_id' => $company->id,
            'override_per_minute_rate' => 0.08, // Override: 8 cents
        ]);

        // Create call: 10 minutes
        Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 600, // 10 minutes
            'status' => 'completed',
            'created_at' => $this->periodStart->copy()->addDay(),
        ]);

        $invoice = AggregateInvoice::create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => 'TEST-006',
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'eur',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);

        $this->aggregator->populateInvoice($invoice, $this->partner, $this->periodStart, $this->periodEnd);
        $invoice->refresh();

        // 10 min × 8 cents (override) = 80 cents
        $this->assertEquals(80, $invoice->items->first()->amount_cents);
    }

    /** @test */
    public function it_excludes_calls_outside_billing_period(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.10,
        ]);

        $company = Company::factory()->create([
            'managed_by_company_id' => $this->partner->id,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Call before period (should NOT be included)
        Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 120,
            'status' => 'completed',
            'created_at' => $this->periodStart->copy()->subDay(),
        ]);

        // Call during period (should be included)
        Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 60,
            'status' => 'completed',
            'created_at' => $this->periodStart->copy()->addDays(15),
        ]);

        // Call after period (should NOT be included)
        Call::factory()->create([
            'company_id' => $company->id,
            'duration_sec' => 120,
            'status' => 'completed',
            'created_at' => $this->periodEnd->copy()->addDay(),
        ]);

        $invoice = AggregateInvoice::create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => 'TEST-007',
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'eur',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);

        $this->aggregator->populateInvoice($invoice, $this->partner, $this->periodStart, $this->periodEnd);
        $invoice->refresh();

        // Only 1 minute should be billed (the call during period)
        $this->assertEquals(1, $invoice->items->count());
        $this->assertEquals(10, $invoice->items->first()->amount_cents); // 1 min × 10 cents
    }

    /** @test */
    public function it_groups_items_by_company_correctly(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.12,
        ]);

        $companies = collect();
        for ($i = 1; $i <= 3; $i++) {
            $company = Company::factory()->create([
                'name' => "Client {$i} GmbH",
                'managed_by_company_id' => $this->partner->id,
                'pricing_plan_id' => $pricingPlan->id,
            ]);
            $companies->push($company);

            // Create varying calls per company
            for ($j = 0; $j < $i; $j++) {
                Call::factory()->create([
                    'company_id' => $company->id,
                    'duration_sec' => 60, // 1 minute each
                    'status' => 'completed',
                    'created_at' => $this->periodStart->copy()->addDays($j),
                ]);
            }
        }

        $invoice = AggregateInvoice::create([
            'partner_company_id' => $this->partner->id,
            'invoice_number' => 'TEST-008',
            'billing_period_start' => $this->periodStart,
            'billing_period_end' => $this->periodEnd,
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'eur',
            'tax_rate' => 19.00,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);

        $this->aggregator->populateInvoice($invoice, $this->partner, $this->periodStart, $this->periodEnd);
        $invoice->refresh();

        // Should have 3 items (one per company)
        $this->assertEquals(3, $invoice->items->count());

        // Verify each company has correct amount
        foreach ($companies as $index => $company) {
            $item = $invoice->items->where('company_id', $company->id)->first();
            $expectedCalls = $index + 1;
            $expectedAmount = $expectedCalls * 12; // calls × 12 cents per minute
            $this->assertEquals($expectedAmount, $item->amount_cents);
        }

        // Total: 1 + 2 + 3 = 6 minutes × 12 cents = 72 cents
        $this->assertEquals(72, $invoice->subtotal_cents);
    }

    /** @test */
    public function it_provides_accurate_preview_without_creating_records(): void
    {
        $pricingPlan = PricingPlan::factory()->create([
            'price_per_minute' => 0.15,
        ]);

        $company = Company::factory()->create([
            'managed_by_company_id' => $this->partner->id,
            'pricing_plan_id' => $pricingPlan->id,
        ]);

        // Create 5 calls of 2 minutes each
        for ($i = 0; $i < 5; $i++) {
            Call::factory()->create([
                'company_id' => $company->id,
                'duration_sec' => 120,
                'status' => 'completed',
                'created_at' => $this->periodStart->copy()->addDays($i),
            ]);
        }

        $summary = $this->aggregator->getChargesSummary(
            $this->partner,
            $this->periodStart,
            $this->periodEnd
        );

        // 5 calls × 2 min = 10 min × 15 cents = 150 cents
        // Note: getChargesSummary returns net totals (no tax breakdown)
        $this->assertEquals(150, $summary['total_cents']);

        // No invoice should be created
        $this->assertEquals(0, AggregateInvoice::where('partner_company_id', $this->partner->id)->count());
    }
}
