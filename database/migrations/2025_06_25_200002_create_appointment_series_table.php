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
        Schema::create('appointment_series', function (Blueprint $table) {
            $table->id();
            $table->string('series_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            
            // Recurrence pattern
            $table->enum('recurrence_type', ['daily', 'weekly', 'biweekly', 'monthly', 'custom']);
            $table->json('recurrence_pattern'); // Stores detailed pattern (e.g., which days of week)
            $table->integer('recurrence_interval')->default(1); // Every N days/weeks/months
            $table->date('series_start_date');
            $table->date('series_end_date')->nullable();
            $table->integer('occurrences_count')->nullable(); // Alternative to end date
            
            // Time slot information
            $table->time('appointment_time');
            $table->integer('duration_minutes');
            
            // Series management
            $table->integer('total_appointments')->default(0);
            $table->integer('completed_appointments')->default(0);
            $table->integer('cancelled_appointments')->default(0);
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            
            // Exceptions and modifications
            $table->json('exceptions')->nullable(); // Dates to skip
            $table->json('modifications')->nullable(); // Individual appointment changes
            
            // Booking details
            $table->decimal('price_per_session', 10, 2)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->boolean('auto_confirm')->default(false);
            $table->boolean('send_reminders')->default(true);
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->string('created_by')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['series_start_date', 'series_end_date']);
            $table->index(['staff_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_series');
    }
};