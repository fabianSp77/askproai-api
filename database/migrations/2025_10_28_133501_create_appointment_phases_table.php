<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointment_phases', function (Blueprint $table) {
            $table->id();

            // Appointment relationship
            $table->foreignId('appointment_id')
                ->constrained('appointments')
                ->onDelete('cascade')
                ->comment('Parent appointment');

            // Phase configuration
            $table->enum('phase_type', ['initial', 'processing', 'final'])
                ->comment('Phase type: initial (active), processing (gap), final (active)');

            $table->integer('start_offset_minutes')
                ->comment('Minutes from appointment start');

            $table->integer('duration_minutes')
                ->comment('Phase duration in minutes');

            $table->boolean('staff_required')->default(true)
                ->comment('FALSE = processing phase (staff available for other clients)');

            // Denormalized timestamps for performance (faster availability queries)
            $table->timestamp('start_time')
                ->comment('Phase start time (denormalized from appointment.scheduled_at + offset)');

            $table->timestamp('end_time')
                ->comment('Phase end time (start_time + duration)');

            $table->timestamps();

            // Performance indexes for availability queries
            $table->index(['start_time', 'end_time'], 'appointment_phases_time_range_index');
            $table->index(['appointment_id', 'phase_type'], 'appointment_phases_appointment_phase_index');
            $table->index('staff_required', 'appointment_phases_staff_required_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_phases');
    }
};
