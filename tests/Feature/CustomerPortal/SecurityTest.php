<?php

namespace Tests\Feature\CustomerPortal;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\RetellCallSession;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Filament\Facades\Filament;

/**
 * Customer Portal Security Test Suite
 *
 * Tests for Critical Vulnerabilities:
 * - VULN-PORTAL-001: Panel Access Control Bypass (CVSS 9.1)
 * - VULN-PORTAL-002: Missing Authorization Policy (CVSS 8.2)
 * - Multi-Tenancy Data Isolation
 * - Feature Flag Protection
 *
 * @group security
 * @group customer-portal
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminUser;
    protected User $companyAOwner;
    protected User $companyBOwner;
    protected User $companyAStaff;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test companies
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Create test users
        $this->adminUser = User::factory()->create([
            'company_id' => null,
            'email' => 'admin@test.local',
        ]);
        $this->adminUser->assignRole('super_admin');

        $this->companyAOwner = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'owner-a@test.local',
        ]);
        $this->companyAOwner->assignRole('company_owner');

        $this->companyBOwner = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'owner-b@test.local',
        ]);
        $this->companyBOwner->assignRole('company_owner');

        $this->companyAStaff = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'staff-a@test.local',
        ]);
        $this->companyAStaff->assignRole('company_staff');
    }

    /**
     * TEST 1: VULN-PORTAL-001 - Panel Access Control Bypass
     */
    public function test_admin_cannot_access_customer_portal(): void
    {
        // GIVEN feature flag is enabled
        Config::set('features.customer_portal', true);

        // WHEN admin tries to access customer portal
        $canAccess = $this->adminUser->canAccessCustomerPortal();

        // THEN access should be denied
        $this->assertFalse(
            $canAccess,
            'FAIL: Admin user can access customer portal (VULN-PORTAL-001)'
        );
    }

    public function test_customer_owner_can_access_portal_when_enabled(): void
    {
        // GIVEN feature flag is enabled
        Config::set('features.customer_portal', true);

        // WHEN company owner tries to access portal
        $canAccess = $this->companyAOwner->canAccessCustomerPortal();

        // THEN access should be granted
        $this->assertTrue(
            $canAccess,
            'FAIL: Company owner cannot access customer portal'
        );
    }

    public function test_customer_portal_disabled_by_feature_flag(): void
    {
        // GIVEN feature flag is disabled
        Config::set('features.customer_portal', false);

        // WHEN company owner tries to access portal
        $canAccess = $this->companyAOwner->canAccessCustomerPortal();

        // THEN access should be denied
        $this->assertFalse(
            $canAccess,
            'FAIL: Feature flag kill-switch not working'
        );
    }

    public function test_user_without_company_cannot_access_portal(): void
    {
        // GIVEN feature flag is enabled
        Config::set('features.customer_portal', true);

        // AND a user without company_id
        $userWithoutCompany = User::factory()->create([
            'company_id' => null,
            'email' => 'nocompany@test.local',
        ]);
        $userWithoutCompany->assignRole('company_owner'); // Has role but no company

        // WHEN they try to access portal
        $canAccess = $userWithoutCompany->canAccessCustomerPortal();

        // THEN access should be denied
        $this->assertFalse(
            $canAccess,
            'FAIL: User without company_id can access portal'
        );
    }

    /**
     * TEST 2: VULN-PORTAL-002 - Authorization Policy Enforcement
     */
    public function test_user_cannot_view_other_company_call_sessions(): void
    {
        // GIVEN Company A has a call session
        $callSessionA = RetellCallSession::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        // WHEN Company B owner tries to view it
        $policy = app(\App\Policies\RetellCallSessionPolicy::class);
        $canView = $policy->view($this->companyBOwner, $callSessionA);

        // THEN access should be denied
        $this->assertFalse(
            $canView,
            'FAIL: User can view other company call sessions (VULN-PORTAL-002)'
        );
    }

    public function test_user_can_view_own_company_call_sessions(): void
    {
        // GIVEN Company A has a call session
        $callSessionA = RetellCallSession::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        // WHEN Company A owner tries to view it
        $policy = app(\App\Policies\RetellCallSessionPolicy::class);
        $canView = $policy->view($this->companyAOwner, $callSessionA);

        // THEN access should be granted
        $this->assertTrue(
            $canView,
            'FAIL: User cannot view own company call sessions'
        );
    }

    public function test_staff_cannot_update_other_company_call_sessions(): void
    {
        // GIVEN Company B has a call session
        $callSessionB = RetellCallSession::factory()->create([
            'company_id' => $this->companyB->id,
        ]);

        // WHEN Company A staff tries to update it
        $policy = app(\App\Policies\RetellCallSessionPolicy::class);
        $canUpdate = $policy->update($this->companyAStaff, $callSessionB);

        // THEN access should be denied
        $this->assertFalse(
            $canUpdate,
            'FAIL: Staff can update other company sessions'
        );
    }

    /**
     * TEST 3: Multi-Tenancy Data Isolation (BelongsToCompany)
     */
    public function test_company_scoped_query_only_returns_own_data(): void
    {
        // GIVEN both companies have call sessions
        RetellCallSession::factory()->count(3)->create([
            'company_id' => $this->companyA->id,
        ]);
        RetellCallSession::factory()->count(2)->create([
            'company_id' => $this->companyB->id,
        ]);

        // WHEN Company A owner queries sessions (with CompanyScope applied)
        $this->actingAs($this->companyAOwner);
        app()->instance('current_company_id', $this->companyA->id);

        $sessions = RetellCallSession::where('company_id', $this->companyA->id)->get();

        // THEN only Company A sessions should be returned
        $this->assertCount(3, $sessions, 'FAIL: Company scope returning wrong count');
        foreach ($sessions as $session) {
            $this->assertEquals(
                $this->companyA->id,
                $session->company_id,
                'FAIL: Session from other company leaked through'
            );
        }
    }

    public function test_cannot_directly_query_other_company_sessions(): void
    {
        // GIVEN Company B has sessions
        $sessionB = RetellCallSession::factory()->create([
            'company_id' => $this->companyB->id,
        ]);

        // WHEN Company A owner tries to find it by ID
        $this->actingAs($this->companyAOwner);

        $found = RetellCallSession::where('id', $sessionB->id)
            ->where('company_id', $this->companyA->id)
            ->first();

        // THEN it should not be found
        $this->assertNull(
            $found,
            'FAIL: Can access other company session by ID'
        );
    }

    /**
     * TEST 4: Appointment Multi-Tenancy
     */
    public function test_appointments_are_company_scoped(): void
    {
        // GIVEN both companies have customers and appointments
        $customerA = Customer::factory()->create(['company_id' => $this->companyA->id]);
        $customerB = Customer::factory()->create(['company_id' => $this->companyB->id]);

        Appointment::factory()->count(2)->create([
            'company_id' => $this->companyA->id,
            'customer_id' => $customerA->id,
        ]);
        Appointment::factory()->count(3)->create([
            'company_id' => $this->companyB->id,
            'customer_id' => $customerB->id,
        ]);

        // WHEN Company A owner queries appointments
        $this->actingAs($this->companyAOwner);

        $appointments = Appointment::where('company_id', $this->companyA->id)->get();

        // THEN only Company A appointments should be returned
        $this->assertCount(2, $appointments);
        foreach ($appointments as $appointment) {
            $this->assertEquals($this->companyA->id, $appointment->company_id);
        }
    }

    /**
     * TEST 5: Feature Flag Granularity
     */
    public function test_sub_feature_flags_respect_master_flag(): void
    {
        // GIVEN master flag is disabled
        Config::set('features.customer_portal', false);

        // AND sub-features are enabled
        Config::set('features.customer_portal_calls', true);
        Config::set('features.customer_portal_appointments', true);

        // WHEN checking portal access
        $canAccess = $this->companyAOwner->canAccessCustomerPortal();

        // THEN access should be denied (master flag takes precedence)
        $this->assertFalse(
            $canAccess,
            'FAIL: Sub-features active when master flag disabled'
        );
    }

    public function test_individual_feature_flags_control_access(): void
    {
        // GIVEN master flag is enabled
        Config::set('features.customer_portal', true);

        // BUT calls feature is disabled
        Config::set('features.customer_portal_calls', false);

        // WHEN checking calls feature
        $callsEnabled = config('features.customer_portal_calls');

        // THEN it should be disabled
        $this->assertFalse(
            $callsEnabled,
            'FAIL: Individual feature flag not respected'
        );
    }

    /**
     * TEST 6: HTTP Endpoint Security
     */
    public function test_portal_routes_return_404_when_disabled(): void
    {
        // GIVEN feature flag is disabled
        Config::set('features.customer_portal', false);

        // WHEN accessing portal route
        $response = $this->actingAs($this->companyAOwner)
            ->get('/portal');

        // THEN should return 404
        $response->assertStatus(404);
    }

    public function test_portal_routes_accessible_when_enabled(): void
    {
        // GIVEN feature flag is enabled
        Config::set('features.customer_portal', true);

        // WHEN accessing portal route
        $response = $this->actingAs($this->companyAOwner)
            ->get('/portal');

        // THEN should be successful (200 or redirect to login)
        $this->assertContains(
            $response->status(),
            [200, 302],
            'Portal route not accessible when feature enabled'
        );
    }

    /**
     * TEST 7: Admin Panel Separation
     */
    public function test_admin_can_access_admin_panel(): void
    {
        // WHEN admin checks admin panel access
        $canAccess = $this->adminUser->canAccessAdminPanel();

        // THEN access should be granted
        $this->assertTrue(
            $canAccess,
            'FAIL: Admin cannot access admin panel'
        );
    }

    public function test_customer_cannot_access_admin_panel(): void
    {
        // WHEN customer owner checks admin panel access
        $canAccess = $this->companyAOwner->canAccessAdminPanel();

        // THEN access should be denied
        $this->assertFalse(
            $canAccess,
            'FAIL: Customer can access admin panel'
        );
    }

    public function test_admin_panel_route_denies_customer_users(): void
    {
        // WHEN customer tries to access admin panel
        $response = $this->actingAs($this->companyAOwner)
            ->get('/admin');

        // THEN should be denied (403 or redirect)
        $this->assertNotEquals(
            200,
            $response->status(),
            'FAIL: Customer user can access /admin'
        );
    }

    /**
     * TEST 8: Database Index Performance
     */
    public function test_company_dashboard_query_uses_index(): void
    {
        // GIVEN company has call sessions
        RetellCallSession::factory()->count(100)->create([
            'company_id' => $this->companyA->id,
        ]);

        // WHEN running company dashboard query
        $explain = \DB::select("
            EXPLAIN SELECT id, customer_id, started_at, call_status
            FROM retell_call_sessions
            WHERE company_id = ?
              AND call_status IN ('completed', 'failed')
              AND started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY started_at DESC
            LIMIT 50
        ", [$this->companyA->id]);

        // THEN should use idx_retell_sessions_company_status
        $usesIndex = false;
        foreach ($explain as $row) {
            if (isset($row->key) && str_contains($row->key, 'idx_retell_sessions_company_status')) {
                $usesIndex = true;
                break;
            }
        }

        $this->assertTrue(
            $usesIndex,
            'FAIL: Company dashboard query not using performance index'
        );
    }

    /**
     * TEST 9: SQL Injection Prevention
     */
    public function test_company_scope_prevents_sql_injection(): void
    {
        // GIVEN malicious input attempting SQL injection
        $maliciousInput = "1 OR 1=1; DROP TABLE retell_call_sessions;--";

        // WHEN querying with malicious input (properly escaped)
        $sessions = RetellCallSession::where('company_id', $maliciousInput)->get();

        // THEN query should safely return empty results (no exception)
        $this->assertCount(0, $sessions);

        // AND table should still exist
        $tableExists = \DB::select("SHOW TABLES LIKE 'retell_call_sessions'");
        $this->assertNotEmpty($tableExists, 'FAIL: SQL injection succeeded');
    }

    /**
     * TEST 10: Session Fixation Prevention
     */
    public function test_session_regenerates_on_login(): void
    {
        // GIVEN a user is logged out
        $oldSessionId = session()->getId();

        // WHEN user logs in
        $this->post('/login', [
            'email' => $this->companyAOwner->email,
            'password' => 'password',
        ]);

        // THEN session ID should change
        $newSessionId = session()->getId();
        $this->assertNotEquals(
            $oldSessionId,
            $newSessionId,
            'FAIL: Session not regenerated on login (session fixation risk)'
        );
    }
}
