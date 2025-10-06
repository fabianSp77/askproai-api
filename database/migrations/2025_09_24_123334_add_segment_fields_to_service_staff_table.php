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
        
        if (!Schema::hasTable('service_staff')) {
            return;
        }

        Schema::table('service_staff', function (Blueprint $table) {
            // Add segment capabilities
            if (!Schema::hasColumn('service_staff', 'allowed_segments')) {
                $table->json('allowed_segments')->nullable()
                    ->comment('["A", "B", "C"] - which segments staff can handle');
            }

            // Use string instead of enum for portability
            if (!Schema::hasColumn('service_staff', 'skill_level')) {
                $table->string('skill_level', 20)->default('regular')
                    ->comment('junior|regular|senior|expert');
            }

            if (!Schema::hasColumn('service_staff', 'weight')) {
                $table->decimal('weight', 3, 2)->default(1.0)
                    ->comment('Preference weight for staff selection (0.00-9.99)');
            }

            // Add index for performance
            if (!Schema::hasIndex('service_staff', 'service_staff_service_id_skill_level_index')) {
                $table->index(['service_id', 'skill_level']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_staff', function (Blueprint $table) {
            // Drop index first
            if (Schema::hasIndex('service_staff', 'service_staff_service_id_skill_level_index')) {
                $table->dropIndex(['service_id', 'skill_level']);
            }

            // Drop columns if they exist
            $columnsToRemove = ['allowed_segments', 'skill_level', 'weight'];

            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('service_staff', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};