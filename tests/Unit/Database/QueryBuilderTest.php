<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class QueryBuilderTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private Company $company;
    private Company $otherCompany;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->company = Company::factory()->create();
        $this->otherCompany = Company::factory()->create();
    }

    /** @test */
    public function it_builds_complex_where_clauses()
    {
        // Create test data
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'city' => 'Berlin',
        ]);
        
        Customer::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'city' => 'Munich',
        ]);
        
        Customer::factory()->count(4)->create([
            'company_id' => $this->otherCompany->id,
            'city' => 'Berlin',
        ]);

        // Complex query
        $berlinCustomers = Customer::where('company_id', $this->company->id)
            ->where(function ($query) {
                $query->where('city', 'Berlin')
                    ->orWhere('postal_code', 'LIKE', '10%');
            })
            ->get();

        $this->assertCount(3, $berlinCustomers);
    }

    /** @test */
    public function it_uses_query_scopes_correctly()
    {
        // Create appointments with different statuses
        Appointment::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'scheduled',
            'appointment_datetime' => now()->addDays(1),
        ]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
            'appointment_datetime' => now()->subDays(1),
        ]);

        // Use scopes
        $upcomingCount = Appointment::where('company_id', $this->company->id)
            ->upcoming()
            ->count();
            
        $this->assertEquals(5, $upcomingCount);
    }

    /** @test */
    public function it_performs_efficient_joins()
    {
        // Create related data
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        
        Appointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        // Join query
        $result = DB::table('appointments')
            ->join('customers', 'appointments.customer_id', '=', 'customers.id')
            ->where('appointments.company_id', $this->company->id)
            ->select('appointments.*', 'customers.name as customer_name')
            ->get();

        $this->assertCount(3, $result);
        $this->assertEquals($customer->name, $result->first()->customer_name);
    }

    /** @test */
    public function it_handles_subqueries()
    {
        // Create calls with different durations
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'duration' => 300, // 5 minutes
        ]);
        
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'duration' => 600, // 10 minutes
        ]);

        // Subquery to find calls longer than average
        $avgDuration = Call::where('company_id', $this->company->id)
            ->avg('duration');

        $longCalls = Call::where('company_id', $this->company->id)
            ->where('duration', '>', function ($query) use ($avgDuration) {
                $query->selectRaw('AVG(duration)')
                    ->from('calls')
                    ->where('company_id', $this->company->id);
            })
            ->get();

        $this->assertCount(3, $longCalls);
    }

    /** @test */
    public function it_uses_raw_expressions_safely()
    {
        // Create appointments
        Appointment::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'appointment_datetime' => now(),
        ]);

        // Raw query with bindings
        $monthlyAppointments = Appointment::where('company_id', $this->company->id)
            ->whereRaw('MONTH(appointment_datetime) = ?', [now()->month])
            ->whereRaw('YEAR(appointment_datetime) = ?', [now()->year])
            ->count();

        $this->assertEquals(10, $monthlyAppointments);
    }

    /** @test */
    public function it_handles_eager_loading_with_constraints()
    {
        // Create customers with appointments
        $customers = Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        foreach ($customers as $customer) {
            Appointment::factory()->count(2)->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'status' => 'scheduled',
            ]);
            
            Appointment::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'status' => 'cancelled',
            ]);
        }

        // Eager load with constraints
        $customersWithScheduled = Customer::where('company_id', $this->company->id)
            ->with(['appointments' => function ($query) {
                $query->where('status', 'scheduled');
            }])
            ->get();

        $this->assertCount(3, $customersWithScheduled);
        $this->assertCount(2, $customersWithScheduled->first()->appointments);
    }

    /** @test */
    public function it_performs_aggregations_correctly()
    {
        // Create calls with costs
        Call::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'cost' => 10.50,
        ]);
        
        Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'cost' => 15.75,
        ]);

        // Aggregations
        $stats = Call::where('company_id', $this->company->id)
            ->selectRaw('
                COUNT(*) as total_calls,
                SUM(cost) as total_cost,
                AVG(cost) as avg_cost,
                MIN(cost) as min_cost,
                MAX(cost) as max_cost
            ')
            ->first();

        $this->assertEquals(8, $stats->total_calls);
        $this->assertEquals(99.75, $stats->total_cost);
        $this->assertEquals(10.50, $stats->min_cost);
        $this->assertEquals(15.75, $stats->max_cost);
    }

    /** @test */
    public function it_handles_union_queries()
    {
        // Create different types of activities
        $calls = Call::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);
        
        $appointments = Appointment::factory()->count(2)->create([
            'company_id' => $this->company->id,
        ]);

        // Union query
        $callActivities = DB::table('calls')
            ->select('id', 'created_at', DB::raw("'call' as type"))
            ->where('company_id', $this->company->id);

        $appointmentActivities = DB::table('appointments')
            ->select('id', 'created_at', DB::raw("'appointment' as type"))
            ->where('company_id', $this->company->id);

        $allActivities = $callActivities->union($appointmentActivities)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->assertCount(5, $allActivities);
    }

    /** @test */
    public function it_uses_database_transactions_in_queries()
    {
        DB::beginTransaction();

        try {
            // Create records within transaction
            $customer = Customer::factory()->create([
                'company_id' => $this->company->id,
            ]);

            // Query within same transaction
            $found = Customer::where('id', $customer->id)->first();
            $this->assertNotNull($found);

            // Rollback
            DB::rollBack();

            // Query after rollback
            $notFound = Customer::where('id', $customer->id)->first();
            $this->assertNull($notFound);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /** @test */
    public function it_handles_chunking_for_large_datasets()
    {
        // Create large dataset
        Customer::factory()->count(100)->create([
            'company_id' => $this->company->id,
        ]);

        $processedCount = 0;

        // Process in chunks
        Customer::where('company_id', $this->company->id)
            ->chunk(20, function ($customers) use (&$processedCount) {
                $processedCount += $customers->count();
                
                // Ensure chunk size is correct
                $this->assertLessThanOrEqual(20, $customers->count());
            });

        $this->assertEquals(100, $processedCount);
    }

    /** @test */
    public function it_uses_cursor_for_memory_efficient_processing()
    {
        // Create dataset
        Customer::factory()->count(50)->create([
            'company_id' => $this->company->id,
        ]);

        $processedCount = 0;

        // Process using cursor
        foreach (Customer::where('company_id', $this->company->id)->cursor() as $customer) {
            $processedCount++;
            $this->assertInstanceOf(Customer::class, $customer);
        }

        $this->assertEquals(50, $processedCount);
    }

    /** @test */
    public function it_handles_dynamic_where_conditions()
    {
        // Create customers
        Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'city' => 'Berlin',
            'is_active' => true,
        ]);
        
        Customer::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'city' => 'Munich',
            'is_active' => false,
        ]);

        // Dynamic conditions
        $filters = [
            'city' => 'Berlin',
            'is_active' => true,
        ];

        $query = Customer::where('company_id', $this->company->id);

        foreach ($filters as $field => $value) {
            $query->where($field, $value);
        }

        $results = $query->get();
        $this->assertCount(3, $results);
    }
}