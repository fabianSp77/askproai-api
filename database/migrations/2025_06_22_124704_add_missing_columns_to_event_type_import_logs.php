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
        Schema::table('event_type_import_logs', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('event_type_import_logs', 'total_errors')) {
                $table->integer('total_errors')->default(0)->after('total_failed');
            }
            
            if (!Schema::hasColumn('event_type_import_logs', 'error_details')) {
                $table->json('error_details')->nullable()->after('error_message');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_type_import_logs', function (Blueprint $table) {
            $table->dropColumn(['total_errors', 'error_details']);
        });
    }
};