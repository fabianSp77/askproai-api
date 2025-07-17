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
        // Skip for SQLite in tests
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        
        // Check if customer_id is already nullable
        $columns = DB::select("SHOW COLUMNS FROM branches WHERE Field = 'customer_id'");
        if (!empty($columns) && $columns[0]->Null === 'NO') {
            // Drop foreign key constraint first
            Schema::table('branches', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
            
            // Make customer_id nullable
            Schema::table('branches', function (Blueprint $table) {
                $table->bigInteger('customer_id')->unsigned()->nullable()->change();
            });
            
            // Re-add foreign key constraint
            Schema::table('branches', function (Blueprint $table) {
                $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->uuid('customer_id')->nullable(false)->change();
        });
    }
};