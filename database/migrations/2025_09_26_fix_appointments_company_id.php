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
        // First, set a default company_id for any appointments without one
        $defaultCompany = DB::table('companies')->first();

        if ($defaultCompany) {
            DB::table('appointments')
                ->whereNull('company_id')
                ->orWhere('company_id', 0)
                ->update(['company_id' => $defaultCompany->id]);
        }

        // Now modify the column to have a default value
        
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            // Get the first company ID as default
            $defaultCompany = DB::table('companies')->first();
            $defaultId = $defaultCompany ? $defaultCompany->id : 1;

            // Modify column to have default value
            $table->unsignedBigInteger('company_id')->default($defaultId)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->default(null)->change();
        });
    }
};