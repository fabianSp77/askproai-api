<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\CustomerAuth;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\Invoice;
use App\Models\BillingPeriod;
use App\Notifications\CustomerResetPasswordNotification;
use App\Notifications\CustomerVerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private Branch $branch;
    private CustomerAuth $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->company = Company::factory()->create();
        $this->branch = Branch::factory()->create(['company_id' => $this->company->id]);
        
        $this->customer = CustomerAuth::factory()->create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+491234567890',
            'portal_enabled' => true,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Test mass assignable attributes
     */
    public function test_mass_assignable_attributes()
    {
        $data = [
            'company_id' => 2,
            'branch_id' => 3,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '+499876543210',
            'password' => bcrypt('password'),
            'portal_enabled' => false,
            'portal_access_token' => 'token123',
            'portal_token_expires_at' => now()->addDay(),
            'last_portal_login_at' => now(),
            'preferred_language' => 'de',
            'email_verified_at' => now(),
        ];
        
        $customer = CustomerAuth::create($data);
        
        $this->assertEquals('Jane', $customer->first_name);
        $this->assertEquals('Smith', $customer->last_name);
        $this->assertEquals('jane@example.com', $customer->email);
        $this->assertFalse($customer->portal_enabled);
    }

    /**
     * Test hidden attributes
     */
    public function test_hidden_attributes()
    {
        $array = $this->customer->toArray();
        
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayNotHasKey('portal_access_token', $array);
    }

    /**
     * Test date casting
     */
    public function test_date_casting()
    {
        $this->assertInstanceOf(Carbon::class, $this->customer->email_verified_at);
        
        $this->customer->update([
            'portal_token_expires_at' => now()->addDay(),
            'last_portal_login_at' => now()->subHour(),
        ]);
        
        $this->assertInstanceOf(Carbon::class, $this->customer->portal_token_expires_at);
        $this->assertInstanceOf(Carbon::class, $this->customer->last_portal_login_at);
    }

    /**
     * Test company relationship
     */
    public function test_company_relationship()
    {
        $company = $this->customer->company;
        
        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals($this->company->id, $company->id);
    }

    /**
     * Test branch relationship
     */
    public function test_branch_relationship()
    {
        $branch = $this->customer->branch;
        
        $this->assertInstanceOf(Branch::class, $branch);
        $this->assertEquals($this->branch->id, $branch->id);
    }

    /**
     * Test appointments relationship
     */
    public function test_appointments_relationship()
    {
        // Create appointments
        Appointment::factory()->count(3)->create([
            'customer_id' => $this->customer->id,
        ]);
        
        $appointments = $this->customer->appointments;
        
        $this->assertCount(3, $appointments);
        $this->assertInstanceOf(Appointment::class, $appointments->first());
    }

    /**
     * Test calls relationship
     */
    public function test_calls_relationship()
    {
        // Create calls
        Call::factory()->count(2)->create([
            'customer_id' => $this->customer->id,
        ]);
        
        $calls = $this->customer->calls;
        
        $this->assertCount(2, $calls);
        $this->assertInstanceOf(Call::class, $calls->first());
    }

    /**
     * Test invoices relationship
     */
    public function test_invoices_relationship()
    {
        // Create invoice with billing period
        $billingPeriod = BillingPeriod::factory()->create([
            'company_id' => $this->company->id,
        ]);
        
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'metadata' => ['customer_id' => $this->customer->id],
        ]);
        
        $billingPeriod->update(['invoice_id' => $invoice->id]);
        
        // Create appointment linked to billing period
        $appointment = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'billing_period_id' => $billingPeriod->id,
        ]);
        
        $invoices = $this->customer->invoices()->get();
        
        $this->assertCount(1, $invoices);
        $this->assertEquals($invoice->id, $invoices->first()->id);
    }

    /**
     * Test full name attribute
     */
    public function test_full_name_attribute()
    {
        $this->assertEquals('John Doe', $this->customer->full_name);
        
        // Test with empty last name
        $this->customer->update(['last_name' => '']);
        $this->assertEquals('John', $this->customer->full_name);
        
        // Test with empty first name
        $this->customer->update(['first_name' => '', 'last_name' => 'Doe']);
        $this->assertEquals('Doe', $this->customer->full_name);
    }

    /**
     * Test has portal access
     */
    public function test_has_portal_access()
    {
        $this->assertTrue($this->customer->hasPortalAccess());
        
        // Test without email verification
        $this->customer->update(['email_verified_at' => null]);
        $this->assertFalse($this->customer->hasPortalAccess());
        
        // Test with portal disabled
        $this->customer->update([
            'email_verified_at' => now(),
            'portal_enabled' => false,
        ]);
        $this->assertFalse($this->customer->hasPortalAccess());
    }

    /**
     * Test generate portal access token
     */
    public function test_generate_portal_access_token()
    {
        $token = $this->customer->generatePortalAccessToken();
        
        $this->assertIsString($token);
        $this->assertEquals(60, strlen($token));
        
        // Check database was updated
        $this->customer->refresh();
        $this->assertNotNull($this->customer->portal_access_token);
        $this->assertEquals(hash('sha256', $token), $this->customer->portal_access_token);
        $this->assertNotNull($this->customer->portal_token_expires_at);
        $this->assertTrue($this->customer->portal_token_expires_at->isFuture());
        $this->assertTrue($this->customer->portal_token_expires_at->diffInHours(now()) >= 23);
    }

    /**
     * Test verify portal access token - valid
     */
    public function test_verify_portal_access_token_valid()
    {
        $token = $this->customer->generatePortalAccessToken();
        
        $result = $this->customer->verifyPortalAccessToken($token);
        
        $this->assertTrue($result);
    }

    /**
     * Test verify portal access token - invalid token
     */
    public function test_verify_portal_access_token_invalid()
    {
        $this->customer->generatePortalAccessToken();
        
        $result = $this->customer->verifyPortalAccessToken('invalid_token');
        
        $this->assertFalse($result);
    }

    /**
     * Test verify portal access token - expired
     */
    public function test_verify_portal_access_token_expired()
    {
        $token = $this->customer->generatePortalAccessToken();
        
        // Manually expire the token
        $this->customer->update([
            'portal_token_expires_at' => now()->subMinute(),
        ]);
        
        $result = $this->customer->verifyPortalAccessToken($token);
        
        $this->assertFalse($result);
    }

    /**
     * Test verify portal access token - no token set
     */
    public function test_verify_portal_access_token_no_token()
    {
        $result = $this->customer->verifyPortalAccessToken('any_token');
        
        $this->assertFalse($result);
    }

    /**
     * Test send password reset notification
     */
    public function test_send_password_reset_notification()
    {
        Notification::fake();
        
        $token = 'reset_token_123';
        $this->customer->sendPasswordResetNotification($token);
        
        Notification::assertSentTo(
            $this->customer,
            CustomerResetPasswordNotification::class,
            function ($notification, $channels) use ($token) {
                return true; // Would check token in notification if accessible
            }
        );
    }

    /**
     * Test send email verification notification
     */
    public function test_send_email_verification_notification()
    {
        Notification::fake();
        
        $this->customer->sendEmailVerificationNotification();
        
        Notification::assertSentTo(
            $this->customer,
            CustomerVerifyEmailNotification::class
        );
    }

    /**
     * Test upcoming appointments attribute
     */
    public function test_upcoming_appointments_attribute()
    {
        // Create future appointments
        $future1 = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->addDay(),
        ]);
        
        $future2 = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->addDays(2),
        ]);
        
        // Create past appointment (should not be included)
        Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->subDay(),
        ]);
        
        // Create 6 more future appointments (to test limit)
        Appointment::factory()->count(6)->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->addDays(3),
        ]);
        
        $upcoming = $this->customer->upcoming_appointments;
        
        $this->assertCount(5, $upcoming); // Limited to 5
        $this->assertEquals($future1->id, $upcoming->first()->id); // Ordered by start_time
    }

    /**
     * Test past appointments attribute
     */
    public function test_past_appointments_attribute()
    {
        // Create past appointments
        $past1 = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->subDay(),
        ]);
        
        $past2 = Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->subDays(2),
        ]);
        
        // Create future appointment (should not be included)
        Appointment::factory()->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->addDay(),
        ]);
        
        // Create 10 more past appointments (to test limit)
        Appointment::factory()->count(10)->create([
            'customer_id' => $this->customer->id,
            'start_time' => now()->subDays(3),
        ]);
        
        $past = $this->customer->past_appointments;
        
        $this->assertCount(10, $past); // Limited to 10
        $this->assertEquals($past1->id, $past->first()->id); // Most recent first
    }

    /**
     * Test record portal login
     */
    public function test_record_portal_login()
    {
        $this->assertNull($this->customer->last_portal_login_at);
        
        $this->customer->recordPortalLogin();
        
        $this->customer->refresh();
        $this->assertNotNull($this->customer->last_portal_login_at);
        $this->assertTrue($this->customer->last_portal_login_at->isToday());
    }

    /**
     * Test Sanctum API tokens
     */
    public function test_sanctum_api_tokens()
    {
        // Create token
        $token = $this->customer->createToken('test-device');
        
        $this->assertInstanceOf(PersonalAccessToken::class, $token->accessToken);
        $this->assertIsString($token->plainTextToken);
        
        // Check token exists
        $this->assertEquals(1, $this->customer->tokens()->count());
        
        // Delete tokens
        $this->customer->tokens()->delete();
        $this->assertEquals(0, $this->customer->tokens()->count());
    }

    /**
     * Test uses customers table
     */
    public function test_uses_customers_table()
    {
        $reflection = new \ReflectionClass($this->customer);
        $property = $reflection->getProperty('table');
        $property->setAccessible(true);
        
        $this->assertEquals('customers', $property->getValue($this->customer));
    }
}