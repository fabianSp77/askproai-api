<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'branch_id')) {
                $table->uuid('branch_id')->nullable()->after('kunde_id');
            }
            if (! Schema::hasColumn('appointments', 'staff_id')) {
                $table->uuid('staff_id')->nullable()->after('branch_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['branch_id', 'staff_id']);
        });
    }
};
