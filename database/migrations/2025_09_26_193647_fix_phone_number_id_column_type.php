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
        
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            // Change column type to match phone_numbers.id (UUID)
            $table->char('phone_number_id', 36)->nullable()->change();

            // Add foreign key constraint
            $table->foreign('phone_number_id')
                  ->references('id')
                  ->on('phone_numbers')
                  ->onDelete('set null');
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
