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
        Schema::table('system_test_runs', function (Blueprint $table) {
            if (!Schema::hasColumn('system_test_runs', 'metadata')) {
                $table->json('metadata')
                    ->nullable()
                    ->after('error_message')
                    ->comment('Test context metadata (company name, team_id, event_ids)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_test_runs', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
