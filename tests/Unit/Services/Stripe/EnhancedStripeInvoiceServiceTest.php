<?php

namespace Tests\Unit\Services\Stripe;

use Tests\TestCase;
use App\Services\Stripe\EnhancedStripeInvoiceService;
use App\Services\Tax\TaxService;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\BillingPeriod;
use App\Models\TaxRate;
use App\Models\CompanyPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Mockery;
use Carbon\Carbon;

class EnhancedStripeInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private EnhancedStripeInvoiceService $service;
    private TaxService $taxService;
    private StripeClient $stripeClient;
    private Company $company;
    private TaxRate $standardTaxRate;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock Stripe client
        $this->stripeClient = Mockery::mock(StripeClient::class);
        
        // Mock tax service
        $this->taxService = Mockery::mock(TaxService::class);
        
        // Create service with mocked dependencies
        $this->service = Mockery::mock(EnhancedStripeInvoiceService::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        
        // Inject mocked dependencies
        $reflection = new \ReflectionClass($this->service);
        
        $taxServiceProperty = $reflection->getProperty('taxService');
        $taxServiceProperty->setAccessible(true);
        $taxServiceProperty->setValue($this->service, $this->taxService);
        
        $stripeProperty = $reflection->getProperty('stripe');
        $stripeProperty->setAccessible(true);
        $stripeProperty->setValue($this->service, $this->stripeClient);
        
        // Create test data
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'is_small_business' => false,
            'currency' => 'EUR',
            'invoice_prefix' => 'TST',
            'next_invoice_number' => 1,
            'payment_terms' => 'net30',
            'stripe_customer_id' => 'cus_test123',
        ]);
        
        $this->standardTaxRate = TaxRate::create([
            'name' => 'Standard',
            'rate' => 19,
            'is_system' => true,
            'is_default' => true,
            'stripe_tax_rate_id' => 'txr_test123',
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test create draft invoice
     */
    public function test_create_draft_invoice()
    {
        $options = [
            'branch_id' => 1,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'billing_reason' => Invoice::REASON_MANUAL,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ];
        
        $this->taxService->shouldReceive('getTaxNote')
            ->once()
            ->with($this->company, null)
            ->andReturn(null);
        
        $invoice = $this->service->createDraftInvoice($this->company, $options);
        
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($this->company->id, $invoice->company_id);
        $this->assertEquals('TST-' . now()->year . '-00001', $invoice->invoice_number);
        $this->assertEquals(Invoice::STATUS_DRAFT, $invoice->status);
        $this->assertEquals(0, $invoice->subtotal);
        $this->assertEquals(0, $invoice->tax_amount);
        $this->assertEquals(0, $invoice->total);
        $this->assertEquals('EUR', $invoice->currency);
        $this->assertTrue($invoice->manual_editable);
        $this->assertFalse($invoice->auto_advance);
        $this->assertEquals(1, $invoice->branch_id);
        $this->assertEquals(Invoice::REASON_MANUAL, $invoice->billing_reason);
        
        // Check invoice number was incremented
        $this->assertEquals(2, $this->company->fresh()->next_invoice_number);
    }

    /**
     * Test create draft invoice for small business
     */
    public function test_create_draft_invoice_small_business()
    {
        $this->company->update(['is_small_business' => true]);
        
        $this->taxService->shouldReceive('getTaxNote')
            ->once()
            ->with($this->company, null)
            ->andReturn('Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.');
        
        $invoice = $this->service->createDraftInvoice($this->company);
        
        $this->assertEquals('Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.', $invoice->tax_note);
    }

    /**
     * Test preview invoice
     */
    public function test_preview_invoice()
    {
        $items = [
            [
                'description' => 'Service A',
                'quantity' => 2,
                'unit' => 'Stunden',
                'unit_price' => 50,
                'tax_rate_id' => $this->standardTaxRate->id,
            ],
            [
                'description' => 'Service B',
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ];
        
        // Mock tax calculations
        $this->taxService->shouldReceive('calculateTax')
            ->once()
            ->with(100, $this->company, $this->standardTaxRate->id)
            ->andReturn([
                'net_amount' => 100,
                'tax_amount' => 19,
                'gross_amount' => 119,
                'tax_rate' => 19,
                'tax_rate_name' => 'Standard',
                'tax_note' => null,
            ]);
        
        $this->taxService->shouldReceive('calculateTax')
            ->once()
            ->with(100, $this->company, null)
            ->andReturn([
                'net_amount' => 100,
                'tax_amount' => 19,
                'gross_amount' => 119,
                'tax_rate' => 19,
                'tax_rate_name' => 'Standard',
                'tax_note' => null,
            ]);
        
        $this->taxService->shouldReceive('getTaxNote')
            ->once()
            ->with($this->company, null)
            ->andReturn(null);
        
        $this->service->shouldReceive('getPaymentTermsText')
            ->once()
            ->with($this->company)
            ->andReturn('Zahlbar innerhalb von 30 Tagen');
        
        $this->service->shouldReceive('calculateDueDate')
            ->once()
            ->andReturn(now()->addDays(30));
        
        $preview = $this->service->previewInvoice($this->company, $items);
        
        $this->assertIsArray($preview);
        $this->assertCount(2, $preview['items']);
        $this->assertEquals(200, $preview['subtotal']);
        $this->assertEquals(38, $preview['tax_amount']);
        $this->assertEquals(238, $preview['total']);
        $this->assertEquals('EUR', $preview['currency']);
        $this->assertFalse($preview['is_small_business']);
        $this->assertNull($preview['tax_note']);
        $this->assertEquals('Zahlbar innerhalb von 30 Tagen', $preview['payment_terms']);
        
        // Check tax breakdown
        $this->assertCount(1, $preview['tax_breakdown']);
        $this->assertEquals(19, $preview['tax_breakdown'][0]['rate']);
        $this->assertEquals(200, $preview['tax_breakdown'][0]['net_amount']);
        $this->assertEquals(38, $preview['tax_breakdown'][0]['tax_amount']);
    }

    /**
     * Test preview invoice for small business
     */
    public function test_preview_invoice_small_business()
    {
        $this->company->update(['is_small_business' => true]);
        
        $items = [
            [
                'description' => 'Service A',
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ];
        
        $this->taxService->shouldReceive('calculateTax')
            ->once()
            ->andReturn([
                'net_amount' => 100,
                'tax_amount' => 0,
                'gross_amount' => 100,
                'tax_rate' => 0,
                'tax_rate_name' => 'Kleinunternehmer',
                'tax_note' => 'Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.',
            ]);
        
        $this->taxService->shouldReceive('getTaxNote')
            ->once()
            ->andReturn('Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.');
        
        $this->service->shouldReceive('getPaymentTermsText')
            ->once()
            ->andReturn('Zahlbar innerhalb von 30 Tagen');
        
        $this->service->shouldReceive('calculateDueDate')
            ->once()
            ->andReturn(now()->addDays(30));
        
        $preview = $this->service->previewInvoice($this->company, $items);
        
        $this->assertEquals(100, $preview['subtotal']);
        $this->assertEquals(0, $preview['tax_amount']);
        $this->assertEquals(100, $preview['total']);
        $this->assertTrue($preview['is_small_business']);
        $this->assertEquals('Gemäß § 19 UStG wird keine Umsatzsteuer berechnet.', $preview['tax_note']);
    }

    /**
     * Test create invoice for billing period
     */
    public function test_create_invoice_for_billing_period()
    {
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'total_minutes' => 150,
            'pricing_model_id' => null,
        ]);
        
        $pricing = CompanyPricing::factory()->create([
            'company_id' => $this->company->id,
            'monthly_base_fee' => 99,
            'included_minutes' => 100,
            'price_per_minute' => 0.50,
            'overage_price_per_minute' => 0.60,
        ]);
        
        // Mock Stripe operations
        $this->service->shouldReceive('ensureStripeCustomer')
            ->once()
            ->with($this->company)
            ->andReturn('cus_test123');
        
        $this->service->shouldReceive('generateInvoiceNumber')
            ->once()
            ->with($this->company)
            ->andReturn('TST-2025-00001');
        
        $this->taxService->shouldReceive('getApplicableTaxRate')
            ->once()
            ->with($this->company)
            ->andReturn($this->standardTaxRate);
        
        $this->taxService->shouldReceive('getTaxNote')
            ->once()
            ->with($this->company, null)
            ->andReturn(null);
        
        $this->taxService->shouldReceive('syncStripeTaxRate')
            ->once()
            ->with($this->standardTaxRate)
            ->andReturn('txr_test123');
        
        $this->service->shouldReceive('calculateDueDate')
            ->once()
            ->with($this->company)
            ->andReturn(now()->addDays(30));
        
        $this->service->shouldReceive('getDaysUntilDue')
            ->once()
            ->with($this->company)
            ->andReturn(30);
        
        $this->service->shouldReceive('getInvoiceFooter')
            ->once()
            ->with($this->company)
            ->andReturn('Thank you for your business');
        
        // Mock Stripe invoice creation
        $stripeInvoice = Mockery::mock();
        $stripeInvoice->id = 'in_test123';
        
        $invoices = Mockery::mock();
        $invoices->shouldReceive('create')
            ->once()
            ->andReturn($stripeInvoice);
        
        $this->stripeClient->invoices = $invoices;
        
        // Mock adding invoice items
        $this->service->shouldReceive('addInvoiceItemsWithTax')
            ->once();
        
        // Mock finalize
        $this->service->shouldReceive('finalizeInvoice')
            ->once()
            ->andReturn(true);
        
        // Mock update company revenue
        $this->service->shouldReceive('updateCompanyRevenue')
            ->once();
        
        $invoice = $this->service->createInvoiceForBillingPeriod($billingPeriod);
        
        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($this->company->id, $invoice->company_id);
        $this->assertEquals('TST-2025-00001', $invoice->invoice_number);
        $this->assertEquals('in_test123', $invoice->stripe_invoice_id);
        $this->assertEquals(Invoice::STATUS_DRAFT, $invoice->status);
        $this->assertEquals(Invoice::REASON_SUBSCRIPTION_CYCLE, $invoice->billing_reason);
        $this->assertTrue($invoice->auto_advance);
        
        // Check billing period was updated
        $billingPeriod->refresh();
        $this->assertEquals($invoice->id, $billingPeriod->invoice_id);
        $this->assertTrue($billingPeriod->is_invoiced);
    }

    /**
     * Test create invoice for billing period with error
     */
    public function test_create_invoice_for_billing_period_with_error()
    {
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $this->service->shouldReceive('ensureStripeCustomer')
            ->once()
            ->andThrow(new \Exception('Stripe error'));
        
        Log::shouldReceive('error')->once();
        
        $invoice = $this->service->createInvoiceForBillingPeriod($billingPeriod);
        
        $this->assertNull($invoice);
        
        // Check no invoice was created
        $this->assertEquals(0, Invoice::count());
    }

    /**
     * Test finalize invoice
     */
    public function test_finalize_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'stripe_invoice_id' => 'in_test123',
            'status' => Invoice::STATUS_DRAFT,
            'finalized_at' => null,
        ]);
        
        $invoice->items()->create([
            'description' => 'Test Item',
            'amount' => 100,
        ]);
        
        // Mock validation
        $this->service->shouldReceive('validateInvoiceCompliance')
            ->once()
            ->with($invoice)
            ->andReturn(['valid' => true, 'errors' => []]);
        
        // Mock Stripe operations
        $stripeInvoice = Mockery::mock();
        $stripeInvoice->invoice_pdf = 'https://stripe.com/invoice.pdf';
        
        $invoices = Mockery::mock();
        $invoices->shouldReceive('finalizeInvoice')
            ->once()
            ->with('in_test123')
            ->andReturn($stripeInvoice);
        
        $invoices->shouldReceive('sendInvoice')
            ->once()
            ->with('in_test123');
        
        $this->stripeClient->invoices = $invoices;
        
        $result = $this->service->finalizeInvoice($invoice);
        
        $this->assertTrue($result);
        
        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_OPEN, $invoice->status);
        $this->assertNotNull($invoice->finalized_at);
        $this->assertFalse($invoice->manual_editable);
        $this->assertEquals('https://stripe.com/invoice.pdf', $invoice->pdf_url);
    }

    /**
     * Test finalize already finalized invoice
     */
    public function test_finalize_already_finalized_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'finalized_at' => now(),
        ]);
        
        Log::shouldReceive('warning')->once();
        
        $result = $this->service->finalizeInvoice($invoice);
        
        $this->assertFalse($result);
    }

    /**
     * Test finalize invoice with validation errors
     */
    public function test_finalize_invoice_with_validation_errors()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $this->service->shouldReceive('validateInvoiceCompliance')
            ->once()
            ->with($invoice)
            ->andReturn([
                'valid' => false,
                'errors' => ['Missing invoice items'],
            ]);
        
        Log::shouldReceive('error')->once();
        
        $result = $this->service->finalizeInvoice($invoice);
        
        $this->assertFalse($result);
    }

    /**
     * Test validate invoice compliance
     */
    public function test_validate_invoice_compliance_valid()
    {
        $this->company->update([
            'address' => 'Test Street 123',
            'tax_id' => 'DE123456789',
        ]);
        
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'invoice_number' => 'TST-2025-00001',
        ]);
        
        $invoice->items()->create([
            'description' => 'Test Item',
            'amount' => 100,
        ]);
        
        // Call protected method via reflection
        $reflection = new \ReflectionMethod($this->service, 'validateInvoiceCompliance');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->service, $invoice);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    /**
     * Test validate invoice compliance with errors
     */
    public function test_validate_invoice_compliance_with_errors()
    {
        $company = Company::factory()->create([
            'name' => '',
            'address' => null,
            'tax_id' => null,
            'vat_id' => null,
            'is_small_business' => false,
        ]);
        
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'invoice_number' => null,
        ]);
        
        // Call protected method via reflection
        $reflection = new \ReflectionMethod($this->service, 'validateInvoiceCompliance');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($this->service, $invoice);
        
        $this->assertFalse($result['valid']);
        $this->assertContains('Rechnungsnummer fehlt', $result['errors']);
        $this->assertContains('Firmenname fehlt', $result['errors']);
        $this->assertContains('Firmenadresse fehlt', $result['errors']);
        $this->assertContains('Steuernummer oder USt-IdNr. fehlt', $result['errors']);
        $this->assertContains('Keine Rechnungspositionen vorhanden', $result['errors']);
    }

    /**
     * Test generate invoice number
     */
    public function test_generate_invoice_number()
    {
        $this->company->update([
            'invoice_prefix' => 'TEST',
            'next_invoice_number' => 42,
        ]);
        
        // Call protected method via reflection
        $reflection = new \ReflectionMethod($this->service, 'generateInvoiceNumber');
        $reflection->setAccessible(true);
        
        $number = $reflection->invoke($this->service, $this->company);
        
        $this->assertEquals('TEST-' . now()->year . '-00042', $number);
        $this->assertEquals(43, $this->company->fresh()->next_invoice_number);
    }

    /**
     * Test update company revenue
     */
    public function test_update_company_revenue()
    {
        $this->company->update([
            'revenue_ytd' => 1000,
            'is_small_business' => false,
        ]);
        
        // Call protected method via reflection
        $reflection = new \ReflectionMethod($this->service, 'updateCompanyRevenue');
        $reflection->setAccessible(true);
        
        $reflection->invoke($this->service, $this->company, 500);
        
        $this->assertEquals(1500, $this->company->fresh()->revenue_ytd);
    }

    /**
     * Test update company revenue with threshold check
     */
    public function test_update_company_revenue_with_threshold_check()
    {
        $this->company->update([
            'revenue_ytd' => 45000,
            'is_small_business' => true,
        ]);
        
        $this->taxService->shouldReceive('checkSmallBusinessThresholds')
            ->once()
            ->with($this->company)
            ->andReturn([
                'status' => 'exceeded',
                'revenue_ytd' => 51000,
            ]);
        
        Log::shouldReceive('warning')->once();
        
        // Call protected method via reflection
        $reflection = new \ReflectionMethod($this->service, 'updateCompanyRevenue');
        $reflection->setAccessible(true);
        
        $reflection->invoke($this->service, $this->company, 6000);
        
        $this->assertEquals(51000, $this->company->fresh()->revenue_ytd);
    }

    /**
     * Test get payment terms text
     */
    public function test_get_payment_terms_text()
    {
        // Call protected method via reflection
        $reflection = new \ReflectionMethod($this->service, 'getPaymentTermsText');
        $reflection->setAccessible(true);
        
        $testCases = [
            'due_on_receipt' => 'Zahlbar sofort nach Erhalt',
            'net15' => 'Zahlbar innerhalb von 15 Tagen',
            'net30' => 'Zahlbar innerhalb von 30 Tagen',
            'net60' => 'Zahlbar innerhalb von 60 Tagen',
            'custom' => 'Zahlbar innerhalb von 30 Tagen', // Default
        ];
        
        foreach ($testCases as $terms => $expected) {
            $this->company->update(['payment_terms' => $terms]);
            $result = $reflection->invoke($this->service, $this->company);
            $this->assertEquals($expected, $result);
        }
    }

    /**
     * Test get invoice footer
     */
    public function test_get_invoice_footer()
    {
        $this->company->update([
            'tax_id' => 'DE123456789',
            'vat_id' => 'DE999999999',
        ]);
        
        // Call protected method via reflection
        $reflection = new \ReflectionMethod($this->service, 'getInvoiceFooter');
        $reflection->setAccessible(true);
        
        $footer = $reflection->invoke($this->service, $this->company);
        
        $this->assertStringContainsString('Vielen Dank für Ihr Vertrauen', $footer);
        $this->assertStringContainsString('Steuernummer: DE123456789', $footer);
        $this->assertStringContainsString('USt-IdNr.: DE999999999', $footer);
    }
}