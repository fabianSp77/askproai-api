<?php

namespace Tests\Unit\Services\Tax;

use Tests\TestCase;
use App\Services\Tax\TaxService;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\TaxRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;

class TaxServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaxService $taxService;
    private Company $company;
    private TaxRate $standardTaxRate;
    private TaxRate $zeroTaxRate;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->taxService = new TaxService();
        
        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'is_small_business' => false,
            'revenue_ytd' => 10000,
            'revenue_previous_year' => 15000,
        ]);
        
        // Create tax rates
        $this->standardTaxRate = TaxRate::create([
            'name' => 'Standard',
            'rate' => 19,
            'is_system' => true,
            'is_default' => true,
            'company_id' => null,
        ]);
        
        $this->zeroTaxRate = TaxRate::create([
            'name' => 'Kleinunternehmer',
            'rate' => 0,
            'is_system' => true,
            'is_default' => false,
            'company_id' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test tax calculation for regular company
     */
    public function test_calculate_tax_for_regular_company()
    {
        $result = $this->taxService->calculateTax(100, $this->company);
        
        $this->assertEquals(100, $result['net_amount']);
        $this->assertEquals(19, $result['tax_amount']);
        $this->assertEquals(119, $result['gross_amount']);
        $this->assertEquals(19, $result['tax_rate']);
        $this->assertEquals('Standard', $result['tax_rate_name']);
        $this->assertNull($result['tax_note']);
    }

    /**
     * Test tax calculation for small business
     */
    public function test_calculate_tax_for_small_business()
    {
        $this->company->update(['is_small_business' => true]);
        
        $result = $this->taxService->calculateTax(100, $this->company);
        
        $this->assertEquals(100, $result['net_amount']);
        $this->assertEquals(0, $result['tax_amount']);
        $this->assertEquals(100, $result['gross_amount']);
        $this->assertEquals(0, $result['tax_rate']);
        $this->assertEquals('Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.', $result['tax_note']);
    }

    /**
     * Test tax calculation with specific tax rate
     */
    public function test_calculate_tax_with_specific_tax_rate()
    {
        $customRate = TaxRate::create([
            'name' => 'Reduced',
            'rate' => 7,
            'is_system' => false,
            'company_id' => $this->company->id,
        ]);
        
        $result = $this->taxService->calculateTax(100, $this->company, $customRate->id);
        
        $this->assertEquals(100, $result['net_amount']);
        $this->assertEquals(7, $result['tax_amount']);
        $this->assertEquals(107, $result['gross_amount']);
        $this->assertEquals(7, $result['tax_rate']);
        $this->assertEquals('Reduced', $result['tax_rate_name']);
    }

    /**
     * Test get applicable tax rate for regular company
     */
    public function test_get_applicable_tax_rate_for_regular_company()
    {
        $taxRate = $this->taxService->getApplicableTaxRate($this->company);
        
        $this->assertEquals(19, $taxRate->rate);
        $this->assertEquals('Standard', $taxRate->name);
        $this->assertTrue($taxRate->is_default);
    }

    /**
     * Test get applicable tax rate for small business
     */
    public function test_get_applicable_tax_rate_for_small_business()
    {
        $this->company->update(['is_small_business' => true]);
        
        $taxRate = $this->taxService->getApplicableTaxRate($this->company);
        
        $this->assertEquals(0, $taxRate->rate);
        $this->assertStringContainsString('Kleinunternehmer', $taxRate->name);
    }

    /**
     * Test get applicable tax rate with invalid tax rate ID
     */
    public function test_get_applicable_tax_rate_with_invalid_id()
    {
        $taxRate = $this->taxService->getApplicableTaxRate($this->company, 999);
        
        // Should fallback to default
        $this->assertEquals(19, $taxRate->rate);
    }

    /**
     * Test small business threshold check - safe status
     */
    public function test_check_small_business_thresholds_safe()
    {
        $this->company->update([
            'revenue_ytd' => 5000,
            'revenue_previous_year' => 10000,
        ]);
        
        $result = $this->taxService->checkSmallBusinessThresholds($this->company);
        
        $this->assertEquals('safe', $result['status']);
        $this->assertTrue($result['can_be_small_business']);
        $this->assertEquals(5000, $result['revenue_current_year']);
        $this->assertEquals(10000, $result['revenue_previous_year']);
        $this->assertEquals(10, $result['percentage_current_year']); // 5000/50000*100
        $this->assertLessThan(50, $result['percentage_previous_year']); // 10000/22000*100
    }

    /**
     * Test small business threshold check - warning status
     */
    public function test_check_small_business_thresholds_warning()
    {
        $this->company->update([
            'revenue_ytd' => 42000, // 84% of 50000
            'revenue_previous_year' => 10000,
        ]);
        
        $result = $this->taxService->checkSmallBusinessThresholds($this->company);
        
        $this->assertEquals('warning', $result['status']);
        $this->assertEquals('80% der Kleinunternehmergrenze erreicht.', $result['message']);
        $this->assertTrue($result['can_be_small_business']);
    }

    /**
     * Test small business threshold check - critical status (projection)
     */
    public function test_check_small_business_thresholds_critical()
    {
        // Set current month to March (month 3)
        $this->travelTo(now()->setMonth(3));
        
        $this->company->update([
            'revenue_ytd' => 15000, // 15000 in 3 months = 60000 projected
            'revenue_previous_year' => 10000,
        ]);
        
        $result = $this->taxService->checkSmallBusinessThresholds($this->company);
        
        $this->assertEquals('critical', $result['status']);
        $this->assertEquals('Kleinunternehmergrenze wird voraussichtlich überschritten.', $result['message']);
        $this->assertEquals(60000, $result['revenue_projected']);
        $this->assertTrue($result['can_be_small_business']); // Still true as not exceeded yet
    }

    /**
     * Test small business threshold check - exceeded in current year
     */
    public function test_check_small_business_thresholds_exceeded_current()
    {
        $this->company->update([
            'revenue_ytd' => 55000,
            'revenue_previous_year' => 10000,
        ]);
        
        $result = $this->taxService->checkSmallBusinessThresholds($this->company);
        
        $this->assertEquals('exceeded', $result['status']);
        $this->assertEquals('Kleinunternehmergrenze im laufenden Jahr überschritten.', $result['message']);
        $this->assertFalse($result['can_be_small_business']);
    }

    /**
     * Test small business threshold check - exceeded in previous year
     */
    public function test_check_small_business_thresholds_exceeded_previous()
    {
        $this->company->update([
            'revenue_ytd' => 10000,
            'revenue_previous_year' => 25000,
        ]);
        
        $result = $this->taxService->checkSmallBusinessThresholds($this->company);
        
        $this->assertEquals('exceeded', $result['status']);
        $this->assertEquals('Kleinunternehmergrenze im Vorjahr überschritten. Status nicht mehr möglich.', $result['message']);
        $this->assertFalse($result['can_be_small_business']);
    }

    /**
     * Test VAT ID validation with valid ID
     */
    public function test_validate_vat_id_valid()
    {
        Http::fake([
            'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number' => Http::response([
                'valid' => true,
                'name' => 'Test Company GmbH',
                'address' => 'Test Street 123, 12345 Berlin',
            ], 200),
        ]);
        
        $result = $this->taxService->validateVatId('DE123456789');
        
        $this->assertTrue($result['valid']);
        $this->assertEquals('Test Company GmbH', $result['company_name']);
        $this->assertEquals('Test Street 123, 12345 Berlin', $result['company_address']);
        $this->assertArrayHasKey('request_date', $result);
    }

    /**
     * Test VAT ID validation with invalid ID
     */
    public function test_validate_vat_id_invalid()
    {
        Http::fake([
            'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number' => Http::response([
                'valid' => false,
            ], 200),
        ]);
        
        $result = $this->taxService->validateVatId('DE999999999');
        
        $this->assertFalse($result['valid']);
        $this->assertNull($result['company_name'] ?? null);
    }

    /**
     * Test VAT ID validation with invalid format
     */
    public function test_validate_vat_id_invalid_format()
    {
        $result = $this->taxService->validateVatId('INVALID');
        
        $this->assertFalse($result['valid']);
        $this->assertEquals('Invalid VAT ID format', $result['error']);
    }

    /**
     * Test VAT ID validation with service error
     */
    public function test_validate_vat_id_service_error()
    {
        Http::fake([
            'https://ec.europa.eu/taxation_customs/vies/rest-api/check-vat-number' => Http::response([], 500),
        ]);
        
        Log::shouldReceive('error')->once();
        
        $result = $this->taxService->validateVatId('DE123456789');
        
        $this->assertFalse($result['valid']);
        $this->assertEquals('VIES service unavailable', $result['error']);
    }

    /**
     * Test get tax note for small business
     */
    public function test_get_tax_note_small_business()
    {
        $this->company->update(['is_small_business' => true]);
        
        $note = $this->taxService->getTaxNote($this->company, $this->standardTaxRate);
        
        $this->assertEquals('Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.', $note);
    }

    /**
     * Test get tax note for reverse charge
     */
    public function test_get_tax_note_reverse_charge()
    {
        $reverseChargeRate = TaxRate::create([
            'name' => 'Reverse Charge B2B',
            'rate' => 0,
            'is_system' => true,
        ]);
        
        $note = $this->taxService->getTaxNote($this->company, $reverseChargeRate);
        
        $this->assertEquals('Steuerschuldnerschaft des Leistungsempfängers (Reverse Charge)', $note);
    }

    /**
     * Test get tax note for regular taxation
     */
    public function test_get_tax_note_regular()
    {
        $note = $this->taxService->getTaxNote($this->company, $this->standardTaxRate);
        
        $this->assertNull($note);
    }

    /**
     * Test sync Stripe tax rate creation
     */
    public function test_sync_stripe_tax_rate_creation()
    {
        // Mock Stripe client
        $stripeClient = Mockery::mock(\Stripe\StripeClient::class);
        $taxRates = Mockery::mock();
        
        $stripeClient->taxRates = $taxRates;
        
        $stripeTaxRate = new \stdClass();
        $stripeTaxRate->id = 'txr_test123';
        
        $taxRates->shouldReceive('create')
            ->once()
            ->with([
                'display_name' => 'Standard',
                'percentage' => 19,
                'inclusive' => false,
                'country' => 'DE',
                'description' => null,
                'metadata' => [
                    'askproai_tax_rate_id' => $this->standardTaxRate->id,
                    'is_system' => 'true',
                ],
            ])
            ->andReturn($stripeTaxRate);
        
        // Inject mock
        $this->app->instance(\Stripe\StripeClient::class, $stripeClient);
        
        $result = $this->taxService->syncStripeTaxRate($this->standardTaxRate);
        
        $this->assertEquals('txr_test123', $result);
        $this->assertEquals('txr_test123', $this->standardTaxRate->fresh()->stripe_tax_rate_id);
    }

    /**
     * Test sync Stripe tax rate when already exists
     */
    public function test_sync_stripe_tax_rate_already_exists()
    {
        $this->standardTaxRate->update(['stripe_tax_rate_id' => 'txr_existing']);
        
        $result = $this->taxService->syncStripeTaxRate($this->standardTaxRate);
        
        $this->assertEquals('txr_existing', $result);
    }

    /**
     * Test sync Stripe tax rate with error
     */
    public function test_sync_stripe_tax_rate_error()
    {
        // Mock Stripe client
        $stripeClient = Mockery::mock(\Stripe\StripeClient::class);
        $taxRates = Mockery::mock();
        
        $stripeClient->taxRates = $taxRates;
        
        $taxRates->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Stripe API error'));
        
        // Inject mock
        $this->app->instance(\Stripe\StripeClient::class, $stripeClient);
        
        Log::shouldReceive('error')->once();
        
        $result = $this->taxService->syncStripeTaxRate($this->standardTaxRate);
        
        $this->assertNull($result);
    }

    /**
     * Test get invoice tax configuration
     */
    public function test_get_invoice_tax_configuration()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        // Create invoice items
        $invoice->items()->createMany([
            [
                'description' => 'Item 1',
                'amount' => 100,
                'tax_rate_id' => $this->standardTaxRate->id,
            ],
            [
                'description' => 'Item 2',
                'amount' => 200,
                'tax_rate_id' => $this->standardTaxRate->id,
            ],
        ]);
        
        $result = $this->taxService->getInvoiceTaxConfiguration($invoice);
        
        $this->assertCount(2, $result['items']);
        $this->assertEquals(300, $result['total_net']);
        $this->assertEquals(57, $result['total_tax']); // 300 * 0.19
        $this->assertEquals(357, $result['total_gross']);
        $this->assertCount(1, $result['tax_breakdown']); // Single rate
        $this->assertEquals(19, $result['tax_breakdown'][0]['rate']);
        $this->assertEquals(300, $result['tax_breakdown'][0]['net_amount']);
        $this->assertEquals(57, $result['tax_breakdown'][0]['tax_amount']);
        $this->assertFalse($result['is_small_business']);
    }

    /**
     * Test get invoice tax configuration for small business
     */
    public function test_get_invoice_tax_configuration_small_business()
    {
        $this->company->update(['is_small_business' => true]);
        
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $invoice->items()->create([
            'description' => 'Item 1',
            'amount' => 100,
        ]);
        
        $result = $this->taxService->getInvoiceTaxConfiguration($invoice);
        
        $this->assertEquals(100, $result['total_net']);
        $this->assertEquals(0, $result['total_tax']);
        $this->assertEquals(100, $result['total_gross']);
        $this->assertEquals('Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.', $result['tax_note']);
        $this->assertTrue($result['is_small_business']);
    }

    /**
     * Test caching of small business tax rate
     */
    public function test_small_business_tax_rate_caching()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('tax_rate_small_business', 3600, Mockery::type('callable'))
            ->andReturn($this->zeroTaxRate);
        
        $this->company->update(['is_small_business' => true]);
        
        $taxRate = $this->taxService->getApplicableTaxRate($this->company);
        
        $this->assertEquals(0, $taxRate->rate);
    }
}