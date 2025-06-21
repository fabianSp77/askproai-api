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
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'price')) {
                $table->integer('price')->nullable();
            }
            if (!Schema::hasColumn('appointments', 'calcom_booking_id')) {
                $table->unsignedBigInteger('calcom_booking_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['notes', 'price', 'calcom_booking_id']);
        });
    }
};