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
        Schema::table('branches', function (Blueprint $table) {
            // Check if columns exist before modifying
            if (Schema::hasColumn('branches', 'customer_id')) {
                // customer_id is already bigint and nullable in production, skip this
                // $table->uuid('customer_id')->nullable()->change();
            }
            
            // Add company_id if it doesn't exist
            if (!Schema::hasColumn('branches', 'company_id')) {
                $table->uuid('company_id')->after('id')->index();
            }
            
            // Add uuid column if it doesn't exist
            if (!Schema::hasColumn('branches', 'uuid')) {
                $table->string('uuid')->nullable()->after('id');
            }
        });
        
        // Migrate data from customer_id to company_id if needed
        if (Schema::hasColumn('branches', 'customer_id') && Schema::hasColumn('branches', 'company_id')) {
            // Only for records where company_id is null but customer_id is not
            DB::statement('UPDATE branches SET company_id = customer_id WHERE company_id IS NULL AND customer_id IS NOT NULL');
        }
        
        // Update existing records to have a uuid if they don't
        if (Schema::hasColumn('branches', 'uuid')) {
            $branches = DB::table('branches')->whereNull('uuid')->get();
            foreach ($branches as $branch) {
                DB::table('branches')
                    ->where('id', $branch->id)
                    ->update(['uuid' => \Illuminate\Support\Str::uuid()]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};