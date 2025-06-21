<?php

namespace Tests\Integration\Stripe;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\TaxService;
use App\Services\InvoiceComplianceService;
use Carbon\Carbon;

class TaxComplianceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected TaxService $taxService;
    protected InvoiceComplianceService $complianceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxService = app(TaxService::class);
        $this->complianceService = app(InvoiceComplianceService::class);
    }

    /** @test */
    public function it_calculates_german_vat_correctly()
    {
        $company = Company::factory()->create([
            'country' => 'DE',
            'vat_number' => null, // B2C customer
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'subtotal' => 100.00,
            'currency' => 'EUR',
        ]);

        $taxResult = $this->taxService->calculateTax($invoice);

        $this->assertEquals(19.0, $taxResult['rate']);
        $this->assertEquals(19.00, $taxResult['amount']);
        $this->assertEquals(119.00, $taxResult['total']);
        $this->assertEquals('DE VAT 19%', $taxResult['description']);
        $this->assertFalse($taxResult['reverse_charge']);
    }

    /** @test */
    public function it_applies_reverse_charge_for_eu_b2b()
    {
        $company = Company::factory()->create([
            'country' => 'FR',
            'vat_number' => 'FR12345678901', // Valid EU VAT number
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'subtotal' => 100.00,
            'currency' => 'EUR',
        ]);

        $taxResult = $this->taxService->calculateTax($invoice);

        $this->assertEquals(0, $taxResult['rate']);
        $this->assertEquals(0, $taxResult['amount']);
        $this->assertEquals(100.00, $taxResult['total']);
        $this->assertEquals('Reverse Charge - EU B2B', $taxResult['description']);
        $this->assertTrue($taxResult['reverse_charge']);
        $this->assertArrayHasKey('vat_validated', $taxResult);
        $this->assertTrue($taxResult['vat_validated']);
    }

    /** @test */
    public function it_applies_standard_vat_for_eu_b2c()
    {
        $testCases = [
            ['country' => 'FR', 'rate' => 20.0],
            ['country' => 'IT', 'rate' => 22.0],
            ['country' => 'ES', 'rate' => 21.0],
            ['country' => 'NL', 'rate' => 21.0],
            ['country' => 'BE', 'rate' => 21.0],
            ['country' => 'AT', 'rate' => 20.0],
        ];

        foreach ($testCases as $testCase) {
            $company = Company::factory()->create([
                'country' => $testCase['country'],
                'vat_number' => null, // B2C
            ]);

            $invoice = Invoice::factory()->create([
                'company_id' => $company->id,
                'subtotal' => 100.00,
                'currency' => 'EUR',
            ]);

            $taxResult = $this->taxService->calculateTax($invoice);

            $this->assertEquals($testCase['rate'], $taxResult['rate']);
            $this->assertEquals($testCase['rate'], $taxResult['amount']);
            $this->assertEquals(100 + $testCase['rate'], $taxResult['total']);
            $this->assertStringContainsString($testCase['country'] . ' VAT', $taxResult['description']);
            $this->assertFalse($taxResult['reverse_charge']);
        }
    }

    /** @test */
    public function it_handles_non_eu_countries_with_zero_tax()
    {
        $countries = ['US', 'CA', 'JP', 'AU', 'CH', 'NO'];

        foreach ($countries as $country) {
            $company = Company::factory()->create([
                'country' => $country,
            ]);

            $invoice = Invoice::factory()->create([
                'company_id' => $company->id,
                'subtotal' => 100.00,
                'currency' => 'EUR',
            ]);

            $taxResult = $this->taxService->calculateTax($invoice);

            $this->assertEquals(0, $taxResult['rate']);
            $this->assertEquals(0, $taxResult['amount']);
            $this->assertEquals(100.00, $taxResult['total']);
            $this->assertEquals('No VAT - Outside EU', $taxResult['description']);
            $this->assertFalse($taxResult['reverse_charge']);
        }
    }

    /** @test */
    public function it_validates_vat_numbers_and_caches_results()
    {
        // Test valid VAT number
        $validVat = 'DE123456789';
        $result1 = $this->taxService->validateVatNumber($validVat);
        
        $this->assertTrue($result1['valid']);
        $this->assertEquals('DE', $result1['country_code']);
        $this->assertEquals('123456789', $result1['vat_number']);
        $this->assertArrayHasKey('name', $result1);
        $this->assertArrayHasKey('address', $result1);

        // Test caching - second call should not hit API
        $startTime = microtime(true);
        $result2 = $this->taxService->validateVatNumber($validVat);
        $endTime = microtime(true);
        
        $this->assertTrue($result2['valid']);
        $this->assertLessThan(0.01, $endTime - $startTime); // Should be instant from cache

        // Test invalid VAT number
        $invalidVat = 'XX999999999';
        $result3 = $this->taxService->validateVatNumber($invalidVat);
        
        $this->assertFalse($result3['valid']);
        $this->assertArrayHasKey('error', $result3);
    }

    /** @test */
    public function it_generates_compliant_invoice_numbers()
    {
        $company = Company::factory()->create();

        // Generate first invoice number of the year
        $invoiceNumber1 = $this->complianceService->generateInvoiceNumber($company);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{6}$/', $invoiceNumber1);
        $this->assertStringContainsString(date('Y'), $invoiceNumber1);

        // Generate second invoice number
        $invoiceNumber2 = $this->complianceService->generateInvoiceNumber($company);
        $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{6}$/', $invoiceNumber2);
        
        // Extract sequence numbers
        $seq1 = intval(substr($invoiceNumber1, -6));
        $seq2 = intval(substr($invoiceNumber2, -6));
        
        $this->assertEquals($seq1 + 1, $seq2);
    }

    /** @test */
    public function it_ensures_invoice_compliance_for_germany()
    {
        $company = Company::factory()->create([
            'country' => 'DE',
            'vat_number' => 'DE123456789',
            'tax_id' => '12/345/67890',
            'name' => 'Test GmbH',
            'address' => 'Teststraße 123',
            'postal_code' => '10115',
            'city' => 'Berlin',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'invoice_number' => 'INV-2024-000001',
            'issue_date' => Carbon::now(),
            'subtotal' => 100.00,
            'tax_amount' => 19.00,
            'total_amount' => 119.00,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => 'AI Phone Service',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total' => 100.00,
        ]);

        $compliance = $this->complianceService->validateCompliance($invoice);

        $this->assertTrue($compliance['is_compliant']);
        $this->assertEmpty($compliance['errors']);
        $this->assertContains('invoice_number_present', $compliance['checks_passed']);
        $this->assertContains('tax_number_present', $compliance['checks_passed']);
        $this->assertContains('vat_number_present', $compliance['checks_passed']);
        $this->assertContains('seller_address_complete', $compliance['checks_passed']);
        $this->assertContains('buyer_address_complete', $compliance['checks_passed']);
        $this->assertContains('line_items_present', $compliance['checks_passed']);
        $this->assertContains('tax_calculation_correct', $compliance['checks_passed']);
    }

    /** @test */
    public function it_detects_non_compliant_invoices()
    {
        $company = Company::factory()->create([
            'country' => 'DE',
            'vat_number' => null, // Missing VAT number
            'address' => null, // Missing address
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'invoice_number' => null, // Missing invoice number
            'subtotal' => 100.00,
            'tax_amount' => 15.00, // Wrong tax amount
            'total_amount' => 115.00,
        ]);

        $compliance = $this->complianceService->validateCompliance($invoice);

        $this->assertFalse($compliance['is_compliant']);
        $this->assertNotEmpty($compliance['errors']);
        $this->assertContains('Invoice number is required', $compliance['errors']);
        $this->assertContains('Seller VAT number is required for German invoices', $compliance['errors']);
        $this->assertContains('Seller address is incomplete', $compliance['errors']);
        $this->assertContains('Tax calculation is incorrect', $compliance['errors']);
    }

    /** @test */
    public function it_handles_reduced_vat_rates_for_specific_services()
    {
        $company = Company::factory()->create([
            'country' => 'DE',
        ]);

        // Test reduced rate for books/publications
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'subtotal' => 100.00,
            'metadata' => ['service_type' => 'digital_publication'],
        ]);

        $taxResult = $this->taxService->calculateTax($invoice);

        $this->assertEquals(7.0, $taxResult['rate']);
        $this->assertEquals(7.00, $taxResult['amount']);
        $this->assertEquals(107.00, $taxResult['total']);
        $this->assertEquals('DE VAT 7% (Reduced Rate)', $taxResult['description']);
    }

    /** @test */
    public function it_handles_tax_exempt_services()
    {
        $company = Company::factory()->create([
            'country' => 'DE',
            'is_tax_exempt' => true,
            'tax_exempt_reason' => 'Medical services under §4 UStG',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'subtotal' => 100.00,
        ]);

        $taxResult = $this->taxService->calculateTax($invoice);

        $this->assertEquals(0, $taxResult['rate']);
        $this->assertEquals(0, $taxResult['amount']);
        $this->assertEquals(100.00, $taxResult['total']);
        $this->assertEquals('Tax Exempt - Medical services under §4 UStG', $taxResult['description']);
        $this->assertFalse($taxResult['reverse_charge']);
        $this->assertTrue($taxResult['tax_exempt']);
    }

    /** @test */
    public function it_generates_tax_report_for_period()
    {
        $company = Company::factory()->create(['country' => 'DE']);

        // Create invoices for the period
        $invoices = [];
        
        // German B2C invoice
        $invoices[] = Invoice::factory()->create([
            'company_id' => $company->id,
            'subtotal' => 1000.00,
            'tax_amount' => 190.00,
            'total_amount' => 1190.00,
            'tax_rate' => 19.0,
            'paid_at' => Carbon::now()->subDays(15),
        ]);

        // EU B2B invoice (reverse charge)
        $invoices[] = Invoice::factory()->create([
            'company_id' => $company->id,
            'subtotal' => 500.00,
            'tax_amount' => 0,
            'total_amount' => 500.00,
            'tax_rate' => 0,
            'is_reverse_charge' => true,
            'buyer_country' => 'FR',
            'buyer_vat_number' => 'FR12345678901',
            'paid_at' => Carbon::now()->subDays(10),
        ]);

        // Generate tax report
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $report = $this->taxService->generateTaxReport($company, $startDate, $endDate);

        $this->assertEquals(1500.00, $report['total_revenue']);
        $this->assertEquals(190.00, $report['total_tax_collected']);
        $this->assertEquals(500.00, $report['reverse_charge_amount']);
        $this->assertEquals(2, $report['invoice_count']);
        
        $this->assertArrayHasKey('by_country', $report);
        $this->assertEquals(1190.00, $report['by_country']['DE']['total']);
        $this->assertEquals(190.00, $report['by_country']['DE']['tax']);
        $this->assertEquals(500.00, $report['by_country']['FR']['total']);
        $this->assertEquals(0, $report['by_country']['FR']['tax']);
        
        $this->assertArrayHasKey('by_tax_rate', $report);
        $this->assertEquals(1000.00, $report['by_tax_rate']['19.0']['base']);
        $this->assertEquals(190.00, $report['by_tax_rate']['19.0']['tax']);
    }

    /** @test */
    public function it_handles_currency_conversion_for_tax_calculation()
    {
        $company = Company::factory()->create([
            'country' => 'DE',
            'default_currency' => 'EUR',
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'subtotal' => 100.00,
            'currency' => 'USD',
            'exchange_rate' => 0.85, // 1 USD = 0.85 EUR
        ]);

        $taxResult = $this->taxService->calculateTax($invoice);

        // Tax should be calculated on EUR amount
        $eurAmount = 100.00 * 0.85;
        $expectedTax = $eurAmount * 0.19;
        
        $this->assertEquals(19.0, $taxResult['rate']);
        $this->assertEquals($expectedTax, $taxResult['amount']);
        $this->assertEquals($eurAmount + $expectedTax, $taxResult['total']);
        $this->assertEquals('EUR', $taxResult['currency']);
        $this->assertArrayHasKey('original_currency', $taxResult);
        $this->assertEquals('USD', $taxResult['original_currency']);
    }
}