<?php

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\User;
use App\Models\Policy;
use App\Models\BookingType;
use App\Models\Service;
use App\Models\Booking;
use App\Models\CallbackRequest;
use App\Models\NotificationConfiguration;
use App\Models\PolicyConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Policy Authorization Test Suite
 *
 * Tests all 8 authorization policies:
 * - BookingPolicy
 * - BookingTypePolicy
 * - CallbackRequestPolicy
 * - NotificationConfigurationPolicy
 * - PolicyConfigurationPolicy
 * - PolicyPolicy
 * - ServicePolicy
 * - UserPolicy
 */
class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Company $companyA;
    private Company $companyB;
    private User $userA;
    private User $userB;
    private User $adminA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::factory()->create();
        $this->companyB = Company::factory()->create();

        $this->userA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'role' => 'user',
        ]);

        $this->userB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'role' => 'user',
        ]);

        $this->adminA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'role' => 'admin',
        ]);
    }

    // ==================== BookingPolicy Tests ====================

    /**
     * @test
     * Test BookingPolicy view authorization
     */
    public function booking_policy_authorizes_view_for_same_company(): void
    {
        $booking = Booking::factory()->create([
            'company_id' => $this->companyA->id,
            'user_id' => $this->userA->id,
        ]);

        $this->assertTrue($this->userA->can('view', $booking));
        $this->assertFalse($this->userB->can('view', $booking));
    }

    /**
     * @test
     * Test BookingPolicy update authorization
     */
    public function booking_policy_authorizes_update_for_same_company(): void
    {
        $booking = Booking::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->adminA->can('update', $booking));
        $this->assertFalse($this->userB->can('update', $booking));
    }

    /**
     * @test
     * Test BookingPolicy delete authorization
     */
    public function booking_policy_authorizes_delete_for_same_company(): void
    {
        $booking = Booking::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->adminA->can('delete', $booking));
        $this->assertFalse($this->userB->can('delete', $booking));
    }

    // ==================== BookingTypePolicy Tests ====================

    /**
     * @test
     * Test BookingTypePolicy view authorization
     */
    public function booking_type_policy_authorizes_view_for_same_company(): void
    {
        $bookingType = BookingType::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->userA->can('view', $bookingType));
        $this->assertFalse($this->userB->can('view', $bookingType));
    }

    /**
     * @test
     * Test BookingTypePolicy update authorization
     */
    public function booking_type_policy_authorizes_update_for_admins(): void
    {
        $bookingType = BookingType::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->adminA->can('update', $bookingType));
        $this->assertFalse($this->userB->can('update', $bookingType));
    }

    // ==================== PolicyPolicy Tests ====================

    /**
     * @test
     * Test PolicyPolicy view authorization
     */
    public function policy_policy_authorizes_view_for_same_company(): void
    {
        $policy = Policy::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->userA->can('view', $policy));
        $this->assertFalse($this->userB->can('view', $policy));
    }

    /**
     * @test
     * Test PolicyPolicy update authorization
     */
    public function policy_policy_authorizes_update_for_same_company(): void
    {
        $policy = Policy::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->adminA->can('update', $policy));
        $this->assertFalse($this->userB->can('update', $policy));
    }

    /**
     * @test
     * Test PolicyPolicy delete authorization
     */
    public function policy_policy_authorizes_delete_for_same_company(): void
    {
        $policy = Policy::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->adminA->can('delete', $policy));
        $this->assertFalse($this->userB->can('delete', $policy));
    }

    // ==================== ServicePolicy Tests ====================

    /**
     * @test
     * Test ServicePolicy view authorization
     */
    public function service_policy_authorizes_view_for_same_company(): void
    {
        $service = Service::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->userA->can('view', $service));
        $this->assertFalse($this->userB->can('view', $service));
    }

    /**
     * @test
     * Test ServicePolicy update authorization
     */
    public function service_policy_authorizes_update_for_admins(): void
    {
        $service = Service::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->adminA->can('update', $service));
        $this->assertFalse($this->userB->can('update', $service));
    }

    // ==================== CallbackRequestPolicy Tests ====================

    /**
     * @test
     * Test CallbackRequestPolicy view authorization
     */
    public function callback_request_policy_authorizes_view_for_same_company(): void
    {
        $callback = CallbackRequest::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->userA->can('view', $callback));
        $this->assertFalse($this->userB->can('view', $callback));
    }

    /**
     * @test
     * Test CallbackRequestPolicy update authorization
     */
    public function callback_request_policy_authorizes_update_for_same_company(): void
    {
        $callback = CallbackRequest::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->adminA->can('update', $callback));
        $this->assertFalse($this->userB->can('update', $callback));
    }

    // ==================== NotificationConfigurationPolicy Tests ====================

    /**
     * @test
     * Test NotificationConfigurationPolicy view authorization
     */
    public function notification_config_policy_authorizes_view_for_same_company(): void
    {
        $config = NotificationConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->userA->can('view', $config));
        $this->assertFalse($this->userB->can('view', $config));
    }

    /**
     * @test
     * Test NotificationConfigurationPolicy update authorization
     */
    public function notification_config_policy_authorizes_update_for_admins(): void
    {
        $config = NotificationConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->adminA->can('update', $config));
        $this->assertFalse($this->userB->can('update', $config));
    }

    // ==================== PolicyConfigurationPolicy Tests ====================

    /**
     * @test
     * Test PolicyConfigurationPolicy view authorization
     */
    public function policy_config_policy_authorizes_view_for_same_company(): void
    {
        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->userA->can('view', $config));
        $this->assertFalse($this->userB->can('view', $config));
    }

    /**
     * @test
     * Test PolicyConfigurationPolicy update authorization
     */
    public function policy_config_policy_authorizes_update_for_admins(): void
    {
        $config = PolicyConfiguration::factory()->create([
            'company_id' => $this->companyA->id,
        ]);

        $this->assertTrue($this->adminA->can('update', $config));
        $this->assertFalse($this->userB->can('update', $config));
    }

    // ==================== UserPolicy Tests ====================

    /**
     * @test
     * Test UserPolicy view authorization
     */
    public function user_policy_authorizes_view_for_same_company(): void
    {
        $this->assertTrue($this->userA->can('view', $this->userA));
        $this->assertFalse($this->userA->can('view', $this->userB));
    }

    /**
     * @test
     * Test UserPolicy update authorization
     */
    public function user_policy_authorizes_update_for_same_company(): void
    {
        $this->assertTrue($this->adminA->can('update', $this->userA));
        $this->assertFalse($this->adminA->can('update', $this->userB));
    }
}
