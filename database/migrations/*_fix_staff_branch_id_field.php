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
        Schema::table('staff', function (Blueprint $table) {
            // Mache branch_id nullable, falls es existiert
            if (Schema::hasColumn('staff', 'branch_id')) {
                $table->uuid('branch_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Mache branch_id wieder not nullable
            if (Schema::hasColumn('staff', 'branch_id')) {
                $table->uuid('branch_id')->nullable(false)->change();
            }
        });
    }
};
