<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * C19: Add booking_details column to calls table
     * Used in RetellFunctionCallHandler.php:6135 to store json_encoded booking data
     */
    public function up(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (!Schema::hasColumn('calls', 'booking_details')) {
                $table->json('booking_details')->nullable()->after('metadata');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calls', function (Blueprint $table) {
            if (Schema::hasColumn('calls', 'booking_details')) {
                $table->dropColumn('booking_details');
            }
        });
    }
};
