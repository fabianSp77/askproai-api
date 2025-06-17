<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing null or invalid JSON values
        DB::table('companies')
            ->whereNull('settings')
            ->orWhere('settings', '')
            ->orWhere('settings', '{}')
            ->update(['settings' => json_encode([])]);
            
        DB::table('companies')
            ->whereNull('metadata')
            ->orWhere('metadata', '')
            ->orWhere('metadata', '{}')
            ->update(['metadata' => json_encode([])]);
            
        // Modify columns to have proper defaults
        Schema::table('companies', function (Blueprint $table) {
            $table->json('settings')->nullable()->default(json_encode([]))->change();
            $table->json('metadata')->nullable()->default(json_encode([]))->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('settings')->nullable()->change();
            $table->json('metadata')->nullable()->change();
        });
    }
};
