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
        
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            // Verify foreign key doesn't already exist before adding
            // Change column type to match phone_numbers.id (bigint unsigned)
            $table->unsignedBigInteger('phone_number_id')->nullable()->change();

            // Check if foreign key already exists before adding
            $foreignKeys = collect(DB::select("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'calls'
                AND COLUMN_NAME = 'phone_number_id'
                AND REFERENCED_TABLE_NAME = 'phone_numbers'
            "));

            if ($foreignKeys->isEmpty()) {
                $table->foreign('phone_number_id')
                      ->references('id')
                      ->on('phone_numbers')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['phone_number_id']);

            // Change back to bigint
            $table->unsignedBigInteger('phone_number_id')->nullable()->change();
        });
    }
};
