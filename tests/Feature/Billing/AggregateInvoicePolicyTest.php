<?php

namespace Tests\Feature\Billing;

use App\Models\AggregateInvoice;
use App\Models\Company;
use App\Models\User;
use App\Policies\AggregateInvoicePolicy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests for AggregateInvoicePolicy.
 *
 * Verifies role-based access control for partner invoices.
 */
class AggregateInvoicePolicyTest extends TestCase
{
    use DatabaseTransactions;

    private AggregateInvoicePolicy $policy;
    private Company $partner;
    private Company $otherPartner;
    private AggregateInvoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AggregateInvoicePolicy();

        // Create partner companies
        $this->partner = Company::factory()->create([
            'name' => 'Test Partner',
            'is_partner' => true,
        ]);

        $this->otherPartner = Company::factory()->create([
            'name' => 'Other Partner',
            'is_partner' => true,
        ]);

        // Create a draft invoice for the partner
        $this->invoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);

        // Ensure roles exist
        $this->ensureRolesExist();
    }

    private function ensureRolesExist(): void
    {
        $roles = [
            'super_admin', 'admin', 'manager', 'billing_manager', 'accountant',
            'partner_admin', 'partner_owner', 'partner_manager',
            'reseller_admin', 'reseller_owner',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }
    }

    private function createUserWithRole(string $role, ?Company $company = null): User
    {
        $user = User::factory()->create([
            'company_id' => $company?->id,
        ]);
        $user->assignRole($role);
        return $user;
    }

    /** @test */
    public function super_admin_can_do_everything(): void
    {
        $user = $this->createUserWithRole('super_admin');

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $this->invoice));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $this->invoice));
        $this->assertTrue($this->policy->delete($user, $this->invoice));
        $this->assertTrue($this->policy->finalize($user, $this->invoice));
    }

    /** @test */
    public function admin_can_view_and_manage_invoices(): void
    {
        $user = $this->createUserWithRole('admin');

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $this->invoice));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $this->invoice));
        $this->assertTrue($this->policy->delete($user, $this->invoice));
    }

    /** @test */
    public function billing_manager_can_manage_invoices(): void
    {
        $user = $this->createUserWithRole('billing_manager');

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $this->invoice));
        $this->assertTrue($this->policy->create($user));
        $this->assertTrue($this->policy->update($user, $this->invoice));
        $this->assertTrue($this->policy->finalize($user, $this->invoice));
    }

    /** @test */
    public function partner_can_only_view_own_invoices(): void
    {
        $user = $this->createUserWithRole('partner_admin', $this->partner);

        // Can view own invoice
        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->view($user, $this->invoice));

        // Cannot view other partner's invoice
        $otherInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->otherPartner->id,
            'status' => AggregateInvoice::STATUS_DRAFT,
        ]);
        $this->assertFalse($this->policy->view($user, $otherInvoice));

        // Cannot create, update, or delete
        $this->assertFalse($this->policy->create($user));
        $this->assertFalse($this->policy->update($user, $this->invoice));
        $this->assertFalse($this->policy->delete($user, $this->invoice));
    }

    /** @test */
    public function partner_can_download_own_invoice_pdf(): void
    {
        $user = $this->createUserWithRole('partner_admin', $this->partner);

        $this->assertTrue($this->policy->downloadPdf($user, $this->invoice));

        // But not other partner's PDF
        $otherInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->otherPartner->id,
        ]);
        $this->assertFalse($this->policy->downloadPdf($user, $otherInvoice));
    }

    /** @test */
    public function only_draft_invoices_can_be_finalized(): void
    {
        $user = $this->createUserWithRole('billing_manager');

        // Draft invoice can be finalized
        $this->assertTrue($this->policy->finalize($user, $this->invoice));

        // Open invoice cannot be finalized
        $openInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_OPEN,
        ]);
        $this->assertFalse($this->policy->finalize($user, $openInvoice));
    }

    /** @test */
    public function only_open_invoices_can_be_sent(): void
    {
        $user = $this->createUserWithRole('billing_manager');

        // Draft invoice cannot be sent
        $this->assertFalse($this->policy->send($user, $this->invoice));

        // Open invoice can be sent
        $openInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_OPEN,
        ]);
        $this->assertTrue($this->policy->send($user, $openInvoice));
    }

    /** @test */
    public function only_open_invoices_can_be_marked_as_paid(): void
    {
        $user = $this->createUserWithRole('billing_manager');

        // Draft cannot be marked paid
        $this->assertFalse($this->policy->markAsPaid($user, $this->invoice));

        // Open can be marked paid
        $openInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_OPEN,
        ]);
        $this->assertTrue($this->policy->markAsPaid($user, $openInvoice));

        // Already paid cannot be marked paid again
        $paidInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_PAID,
        ]);
        $this->assertFalse($this->policy->markAsPaid($user, $paidInvoice));
    }

    /** @test */
    public function paid_invoices_cannot_be_voided(): void
    {
        $user = $this->createUserWithRole('billing_manager');

        $paidInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_PAID,
        ]);

        $this->assertFalse($this->policy->void($user, $paidInvoice));
    }

    /** @test */
    public function draft_invoices_can_be_voided(): void
    {
        $user = $this->createUserWithRole('billing_manager');

        $this->assertTrue($this->policy->void($user, $this->invoice));
    }

    /** @test */
    public function paid_invoices_cannot_be_deleted(): void
    {
        $user = $this->createUserWithRole('admin');

        $paidInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_PAID,
        ]);

        $this->assertFalse($this->policy->delete($user, $paidInvoice));
    }

    /** @test */
    public function only_internal_staff_can_view_stripe_link(): void
    {
        $admin = $this->createUserWithRole('admin');
        $partner = $this->createUserWithRole('partner_admin', $this->partner);

        // Admin can see Stripe link
        $this->assertTrue($this->policy->viewStripeLink($admin, $this->invoice));

        // Partner cannot see Stripe admin link
        $this->assertFalse($this->policy->viewStripeLink($partner, $this->invoice));
    }

    /** @test */
    public function resend_requires_already_sent_invoice(): void
    {
        $user = $this->createUserWithRole('billing_manager');

        // Never-sent open invoice cannot be resent
        $openInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_OPEN,
            'sent_at' => null,
        ]);
        $this->assertFalse($this->policy->resend($user, $openInvoice));

        // Already-sent open invoice can be resent
        $sentInvoice = AggregateInvoice::factory()->create([
            'partner_company_id' => $this->partner->id,
            'status' => AggregateInvoice::STATUS_OPEN,
            'sent_at' => now()->subDay(),
        ]);
        $this->assertTrue($this->policy->resend($user, $sentInvoice));
    }
}
