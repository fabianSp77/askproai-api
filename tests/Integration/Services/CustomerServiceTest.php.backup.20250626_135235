<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\CustomerService;
use App\Services\AppointmentService;
use App\Models\Customer;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\User;
use App\Models\Staff;
use App\Models\Branch;
use App\Events\CustomerCreated;
use App\Events\CustomerMerged;
use App\Repositories\CustomerRepository;
use App\Repositories\AppointmentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Mockery;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerService $customerService;
    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->company = Company::factory()->create();
        
        // Create and authenticate user
        $this->user = User::factory()->for($this->company)->create();
        $this->actingAs($this->user);

        // Create service instance
        $this->customerService = new CustomerService(
            new CustomerRepository(),
            new AppointmentRepository()
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test customer creation workflow with event
     */
    public function test_creates_customer_and_fires_event()
    {
        Event::fake();

        $customerData = [
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'email' => 'john.doe@example.com',
            'address' => '123 Main St, Berlin',
            'birthdate' => '1985-05-15',
            'company_id' => $this->company->id,
        ];

        $customer = $this->customerService->create($customerData);

        // Assert customer was created
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'John Doe',
            'phone' => '+491234567890',
            'email' => 'john.doe@example.com',
            'address' => '123 Main St, Berlin',
            'birthdate' => '1985-05-15',
            'company_id' => $this->company->id,
        ]);

        // Assert event was fired
        Event::assertDispatched(CustomerCreated::class, function ($event) use ($customer) {
            return $event->customer->id === $customer->id;
        });
    }

    /**
     * Test customer creation fails with duplicate phone
     */
    public function test_fails_to_create_customer_with_duplicate_phone()
    {
        Event::fake();

        // Create existing customer
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+499999999999',
        ]);

        $customerData = [
            'name' => 'Duplicate Phone',
            'phone' => '+499999999999',
            'company_id' => $this->company->id,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Customer with this phone number already exists');

        $this->customerService->create($customerData);

        // Assert no new customer was created
        $this->assertEquals(1, Customer::where('phone', '+499999999999')->count());

        // Assert no event was fired
        Event::assertNotDispatched(CustomerCreated::class);
    }

    /**
     * Test customer creation fails with duplicate email
     */
    public function test_fails_to_create_customer_with_duplicate_email()
    {
        Event::fake();

        // Create existing customer
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@example.com',
        ]);

        $customerData = [
            'name' => 'Duplicate Email',
            'phone' => '+498888888888',
            'email' => 'existing@example.com',
            'company_id' => $this->company->id,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Customer with this email already exists');

        $this->customerService->create($customerData);
    }

    /**
     * Test customer update workflow
     */
    public function test_updates_customer_data()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
            'phone' => '+491111111111',
            'email' => 'original@example.com',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'address' => 'New Address',
        ];

        $updatedCustomer = $this->customerService->update($customer->id, $updateData);

        // Assert customer was updated
        $this->assertEquals('Updated Name', $updatedCustomer->name);
        $this->assertEquals('updated@example.com', $updatedCustomer->email);
        $this->assertEquals('New Address', $updatedCustomer->address);
        $this->assertEquals('+491111111111', $updatedCustomer->phone); // Phone unchanged
    }

    /**
     * Test customer update fails when changing to existing phone
     */
    public function test_fails_to_update_customer_with_existing_phone()
    {
        // Create two customers
        $customer1 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+491111111111',
        ]);

        $customer2 = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+492222222222',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Phone number already in use by another customer');

        $this->customerService->update($customer2->id, [
            'phone' => '+491111111111', // Try to use customer1's phone
        ]);
    }

    /**
     * Test merging duplicate customers
     */
    public function test_merges_duplicate_customers_with_all_data()
    {
        Event::fake();

        // Create primary customer
        $primaryCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Primary Customer',
            'phone' => '+491111111111',
            'email' => null,
            'address' => 'Primary Address',
            'notes' => 'Primary notes',
            'tags' => ['vip'],
            'no_show_count' => 2,
        ]);

        // Create duplicate customer
        $duplicateCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Duplicate Customer',
            'phone' => '+492222222222',
            'email' => 'duplicate@example.com',
            'address' => null,
            'notes' => 'Duplicate notes',
            'tags' => ['regular', 'new'],
            'no_show_count' => 3,
        ]);

        // Create appointments for both customers
        $primaryAppointments = Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $primaryCustomer->id,
        ]);

        $duplicateAppointments = Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $duplicateCustomer->id,
        ]);

        // Create calls for duplicate customer
        Call::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $duplicateCustomer->id,
        ]);

        $mergedCustomer = $this->customerService->mergeDuplicates($primaryCustomer->id, $duplicateCustomer->id);

        // Assert primary customer was updated with merged data
        $this->assertEquals($primaryCustomer->id, $mergedCustomer->id);
        $this->assertEquals('+491111111111', $mergedCustomer->phone); // Primary phone kept
        $this->assertEquals('duplicate@example.com', $mergedCustomer->email); // Duplicate email used
        $this->assertEquals('Primary Address', $mergedCustomer->address); // Primary address kept
        $this->assertStringContainsString('Primary notes', $mergedCustomer->notes);
        $this->assertStringContainsString('Duplicate notes', $mergedCustomer->notes);
        $this->assertEquals(['vip', 'regular', 'new'], $mergedCustomer->tags);
        $this->assertEquals(5, $mergedCustomer->no_show_count); // 2 + 3

        // Assert all appointments moved to primary customer
        $this->assertEquals(5, Appointment::where('customer_id', $primaryCustomer->id)->count());
        $this->assertEquals(0, Appointment::where('customer_id', $duplicateCustomer->id)->count());

        // Assert all calls moved to primary customer
        $this->assertEquals(2, Call::where('customer_id', $primaryCustomer->id)->count());
        $this->assertEquals(0, Call::where('customer_id', $duplicateCustomer->id)->count());

        // Assert duplicate customer was deleted
        $this->assertNull(Customer::find($duplicateCustomer->id));

        // Assert event was fired
        Event::assertDispatched(CustomerMerged::class, function ($event) use ($primaryCustomer, $duplicateCustomer) {
            return $event->primaryCustomer->id === $primaryCustomer->id &&
                   $event->mergedCustomer->id === $duplicateCustomer->id;
        });
    }

    /**
     * Test getting customer history with all related data
     */
    public function test_gets_complete_customer_history()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create appointments with different statuses
        $appointments = [
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'status' => 'completed',
                'price' => 100,
                'starts_at' => Carbon::now()->subDays(10),
            ]),
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'status' => 'completed',
                'price' => 150,
                'starts_at' => Carbon::now()->subDays(5),
            ]),
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'status' => 'no_show',
                'price' => 75,
                'starts_at' => Carbon::now()->subDays(2),
            ]),
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'status' => 'scheduled',
                'price' => 120,
                'starts_at' => Carbon::now()->addDays(3),
            ]),
        ];

        // Create calls
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $history = $this->customerService->getHistory($customer->id);

        // Assert history structure
        $this->assertArrayHasKey('customer', $history);
        $this->assertArrayHasKey('appointments', $history);
        $this->assertArrayHasKey('calls', $history);
        $this->assertArrayHasKey('activities', $history);
        $this->assertArrayHasKey('statistics', $history);

        // Assert customer data
        $this->assertEquals($customer->id, $history['customer']->id);

        // Assert appointments are sorted by date descending
        $this->assertCount(4, $history['appointments']);
        $this->assertTrue($history['appointments'][0]->starts_at->isAfter($history['appointments'][1]->starts_at));

        // Assert statistics
        $stats = $history['statistics'];
        $this->assertEquals(4, $stats['total_appointments']);
        $this->assertEquals(2, $stats['completed_appointments']);
        $this->assertEquals(1, $stats['no_shows']);
        $this->assertEquals(250, $stats['total_spent']); // 100 + 150
        $this->assertEquals(125, $stats['average_appointment_value']); // 250 / 2
        $this->assertEquals($appointments[2]->starts_at->toDateTimeString(), $stats['last_appointment']->toDateTimeString());
        $this->assertEquals($appointments[3]->starts_at->toDateTimeString(), $stats['next_appointment']->toDateTimeString());

        // Assert calls
        $this->assertCount(3, $history['calls']);
    }

    /**
     * Test finding potential duplicate customers
     */
    public function test_finds_potential_duplicate_customers()
    {
        // Create main customer
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'John Michael Doe',
            'phone' => '+491234567890',
            'email' => 'john.doe@company.com',
        ]);

        // Create potential duplicates
        $duplicates = [
            // Similar phone number
            Customer::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'J. Doe',
                'phone' => '01234567890', // Same number without country code
            ]),
            // Similar name
            Customer::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Michael Doe',
                'phone' => '+499999999999',
            ]),
            // Same email domain
            Customer::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Jane Smith',
                'email' => 'jane.smith@company.com',
            ]),
        ];

        // Create non-duplicate (different company)
        Customer::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'name' => 'John Doe',
            'phone' => '+491234567890',
        ]);

        $potentialDuplicates = $this->customerService->findPotentialDuplicates($customer->id);

        // Assert duplicates were found
        $this->assertGreaterThanOrEqual(3, $potentialDuplicates->count());
        
        // Assert duplicates are from same company
        foreach ($potentialDuplicates as $duplicate) {
            $this->assertEquals($this->company->id, $duplicate->company_id);
            $this->assertNotEquals($customer->id, $duplicate->id);
        }
    }

    /**
     * Test blocking customer cancels future appointments
     */
    public function test_blocks_customer_and_cancels_future_appointments()
    {
        Event::fake();

        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'is_blocked' => false,
        ]);

        // Create appointments
        $pastAppointment = Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
            'starts_at' => Carbon::now()->subDay(),
        ]);

        $futureAppointments = [
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'status' => 'scheduled',
                'starts_at' => Carbon::now()->addDay(),
            ]),
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'status' => 'scheduled',
                'starts_at' => Carbon::now()->addWeek(),
            ]),
        ];

        // Mock AppointmentService
        $appointmentServiceMock = Mockery::mock(AppointmentService::class);
        $appointmentServiceMock->shouldReceive('cancel')
            ->times(2)
            ->with(Mockery::type('int'), Mockery::on(function ($reason) {
                return str_contains($reason, 'Customer blocked: Too many no-shows');
            }))
            ->andReturn(true);

        $this->app->instance(AppointmentService::class, $appointmentServiceMock);

        $this->customerService->block($customer->id, 'Too many no-shows');

        // Assert customer was blocked
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_blocked' => true,
            'block_reason' => 'Too many no-shows',
        ]);

        // Assert blocked_at timestamp is set
        $blockedCustomer = Customer::find($customer->id);
        $this->assertNotNull($blockedCustomer->blocked_at);

        // Past appointment should remain unchanged
        $this->assertDatabaseHas('appointments', [
            'id' => $pastAppointment->id,
            'status' => 'completed',
        ]);
    }

    /**
     * Test unblocking customer
     */
    public function test_unblocks_customer()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'is_blocked' => true,
            'blocked_at' => Carbon::now()->subDay(),
            'block_reason' => 'Previous violation',
        ]);

        $this->customerService->unblock($customer->id);

        // Assert customer was unblocked
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_blocked' => false,
            'blocked_at' => null,
            'block_reason' => null,
        ]);
    }

    /**
     * Test tag management
     */
    public function test_manages_customer_tags()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'tags' => ['existing'],
        ]);

        // Add tags
        $this->customerService->addTag($customer->id, 'vip');
        $this->customerService->addTag($customer->id, 'premium');

        $customer->refresh();
        $this->assertContains('existing', $customer->tags);
        $this->assertContains('vip', $customer->tags);
        $this->assertContains('premium', $customer->tags);

        // Remove tag
        $this->customerService->removeTag($customer->id, 'existing');

        $customer->refresh();
        $this->assertNotContains('existing', $customer->tags);
        $this->assertContains('vip', $customer->tags);
        $this->assertContains('premium', $customer->tags);
    }

    /**
     * Test getting customers by tag
     */
    public function test_gets_customers_by_tag()
    {
        // Create customers with different tags
        $vipCustomers = Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'tags' => ['vip', 'premium'],
        ]);

        $regularCustomers = Customer::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'tags' => ['regular'],
        ]);

        // Get customers by tag
        $foundVipCustomers = $this->customerService->getByTag('vip');
        $foundRegularCustomers = $this->customerService->getByTag('regular');

        // Assert correct customers found
        $this->assertCount(3, $foundVipCustomers);
        $this->assertCount(2, $foundRegularCustomers);

        foreach ($foundVipCustomers as $customer) {
            $this->assertContains('vip', $customer->tags);
        }

        foreach ($foundRegularCustomers as $customer) {
            $this->assertContains('regular', $customer->tags);
        }
    }

    /**
     * Test customer data export
     */
    public function test_exports_customer_data()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create related data
        Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        Call::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $exportData = $this->customerService->export($customer->id);

        // Assert export structure
        $this->assertArrayHasKey('customer', $exportData);
        $this->assertArrayHasKey('appointments', $exportData);
        $this->assertArrayHasKey('calls', $exportData);
        $this->assertArrayHasKey('statistics', $exportData);
        $this->assertArrayHasKey('exported_at', $exportData);

        // Assert data is included
        $this->assertEquals($customer->id, $exportData['customer']['id']);
        $this->assertCount(2, $exportData['appointments']);
        $this->assertCount(1, $exportData['calls']);
        $this->assertNotNull($exportData['exported_at']);
    }

    /**
     * Test phone number normalization
     */
    public function test_normalizes_phone_numbers_on_create()
    {
        Event::fake();

        $customerData = [
            'name' => 'Test Customer',
            'phone' => '+49 (30) 123-456-78', // Formatted phone
            'email' => '  TEST@EXAMPLE.COM  ', // Uppercase with spaces
            'company_id' => $this->company->id,
        ];

        $customer = $this->customerService->create($customerData);

        // Assert phone was normalized (only digits and +)
        $this->assertEquals('+493012345678', $customer->phone);
        
        // Assert email was normalized (lowercase, trimmed)
        $this->assertEquals('test@example.com', $customer->email);
    }

    /**
     * Test finding duplicates with phone number variants
     */
    public function test_finds_duplicates_with_phone_variants()
    {
        // Create customer with German phone
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'phone' => '+491234567890',
        ]);

        // Create duplicates with phone variants
        $duplicates = [
            Customer::factory()->create([
                'company_id' => $this->company->id,
                'phone' => '01234567890', // Without country code
            ]),
            Customer::factory()->create([
                'company_id' => $this->company->id,
                'phone' => '491234567890', // Without +
            ]),
        ];

        $potentialDuplicates = $this->customerService->findPotentialDuplicates($customer->id);

        // Assert phone variants were found
        $duplicateIds = $potentialDuplicates->pluck('id')->toArray();
        foreach ($duplicates as $duplicate) {
            $this->assertContains($duplicate->id, $duplicateIds);
        }
    }

    /**
     * Test transaction rollback on merge failure
     */
    public function test_rolls_back_merge_on_failure()
    {
        $primary = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $duplicate = Customer::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create appointments
        Appointment::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $duplicate->id,
        ]);

        // Mock repository to throw exception during delete
        $customerRepoMock = Mockery::mock(CustomerRepository::class)->makePartial();
        $customerRepoMock->shouldReceive('findOrFail')
            ->andReturnUsing(function ($id) use ($primary, $duplicate) {
                return $id === $primary->id ? $primary : $duplicate;
            });
        $customerRepoMock->shouldReceive('update')->andReturn(true);
        $customerRepoMock->shouldReceive('delete')
            ->andThrow(new \Exception('Delete failed'));

        $customerService = new CustomerService(
            $customerRepoMock,
            new AppointmentRepository()
        );

        try {
            $customerService->mergeDuplicates($primary->id, $duplicate->id);
            $this->fail('Exception should have been thrown');
        } catch (\Exception $e) {
            // Expected exception
        }

        // Assert nothing was changed due to transaction rollback
        $this->assertDatabaseHas('customers', ['id' => $duplicate->id]);
        $this->assertDatabaseHas('appointments', ['customer_id' => $duplicate->id]);
        $this->assertEquals($primary->fresh()->no_show_count, $primary->no_show_count);
    }
}