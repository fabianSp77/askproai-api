<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Call;
use App\Models\Customer;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        // Arrange & Act
        $tenant = new Tenant();

        // Assert
        $this->assertEquals(['name', 'slug'], $tenant->getFillable());
    }

    /** @test */
    public function it_hides_sensitive_attributes()
    {
        // Arrange
        $tenant = Tenant::factory()->create();

        // Assert
        $this->assertContains('api_key_hash', $tenant->getHidden());
    }

    /** @test */
    public function it_uses_uuid_as_primary_key()
    {
        // Arrange & Act
        $tenant = new Tenant();

        // Assert
        $this->assertFalse($tenant->getIncrementing());
        $this->assertEquals('string', $tenant->getKeyType());
    }

    /** @test */
    public function it_automatically_generates_uuid_on_creation()
    {
        // Act
        $tenant = Tenant::factory()->create(['name' => 'Test Tenant']);

        // Assert
        $this->assertNotNull($tenant->id);
        $this->assertTrue(Str::isUuid($tenant->id));
    }

    /** @test */
    public function it_automatically_generates_slug_from_name()
    {
        // Act
        $tenant = Tenant::factory()->create(['name' => 'My Great Company']);

        // Assert
        $this->assertEquals('my-great-company', $tenant->slug);
    }

    /** @test */
    public function it_generates_api_key_hash_on_creation()
    {
        // Act
        $tenant = Tenant::factory()->create();

        // Assert
        $this->assertNotNull($tenant->api_key_hash);
        // Should have temporary plain key for initial response
        $this->assertNotNull($tenant->getRawAttribute('plain_api_key'));
        $this->assertStringStartsWith('ask_', $tenant->getRawAttribute('plain_api_key'));
    }

    /** @test */
    public function it_can_verify_api_key()
    {
        // Arrange
        $plainKey = 'ask_test123456789012345678901234';
        $tenant = Tenant::factory()->create();
        $tenant->api_key_hash = Hash::make($plainKey);
        $tenant->save();

        // Act & Assert
        $this->assertTrue($tenant->verifyApiKey($plainKey));
        $this->assertFalse($tenant->verifyApiKey('wrong_key'));
        $this->assertFalse($tenant->verifyApiKey('ask_wrong12345678901234567890123'));
    }

    /** @test */
    public function it_can_regenerate_api_key()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $originalHash = $tenant->api_key_hash;

        // Act
        $newPlainKey = $tenant->regenerateApiKey();

        // Assert
        $this->assertStringStartsWith('ask_', $newPlainKey);
        $this->assertEquals(36, strlen($newPlainKey)); // ask_ + 32 chars
        
        $tenant->refresh();
        $this->assertNotEquals($originalHash, $tenant->api_key_hash);
        $this->assertTrue($tenant->verifyApiKey($newPlainKey));
    }

    /** @test */
    public function it_can_find_tenant_by_api_key()
    {
        // Arrange
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant One']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant Two']);
        
        $key1 = $tenant1->regenerateApiKey();
        $key2 = $tenant2->regenerateApiKey();

        // Act & Assert
        $foundTenant1 = Tenant::findByApiKey($key1);
        $foundTenant2 = Tenant::findByApiKey($key2);
        $notFound = Tenant::findByApiKey('ask_invalid_key_123456789012345678');

        $this->assertNotNull($foundTenant1);
        $this->assertEquals($tenant1->id, $foundTenant1->id);
        
        $this->assertNotNull($foundTenant2);
        $this->assertEquals($tenant2->id, $foundTenant2->id);
        
        $this->assertNull($notFound);
    }

    /** @test */
    public function it_handles_empty_or_invalid_api_keys_gracefully()
    {
        // Arrange
        Tenant::factory()->create();

        // Act & Assert
        $this->assertNull(Tenant::findByApiKey(''));
        $this->assertNull(Tenant::findByApiKey('invalid'));
        $this->assertNull(Tenant::findByApiKey('ask_short'));
        $this->assertNull(Tenant::findByApiKey(null));
    }

    /** @test */
    public function it_has_users_relationship()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $users = User::factory(3)->create(['tenant_id' => $tenant->id]);

        // Act
        $tenantUsers = $tenant->users;

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tenantUsers);
        $this->assertCount(3, $tenantUsers);
        
        foreach ($users as $user) {
            $this->assertTrue($tenantUsers->contains($user));
        }
    }

    /** @test */
    public function it_has_calls_relationship()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $calls = Call::factory(5)->create(['tenant_id' => $tenant->id]);

        // Act
        $tenantCalls = $tenant->calls;

        // Assert
        $this->assertCount(5, $tenantCalls);
        
        foreach ($calls as $call) {
            $this->assertTrue($tenantCalls->contains($call));
        }
    }

    /** @test */
    public function it_has_customers_relationship()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $customers = Customer::factory(4)->create(['tenant_id' => $tenant->id]);

        // Act
        $tenantCustomers = $tenant->customers;

        // Assert
        $this->assertCount(4, $tenantCustomers);
        
        foreach ($customers as $customer) {
            $this->assertTrue($tenantCustomers->contains($customer));
        }
    }

    /** @test */
    public function it_has_appointments_relationship()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $appointments = Appointment::factory(3)->create(['tenant_id' => $tenant->id]);

        // Act
        $tenantAppointments = $tenant->appointments;

        // Assert
        $this->assertCount(3, $tenantAppointments);
        
        foreach ($appointments as $appointment) {
            $this->assertTrue($tenantAppointments->contains($appointment));
        }
    }

    /** @test */
    public function it_isolates_data_between_tenants()
    {
        // Arrange
        $tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);
        
        User::factory(2)->create(['tenant_id' => $tenant1->id]);
        User::factory(3)->create(['tenant_id' => $tenant2->id]);
        
        Call::factory(4)->create(['tenant_id' => $tenant1->id]);
        Call::factory(2)->create(['tenant_id' => $tenant2->id]);

        // Act & Assert
        $this->assertCount(2, $tenant1->users);
        $this->assertCount(3, $tenant2->users);
        $this->assertCount(4, $tenant1->calls);
        $this->assertCount(2, $tenant2->calls);
    }

    /** @test */
    public function it_can_check_if_tenant_is_active()
    {
        // Arrange
        $activeTenant = Tenant::factory()->create(['is_active' => true]);
        $inactiveTenant = Tenant::factory()->create(['is_active' => false]);

        // Act & Assert
        $this->assertTrue($activeTenant->isActive());
        $this->assertFalse($inactiveTenant->isActive());
    }

    /** @test */
    public function it_can_deactivate_tenant()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['is_active' => true]);

        // Act
        $tenant->deactivate();

        // Assert
        $this->assertFalse($tenant->is_active);
        $this->assertFalse($tenant->isActive());
    }

    /** @test */
    public function it_can_calculate_total_calls()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        
        Call::factory(5)->create(['tenant_id' => $tenant->id]);
        Call::factory(3)->create(['tenant_id' => $otherTenant->id]); // Should not count

        // Act
        $totalCalls = $tenant->getTotalCallsCount();

        // Assert
        $this->assertEquals(5, $totalCalls);
    }

    /** @test */
    public function it_can_calculate_successful_calls_rate()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        
        // 3 successful calls
        Call::factory(3)->create([
            'tenant_id' => $tenant->id,
            'call_successful' => true
        ]);
        
        // 2 failed calls
        Call::factory(2)->create([
            'tenant_id' => $tenant->id,
            'call_successful' => false
        ]);

        // Act
        $successRate = $tenant->getSuccessfulCallsRate();

        // Assert
        $this->assertEquals(60, $successRate); // 3 out of 5 = 60%
    }

    /** @test */
    public function it_handles_zero_calls_for_success_rate()
    {
        // Arrange
        $tenant = Tenant::factory()->create();

        // Act
        $successRate = $tenant->getSuccessfulCallsRate();

        // Assert
        $this->assertEquals(0, $successRate);
    }

    /** @test */
    public function it_can_get_balance_in_euros()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['balance_cents' => 12345]);

        // Act
        $balanceEuros = $tenant->getBalanceInEuros();

        // Assert
        $this->assertEquals(123.45, $balanceEuros);
    }

    /** @test */
    public function it_can_add_balance()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['balance_cents' => 1000]);

        // Act
        $tenant->addBalance(500); // Add 5.00 EUR

        // Assert
        $this->assertEquals(1500, $tenant->balance_cents);
        $this->assertEquals(15.00, $tenant->getBalanceInEuros());
    }

    /** @test */
    public function it_can_deduct_balance()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['balance_cents' => 2000]);

        // Act
        $result = $tenant->deductBalance(500); // Deduct 5.00 EUR

        // Assert
        $this->assertTrue($result);
        $this->assertEquals(1500, $tenant->balance_cents);
    }

    /** @test */
    public function it_prevents_negative_balance()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['balance_cents' => 1000]);

        // Act
        $result = $tenant->deductBalance(1500); // Try to deduct more than available

        // Assert
        $this->assertFalse($result);
        $this->assertEquals(1000, $tenant->balance_cents); // Balance unchanged
    }

    /** @test */
    public function it_can_check_sufficient_balance()
    {
        // Arrange
        $tenant = Tenant::factory()->create(['balance_cents' => 2000]);

        // Act & Assert
        $this->assertTrue($tenant->hasSufficientBalance(1500));
        $this->assertTrue($tenant->hasSufficientBalance(2000));
        $this->assertFalse($tenant->hasSufficientBalance(2500));
    }
}