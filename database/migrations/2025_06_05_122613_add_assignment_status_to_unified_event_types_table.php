<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unified_event_types', function (Blueprint $table) {
            $table->enum('assignment_status', ['assigned', 'unassigned'])->default('unassigned')->after('is_active');
            $table->index('assignment_status');
        });

        // Alle existierenden Event Types als unassigned markieren
        DB::table('unified_event_types')->update(['assignment_status' => 'unassigned']);
    }

    public function down(): void
    {
        Schema::table('unified_event_types', function (Blueprint $table) {
            $table->dropColumn('assignment_status');
        });
    }
};
