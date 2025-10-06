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
        
        if (!Schema::hasTable('branch_service')) {
            return;
        }

        Schema::table('branch_service', function (Blueprint $table) {
            // Change branch_id from bigint to UUID (char(36))
            $table->char('branch_id', 36)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_service', function (Blueprint $table) {
            // Revert branch_id back to bigint
            $table->unsignedBigInteger('branch_id')->change();
        });
    }
};