<?php

namespace Tests\Unit\Services\Billing;

use Tests\TestCase;
use App\Services\Billing\BillingPeriodService;
use App\Services\Billing\UsageCalculationService;
use App\Services\StripeServiceWithCircuitBreaker;
use App\Models\BillingPeriod;
use App\Models\Company;
use App\Models\Call;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class BillingPeriodServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BillingPeriodService $service;
    protected $mockStripeService;
    protected $mockUsageService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockStripeService = Mockery::mock(StripeServiceWithCircuitBreaker::class);
        $this->mockUsageService = Mockery::mock(UsageCalculationService::class);
        
        $this->service = new BillingPeriodService(
            $this->mockStripeService,
            $this->mockUsageService
        );
    }

    public function test_process_period_throws_exception_for_inactive_period()
    {
        $period = BillingPeriod::factory()->create(['status' => 'processed']);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only active periods can be processed');
        
        $this->service->processPeriod($period);
    }

    public function test_process_period_throws_exception_for_future_period()
    {
        $period = BillingPeriod::factory()->create([
            'status' => 'active',
            'end_date' => Carbon::now()->addDay()
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Period has not ended yet');
        
        $this->service->processPeriod($period);
    }

    public function test_calculate_period_usage_updates_billing_period()
    {
        $company = Company::factory()->create();
        $period = BillingPeriod::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'start_date' => Carbon::now()->subMonth(),
            'end_date' => Carbon::now()->subDay(),
            'included_minutes' => 500,
            'price_per_minute' => 0.10,
            'base_fee' => 49.00
        ]);
        
        // Create test calls
        Call::factory()->count(5)->create([
            'company_id' => $company->id,
            'start_time' => $period->start_date->addDay(),
            'duration_seconds' => 300, // 5 minutes each
            'status' => 'completed'
        ]);
        
        $this->service->calculatePeriodUsage($period);
        
        $period->refresh();
        
        $this->assertEquals(25, $period->total_minutes); // 5 calls * 5 minutes
        $this->assertEquals(25, $period->used_minutes);
        $this->assertEquals(0, $period->overage_minutes); // Under 500 included
        $this->assertEquals(0, $period->overage_cost);
        $this->assertEquals(49.00, $period->total_cost); // Just base fee
    }

    public function test_calculate_period_usage_with_overage()
    {
        $company = Company::factory()->create();
        $period = BillingPeriod::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
            'start_date' => Carbon::now()->subMonth(),
            'end_date' => Carbon::now()->subDay(),
            'included_minutes' => 100,
            'price_per_minute' => 0.10,
            'base_fee' => 49.00
        ]);
        
        // Create test calls totaling 150 minutes
        Call::factory()->count(10)->create([
            'company_id' => $company->id,
            'start_time' => $period->start_date->addDay(),
            'duration_seconds' => 900, // 15 minutes each
            'status' => 'completed'
        ]);
        
        $this->service->calculatePeriodUsage($period);
        
        $period->refresh();
        
        $this->assertEquals(150, $period->total_minutes);
        $this->assertEquals(150, $period->used_minutes);
        $this->assertEquals(50, $period->overage_minutes); // 150 - 100 included
        $this->assertEquals(5.00, $period->overage_cost); // 50 * 0.10
        $this->assertEquals(54.00, $period->total_cost); // 49 + 5
    }

    public function test_create_invoice_throws_exception_for_unprocessed_period()
    {
        $period = BillingPeriod::factory()->create(['status' => 'active']);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only processed periods can be invoiced');
        
        $this->service->createInvoice($period);
    }

    public function test_create_invoice_throws_exception_for_already_invoiced_period()
    {
        $period = BillingPeriod::factory()->create([
            'status' => 'processed',
            'is_invoiced' => true
        ]);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Period is already invoiced');
        
        $this->service->createInvoice($period);
    }

    public function test_create_invoice_generates_invoice_successfully()
    {
        $company = Company::factory()->create();
        $period = BillingPeriod::factory()->create([
            'company_id' => $company->id,
            'status' => 'processed',
            'is_invoiced' => false,
            'base_fee' => 49.00,
            'included_minutes' => 500,
            'used_minutes' => 600,
            'overage_minutes' => 100,
            'price_per_minute' => 0.10,
            'overage_cost' => 10.00,
            'total_cost' => 59.00
        ]);
        
        $invoice = $this->service->createInvoice($period);
        
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($company->id, $invoice->company_id);
        $this->assertEquals(59.00, $invoice->subtotal);
        $this->assertEquals(11.21, $invoice->tax_amount); // 19% tax
        $this->assertEquals(70.21, $invoice->total);
        $this->assertEquals('draft', $invoice->status);
        
        $period->refresh();
        $this->assertTrue($period->is_invoiced);
        $this->assertNotNull($period->invoiced_at);
        $this->assertEquals($invoice->id, $period->invoice_id);
        $this->assertEquals('invoiced', $period->status);
    }

    public function test_create_periods_for_month()
    {
        $companies = Company::factory()->count(3)->create(['is_active' => true]);
        
        $created = $this->service->createPeriodsForMonth(Carbon::now());
        
        $this->assertEquals(3, $created);
        
        foreach ($companies as $company) {
            $this->assertDatabaseHas('billing_periods', [
                'company_id' => $company->id,
                'status' => 'pending'
            ]);
        }
    }

    public function test_create_periods_skips_existing_periods()
    {
        $company = Company::factory()->create(['is_active' => true]);
        $startDate = Carbon::now()->startOfMonth();
        
        // Create existing period
        BillingPeriod::factory()->create([
            'company_id' => $company->id,
            'start_date' => $startDate
        ]);
        
        $created = $this->service->createPeriodsForMonth(Carbon::now());
        
        $this->assertEquals(0, $created);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}