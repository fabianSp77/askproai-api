<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Staff;
use App\Services\Cache\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
    $this->startTime = microtime(true);
});

afterEach(function () {
    $duration = microtime(true) - $this->startTime;
    echo "\nExecution time: " . round($duration * 1000, 2) . "ms\n";
});

it('benchmarks customer listing performance', function () {
    // Create test data
    $company = Company::factory()->create();
    Customer::factory(1000)->create(['company_id' => $company->id]);

    $iterations = 10;
    $times = [];

    for ($i = 0; $i < $iterations; $i++) {
        Cache::flush();
        $start = microtime(true);

        Customer::where('company_id', $company->id)
            ->with(['appointments', 'calls'])
            ->paginate(50);

        $times[] = microtime(true) - $start;
    }

    $avgTime = array_sum($times) / count($times);

    expect($avgTime)->toBeLessThan(0.5); // Should complete within 500ms
    echo "Average query time: " . round($avgTime * 1000, 2) . "ms";
});

it('benchmarks appointment scheduling performance', function () {
    $setup = createCompanyWithFullSetup();
    $customers = Customer::factory(100)->create(['company_id' => $setup['company']->id]);

    $times = [];

    foreach ($customers->take(20) as $customer) {
        $start = microtime(true);

        // Simulate appointment scheduling process
        $availableSlots = \App\Services\AppointmentService::getAvailableSlots(
            $setup['staff']->id,
            now()->addDay(),
            $setup['service']->duration
        );

        $appointment = Appointment::create([
            'company_id' => $setup['company']->id,
            'customer_id' => $customer->id,
            'staff_id' => $setup['staff']->id,
            'service_id' => $setup['service']->id,
            'appointment_date' => now()->addDay(),
            'appointment_time' => '10:00:00',
            'duration' => 60,
            'status' => 'scheduled',
            'price' => 100.00
        ]);

        $times[] = microtime(true) - $start;
    }

    $avgTime = array_sum($times) / count($times);

    expect($avgTime)->toBeLessThan(0.1); // Should complete within 100ms per appointment
    echo "Average scheduling time: " . round($avgTime * 1000, 2) . "ms";
});

it('benchmarks cache performance vs database queries', function () {
    $company = Company::factory()->create();
    Customer::factory(100)->create(['company_id' => $company->id]);

    // Benchmark database queries
    $dbStart = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        Customer::where('company_id', $company->id)->count();
    }
    $dbTime = microtime(true) - $dbStart;

    // Benchmark cached queries
    $cacheStart = microtime(true);
    for ($i = 0; $i < 100; $i++) {
        Cache::remember("customer_count_{$company->id}", 60, function () use ($company) {
            return Customer::where('company_id', $company->id)->count();
        });
    }
    $cacheTime = microtime(true) - $cacheStart;

    expect($cacheTime)->toBeLessThan($dbTime);

    $improvement = (($dbTime - $cacheTime) / $dbTime) * 100;
    echo "Cache improvement: " . round($improvement, 2) . "%";
});

it('benchmarks bulk operations performance', function () {
    $company = Company::factory()->create();

    // Prepare bulk data
    $customerData = [];
    for ($i = 0; $i < 10000; $i++) {
        $customerData[] = [
            'company_id' => $company->id,
            'first_name' => 'Test',
            'last_name' => "User{$i}",
            'email' => "test{$i}@example.com",
            'phone' => "+123456{$i}",
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ];
    }

    // Benchmark bulk insert
    $bulkStart = microtime(true);
    foreach (array_chunk($customerData, 1000) as $chunk) {
        Customer::insert($chunk);
    }
    $bulkTime = microtime(true) - $bulkStart;

    expect($bulkTime)->toBeLessThan(10); // Should complete within 10 seconds
    echo "Bulk insert time for 10k records: " . round($bulkTime, 2) . "s";
});

