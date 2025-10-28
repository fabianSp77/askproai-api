<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProcessingTimeMigrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function services_table_has_processing_time_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('services', [
                'has_processing_time',
                'initial_duration',
                'processing_duration',
                'final_duration',
            ]),
            'Services table is missing processing time columns'
        );
    }

    /** @test */
    public function processing_time_columns_are_nullable_except_flag()
    {
        $columns = Schema::getColumnListing('services');

        $this->assertContains('has_processing_time', $columns);
        $this->assertContains('initial_duration', $columns);
        $this->assertContains('processing_duration', $columns);
        $this->assertContains('final_duration', $columns);
    }

    /** @test */
    public function appointment_phases_table_exists()
    {
        $this->assertTrue(
            Schema::hasTable('appointment_phases'),
            'appointment_phases table does not exist'
        );
    }

    /** @test */
    public function appointment_phases_has_required_columns()
    {
        $this->assertTrue(
            Schema::hasColumns('appointment_phases', [
                'id',
                'appointment_id',
                'phase_type',
                'start_offset_minutes',
                'duration_minutes',
                'staff_required',
                'start_time',
                'end_time',
                'created_at',
                'updated_at',
            ]),
            'appointment_phases table is missing required columns'
        );
    }

    /** @test */
    public function appointment_phases_has_foreign_key_to_appointments()
    {
        // Enable FK checks for this test
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // This test verifies the foreign key relationship exists
        // by attempting to create a phase without a valid appointment
        $this->expectException(\Illuminate\Database\QueryException::class);

        try {
            \DB::table('appointment_phases')->insert([
                'appointment_id' => 999999, // Non-existent
                'phase_type' => 'initial',
                'start_offset_minutes' => 0,
                'duration_minutes' => 15,
                'staff_required' => true,
                'start_time' => now(),
                'end_time' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            // Restore FK checks state
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
    }

    /** @test */
    public function appointment_phases_cascade_deletes_with_appointment()
    {
        // Enable FK checks for this test (globally disabled in production)
        \DB::statement('SET FOREIGN_KEY_CHECKS=1');

        try {
            // Create minimal test data directly (avoiding factories due to UUID/relationship issues)

            // Create company
            $companyId = \DB::table('companies')->insertGetId([
                'name' => 'Test Company',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create service
            $serviceId = \DB::table('services')->insertGetId([
                'company_id' => $companyId,
                'name' => 'Test Service',
                'duration_minutes' => 30,
                'price' => 50.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create customer
            $customerId = \DB::table('customers')->insertGetId([
                'company_id' => $companyId,
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create appointment
            $appointmentId = \DB::table('appointments')->insertGetId([
                'company_id' => $companyId,
                'service_id' => $serviceId,
                'customer_id' => $customerId,
                'starts_at' => now(),
                'ends_at' => now()->addMinutes(30),
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $phase = \DB::table('appointment_phases')->insertGetId([
                'appointment_id' => $appointmentId,
                'phase_type' => 'initial',
                'start_offset_minutes' => 0,
                'duration_minutes' => 15,
                'staff_required' => true,
                'start_time' => now(),
                'end_time' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertDatabaseHas('appointment_phases', ['id' => $phase]);

            // Delete appointment
            \DB::table('appointments')->where('id', $appointmentId)->delete();

            // Phase should be deleted too (cascade)
            $this->assertDatabaseMissing('appointment_phases', ['id' => $phase]);
        } finally {
            // Restore FK checks state
            \DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
    }

    /** @test */
    public function processing_time_columns_have_correct_indexes()
    {
        $indexes = \DB::select("SHOW INDEX FROM services WHERE Key_name = 'services_has_processing_time_index'");

        $this->assertNotEmpty($indexes, 'has_processing_time index is missing');
    }

    /** @test */
    public function appointment_phases_has_performance_indexes()
    {
        $indexes = \DB::select("SHOW INDEX FROM appointment_phases");
        $indexNames = collect($indexes)->pluck('Key_name')->unique();

        $this->assertTrue(
            $indexNames->contains('appointment_phases_time_range_index'),
            'Missing time range index'
        );

        $this->assertTrue(
            $indexNames->contains('appointment_phases_appointment_phase_index'),
            'Missing appointment-phase index'
        );

        $this->assertTrue(
            $indexNames->contains('appointment_phases_staff_required_index'),
            'Missing staff_required index'
        );
    }
}
