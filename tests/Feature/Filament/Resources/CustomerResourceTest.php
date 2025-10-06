<?php

use App\Filament\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Company;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->company = Company::factory()->create();
});

it('can list customers', function () {
    actingAsAdmin();

    Customer::factory(3)->create(['company_id' => $this->company->id]);

    Livewire::test(CustomerResource\Pages\ListCustomers::class)
        ->assertSuccessful()
        ->assertCountTableRecords(3);
});

it('can create customer with valid data', function () {
    actingAsCompanyOwner($this->company);

    $customerData = [
        'company_id' => $this->company->id,
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'max.mustermann@example.com',
        'phone' => '+49 30 98765432',
        'status' => 'active',
        'date_of_birth' => '1990-01-15',
        'gender' => 'male',
        'address' => 'Musterstraße 42',
        'city' => 'München',
        'state' => 'Bayern',
        'postal_code' => '80331',
        'country' => 'Germany',
        'preferred_language' => 'de',
        'preferred_contact_method' => 'email',
        'marketing_consent' => true,
        'notes' => 'VIP Kunde',
    ];

    Livewire::test(CustomerResource\Pages\CreateCustomer::class)
        ->fillForm($customerData)
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('customers', [
        'first_name' => 'Max',
        'last_name' => 'Mustermann',
        'email' => 'max.mustermann@example.com'
    ]);
});

it('validates email uniqueness per company', function () {
    actingAsCompanyOwner($this->company);

    $existingCustomer = Customer::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'duplicate@example.com'
    ]);

    Livewire::test(CustomerResource\Pages\CreateCustomer::class)
        ->fillForm([
            'company_id' => $this->company->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'duplicate@example.com',
            'phone' => '+49 30 11111111'
        ])
        ->call('create')
        ->assertHasFormErrors(['email']);
});

it('can edit existing customer', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create();

    Livewire::test(CustomerResource\Pages\EditCustomer::class, [
        'record' => $customer->getRouteKey()
    ])
        ->assertFormSet([
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email
        ])
        ->fillForm([
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($customer->refresh())
        ->first_name->toBe('Updated')
        ->last_name->toBe('Name');
});

it('can view customer details with stats', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create();

    // Create related data for stats
    \App\Models\Appointment::factory(5)->create([
        'customer_id' => $customer->id,
        'status' => 'completed',
        'price' => 50.00
    ]);

    Livewire::test(CustomerResource\Pages\ViewCustomer::class, [
        'record' => $customer->getRouteKey()
    ])
        ->assertSuccessful()
        ->assertSee($customer->full_name)
        ->assertSee($customer->email);
});

it('can filter customers by status', function () {
    actingAsAdmin();

    Customer::factory(3)->create(['status' => 'active']);
    Customer::factory(2)->create(['status' => 'inactive']);
    Customer::factory(1)->create(['status' => 'blacklisted']);

    Livewire::test(CustomerResource\Pages\ListCustomers::class)
        ->assertCountTableRecords(6)
        ->filterTable('status', 'active')
        ->assertCountTableRecords(3)
        ->filterTable('status', 'blacklisted')
        ->assertCountTableRecords(1);
});

it('can search customers by name or email', function () {
    actingAsAdmin();

    Customer::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com'
    ]);

    Customer::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'jane.smith@example.com'
    ]);

    Livewire::test(CustomerResource\Pages\ListCustomers::class)
        ->searchTable('John')
        ->assertCountTableRecords(1)
        ->resetTableFilters()
        ->searchTable('smith@example')
        ->assertCountTableRecords(1);
});

it('can export customers to CSV', function () {
    actingAsAdmin();

    Customer::factory(5)->create();

    Livewire::test(CustomerResource\Pages\ListCustomers::class)
        ->callTableBulkAction('export', [])
        ->assertSuccessful();
});

it('can bulk update customer status', function () {
    actingAsAdmin();

    $customers = Customer::factory(3)->create(['status' => 'active']);

    Livewire::test(CustomerResource\Pages\ListCustomers::class)
        ->callTableBulkAction('updateStatus', $customers->pluck('id')->toArray(), [
            'status' => 'inactive'
        ])
        ->assertSuccessful();

    foreach ($customers as $customer) {
        expect($customer->refresh()->status)->toBe('inactive');
    }
});

it('manages customer appointments through relation manager', function () {
    actingAsAdmin();

    $customer = Customer::factory()->create();
    $appointments = \App\Models\Appointment::factory(3)->create([
        'customer_id' => $customer->id
    ]);

    Livewire::test(CustomerResource\RelationManagers\AppointmentsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => CustomerResource\Pages\EditCustomer::class,
    ])
        ->assertSuccessful()
        ->assertCountTableRecords(3);
});

it('calculates customer lifetime value correctly', function () {
    $customer = Customer::factory()->create();

    \App\Models\Appointment::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'completed',
        'price' => 100.00
    ]);

    \App\Models\Appointment::factory()->create([
        'customer_id' => $customer->id,
        'status' => 'completed',
        'price' => 150.00
    ]);

    expect($customer->lifetime_value)->toBe(250.00);
});

it('tracks customer tags and segments', function () {
    actingAsAdmin();

    $customerData = [
        'company_id' => $this->company->id,
        'first_name' => 'Tagged',
        'last_name' => 'Customer',
        'email' => 'tagged@example.com',
        'phone' => '+49 30 55555555',
        'tags' => ['vip', 'regular', 'newsletter'],
        'customer_segment' => 'premium'
    ];

    Livewire::test(CustomerResource\Pages\CreateCustomer::class)
        ->fillForm($customerData)
        ->call('create')
        ->assertHasNoFormErrors();

    $customer = Customer::where('email', 'tagged@example.com')->first();
    expect($customer->tags)->toContain('vip', 'regular', 'newsletter');
    expect($customer->customer_segment)->toBe('premium');
});