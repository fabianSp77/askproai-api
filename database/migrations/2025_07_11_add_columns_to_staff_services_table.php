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
        Schema::table('staff_services', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_services', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('service_id');
            }
            if (!Schema::hasColumn('staff_services', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('duration_minutes');
            }
            if (!Schema::hasColumn('staff_services', 'active')) {
                $table->boolean('active')->default(true)->after('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_services', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'price', 'active']);
        });
    }
};