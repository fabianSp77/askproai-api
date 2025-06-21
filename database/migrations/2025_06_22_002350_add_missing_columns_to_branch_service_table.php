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
        Schema::table('branch_service', function (Blueprint $table) {
            if (!Schema::hasColumn('branch_service', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('service_id');
            }
            if (!Schema::hasColumn('branch_service', 'duration')) {
                $table->integer('duration')->nullable()->after('price')->comment('Duration in minutes');
            }
            if (!Schema::hasColumn('branch_service', 'active')) {
                $table->boolean('active')->default(true)->after('duration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_service', function (Blueprint $table) {
            if (Schema::hasColumn('branch_service', 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn('branch_service', 'duration')) {
                $table->dropColumn('duration');
            }
            if (Schema::hasColumn('branch_service', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};