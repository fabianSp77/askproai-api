<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix: branch_id and staff_id were incorrectly created as bigint
     * but should be char(36) to match branches.id and staff.id (UUIDs)
     */
    public function up(): void
    {
        Schema::table('calcom_event_map', function (Blueprint $table) {
            $table->char('branch_id', 36)->change();
            $table->char('staff_id', 36)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calcom_event_map', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->change();
            $table->unsignedBigInteger('staff_id')->nullable()->change();
        });
    }
};
