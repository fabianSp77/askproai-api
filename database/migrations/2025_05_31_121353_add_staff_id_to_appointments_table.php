<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'staff_id')) {
                $table->uuid('staff_id')
                    ->nullable()
                    ->after('customer_id');
                
                $table->index('staff_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasIndex('appointments', 'idx_staff_id')) {
                $table->dropIndex('idx_staff_id');
            }
            if (Schema::hasColumn('appointments', 'staff_id')) {
                $table->dropColumn('staff_id');
            }
        });
    }
};
