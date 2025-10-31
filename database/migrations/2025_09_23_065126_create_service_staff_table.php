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
        if (Schema::hasTable('service_staff')) {
            return;
        }

        Schema::create('service_staff', function (Blueprint $table) {
            // Primary keys
            $table->id();
            $table->unsignedBigInteger('service_id');
            $table->char('staff_id', 36); // UUID to match staff table

            // Pivot data
            $table->boolean('is_primary')->default(false)->comment('Primary staff member for this service');
            $table->boolean('can_book')->default(true)->comment('Can accept bookings for this service');
            $table->decimal('custom_price', 10, 2)->nullable()->comment('Staff-specific price override');
            $table->integer('custom_duration_minutes')->nullable()->comment('Staff-specific duration override');
            $table->decimal('commission_rate', 5, 2)->nullable()->comment('Commission percentage for this staff-service');
            $table->json('specialization_notes')->nullable()->comment('Additional notes about staff specialization');
            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->useCurrent();

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->unique(['service_id', 'staff_id']);
            $table->index('service_id');
            $table->index('staff_id');
            $table->index(['service_id', 'is_primary']);
            $table->index(['service_id', 'can_book', 'is_active']);

            // Foreign keys
            $table->foreign('service_id')
                ->references('id')
                ->on('services')
                ->onDelete('cascade');

            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_staff');
    }
};