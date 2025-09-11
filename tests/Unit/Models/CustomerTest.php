<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        // Arrange & Act
        $customer = new Customer();
        $expectedFillable = [
            'tenant_id',
            'name',
            'email',
            'phone',
            'birthdate',
            'address',
            'notes',
            'preferred_language',
            'communication_preference'
        ];

        // Assert
        $this->assertEquals($expectedFillable, $customer->getFillable());
    }

    /** @test */
    public function it_casts_birthdate_correctly()
    {
        // Arrange & Act
        $customer = Customer::factory()->create([
            'birthdate' => '1990-05-15'
        ]);

        // Assert
        $this->assertInstanceOf(Carbon::class, $customer->birthdate);
        $this->assertEquals('1990-05-15', $customer->birthdate->toDateString());
    }

    /** @test */
    public function it_belongs_to_tenant()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

        // Act
        $customerTenant = $customer->tenant;

        // Assert
        $this->assertInstanceOf(Tenant::class, $customerTenant);
        $this->assertEquals($tenant->id, $customerTenant->id);
    }

    /** @test */
    public function it_has_many_calls()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $calls = Call::factory(3)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id
        ]);

        // Act
        $customerCalls = $customer->calls;

        // Assert
        $this->assertCount(3, $customerCalls);
        foreach ($calls as $call) {
            $this->assertTrue($customerCalls->contains($call));
        }
    }

    /** @test */
    public function it_has_many_appointments()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        $appointments = Appointment::factory(4)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id
        ]);

        // Act
        $customerAppointments = $customer->appointments;

        // Assert
        $this->assertCount(4, $customerAppointments);
        foreach ($appointments as $appointment) {
            $this->assertTrue($customerAppointments->contains($appointment));
        }
    }

    /** @test */
    public function it_can_get_full_name()
    {
        // Arrange
        $customer = Customer::factory()->create(['name' => 'John Doe']);

        // Act
        $fullName = $customer->getFullName();

        // Assert
        $this->assertEquals('John Doe', $fullName);
    }

    /** @test */
    public function it_can_get_first_name()
    {
        // Arrange
        $customer = Customer::factory()->create(['name' => 'John Doe Smith']);

        // Act
        $firstName = $customer->getFirstName();

        // Assert
        $this->assertEquals('John', $firstName);
    }

    /** @test */
    public function it_can_get_last_name()
    {
        // Arrange
        $customer = Customer::factory()->create(['name' => 'John Doe Smith']);

        // Act
        $lastName = $customer->getLastName();

        // Assert
        $this->assertEquals('Smith', $lastName);
    }

    /** @test */
    public function it_can_calculate_age()
    {
        // Arrange
        $customer = Customer::factory()->create([
            'birthdate' => now()->subYears(30)->subMonths(6)
        ]);

        // Act
        $age = $customer->getAge();

        // Assert
        $this->assertEquals(30, $age);
    }

    /** @test */
    public function it_returns_null_age_when_no_birthdate()
    {
        // Arrange
        $customer = Customer::factory()->create(['birthdate' => null]);

        // Act
        $age = $customer->getAge();

        // Assert
        $this->assertNull($age);
    }

    /** @test */
    public function it_can_format_phone_number()
    {
        // Arrange
        $customer = Customer::factory()->create(['phone' => '+491234567890']);

        // Act
        $formattedPhone = $customer->getFormattedPhone();

        // Assert
        $this->assertEquals('+49 123 456 7890', $formattedPhone);
    }

    /** @test */
    public function it_can_determine_preferred_contact_method()
    {
        // Arrange
        $emailCustomer = Customer::factory()->create(['communication_preference' => 'email']);
        $phoneCustomer = Customer::factory()->create(['communication_preference' => 'phone']);
        $smsCustomer = Customer::factory()->create(['communication_preference' => 'sms']);

        // Act & Assert
        $this->assertEquals('email', $emailCustomer->getPreferredContactMethod());
        $this->assertEquals('phone', $phoneCustomer->getPreferredContactMethod());
        $this->assertEquals('sms', $smsCustomer->getPreferredContactMethod());
    }

    /** @test */
    public function it_can_get_total_calls_count()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        Call::factory(5)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id
        ]);

        // Act
        $callsCount = $customer->getTotalCallsCount();

        // Assert
        $this->assertEquals(5, $callsCount);
    }

    /** @test */
    public function it_can_get_successful_calls_count()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        Call::factory(3)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'call_successful' => true
        ]);
        
        Call::factory(2)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'call_successful' => false
        ]);

        // Act
        $successfulCallsCount = $customer->getSuccessfulCallsCount();

        // Assert
        $this->assertEquals(3, $successfulCallsCount);
    }

    /** @test */
    public function it_can_calculate_call_success_rate()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        Call::factory(7)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'call_successful' => true
        ]);
        
        Call::factory(3)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'call_successful' => false
        ]);

        // Act
        $successRate = $customer->getCallSuccessRate();

        // Assert
        $this->assertEquals(70, $successRate); // 7 out of 10 = 70%
    }

    /** @test */
    public function it_handles_zero_calls_for_success_rate()
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $successRate = $customer->getCallSuccessRate();

        // Assert
        $this->assertEquals(0, $successRate);
    }

    /** @test */
    public function it_can_get_last_call()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        $oldCall = Call::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(5)
        ]);
        
        $recentCall = Call::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'created_at' => now()->subHours(2)
        ]);

        // Act
        $lastCall = $customer->getLastCall();

        // Assert
        $this->assertEquals($recentCall->id, $lastCall->id);
    }

    /** @test */
    public function it_returns_null_when_no_calls()
    {
        // Arrange
        $customer = Customer::factory()->create();

        // Act
        $lastCall = $customer->getLastCall();

        // Assert
        $this->assertNull($lastCall);
    }

    /** @test */
    public function it_can_get_next_appointment()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        $pastAppointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'start_time' => now()->subDays(2),
            'status' => 'completed'
        ]);
        
        $nextAppointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'start_time' => now()->addDays(3),
            'status' => 'scheduled'
        ]);

        // Act
        $next = $customer->getNextAppointment();

        // Assert
        $this->assertEquals($nextAppointment->id, $next->id);
    }

    /** @test */
    public function it_can_get_appointment_history_count()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        Appointment::factory(6)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id
        ]);

        // Act
        $appointmentCount = $customer->getAppointmentHistoryCount();

        // Assert
        $this->assertEquals(6, $appointmentCount);
    }

    /** @test */
    public function it_can_calculate_no_show_rate()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        Appointment::factory(4)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'status' => 'completed'
        ]);
        
        Appointment::factory(1)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'status' => 'no_show'
        ]);

        // Act
        $noShowRate = $customer->getNoShowRate();

        // Assert
        $this->assertEquals(20, $noShowRate); // 1 out of 5 = 20%
    }

    /** @test */
    public function it_can_determine_if_customer_is_vip()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        $vipCustomer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        Appointment::factory(15)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $vipCustomer->id,
            'status' => 'completed'
        ]);
        
        $regularCustomer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        Appointment::factory(3)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $regularCustomer->id,
            'status' => 'completed'
        ]);

        // Act & Assert
        $this->assertTrue($vipCustomer->isVip());
        $this->assertFalse($regularCustomer->isVip());
    }

    /** @test */
    public function it_can_get_customer_lifetime_value()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        // Appointments with different values
        Appointment::factory(3)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
            'service_price_cents' => 5000 // 50 EUR each
        ]);

        // Act
        $lifetimeValue = $customer->getLifetimeValue();

        // Assert
        $this->assertEquals(150.00, $lifetimeValue); // 3 * 50 EUR = 150 EUR
    }

    /** @test */
    public function it_can_search_customers_by_name()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'John Smith'
        ]);
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Jane Doe'
        ]);

        // Act
        $results = Customer::search('John')->where('tenant_id', $tenant->id)->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('John Smith', $results->first()->name);
    }

    /** @test */
    public function it_can_search_customers_by_email()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'john@example.com',
            'name' => 'John'
        ]);
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'jane@example.com',
            'name' => 'Jane'
        ]);

        // Act
        $results = Customer::search('john@example.com')->where('tenant_id', $tenant->id)->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('john@example.com', $results->first()->email);
    }

    /** @test */
    public function it_can_search_customers_by_phone()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        Customer::factory()->create([
            'tenant_id' => $tenant->id,
            'phone' => '+491234567890',
            'name' => 'Phone Customer'
        ]);

        // Act
        $results = Customer::search('+491234567890')->where('tenant_id', $tenant->id)->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('+491234567890', $results->first()->phone);
    }

    /** @test */
    public function it_can_get_customer_activity_timeline()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        Call::factory(2)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'created_at' => now()->subDays(5)
        ]);
        
        Appointment::factory(1)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'start_time' => now()->subDays(3)
        ]);

        // Act
        $timeline = $customer->getActivityTimeline();

        // Assert
        $this->assertCount(3, $timeline); // 2 calls + 1 appointment
        $this->assertArrayHasKey('type', $timeline[0]);
        $this->assertArrayHasKey('date', $timeline[0]);
        $this->assertArrayHasKey('details', $timeline[0]);
    }

    /** @test */
    public function it_can_determine_customer_risk_level()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        $highRiskCustomer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        // Multiple no-shows = high risk
        Appointment::factory(3)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $highRiskCustomer->id,
            'status' => 'no_show'
        ]);
        
        $lowRiskCustomer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        Appointment::factory(5)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $lowRiskCustomer->id,
            'status' => 'completed'
        ]);

        // Act & Assert
        $this->assertEquals('high', $highRiskCustomer->getRiskLevel());
        $this->assertEquals('low', $lowRiskCustomer->getRiskLevel());
    }

    /** @test */
    public function it_can_get_preferred_appointment_times()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);
        
        // Multiple appointments at 14:00
        Appointment::factory(3)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'start_time' => now()->setTime(14, 0, 0)
        ]);
        
        // One appointment at 10:00
        Appointment::factory(1)->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'start_time' => now()->setTime(10, 0, 0)
        ]);

        // Act
        $preferredTimes = $customer->getPreferredAppointmentTimes();

        // Assert
        $this->assertArrayHasKey('14:00', $preferredTimes);
        $this->assertArrayHasKey('10:00', $preferredTimes);
        $this->assertEquals(3, $preferredTimes['14:00']);
        $this->assertEquals(1, $preferredTimes['10:00']);
    }
}