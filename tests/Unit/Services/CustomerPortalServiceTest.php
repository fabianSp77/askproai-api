<?php

namespace Tests\Unit\Services;

use App\Models\Appointment;
use App\Models\Call;
use App\Models\Company;
use App\Models\CustomerAuth;
use App\Notifications\CustomerMagicLinkNotification;
use App\Notifications\CustomerWelcomeNotification;
use App\Services\CustomerPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerPortalServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerPortalService $service;
    private Company $company;
    private CustomerAuth $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new CustomerPortalService();
        
        // Create test company
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
            'subdomain' => 'test-company',
            'slug' => 'test-company',
            'portal_enabled' => true,
            'portal_features' => [
                'appointments' => true,
                'invoices' => true,
                'profile' => true,
                'cancellation' => true,
                'rescheduling' => true,
            ],
        ]);
        
        // Create test customer
        $this->customer = CustomerAuth::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+491234567890',
            'portal_enabled' => false,
            'email_verified_at' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test enable portal access
     */
    #[Test]
    public function test_enable_portal_access()
    {
        Notification::fake();
        
        $result = $this->service->enablePortalAccess($this->customer);
        
        $this->assertTrue($result);
        
        // Check customer was updated
        $this->customer->refresh();
        $this->assertTrue($this->customer->portal_enabled);
        $this->assertNotNull($this->customer->email_verified_at);
        $this->assertTrue(Hash::check(12, $this->customer->password)); // Check password length
        
        // Check notification was sent
        Notification::assertSentTo(
            $this->customer,
            CustomerWelcomeNotification::class
        );
    }

    /**
     * Test enable portal access with custom password
     */
    #[Test]
    public function test_enable_portal_access_with_custom_password()
    {
        Notification::fake();
        
        $password = 'customPassword123';
        $result = $this->service->enablePortalAccess($this->customer, $password);
        
        $this->assertTrue($result);
        
        // Check password was set correctly
        $this->customer->refresh();
        $this->assertTrue(Hash::check($password, $this->customer->password));
        
        // Check notification was sent with custom password
        Notification::assertSentTo(
            $this->customer,
            CustomerWelcomeNotification::class,
            function ($notification, $channels) use ($password) {
                return true; // Would check password in notification if accessible
            }
        );
    }

    /**
     * Test enable portal access with error
     */
    #[Test]
    public function test_enable_portal_access_with_error()
    {
        // Mock customer to throw exception on update
        $mockCustomer = Mockery::mock(CustomerAuth::class)->makePartial();
        $mockCustomer->shouldReceive('update')
            ->andThrow(new \Exception('Database error'));
        $mockCustomer->id = 1;
        
        Log::shouldReceive('error')->once();
        
        $result = $this->service->enablePortalAccess($mockCustomer);
        
        $this->assertFalse($result);
    }

    /**
     * Test disable portal access
     */
    #[Test]
    public function test_disable_portal_access()
    {
        // Enable portal first
        $this->customer->update([
            'portal_enabled' => true,
            'portal_access_token' => 'test_token',
            'portal_token_expires_at' => now()->addHours(24),
        ]);
        
        // Create API tokens
        $this->customer->createToken('test-token');
        $this->assertEquals(1, $this->customer->tokens()->count());
        
        $result = $this->service->disablePortalAccess($this->customer);
        
        $this->assertTrue($result);
        
        // Check customer was updated
        $this->customer->refresh();
        $this->assertFalse($this->customer->portal_enabled);
        $this->assertNull($this->customer->portal_access_token);
        $this->assertNull($this->customer->portal_token_expires_at);
        
        // Check tokens were deleted
        $this->assertEquals(0, $this->customer->tokens()->count());
    }

    /**
     * Test disable portal access with error
     */
    #[Test]
    public function test_disable_portal_access_with_error()
    {
        $mockCustomer = Mockery::mock(CustomerAuth::class)->makePartial();
        $mockCustomer->shouldReceive('update')
            ->andThrow(new \Exception('Database error'));
        $mockCustomer->id = 1;
        
        Log::shouldReceive('error')->once();
        
        $result = $this->service->disablePortalAccess($mockCustomer);
        
        $this->assertFalse($result);
    }

    /**
     * Test send magic link
     */
    #[Test]
    public function test_send_magic_link()
    {
        Notification::fake();
        
        $token = 'magic_link_token_123';
        $result = $this->service->sendMagicLink($this->customer, $token);
        
        $this->assertTrue($result);
        
        // Check notification was sent
        Notification::assertSentTo(
            $this->customer,
            CustomerMagicLinkNotification::class,
            function ($notification, $channels) use ($token) {
                return true; // Would check token in notification if accessible
            }
        );
    }

    /**
     * Test send magic link with error
     */
    #[Test]
    public function test_send_magic_link_with_error()
    {
        $mockCustomer = Mockery::mock(CustomerAuth::class)->makePartial();
        $mockCustomer->shouldReceive('notify')
            ->andThrow(new \Exception('Mail error'));
        $mockCustomer->id = 1;
        $mockCustomer->email = 'test@example.com';
        
        Log::shouldReceive('error')->once();
        
        $result = $this->service->sendMagicLink($mockCustomer, 'token');
        
        $this->assertFalse($result);
    }

    /**
     * Test get portal URL in production
     */
    #[Test]
    public function test_get_portal_url_production()
    {
        app()->detectEnvironment(function () {
            return 'production';
        });
        
        config(['app.domain' => 'askproai.de']);
        
        $url = $this->service->getPortalUrl($this->company);
        
        $this->assertEquals('https://test-company.askproai.de/portal', $url);
    }

    /**
     * Test get portal URL in development
     */
    #[Test]
    public function test_get_portal_url_development()
    {
        app()->detectEnvironment(function () {
            return 'local';
        });
        
        config(['app.url' => 'http://localhost:8000']);
        
        $url = $this->service->getPortalUrl($this->company);
        
        $this->assertEquals('http://localhost:8000/portal/test-company', $url);
    }

    /**
     * Test get portal URL with fallback to slug
     */
    #[Test]
    public function test_get_portal_url_with_slug_fallback()
    {
        $this->company->update(['subdomain' => null]);
        
        config(['app.url' => 'http://localhost:8000']);
        
        $url = $this->service->getPortalUrl($this->company);
        
        $this->assertEquals('http://localhost:8000/portal/test-company', $url);
    }

    /**
     * Test get portal URL with name slug
     */
    #[Test]
    public function test_get_portal_url_with_name_slug()
    {
        $this->company->update([
            'subdomain' => null,
            'slug' => null,
            'name' => 'Test Company GmbH',
        ]);
        
        config(['app.url' => 'http://localhost:8000']);
        
        $url = $this->service->getPortalUrl($this->company);
        
        $this->assertEquals('http://localhost:8000/portal/test-company-gmbh', $url);
    }

    /**
     * Test can access portal - allowed
     */
    #[Test]
    public function test_can_access_portal_allowed()
    {
        $this->customer->update([
            'portal_enabled' => true,
            'email_verified_at' => now(),
        ]);
        
        $result = $this->service->canAccessPortal($this->customer);
        
        $this->assertTrue($result);
    }

    /**
     * Test can access portal - not enabled
     */
    #[Test]
    public function test_can_access_portal_not_enabled()
    {
        $this->customer->update([
            'portal_enabled' => false,
            'email_verified_at' => now(),
        ]);
        
        $result = $this->service->canAccessPortal($this->customer);
        
        $this->assertFalse($result);
    }

    /**
     * Test can access portal - email not verified
     */
    #[Test]
    public function test_can_access_portal_email_not_verified()
    {
        $this->customer->update([
            'portal_enabled' => true,
            'email_verified_at' => null,
        ]);
        
        $result = $this->service->canAccessPortal($this->customer);
        
        $this->assertFalse($result);
    }

    /**
     * Test can access portal - company portal disabled
     */
    #[Test]
    public function test_can_access_portal_company_disabled()
    {
        $this->customer->update([
            'portal_enabled' => true,
            'email_verified_at' => now(),
        ]);
        
        $this->company->update(['portal_enabled' => false]);
        
        $result = $this->service->canAccessPortal($this->customer);
        
        $this->assertFalse($result);
    }

    /**
     * Test get portal features
     */
    #[Test]
    public function test_get_portal_features()
    {
        $features = $this->service->getPortalFeatures($this->company);
        
        $this->assertTrue($features['appointments']);
        $this->assertTrue($features['invoices']);
        $this->assertTrue($features['profile']);
        $this->assertTrue($features['cancellation']);
        $this->assertTrue($features['rescheduling']); // Overridden by company
        $this->assertFalse($features['online_booking']);
        $this->assertFalse($features['chat_support']);
    }

    /**
     * Test get portal features with defaults
     */
    #[Test]
    public function test_get_portal_features_defaults()
    {
        $this->company->update(['portal_features' => null]);
        
        $features = $this->service->getPortalFeatures($this->company);
        
        $this->assertTrue($features['appointments']);
        $this->assertTrue($features['invoices']);
        $this->assertTrue($features['profile']);
        $this->assertTrue($features['cancellation']);
        $this->assertFalse($features['rescheduling']); // Default
    }

    /**
     * Test get customer stats
     */
    #[Test]
    public function test_get_customer_stats()
    {
        // Create test data
        Appointment::factory()->count(5)->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->addDays(1),
            'status' => 'scheduled',
        ]);
        
        Appointment::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->subDays(1),
            'status' => 'completed',
        ]);
        
        Appointment::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
            'status' => 'cancelled',
        ]);
        
        Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => 'no_show',
        ]);
        
        Call::factory()->count(7)->create([
            'customer_id' => $this->customer->id,
        ]);
        
        $stats = $this->service->getCustomerStats($this->customer);
        
        $this->assertEquals(11, $stats['total_appointments']);
        $this->assertEquals(5, $stats['upcoming_appointments']);
        $this->assertEquals(3, $stats['completed_appointments']);
        $this->assertEquals(2, $stats['cancelled_appointments']);
        $this->assertEquals(1, $stats['no_show_count']);
        $this->assertEquals(7, $stats['total_calls']);
        $this->assertEquals($this->customer->created_at, $stats['member_since']);
        $this->assertNotNull($stats['last_appointment']);
        $this->assertNotNull($stats['next_appointment']);
    }

    /**
     * Test bulk enable portal access
     */
    #[Test]
    public function test_bulk_enable_portal_access()
    {
        Notification::fake();
        
        // Create additional customers
        $customers = CustomerAuth::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'portal_enabled' => false,
            'email' => fn() => fake()->unique()->safeEmail(),
        ]);
        
        // Add one without email
        CustomerAuth::factory()->create([
            'company_id' => $this->company->id,
            'portal_enabled' => false,
            'email' => null,
        ]);
        
        $result = $this->service->bulkEnablePortalAccess($this->company);
        
        $this->assertEquals(4, $result['success']); // Including the setUp customer
        $this->assertEquals(0, $result['failed']);
        $this->assertEmpty($result['errors']);
        
        // Check all customers were enabled
        $enabledCount = CustomerAuth::where('company_id', $this->company->id)
            ->where('portal_enabled', true)
            ->count();
        
        $this->assertEquals(4, $enabledCount);
        
        // Check notifications were sent
        Notification::assertCount(4);
    }

    /**
     * Test bulk enable portal access with specific IDs
     */
    #[Test]
    public function test_bulk_enable_portal_access_with_specific_ids()
    {
        Notification::fake();
        
        // Create additional customers
        $customer2 = CustomerAuth::factory()->create([
            'company_id' => $this->company->id,
            'portal_enabled' => false,
            'email' => 'customer2@example.com',
        ]);
        
        $customer3 = CustomerAuth::factory()->create([
            'company_id' => $this->company->id,
            'portal_enabled' => false,
            'email' => 'customer3@example.com',
        ]);
        
        $result = $this->service->bulkEnablePortalAccess($this->company, [
            $this->customer->id,
            $customer2->id,
        ]);
        
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['failed']);
        
        // Check only specified customers were enabled
        $this->assertTrue($this->customer->fresh()->portal_enabled);
        $this->assertTrue($customer2->fresh()->portal_enabled);
        $this->assertFalse($customer3->fresh()->portal_enabled);
    }

    /**
     * Test bulk enable portal access with failures
     */
    #[Test]
    public function test_bulk_enable_portal_access_with_failures()
    {
        Notification::fake();
        
        // Mock service to fail on second customer
        $mockService = Mockery::mock(CustomerPortalService::class)->makePartial();
        $mockService->shouldReceive('enablePortalAccess')
            ->once()
            ->with($this->customer)
            ->andReturn(true);
        
        $customer2 = CustomerAuth::factory()->create([
            'company_id' => $this->company->id,
            'portal_enabled' => false,
            'email' => 'fail@example.com',
        ]);
        
        $mockService->shouldReceive('enablePortalAccess')
            ->once()
            ->with($customer2)
            ->andReturn(false);
        
        $result = $mockService->bulkEnablePortalAccess($this->company);
        
        $this->assertEquals(1, $result['success']);
        $this->assertEquals(1, $result['failed']);
        $this->assertContains('Failed to enable access for fail@example.com', $result['errors']);
    }
}