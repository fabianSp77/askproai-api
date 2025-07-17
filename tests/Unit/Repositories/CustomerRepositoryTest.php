<?php

namespace Tests\Unit\Repositories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerRepository $repository;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = new CustomerRepository();
        
        // Create test company for tenant scoping
        $this->company = Company::factory()->create();
        
        // Set up tenant context
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    /** @test */
    public function it_returns_correct_model_class_name()
    {
        $this->assertEquals(Customer::class, $this->repository->model());
    }

    /** @test */
    #[Test]
    public function it_can_create_customer()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+49 30 12345678',
            'company_id' => $this->company->id,
            'birthdate' => '1990-01-01',
            'notes' => 'VIP customer'
        ];

        $customer = $this->repository->create($data);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('John Doe', $customer->name);
        $this->assertEquals('john@example.com', $customer->email);
        $this->assertEquals('+493012345678', $customer->phone); // Normalized format
        $this->assertEquals('VIP customer', $customer->notes);
    }

    /** @test */
    public function it_can_update_customer()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name'
        ]);

        $result = $this->repository->update($customer->id, [
            'name' => 'New Name',
            'email' => 'newemail@example.com'
        ]);

        $this->assertTrue($result);
        $customer->refresh();
        $this->assertEquals('New Name', $customer->name);
        $this->assertEquals('newemail@example.com', $customer->email);
    }

    /** @test */
    #[Test]
    public function it_can_delete_customer()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        $result = $this->repository->delete($customer->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    /** @test */
    public function it_can_find_customer_by_phone()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+49 30 12345678'
        ]);

        // Test with exact match
        $found = $this->repository->findByPhone('+49 30 12345678');
        $this->assertInstanceOf(Customer::class, $found);
        $this->assertEquals($customer->id, $found->id);

        // Test with normalized phone (spaces removed)
        $found = $this->repository->findByPhone('+493012345678');
        $this->assertInstanceOf(Customer::class, $found);
        $this->assertEquals($customer->id, $found->id);

        // Test with different formatting
        $found = $this->repository->findByPhone('+49-30-12345678');
        $this->assertInstanceOf(Customer::class, $found);
        $this->assertEquals($customer->id, $found->id);
    }

    /** @test */
    #[Test]
    public function it_returns_null_when_customer_not_found_by_phone()
    {
        $found = $this->repository->findByPhone('+99999999999');
        $this->assertNull($found);
    }

    /** @test */
    public function it_can_find_customer_by_email()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'Test@Example.COM'
        ]);

        // Test case-insensitive search
        $found = $this->repository->findByEmail('test@example.com');
        $this->assertInstanceOf(Customer::class, $found);
        $this->assertEquals($customer->id, $found->id);
    }

    /** @test */
    #[Test]
    public function it_can_find_or_create_customer_by_phone()
    {
        $data = [
            'name' => 'New Customer',
            'phone' => '+49 30 12345678',
            'email' => 'new@example.com',
            'company_id' => $this->company->id
        ];

        // First call creates new customer
        $customer = $this->repository->findOrCreate($data);
        $this->assertEquals('New Customer', $customer->name);
        $this->assertEquals('+493012345678', $customer->phone); // Normalized format

        // Second call finds existing customer and updates name
        $data['name'] = 'Updated Name';
        $found = $this->repository->findOrCreate($data);
        
        $this->assertEquals($customer->id, $found->id);
        $this->assertEquals('Updated Name', $found->name);
    }

    /** @test */
    public function it_can_find_or_create_customer_by_email()
    {
        $data = [
            'name' => 'Email Customer',
            'email' => 'email@example.com',
            'company_id' => $this->company->id
        ];

        // First call creates new customer
        $customer = $this->repository->findOrCreate($data);
        $this->assertEquals('Email Customer', $customer->name);
        $this->assertEquals('email@example.com', $customer->email);

        // Second call finds existing customer by email
        $data['phone'] = '+9876543210';
        $found = $this->repository->findOrCreate($data);
        
        $this->assertEquals($customer->id, $found->id);
        $this->assertEquals('+9876543210', $found->phone);
    }

    /** @test */
    #[Test]
    public function it_can_get_customers_with_appointments()
    {
        // Create customers with appointments
        $customerWithAppointments = Customer::factory()->create(['company_id' => $this->company->id]);
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customerWithAppointments->id
        ]);

        // Create customer without appointments
        Customer::factory()->create(['company_id' => $this->company->id]);

        $customers = $this->repository->getWithAppointments();

        $this->assertCount(1, $customers);
        $this->assertEquals($customerWithAppointments->id, $customers->first()->id);
        $this->assertTrue($customers->first()->relationLoaded('appointments'));
    }

    /** @test */
    public function it_can_get_customers_by_branch()
    {
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);
        $otherBranch = Branch::factory()->create(['company_id' => $this->company->id]);

        $customer1 = Customer::factory()->create(['company_id' => $this->company->id]);
        $customer2 = Customer::factory()->create(['company_id' => $this->company->id]);
        $customer3 = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create appointments for different branches
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer1->id,
            'branch_id' => $branch->id
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer2->id,
            'branch_id' => $branch->id
        ]);

        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer3->id,
            'branch_id' => $otherBranch->id
        ]);

        $customers = $this->repository->getByBranch($branch->id);

        $this->assertCount(2, $customers);
        $this->assertTrue($customers->contains($customer1));
        $this->assertTrue($customers->contains($customer2));
        $this->assertFalse($customers->contains($customer3));
    }

    /** @test */
    #[Test]
    public function it_can_get_customers_with_no_shows()
    {
        $customer1 = Customer::factory()->create(['company_id' => $this->company->id]);
        $customer2 = Customer::factory()->create(['company_id' => $this->company->id]);
        $customer3 = Customer::factory()->create(['company_id' => $this->company->id]);

        // Customer 1: 3 no-shows
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer1->id,
            'status' => 'no_show'
        ]);

        // Customer 2: 1 no-show
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer2->id,
            'status' => 'no_show'
        ]);

        // Customer 3: no no-shows
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer3->id,
            'status' => 'completed'
        ]);

        // Get customers with at least 2 no-shows
        $customers = $this->repository->getWithNoShows(2);

        $this->assertCount(1, $customers);
        $this->assertEquals($customer1->id, $customers->first()->id);
        $this->assertEquals(3, $customers->first()->no_show_count);
    }

    /** @test */
    public function it_can_get_top_customers_by_appointment_count()
    {
        $customer1 = Customer::factory()->create(['company_id' => $this->company->id]);
        $customer2 = Customer::factory()->create(['company_id' => $this->company->id]);
        $customer3 = Customer::factory()->create(['company_id' => $this->company->id]);

        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer1->id
        ]);

        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer2->id
        ]);

        Appointment::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer3->id
        ]);

        $topCustomers = $this->repository->getTopCustomers(2);

        $this->assertCount(2, $topCustomers);
        $this->assertEquals($customer1->id, $topCustomers[0]->id);
        $this->assertEquals($customer2->id, $topCustomers[1]->id);
        $this->assertEquals(5, $topCustomers[0]->appointments_count);
        $this->assertEquals(3, $topCustomers[1]->appointments_count);
    }

    /** @test */
    #[Test]
    public function it_can_search_customers()
    {
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+49 30 12345678'
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jane Smith',
            'email' => 'jane@test.com',
            'phone' => '+0987654321'
        ]);

        // Search by name
        $results = $this->repository->search('John');
        $this->assertCount(1, $results);
        $this->assertEquals($customer1->id, $results->first()->id);

        // Search by email
        $results = $this->repository->search('jane@test');
        $this->assertCount(1, $results);
        $this->assertEquals($customer2->id, $results->first()->id);

        // Search by phone
        $results = $this->repository->search('123456');
        $this->assertCount(1, $results);
        $this->assertEquals($customer1->id, $results->first()->id);

        // Search with normalized phone
        $results = $this->repository->search('0987654321');
        $this->assertCount(1, $results);
        $this->assertEquals($customer2->id, $results->first()->id);
    }

    /** @test */
    public function it_can_get_customer_statistics()
    {
        Customer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'email' => 'test@example.com'
        ]);

        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'email' => null,
            'phone' => '+49 30 12345678'
        ]);

        Customer::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'created_at' => now()->startOfMonth()
        ]);

        // Create customers with appointments (active)
        $activeCustomers = Customer::factory()->count(4)->create(['company_id' => $this->company->id]);
        foreach ($activeCustomers as $customer) {
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id
            ]);
        }

        $stats = $this->repository->getStatistics();

        $this->assertEquals(14, $stats['total']); // 5 + 3 + 2 + 4
        $this->assertEquals(9, $stats['with_email']); // 5 + 4 (active customers)
        $this->assertEquals(7, $stats['with_phone']); // 3 + 4 (assuming active customers have phones)
        $this->assertEquals(4, $stats['active']);
        $this->assertGreaterThanOrEqual(2, $stats['new_this_month']);
    }

    /** @test */
    #[Test]
    public function it_can_manage_customer_tags()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'tags' => ['existing-tag']
        ]);

        // Add new tag
        $result = $this->repository->addTag($customer->id, 'vip');
        $this->assertTrue($result);
        
        $customer->refresh();
        $this->assertContains('vip', $customer->tags);
        $this->assertContains('existing-tag', $customer->tags);

        // Add duplicate tag (should not duplicate)
        $this->repository->addTag($customer->id, 'vip');
        $customer->refresh();
        $this->assertCount(2, $customer->tags);

        // Remove tag
        $result = $this->repository->removeTag($customer->id, 'vip');
        $this->assertTrue($result);
        
        $customer->refresh();
        $this->assertNotContains('vip', $customer->tags);
        $this->assertContains('existing-tag', $customer->tags);
    }

    /** @test */
    public function it_returns_false_when_managing_tags_for_non_existent_customer()
    {
        $result = $this->repository->addTag(999999, 'tag');
        $this->assertFalse($result);

        $result = $this->repository->removeTag(999999, 'tag');
        $this->assertFalse($result);
    }

    /** @test */
    #[Test]
    public function it_can_get_customers_by_tag()
    {
        Customer::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'tags' => ['vip', 'premium']
        ]);

        Customer::factory()->create([
            'company_id' => $this->company->id,
            'tags' => ['regular']
        ]);

        Customer::factory()->create([
            'company_id' => $this->company->id,
            'tags' => ['vip']
        ]);

        $vipCustomers = $this->repository->getByTag('vip');
        $this->assertCount(3, $vipCustomers);

        $premiumCustomers = $this->repository->getByTag('premium');
        $this->assertCount(2, $premiumCustomers);

        $regularCustomers = $this->repository->getByTag('regular');
        $this->assertCount(1, $regularCustomers);
    }

    /** @test */
    public function it_can_get_birthday_customers()
    {
        $today = Carbon::now();

        Customer::factory()->create([
            'company_id' => $this->company->id,
            'birthdate' => $today->copy()->subYears(30)
        ]);

        Customer::factory()->create([
            'company_id' => $this->company->id,
            'birthdate' => $today->copy()->subYears(25)
        ]);

        Customer::factory()->create([
            'company_id' => $this->company->id,
            'birthdate' => $today->copy()->addDay()->subYears(20)
        ]);

        // Get today's birthdays
        $birthdays = $this->repository->getBirthdayCustomers();
        $this->assertCount(2, $birthdays);

        // Get tomorrow's birthdays
        $tomorrowBirthdays = $this->repository->getBirthdayCustomers($today->copy()->addDay());
        $this->assertCount(1, $tomorrowBirthdays);
    }

    /** @test */
    #[Test]
    public function it_can_count_customers()
    {
        Customer::factory()->count(5)->create(['company_id' => $this->company->id]);

        $count = $this->repository->count();
        $this->assertEquals(5, $count);

        // Count with criteria
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'tags' => ['vip']
        ]);

        $vipCount = $this->repository->count(['tags' => 'vip']);
        $this->assertEquals(3, $vipCount);
    }

    /** @test */
    public function it_can_check_if_customer_exists()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'exists@example.com'
        ]);

        $exists = $this->repository->exists(['email' => 'exists@example.com']);
        $this->assertTrue($exists);

        $exists = $this->repository->exists(['email' => 'notexists@example.com']);
        $this->assertFalse($exists);
    }

    /** @test */
    #[Test]
    public function it_can_paginate_customers()
    {
        Customer::factory()->count(25)->create(['company_id' => $this->company->id]);

        $paginated = $this->repository->paginate(10);

        $this->assertEquals(10, $paginated->perPage());
        $this->assertEquals(25, $paginated->total());
        $this->assertCount(10, $paginated->items());
    }

    /** @test */
    public function it_can_load_relationships()
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id
        ]);

        $result = $this->repository->with(['appointments'])->find($customer->id);

        $this->assertTrue($result->relationLoaded('appointments'));
        $this->assertCount(2, $result->appointments);
    }

    /** @test */
    #[Test]
    public function it_handles_phone_normalization_correctly()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+49 (30) 123-456-78'
        ]);

        // All these formats should find the same customer
        $formats = [
            '+49 (30) 123-456-78',
            '+493012345678',
            '+49 30 123 456 78',
            '+49-30-123-456-78'
        ];

        foreach ($formats as $format) {
            $found = $this->repository->findByPhone($format);
            $this->assertNotNull($found);
            $this->assertEquals($customer->id, $found->id);
        }
    }

    /** @test */
    public function it_handles_null_values_gracefully()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => null,
            'phone' => null,
            'birthdate' => null,
            'tags' => null
        ]);

        // Should not throw errors when searching
        $this->assertNotNull($this->repository->find($customer->id));
        
        // Statistics should handle nulls
        $stats = $this->repository->getStatistics();
        $this->assertIsArray($stats);
        
        // Tag operations should handle null tags
        $result = $this->repository->addTag($customer->id, 'new-tag');
        $this->assertTrue($result);
    }
}