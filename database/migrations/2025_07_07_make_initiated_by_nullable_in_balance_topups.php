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
        Schema::table('balance_topups', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['initiated_by']);
            
            // Modify the column to be nullable
            $table->unsignedBigInteger('initiated_by')->nullable()->change();
            
            // Re-add the foreign key constraint
            $table->foreign('initiated_by')->references('id')->on('portal_users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('balance_topups', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['initiated_by']);
            
            // Make the column NOT NULL again (will fail if there are NULL values)
            $table->unsignedBigInteger('initiated_by')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('initiated_by')->references('id')->on('portal_users');
        });
    }
};