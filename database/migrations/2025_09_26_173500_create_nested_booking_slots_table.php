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
        if (Schema::hasTable('nested_booking_slots')) {
            return;
        }

        Schema::create('nested_booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_booking_id')->constrained('appointments')->onDelete('cascade');
            $table->timestamp('available_from');
            $table->timestamp('available_to');
            $table->integer('max_duration_minutes');
            $table->json('allowed_services')->nullable();
            $table->boolean('is_available')->default(true);
            $table->foreignId('child_booking_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->timestamps();

            // Indexes for performance with shorter names
            $table->index(['is_available', 'available_from', 'available_to'], 'idx_slots_availability');
            $table->index('parent_booking_id', 'idx_slots_parent');
            $table->index('child_booking_id', 'idx_slots_child');
        });

        // Add nested booking support to appointments table
        
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('is_nested')->default(false);
            $table->foreignId('parent_booking_id')->nullable()
                  ->constrained('appointments')->onDelete('set null');
            $table->boolean('has_nested_slots')->default(false);
            $table->json('phases')->nullable();
        });

        // Create booking strategies configuration table
        if (Schema::hasTable('booking_strategies')) {
            return;
        }

        Schema::create('booking_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('strategy_type', 50);
            $table->integer('priority')->default(1);
            $table->json('config');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'strategy_type']);
            $table->index(['company_id', 'is_active', 'priority'], 'idx_strategies_active');
        });

        // Create booking alternatives configuration table
        if (Schema::hasTable('booking_alternatives_config')) {
            return;
        }

        Schema::create('booking_alternatives_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->integer('max_alternatives')->default(2);
            $table->integer('time_window_hours')->default(2);
            $table->json('search_strategies')->default('["same_day_different_time","next_workday_same_time","next_week_same_day","next_available_workday"]');
            $table->json('workdays')->default('["monday","tuesday","wednesday","thursday","friday"]');
            $table->time('business_hours_start')->default('09:00');
            $table->time('business_hours_end')->default('18:00');
            $table->boolean('enable_nested_bookings')->default(false);
            $table->boolean('auto_suggest_alternatives')->default(true);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['parent_booking_id']);
            $table->dropColumn(['is_nested', 'parent_booking_id', 'has_nested_slots', 'phases']);
        });

        Schema::dropIfExists('booking_alternatives_config');
        Schema::dropIfExists('booking_strategies');
        Schema::dropIfExists('nested_booking_slots');
    }
};