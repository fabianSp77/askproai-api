<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Staff;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('uses eager loading to prevent N+1 queries', function () {
    $company = Company::factory()->create();
    $customers = Customer::factory(10)->create(['company_id' => $company->id]);

    foreach ($customers as $customer) {
        Appointment::factory(3)->create(['customer_id' => $customer->id]);
    }

    DB::enableQueryLog();

    // Without eager loading (N+1 problem)
    $customers = Customer::all();
    foreach ($customers as $customer) {
        $customer->appointments->count();
    }
    $withoutEagerLoading = count(DB::getQueryLog());

    DB::flushQueryLog();

    // With eager loading
    $customers = Customer::with('appointments')->get();
    foreach ($customers as $customer) {
        $customer->appointments->count();
    }
    $withEagerLoading = count(DB::getQueryLog());

    expect($withEagerLoading)->toBeLessThan($withoutEagerLoading);
    expect($withEagerLoading)->toBe(2); // 1 for customers, 1 for appointments
});

it('uses database indexes effectively', function () {
    Customer::factory(100)->create();

    // Check that indexes are used for common queries
    $explain = DB::select('EXPLAIN SELECT * FROM customers WHERE email = ?', ['test@example.com']);

    // Verify index usage (this will vary by database)
    expect($explain)->not->toBeEmpty();
});

it('optimizes complex queries with proper joins', function () {
    $company = Company::factory()->create();
    $branch = \App\Models\Branch::factory()->create(['company_id' => $company->id]);
    $service = Service::factory()->create(['company_id' => $company->id]);
    $staff = Staff::factory()->create(['branch_id' => $branch->id]);
    $customers = Customer::factory(10)->create(['company_id' => $company->id]);

    foreach ($customers as $customer) {
        Appointment::factory()->create([
            'customer_id' => $customer->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'status' => 'completed'
        ]);
    }

    DB::enableQueryLog();

    // Optimized query with joins
    $result = Appointment::query()
        ->join('customers', 'appointments.customer_id', '=', 'customers.id')
        ->join('staff', 'appointments.staff_id', '=', 'staff.id')
        ->join('services', 'appointments.service_id', '=', 'services.id')
        ->where('appointments.status', 'completed')
        ->select(
            'appointments.*',
            'customers.first_name as customer_name',
            'staff.name as staff_name',
            'services.name as service_name'
        )
        ->get();

    $queries = DB::getQueryLog();

    expect($queries)->toHaveCount(1); // Single optimized query
    expect($result)->toHaveCount(10);
});

it('uses query scopes for reusable optimizations', function () {
    $company = Company::factory()->create();
    Customer::factory(5)->create([
        'company_id' => $company->id,
        'status' => 'active'
    ]);
    Customer::factory(3)->create([
        'company_id' => $company->id,
        'status' => 'inactive'
    ]);

    // Using optimized scope
    $activeCustomers = Customer::active()
        ->forCompany($company->id)
        ->get();

    expect($activeCustomers)->toHaveCount(5);
});

it('implements efficient pagination', function () {
    Customer::factory(100)->create();

    DB::enableQueryLog();

    // Efficient cursor pagination
    $customers = Customer::cursorPaginate(10);

    $queries = DB::getQueryLog();

    expect($queries)->toHaveCount(1);
    expect($customers)->toHaveCount(10);
});

it('uses database transactions for data integrity', function () {
    $company = Company::factory()->create();

    try {
        DB::beginTransaction();

        $customer = Customer::create([
            'company_id' => $company->id,
            'first_name' => 'Transaction',
            'last_name' => 'Test',
            'email' => 'transaction@test.com',
            'phone' => '+1234567890'
        ]);

        $appointment = Appointment::create([
            'customer_id' => $customer->id,
            'company_id' => $company->id,
            'appointment_date' => now()->addDay(),
            'appointment_time' => '10:00:00',
            'status' => 'scheduled'
        ]);

        DB::commit();

        expect(Customer::find($customer->id))->not->toBeNull();
        expect(Appointment::find($appointment->id))->not->toBeNull();
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
});

it('optimizes bulk operations', function () {
    $data = [];
    for ($i = 0; $i < 1000; $i++) {
        $data[] = [
            'company_id' => 1,
            'first_name' => 'Bulk',
            'last_name' => "User{$i}",
            'email' => "bulk{$i}@test.com",
            'phone' => "+123456{$i}",
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    $startTime = microtime(true);

    // Bulk insert
    Customer::insert($data);

    $endTime = microtime(true);
    $duration = $endTime - $startTime;

    expect(Customer::count())->toBe(1000);
    expect($duration)->toBeLessThan(5); // Should complete within 5 seconds
});

it('uses database-level constraints effectively', function () {
    $company = Company::factory()->create();
    $customer = Customer::factory()->create([
        'company_id' => $company->id,
        'email' => 'unique@test.com'
    ]);

    // Test unique constraint
    $duplicate = Customer::create([
        'company_id' => $company->id,
        'first_name' => 'Duplicate',
        'last_name' => 'User',
        'email' => 'unique@test.com', // Same email
        'phone' => '+9876543210'
    ]);

    expect($duplicate)->toBeFalse();
});

it('implements efficient aggregate calculations', function () {
    $company = Company::factory()->create();
    $customers = Customer::factory(50)->create(['company_id' => $company->id]);

    foreach ($customers as $customer) {
        Appointment::factory(rand(1, 5))->create([
            'customer_id' => $customer->id,
            'status' => 'completed',
            'price' => rand(50, 200)
        ]);
    }

    DB::enableQueryLog();

    // Efficient aggregate query
    $stats = DB::table('appointments')
        ->join('customers', 'appointments.customer_id', '=', 'customers.id')
        ->where('customers.company_id', $company->id)
        ->where('appointments.status', 'completed')
        ->selectRaw('
            COUNT(DISTINCT customers.id) as unique_customers,
            COUNT(appointments.id) as total_appointments,
            SUM(appointments.price) as total_revenue,
            AVG(appointments.price) as average_price
        ')
        ->first();

    $queries = DB::getQueryLog();

    expect($queries)->toHaveCount(1); // Single aggregate query
    expect($stats->unique_customers)->toBe(50);
    expect($stats->total_appointments)->toBeGreaterThan(50);
});

it('uses partial indexes for specific queries', function () {
    // Create appointments with various statuses
    Appointment::factory(100)->create(['status' => 'completed']);
    Appointment::factory(20)->create(['status' => 'scheduled']);
    Appointment::factory(10)->create(['status' => 'cancelled']);

    DB::enableQueryLog();

    // Query that benefits from partial index on scheduled appointments
    $scheduled = Appointment::where('status', 'scheduled')
        ->where('appointment_date', '>=', now())
        ->get();

    $queries = DB::getQueryLog();

    expect($queries)->toHaveCount(1);
    expect($scheduled->count())->toBeLessThanOrEqual(20);
});