it('benchmarks complex report generation', function () {
    $company = Company::factory()->create();
    $staff = Staff::factory(10)->create(['company_id' => $company->id]);
    $customers = Customer::factory(500)->create(['company_id' => $company->id]);

    // Create appointments
    foreach ($customers->take(200) as $customer) {
        Appointment::factory(rand(1, 3))->create([
            'customer_id' => $customer->id,
            'staff_id' => $staff->random()->id,
            'status' => 'completed',
            'price' => rand(50, 300),
            'appointment_date' => now()->subDays(rand(1, 30))
        ]);
    }

    $reportStart = microtime(true);

    // Generate complex monthly report
    $report = DB::table('appointments')
        ->join('customers', 'appointments.customer_id', '=', 'customers.id')
        ->join('staff', 'appointments.staff_id', '=', 'staff.id')
        ->where('appointments.company_id', $company->id)
        ->whereBetween('appointments.appointment_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ])
        ->groupBy('staff.id', 'staff.name')
        ->selectRaw('
            staff.id,
            staff.name,
            COUNT(appointments.id) as total_appointments,
            COUNT(DISTINCT customers.id) as unique_customers,
            SUM(appointments.price) as revenue,
            AVG(appointments.price) as avg_price,
            MIN(appointments.appointment_date) as first_appointment,
            MAX(appointments.appointment_date) as last_appointment
        ')
        ->get();

    $reportTime = microtime(true) - $reportStart;

    expect($reportTime)->toBeLessThan(1); // Should complete within 1 second
    echo "Report generation time: " . round($reportTime * 1000, 2) . "ms";
});

it('benchmarks API response times', function () {
    $company = Company::factory()->create();
    $customers = Customer::factory(100)->create(['company_id' => $company->id]);

    $endpoints = [
        '/api/customers' => 'GET',
        '/api/appointments/available-slots' => 'GET',
        '/api/staff/schedule' => 'GET'
    ];

    $times = [];

    foreach ($endpoints as $endpoint => $method) {
        $start = microtime(true);

        // Simulate API request processing
        $query = Customer::where('company_id', $company->id)
            ->with(['appointments', 'calls'])
            ->paginate(20);

        $times[$endpoint] = microtime(true) - $start;
    }

    foreach ($times as $endpoint => $time) {
        expect($time)->toBeLessThan(0.2); // Each endpoint should respond within 200ms
        echo "\n{$endpoint}: " . round($time * 1000, 2) . "ms";
    }
});

it('benchmarks concurrent request handling', function () {
    $company = Company::factory()->create();
    Customer::factory(100)->create(['company_id' => $company->id]);

    $concurrentRequests = 50;
    $times = [];

    // Simulate concurrent requests
    for ($i = 0; $i < $concurrentRequests; $i++) {
        $start = microtime(true);

        // Simulate request processing
        Customer::where('company_id', $company->id)
            ->with('appointments')
            ->take(10)
            ->get();

        $times[] = microtime(true) - $start;
    }

    $avgTime = array_sum($times) / count($times);
    $maxTime = max($times);

    expect($avgTime)->toBeLessThan(0.1); // Average should be under 100ms
    expect($maxTime)->toBeLessThan(0.5); // Max should be under 500ms

    echo "Avg response time: " . round($avgTime * 1000, 2) . "ms";
    echo "\nMax response time: " . round($maxTime * 1000, 2) . "ms";
});

it('measures memory usage for large datasets', function () {
    $initialMemory = memory_get_usage();

    $company = Company::factory()->create();

    // Process large dataset
    Customer::factory(1000)->create(['company_id' => $company->id]);

    $customers = Customer::where('company_id', $company->id)
        ->with(['appointments', 'calls'])
        ->cursor(); // Use cursor for memory efficiency

    $count = 0;
    foreach ($customers as $customer) {
        $count++;
    }

    $finalMemory = memory_get_usage();
    $memoryUsed = ($finalMemory - $initialMemory) / 1024 / 1024; // Convert to MB

    expect($memoryUsed)->toBeLessThan(50); // Should use less than 50MB
    echo "Memory used: " . round($memoryUsed, 2) . "MB for {$count} records";
});

it('benchmarks search performance', function () {
    Customer::factory(5000)->create();

    $searchTerms = ['john', 'smith', 'test@example.com', '+49'];
    $times = [];

    foreach ($searchTerms as $term) {
        Cache::flush();
        $start = microtime(true);

        Customer::where(function ($query) use ($term) {
            $query->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%");
        })->limit(50)->get();

        $times[$term] = microtime(true) - $start;
    }

    foreach ($times as $term => $time) {
        expect($time)->toBeLessThan(0.3); // Should complete within 300ms
        echo "\nSearch '{$term}': " . round($time * 1000, 2) . "ms";
    }
});

it('benchmarks dashboard widget loading', function () {
    $company = Company::factory()->create();

    // Create sample data
    Customer::factory(500)->create(['company_id' => $company->id]);
    Appointment::factory(1000)->create([
        'company_id' => $company->id,
        'appointment_date' => now()
    ]);

    $widgets = [
        'TodayAppointmentsWidget',
        'RevenueWidget',
        'CustomerStatsWidget',
        'StaffPerformanceWidget'
    ];

    $times = [];

    foreach ($widgets as $widget) {
        $start = microtime(true);

        // Simulate widget data loading
        $data = match ($widget) {
            'TodayAppointmentsWidget' => Appointment::where('appointment_date', now()->format('Y-m-d'))->count(),
            'RevenueWidget' => Appointment::where('status', 'completed')->sum('price'),
            'CustomerStatsWidget' => Customer::where('company_id', $company->id)->count(),
            'StaffPerformanceWidget' => Staff::withCount('appointments')->get(),
            default => []
        };

        $times[$widget] = microtime(true) - $start;
    }

    $totalTime = array_sum($times);

    expect($totalTime)->toBeLessThan(1); // All widgets should load within 1 second

    echo "Total dashboard load time: " . round($totalTime * 1000, 2) . "ms\n";
    foreach ($times as $widget => $time) {
        echo "{$widget}: " . round($time * 1000, 2) . "ms\n";
    }
});