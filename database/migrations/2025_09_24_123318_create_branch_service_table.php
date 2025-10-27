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
        if (!Schema::hasTable('branch_service')) {
            Schema::create('branch_service', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->cascadeOnDelete();

                // Override-Felder pro Filiale
                $table->integer('duration_override_minutes')->nullable()
                    ->comment('Branch-specific duration override');
                $table->integer('gap_after_override_minutes')->nullable()
                    ->comment('Branch-specific gap after service');
                $table->decimal('price_override', 10, 2)->nullable()
                    ->comment('Branch-specific price override');
                $table->json('custom_segments')->nullable()
                    ->comment('Branch-specific segment overrides');
                $table->json('branch_policies')->nullable()
                    ->comment('{booking_notice_hours:int,max_advance_days:int}');
                $table->boolean('is_active')->default(true);

                $table->timestamps();

                // Indizes
                $table->unique(['branch_id', 'service_id']);
                $table->index(['service_id', 'is_active']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_service');
    }
};