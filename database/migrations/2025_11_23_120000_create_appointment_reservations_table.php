<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointment_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->uuid('reservation_token'); // Will be set in model creating event
            $table->string('status', 20)->default('active'); // active, converted, expired, cancelled
            $table->string('call_id', 100);
            $table->string('customer_phone', 50);
            $table->string('customer_name')->nullable();

            // Booking details
            $table->unsignedBigInteger('service_id');
            $table->char('staff_id', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable(); // UUID reference to staff table
            $table->timestamp('start_time');
            $table->timestamp('end_time');

            // Compound service support
            $table->boolean('is_compound')->default(false);
            $table->uuid('compound_parent_token')->nullable();
            $table->integer('segment_number')->nullable();
            $table->integer('total_segments')->nullable();

            // Lifecycle
            $table->timestamp('reserved_at')->useCurrent(); // MySQL-compatible
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('converted_to_appointment_id')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'status'], 'idx_company_active');
            $table->index('expires_at', 'idx_expires_at');
            $table->index(['company_id', 'start_time', 'end_time'], 'idx_time_range');
            $table->index('reservation_token', 'idx_token');
            $table->index('call_id', 'idx_call_id');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            // Note: staff uses UUID as primary key, so no explicit foreign key constraint (handled in application layer)
        });

        // Note: Table comment omitted for MySQL compatibility
        // Purpose: Optimistic reservations to prevent race conditions during booking flow
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_reservations');
    }
};
