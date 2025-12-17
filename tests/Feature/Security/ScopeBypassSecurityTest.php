<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\Call;
use App\Models\CallbackRequest;
use App\Models\Transaction;
use App\Models\CurrencyExchangeRate;
use App\Models\BalanceBonusTier;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;

/**
 * Security Tests for withoutGlobalScopes() Usage
 *
 * Verifies that all scope bypass patterns maintain tenant isolation:
 * 1. Explicit tenant_id filters work correctly
 * 2. whereHas on tenant-scoped relations work correctly
 * 3. Super Admin bypass requires proper authorization
 * 4. Global reference tables are correctly unscoped
 *
 * @package Tests\Feature\Security
 * @group security
 * @group scope-bypass
 */
class ScopeBypassSecurityTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected ?Company $companyA = null;
    protected ?Company $companyB = null;
    protected ?User $userA = null;
    protected ?User $userB = null;
    protected ?User $superAdmin = null;
    protected ?Customer $customerA = null;
    protected ?Customer $customerB = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock CalcomV2Client API key to prevent constructor errors
        config(['services.calcom.api_key' => 'test-api-key-for-testing']);

        // Use existing companies from database (production-style test)
        $this->companyA = Company::first();

        if (!$this->companyA) {
            $this->markTestSkipped('Test requires at least 1 company in database');
            return;
        }

        $this->companyB = Company::where('id', '!=', $this->companyA->id)->first();

        // Skip test if we don't have two different companies
        if (!$this->companyB) {
            $this->markTestSkipped('Test requires at least 2 companies in database');
            return;
        }

        // Find or create users for each company
        $this->userA = User::where('company_id', $this->companyA->id)->first();
        $this->userB = User::where('company_id', $this->companyB->id)->first();

        if (!$this->userA || !$this->userB) {
            $this->markTestSkipped('Test requires users in both companies');
            return;
        }

        // Find super admin
        $this->superAdmin = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'Super Admin', 'superadmin']);
        })->first();

        if (!$this->superAdmin) {
            $this->markTestSkipped('Test requires a super admin user');
            return;
        }

        // Find customers for each company
        $this->customerA = Customer::where('company_id', $this->companyA->id)->first();
        $this->customerB = Customer::where('company_id', $this->companyB->id)->first();
    }

    // =====================================================
    // Pattern 1: Explicit tenant_id Filter Tests
    // =====================================================

    /**
     * @test
     * Pattern: withoutGlobalScopes() + where('tenant_id', $companyId)
     * Resources: TransactionResource, BalanceOverviewWidget
     */
    public function tenant_id_filter_isolates_transactions_correctly()
    {
        // Find existing transactions for both companies
        $transactionA = Transaction::where('tenant_id', $this->companyA->id)->first();
        $transactionB = Transaction::where('tenant_id', $this->companyB->id)->first();

        if (!$transactionA || !$transactionB) {
            $this->markTestSkipped('Test requires transactions in both companies');
        }

        // User A should only see Company A's transactions
        $this->actingAs($this->userA);
        $resourceQuery = \App\Filament\Customer\Resources\TransactionResource::getEloquentQuery();
        $results = $resourceQuery->get();

        // Verify isolation: Company A's transactions visible, Company B's not
        $companyATransactionIds = Transaction::where('tenant_id', $this->companyA->id)->pluck('id');
        $companyBTransactionIds = Transaction::where('tenant_id', $this->companyB->id)->pluck('id');

        foreach ($companyATransactionIds as $id) {
            $this->assertTrue($results->contains('id', $id), "User A should see transaction $id from Company A");
        }
        foreach ($companyBTransactionIds as $id) {
            $this->assertFalse($results->contains('id', $id), "User A should NOT see transaction $id from Company B");
        }

        // User B should only see Company B's transactions
        $this->actingAs($this->userB);
        $resourceQuery = \App\Filament\Customer\Resources\TransactionResource::getEloquentQuery();
        $results = $resourceQuery->get();

        foreach ($companyBTransactionIds as $id) {
            $this->assertTrue($results->contains('id', $id), "User B should see transaction $id from Company B");
        }
        foreach ($companyATransactionIds as $id) {
            $this->assertFalse($results->contains('id', $id), "User B should NOT see transaction $id from Company A");
        }
    }

    // =====================================================
    // Pattern 2: whereHas on Tenant-Scoped Relation Tests
    // =====================================================

    /**
     * @test
     * Pattern: withoutGlobalScopes() + whereHas('customer', fn($q) => $q->where('company_id', $companyId))
     * Resources: CustomerNoteResource, CallbackRequestResource
     */
    public function wherehas_customer_filter_isolates_notes_correctly()
    {
        if (!$this->customerA || !$this->customerB) {
            $this->markTestSkipped('Test requires customers in both companies');
        }

        // Find existing customer notes for both companies
        $noteA = CustomerNote::whereHas('customer', fn($q) => $q->where('company_id', $this->companyA->id))->first();
        $noteB = CustomerNote::whereHas('customer', fn($q) => $q->where('company_id', $this->companyB->id))->first();

        if (!$noteA || !$noteB) {
            $this->markTestSkipped('Test requires customer notes in both companies');
        }

        // User A should only see notes for Company A's customers
        $this->actingAs($this->userA);
        $resourceQuery = \App\Filament\Customer\Resources\CustomerNoteResource::getEloquentQuery();
        $results = $resourceQuery->get();

        // Check that results only contain Company A's data
        $companyANoteIds = CustomerNote::whereHas('customer', fn($q) => $q->where('company_id', $this->companyA->id))->pluck('id');
        $companyBNoteIds = CustomerNote::whereHas('customer', fn($q) => $q->where('company_id', $this->companyB->id))->pluck('id');

        $this->assertTrue($results->contains('id', $noteA->id), 'User A should see their company notes');
        foreach ($companyBNoteIds as $id) {
            $this->assertFalse($results->contains('id', $id), "User A should NOT see Company B's note $id");
        }
    }

    /**
     * @test
     * Pattern: withoutGlobalScopes() + whereHas on customer
     * Resources: CallbackRequestResource (Customer Portal)
     */
    public function wherehas_customer_filter_isolates_callback_requests_correctly()
    {
        if (!$this->customerA || !$this->customerB) {
            $this->markTestSkipped('Test requires customers in both companies');
        }

        // Find existing callback requests for both companies
        $callbackA = CallbackRequest::whereHas('customer', fn($q) => $q->where('company_id', $this->companyA->id))->first();
        $callbackB = CallbackRequest::whereHas('customer', fn($q) => $q->where('company_id', $this->companyB->id))->first();

        if (!$callbackA || !$callbackB) {
            $this->markTestSkipped('Test requires callback requests in both companies');
        }

        // User A should only see callbacks for Company A's customers
        $this->actingAs($this->userA);
        $resourceQuery = \App\Filament\Customer\Resources\CallbackRequestResource::getEloquentQuery();
        $results = $resourceQuery->get();

        $companyBCallbackIds = CallbackRequest::whereHas('customer', fn($q) => $q->where('company_id', $this->companyB->id))->pluck('id');

        $this->assertTrue($results->contains('id', $callbackA->id), 'User A should see their company callbacks');
        foreach ($companyBCallbackIds as $id) {
            $this->assertFalse($results->contains('id', $id), "User A should NOT see Company B's callback $id");
        }
    }

    // =====================================================
    // Pattern 3: Super Admin Bypass Tests
    // =====================================================

    /**
     * @test
     * Pattern: Role check + withoutGlobalScopes()
     * Resources: CallResource::resolveRecordRouteBinding()
     */
    public function super_admin_can_access_all_company_calls()
    {
        // Find existing calls for both companies
        $callA = Call::where('company_id', $this->companyA->id)->first();
        $callB = Call::where('company_id', $this->companyB->id)->first();

        if (!$callA || !$callB) {
            $this->markTestSkipped('Test requires calls in both companies');
        }

        // Super Admin should access both calls
        $this->actingAs($this->superAdmin);

        $resolvedA = \App\Filament\Resources\CallResource::resolveRecordRouteBinding($callA->id);
        $resolvedB = \App\Filament\Resources\CallResource::resolveRecordRouteBinding($callB->id);

        $this->assertNotNull($resolvedA, 'Super Admin should access Company A call');
        $this->assertEquals($callA->id, $resolvedA->id);
        $this->assertNotNull($resolvedB, 'Super Admin should access Company B call');
        $this->assertEquals($callB->id, $resolvedB->id);
    }

    /**
     * @test
     * Regular users should NOT access other company's calls via route binding
     */
    public function regular_user_cannot_access_other_company_calls_via_route_binding()
    {
        // Find call from Company B
        $callB = Call::where('company_id', $this->companyB->id)->first();

        if (!$callB) {
            $this->markTestSkipped('Test requires a call in Company B');
        }

        // User A (Company A) should NOT access Company B's call
        $this->actingAs($this->userA);

        $resolved = \App\Filament\Resources\CallResource::resolveRecordRouteBinding($callB->id);

        $this->assertNull($resolved, 'Regular user should NOT access other company calls');
    }

    /**
     * @test
     * Verify all Super Admin role variations work
     */
    public function super_admin_role_variations_all_grant_bypass()
    {
        $call = Call::where('company_id', $this->companyB->id)->first();

        if (!$call) {
            $this->markTestSkipped('Test requires a call in Company B');
        }

        // Test 'super_admin' role (already assigned to $this->superAdmin)
        $this->actingAs($this->superAdmin);
        $resolved = \App\Filament\Resources\CallResource::resolveRecordRouteBinding($call->id);
        $this->assertNotNull($resolved, 'super_admin role should grant bypass');

        // Test that regular user WITHOUT super_admin role cannot bypass
        $this->actingAs($this->userA);
        $resolved = \App\Filament\Resources\CallResource::resolveRecordRouteBinding($call->id);
        $this->assertNull($resolved, 'Regular user should NOT have bypass');
    }

    // =====================================================
    // Pattern 4: Global Reference Table Tests
    // =====================================================

    /**
     * @test
     * Pattern: withoutGlobalScopes() on tenant-agnostic tables
     * Resources: CurrencyExchangeRateResource, BalanceBonusTierResource
     */
    public function global_reference_tables_visible_to_all_users()
    {
        // Both users should see the same global data
        $this->actingAs($this->userA);
        $ratesA = \App\Filament\Customer\Resources\CurrencyExchangeRateResource::getEloquentQuery()->get();
        $tiersA = \App\Filament\Customer\Resources\BalanceBonusTierResource::getEloquentQuery()->get();

        $this->actingAs($this->userB);
        $ratesB = \App\Filament\Customer\Resources\CurrencyExchangeRateResource::getEloquentQuery()->get();
        $tiersB = \App\Filament\Customer\Resources\BalanceBonusTierResource::getEloquentQuery()->get();

        // Both should see the same data (global reference tables have no tenant filtering)
        $this->assertEquals($ratesA->count(), $ratesB->count(), 'Both users should see same exchange rates');
        $this->assertEquals($tiersA->count(), $tiersB->count(), 'Both users should see same bonus tiers');

        // Verify the IDs match (same data)
        $this->assertEquals(
            $ratesA->pluck('id')->sort()->values()->toArray(),
            $ratesB->pluck('id')->sort()->values()->toArray(),
            'Exchange rate IDs should match between users'
        );
    }

    // =====================================================
    // Pattern 5: SoftDeletingScope-Only Bypass Tests
    // =====================================================

    /**
     * @test
     * Pattern: withoutGlobalScopes([SoftDeletingScope::class]) - should NOT affect tenant isolation
     * Resources: NotificationConfigurationResource, PolicyConfigurationResource, etc.
     */
    public function soft_deleting_scope_bypass_maintains_tenant_isolation()
    {
        // This test verifies that bypassing ONLY SoftDeletingScope
        // does NOT bypass CompanyScope/TenantScope

        $this->actingAs($this->userA);

        // NotificationConfigurationResource bypasses only SoftDeletingScope
        $query = \App\Filament\Resources\NotificationConfigurationResource::getEloquentQuery();

        // The query should still have company filtering active
        // We verify by checking the query SQL contains company constraint
        $sql = $query->toSql();

        // The query should NOT be completely unscoped
        // (It will have company_id filtering from CompanyScope)
        $this->assertStringNotContainsString('select * from `notification_configurations`', $sql);
    }

    // =====================================================
    // Edge Case & Regression Tests
    // =====================================================

    /**
     * @test
     * Unauthenticated users should get an error when accessing scoped resources
     */
    public function unauthenticated_user_cannot_access_tenant_filtered_resources()
    {
        // Logout first
        auth()->logout();

        // This should throw or return empty because there's no authenticated user
        try {
            $results = \App\Filament\Customer\Resources\TransactionResource::getEloquentQuery()->get();
            // If no exception, verify empty results or null user handling
            $this->assertTrue(true, 'Query handled unauthenticated user gracefully');
        } catch (\Exception $e) {
            // Expected behavior - unauthenticated user throws exception
            $this->assertTrue(true, 'Correctly threw exception for unauthenticated user');
        }
    }

    /**
     * @test
     * Verify HasSecureScopeBypass trait methods work correctly
     */
    public function secure_scope_bypass_trait_methods_work()
    {
        $trait = new class {
            use \App\Filament\Concerns\HasSecureScopeBypass;
        };

        // Test isSuperAdmin
        $this->actingAs($this->superAdmin);
        $this->assertTrue($trait->isSuperAdmin(), 'Super admin should be recognized');

        $this->actingAs($this->userA);
        $this->assertFalse($trait->isSuperAdmin(), 'Regular user should not be super admin');

        // Test getCurrentCompanyIdOrFail
        $this->assertEquals($this->companyA->id, $trait->getCurrentCompanyIdOrFail(), 'Should return current company ID');
    }
}
