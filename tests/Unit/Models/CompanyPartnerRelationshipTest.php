<?php

namespace Tests\Unit\Models;

use App\Models\Company;
use App\Models\CompanyFeeSchedule;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Unit tests for Company partner-related relationships and methods.
 *
 * Tests: Partner relationships, billing recipients, fee schedules.
 */
class CompanyPartnerRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    private Company $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Company::factory()->create([
            'name' => 'Partner Holding AG',
            'is_partner' => true,
            'partner_billing_email' => 'buchhaltung@partner.de',
            'partner_billing_name' => 'Buchhaltung Partner Holding AG',
            'partner_billing_cc_emails' => ['cfo@partner.de', 'finance@partner.de'],
            'partner_billing_address' => json_encode([
                'company' => 'Partner Holding AG',
                'street' => 'Musterstraße 1',
                'city' => '12345 Berlin',
            ]),
            'partner_payment_terms_days' => 30,
        ]);
    }

    // ========================================
    // MANAGED COMPANIES RELATIONSHIP
    // ========================================

    /** @test */
    public function partner_can_have_managed_companies(): void
    {
        $managedCompany1 = Company::factory()->create([
            'name' => 'Managed Company 1',
            'managed_by_company_id' => $this->partner->id,
        ]);

        $managedCompany2 = Company::factory()->create([
            'name' => 'Managed Company 2',
            'managed_by_company_id' => $this->partner->id,
        ]);

        $this->assertCount(2, $this->partner->fresh()->managedCompanies);
        $this->assertTrue($this->partner->fresh()->managedCompanies->contains($managedCompany1));
        $this->assertTrue($this->partner->fresh()->managedCompanies->contains($managedCompany2));
    }

    /** @test */
    public function managed_company_belongs_to_managing_partner(): void
    {
        $managedCompany = Company::factory()->create([
            'name' => 'Managed Company',
            'managed_by_company_id' => $this->partner->id,
        ]);

        $this->assertInstanceOf(Company::class, $managedCompany->managingPartner);
        $this->assertEquals($this->partner->id, $managedCompany->managingPartner->id);
    }

    /** @test */
    public function company_without_partner_has_null_managing_partner(): void
    {
        $standaloneCompany = Company::factory()->create([
            'name' => 'Standalone Company',
            'managed_by_company_id' => null,
        ]);

        $this->assertNull($standaloneCompany->managingPartner);
    }

    // ========================================
    // BILLING CC EMAILS
    // ========================================

    /** @test */
    public function get_partner_billing_cc_emails_returns_array(): void
    {
        $ccEmails = $this->partner->getPartnerBillingCcEmails();

        $this->assertIsArray($ccEmails);
        $this->assertCount(2, $ccEmails);
        $this->assertContains('cfo@partner.de', $ccEmails);
        $this->assertContains('finance@partner.de', $ccEmails);
    }

    /** @test */
    public function get_partner_billing_cc_emails_returns_empty_array_when_null(): void
    {
        $this->partner->update(['partner_billing_cc_emails' => null]);

        $ccEmails = $this->partner->fresh()->getPartnerBillingCcEmails();

        $this->assertIsArray($ccEmails);
        $this->assertEmpty($ccEmails);
    }

    /** @test */
    public function get_all_billing_recipients_includes_primary_and_cc(): void
    {
        $recipients = $this->partner->getAllBillingRecipients();

        $this->assertCount(3, $recipients);
        $this->assertContains('buchhaltung@partner.de', $recipients);
        $this->assertContains('cfo@partner.de', $recipients);
        $this->assertContains('finance@partner.de', $recipients);
    }

    /** @test */
    public function get_all_billing_recipients_removes_duplicates(): void
    {
        $this->partner->update([
            'partner_billing_email' => 'finance@partner.de',
            'partner_billing_cc_emails' => ['finance@partner.de', 'cfo@partner.de'],
        ]);

        $recipients = $this->partner->fresh()->getAllBillingRecipients();

        $this->assertCount(2, $recipients);
        $this->assertContains('finance@partner.de', $recipients);
        $this->assertContains('cfo@partner.de', $recipients);
    }

    /** @test */
    public function get_all_billing_recipients_handles_empty_primary_email(): void
    {
        $this->partner->update([
            'partner_billing_email' => null,
            'partner_billing_cc_emails' => ['backup@partner.de'],
        ]);

        $recipients = $this->partner->fresh()->getAllBillingRecipients();

        $this->assertCount(1, $recipients);
        $this->assertContains('backup@partner.de', $recipients);
    }

    // ========================================
    // BILLING CONFIG VALIDATION
    // ========================================

    /** @test */
    public function has_valid_billing_config_returns_true_for_configured_partner(): void
    {
        $this->assertTrue($this->partner->hasValidBillingConfig());
    }

    /** @test */
    public function has_valid_billing_config_returns_false_when_not_partner(): void
    {
        $nonPartner = Company::factory()->create([
            'is_partner' => false,
            'partner_billing_email' => 'test@example.com',
        ]);

        $this->assertFalse($nonPartner->hasValidBillingConfig());
    }

    /** @test */
    public function has_valid_billing_config_returns_false_without_email(): void
    {
        $this->partner->update(['partner_billing_email' => null]);

        $this->assertFalse($this->partner->fresh()->hasValidBillingConfig());
    }

    /** @test */
    public function has_valid_billing_config_returns_false_with_empty_email(): void
    {
        $this->partner->update(['partner_billing_email' => '']);

        $this->assertFalse($this->partner->fresh()->hasValidBillingConfig());
    }

    // ========================================
    // FEE SCHEDULE RELATIONSHIP
    // ========================================

    /** @test */
    public function company_can_have_fee_schedule(): void
    {
        $feeSchedule = CompanyFeeSchedule::factory()->create([
            'company_id' => $this->partner->id,
            'billing_mode' => 'per_second',
            'setup_fee' => 5000,
        ]);

        $this->assertInstanceOf(CompanyFeeSchedule::class, $this->partner->fresh()->feeSchedule);
        $this->assertEquals($feeSchedule->id, $this->partner->fresh()->feeSchedule->id);
    }

    /** @test */
    public function company_without_fee_schedule_returns_null(): void
    {
        $companyWithoutSchedule = Company::factory()->create();

        $this->assertNull($companyWithoutSchedule->feeSchedule);
    }
}
