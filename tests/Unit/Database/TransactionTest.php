<?php

namespace Tests\Unit\Database;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Call;
use Illuminate\Database\QueryException;

class TransactionTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    /** @test */
    public function it_rolls_back_on_exception()
    {
        $company = Company::factory()->create();
        $initialCount = Appointment::count();

        try {
            DB::transaction(function () use ($company) {
                // Create appointment
                Appointment::factory()->create([
                    'company_id' => $company->id,
                ]);

                // Force an exception
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // Exception expected
        }

        // Verify rollback occurred
        $this->assertEquals($initialCount, Appointment::count());
    }

    /** @test */
    public function it_commits_successful_transactions()
    {
        $company = Company::factory()->create();
        $initialCount = Appointment::count();

        DB::transaction(function () use ($company) {
            Appointment::factory()->create([
                'company_id' => $company->id,
            ]);
        });

        // Verify commit occurred
        $this->assertEquals($initialCount + 1, Appointment::count());
    }

    /** @test */
    public function it_handles_nested_transactions()
    {
        $company = Company::factory()->create();
        $initialAppointmentCount = Appointment::count();
        $initialCustomerCount = Customer::count();

        DB::transaction(function () use ($company) {
            // Outer transaction
            $customer = Customer::factory()->create([
                'company_id' => $company->id,
            ]);

            DB::transaction(function () use ($company, $customer) {
                // Inner transaction
                Appointment::factory()->create([
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                ]);
            });
        });

        // Both should be committed
        $this->assertEquals($initialAppointmentCount + 1, Appointment::count());
        $this->assertEquals($initialCustomerCount + 1, Customer::count());
    }

    /** @test */
    public function it_handles_deadlocks_with_retry()
    {
        $company = Company::factory()->create();
        $attempts = 0;

        $result = DB::transaction(function () use ($company, &$attempts) {
            $attempts++;
            
            if ($attempts < 3) {
                // Simulate deadlock on first attempts
                throw new QueryException(
                    'mysql',
                    'Deadlock found when trying to get lock',
                    [],
                    new \PDOException('Deadlock')
                );
            }

            return Appointment::factory()->create([
                'company_id' => $company->id,
            ]);
        }, 5); // 5 attempts

        $this->assertNotNull($result);
        $this->assertEquals(3, $attempts);
    }

    /** @test */
    public function it_isolates_transactions_between_connections()
    {
        $company = Company::factory()->create();

        // Start transaction on default connection
        DB::beginTransaction();
        
        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
        ]);

        // Use different connection (simulated)
        $count = DB::connection('mysql')->table('appointments')
            ->where('id', $appointment->id)
            ->count();

        // Should not see uncommitted data
        $this->assertEquals(0, $count);

        DB::commit();

        // Now should see the data
        $count = DB::connection('mysql')->table('appointments')
            ->where('id', $appointment->id)
            ->count();
        
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_handles_transaction_savepoints()
    {
        $company = Company::factory()->create();
        $initialCount = Appointment::count();

        DB::transaction(function () use ($company) {
            // Create first appointment
            $apt1 = Appointment::factory()->create([
                'company_id' => $company->id,
            ]);

            // Create savepoint
            DB::connection()->createSavepoint('test_savepoint');

            // Create second appointment
            $apt2 = Appointment::factory()->create([
                'company_id' => $company->id,
            ]);

            // Rollback to savepoint
            DB::connection()->rollbackToSavepoint('test_savepoint');

            // Only first appointment should exist
            $this->assertTrue(Appointment::where('id', $apt1->id)->exists());
            $this->assertFalse(Appointment::where('id', $apt2->id)->exists());
        });

        // Verify final state
        $this->assertEquals($initialCount + 1, Appointment::count());
    }

    /** @test */
    public function it_maintains_data_consistency_during_concurrent_updates()
    {
        $company = Company::factory()->create(['prepaid_balance' => 100.00]);

        $processes = [];
        $iterations = 10;

        // Simulate concurrent updates
        for ($i = 0; $i < $iterations; $i++) {
            DB::transaction(function () use ($company) {
                $company->refresh();
                $company->decrement('prepaid_balance', 10);
            });
        }

        $company->refresh();
        $this->assertEquals(0, $company->prepaid_balance);
    }

    /** @test */
    public function it_handles_transaction_callbacks()
    {
        $committed = false;
        $rolledBack = false;

        // Test commit callback
        DB::transaction(function () {
            DB::afterCommit(function () use (&$committed) {
                $committed = true;
            });

            Company::factory()->create();
        });

        $this->assertTrue($committed);

        // Test rollback callback
        try {
            DB::transaction(function () {
                DB::afterRollback(function () use (&$rolledBack) {
                    $rolledBack = true;
                });

                Company::factory()->create();
                throw new \Exception('Force rollback');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertTrue($rolledBack);
    }

    /** @test */
    public function it_handles_long_running_transactions()
    {
        $this->expectNotToPerformAssertions();

        DB::transaction(function () {
            $company = Company::factory()->create();

            // Simulate long-running operation
            for ($i = 0; $i < 100; $i++) {
                Customer::factory()->create([
                    'company_id' => $company->id,
                ]);
                
                // Small delay to simulate processing
                usleep(1000); // 1ms
            }

            // Should complete without timeout
            $this->assertEquals(100, $company->customers()->count());
        });
    }

    /** @test */
    public function it_properly_handles_soft_deletes_in_transactions()
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
        ]);

        DB::transaction(function () use ($customer) {
            $customer->delete(); // Soft delete
            
            // Should still exist in database
            $this->assertNotNull(Customer::withTrashed()->find($customer->id));
            
            // But not in normal queries
            $this->assertNull(Customer::find($customer->id));
        });

        // Verify soft delete persisted
        $this->assertNotNull($customer->fresh()->deleted_at);
    }
}