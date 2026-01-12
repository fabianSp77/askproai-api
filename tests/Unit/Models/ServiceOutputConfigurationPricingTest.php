<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\ServiceOutputConfiguration;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit tests for ServiceOutputConfiguration billing/pricing functionality.
 *
 * These tests verify:
 * - Billing mode detection (per_case, monthly_flat, none)
 * - Per-case pricing calculation based on output type
 * - Monthly flat rate retrieval
 * - Billing configuration validation
 *
 * Note: Uses DatabaseTransactions instead of RefreshDatabase to avoid
 * migration compatibility issues with the complex production schema.
 */
class ServiceOutputConfigurationPricingTest extends TestCase
{
    use DatabaseTransactions;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'name' => 'Test Company GmbH',
        ]);
    }

    /** @test */
    public function it_detects_per_case_billing_mode(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'billing_mode' => ServiceOutputConfiguration::BILLING_MODE_PER_CASE,
        ]);

        $this->assertTrue($config->usesPerCaseBilling(), 'Should detect per-case billing mode');
        $this->assertFalse($config->usesMonthlyFlatBilling(), 'Should not detect monthly flat billing');
        $this->assertTrue($config->hasBillingEnabled(), 'Should have billing enabled');
    }

    /** @test */
    public function it_detects_monthly_flat_billing_mode(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'billing_mode' => ServiceOutputConfiguration::BILLING_MODE_MONTHLY_FLAT,
        ]);

        $this->assertTrue($config->usesMonthlyFlatBilling(), 'Should detect monthly flat billing mode');
        $this->assertFalse($config->usesPerCaseBilling(), 'Should not detect per-case billing');
        $this->assertTrue($config->hasBillingEnabled(), 'Should have billing enabled');
    }

    /** @test */
    public function it_detects_disabled_billing_mode(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'billing_mode' => ServiceOutputConfiguration::BILLING_MODE_NONE,
        ]);

        $this->assertFalse($config->hasBillingEnabled(), 'Should not have billing enabled for none mode');
        $this->assertFalse($config->usesPerCaseBilling(), 'Should not use per-case billing');
        $this->assertFalse($config->usesMonthlyFlatBilling(), 'Should not use monthly flat billing');
    }

    /** @test */
    public function it_detects_disabled_billing_when_null(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'billing_mode' => null,
        ]);

        $this->assertFalse($config->hasBillingEnabled(), 'Should not have billing enabled when null');
    }

    /** @test */
    public function it_calculates_email_only_pricing(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'output_type' => ServiceOutputConfiguration::TYPE_EMAIL,
            'base_price_cents' => 100,
            'email_price_cents' => 50,
            'webhook_price_cents' => 75,
        ]);

        $price = $config->calculateCasePrice();

        // base (100) + email (50) = 150
        $this->assertEquals(150, $price, 'Email-only pricing should be base + email');
    }

    /** @test */
    public function it_calculates_webhook_only_pricing(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'output_type' => ServiceOutputConfiguration::TYPE_WEBHOOK,
            'base_price_cents' => 100,
            'email_price_cents' => 50,
            'webhook_price_cents' => 75,
        ]);

        $price = $config->calculateCasePrice();

        // base (100) + webhook (75) = 175
        $this->assertEquals(175, $price, 'Webhook-only pricing should be base + webhook');
    }

    /** @test */
    public function it_calculates_hybrid_pricing(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'output_type' => ServiceOutputConfiguration::TYPE_HYBRID,
            'base_price_cents' => 100,
            'email_price_cents' => 50,
            'webhook_price_cents' => 75,
        ]);

        $price = $config->calculateCasePrice();

        // base (100) + email (50) + webhook (75) = 225
        $this->assertEquals(225, $price, 'Hybrid pricing should be base + email + webhook');
    }

    /** @test */
    public function it_uses_default_email_price_when_null(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'output_type' => ServiceOutputConfiguration::TYPE_EMAIL,
            'base_price_cents' => 0,
            'email_price_cents' => null, // Not set
            'webhook_price_cents' => null,
        ]);

        $price = $config->calculateCasePrice();

        // base (0) + email default (50) = 50
        $this->assertEquals(50, $price, 'Should use default email price of 50 cents when null');
    }

    /** @test */
    public function it_uses_default_webhook_price_when_null(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'output_type' => ServiceOutputConfiguration::TYPE_WEBHOOK,
            'base_price_cents' => 0,
            'email_price_cents' => null,
            'webhook_price_cents' => null, // Not set
        ]);

        $price = $config->calculateCasePrice();

        // base (0) + webhook default (50) = 50
        $this->assertEquals(50, $price, 'Should use default webhook price of 50 cents when null');
    }

    /** @test */
    public function it_uses_zero_base_price_when_null(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'output_type' => ServiceOutputConfiguration::TYPE_EMAIL,
            'base_price_cents' => null, // Not set
            'email_price_cents' => 75,
        ]);

        $price = $config->calculateCasePrice();

        // base default (0) + email (75) = 75
        $this->assertEquals(75, $price, 'Should use zero base price when null');
    }

    /** @test */
    public function it_returns_monthly_flat_price(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'monthly_flat_price_cents' => 4900, // 49 EUR
        ]);

        $monthlyPrice = $config->getMonthlyFlatPrice();

        $this->assertEquals(4900, $monthlyPrice, 'Should return configured monthly flat price');
    }

    /** @test */
    public function it_uses_default_monthly_flat_price_when_null(): void
    {
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'monthly_flat_price_cents' => null, // Not set
        ]);

        $monthlyPrice = $config->getMonthlyFlatPrice();

        $this->assertEquals(2900, $monthlyPrice, 'Should use default monthly flat price of 2900 cents (29 EUR) when null');
    }

    /** @test */
    public function it_calculates_complex_pricing_scenario(): void
    {
        // Real-world scenario: ServiceNow integration with high base + moderate add-ons
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'output_type' => ServiceOutputConfiguration::TYPE_HYBRID,
            'base_price_cents' => 200, // 2 EUR base
            'email_price_cents' => 30,  // 0.30 EUR for email
            'webhook_price_cents' => 100, // 1 EUR for ServiceNow webhook
        ]);

        $price = $config->calculateCasePrice();

        // base (200) + email (30) + webhook (100) = 330 cents = 3.30 EUR
        $this->assertEquals(330, $price, 'Complex pricing should sum all components');
    }

    /** @test */
    public function it_validates_billing_mode_constants(): void
    {
        $validModes = ServiceOutputConfiguration::BILLING_MODES;

        $this->assertContains(ServiceOutputConfiguration::BILLING_MODE_PER_CASE, $validModes, 'per_case should be valid billing mode');
        $this->assertContains(ServiceOutputConfiguration::BILLING_MODE_MONTHLY_FLAT, $validModes, 'monthly_flat should be valid billing mode');
        $this->assertContains(ServiceOutputConfiguration::BILLING_MODE_NONE, $validModes, 'none should be valid billing mode');
        $this->assertCount(3, $validModes, 'Should have exactly 3 billing modes');
    }

    /** @test */
    public function it_handles_zero_pricing_configuration(): void
    {
        // Edge case: Free tier or promotional configuration
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'output_type' => ServiceOutputConfiguration::TYPE_HYBRID,
            'base_price_cents' => 0,
            'email_price_cents' => 0,
            'webhook_price_cents' => 0,
        ]);

        $price = $config->calculateCasePrice();

        $this->assertEquals(0, $price, 'Should handle zero pricing (free tier)');
    }

    /** @test */
    public function it_persists_billing_configuration(): void
    {
        // Verify billing fields are fillable and persist correctly
        $config = ServiceOutputConfiguration::factory()->create([
            'company_id' => $this->company->id,
            'billing_mode' => ServiceOutputConfiguration::BILLING_MODE_PER_CASE,
            'base_price_cents' => 150,
            'email_price_cents' => 60,
            'webhook_price_cents' => 90,
            'monthly_flat_price_cents' => 3500,
        ]);

        $config->refresh();

        $this->assertEquals(ServiceOutputConfiguration::BILLING_MODE_PER_CASE, $config->billing_mode, 'billing_mode should persist');
        $this->assertEquals(150, $config->base_price_cents, 'base_price_cents should persist');
        $this->assertEquals(60, $config->email_price_cents, 'email_price_cents should persist');
        $this->assertEquals(90, $config->webhook_price_cents, 'webhook_price_cents should persist');
        $this->assertEquals(3500, $config->monthly_flat_price_cents, 'monthly_flat_price_cents should persist');
    }
}
