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
        // First, update any NULL uuid values
        DB::table('branches')
            ->whereNull('uuid')
            ->orWhere('uuid', '')
            ->update(['uuid' => DB::raw('UUID()')]);
            
        // Then modify the column to have a default
        Schema::table('branches', function (Blueprint $table) {
            $table->string('uuid')->default(DB::raw('(UUID())'))->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('uuid')->default(null)->change();
        });
    }
};