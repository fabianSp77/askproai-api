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
        Schema::table('appointments', function (Blueprint $table) {
            // Which assignment model was used
            $table->enum('assignment_model_used', [
                'any_staff',
                'service_staff',
                'manual'
            ])->nullable()->after('staff_id');

            // Was fallback model used
            $table->boolean('was_fallback')->default(false)
                ->after('assignment_model_used');

            // Assignment decision context
            $table->json('assignment_metadata')->nullable()
                ->after('was_fallback')
                ->comment('Assignment decision context for audit trail');

            // Note: Indexes removed due to appointments table exceeding MySQL's 64 index limit
            // Query performance will rely on existing staff_id indexes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'assignment_model_used',
                'was_fallback',
                'assignment_metadata',
            ]);
        });
    }
};
