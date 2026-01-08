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
        // Skip in testing environment (SQLite doesn't have INFORMATION_SCHEMA)
        if (app()->environment('testing')) {
            return;
        }

        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            // Note: phone_numbers.id is char(36) UUID, not bigint
            // This migration now skips the column change since create_calls_table
            // already defines it correctly as char(36)
            // $table->char('phone_number_id', 36)->nullable()->change();

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
