<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds fields to support COMPOSITE services with multiple segments
     * (e.g., Dauerwelle with 6 segments: wickeln, einwirkzeit1, fixierung, einwirkzeit2, auswaschen, schneiden)
     */
    public function up(): void
    {
        Schema::table('appointment_phases', function (Blueprint $table) {
            // Segment name from service.segments JSON (e.g., "Haare wickeln", "Einwirkzeit")
            $table->string('segment_name')->nullable()->after('phase_type');

            // Segment key from service.segments JSON (e.g., "A", "A_gap", "B", "B_gap", "C", "D")
            $table->string('segment_key')->nullable()->after('segment_name');

            // Sequential order of segment in service (1, 2, 3, 4, 5, 6)
            $table->integer('sequence_order')->default(1)->after('segment_key');

            // Index for faster queries
            $table->index(['appointment_id', 'sequence_order'], 'idx_appointment_sequence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_phases', function (Blueprint $table) {
            $table->dropIndex('idx_appointment_sequence');
            $table->dropColumn(['segment_name', 'segment_key', 'sequence_order']);
        });
    }
};
