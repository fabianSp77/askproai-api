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
            // Multi-appointment booking support
            $table->unsignedBigInteger('parent_appointment_id')->nullable()->after('id');
            $table->json('recurrence_rule')->nullable()->after('metadata');
            $table->string('series_id')->nullable()->index()->after('external_id');
            $table->string('group_booking_id')->nullable()->index()->after('series_id');
            $table->enum('booking_type', ['single', 'recurring', 'group', 'package'])->default('single')->after('status');
            
            // Package booking support
            $table->integer('package_sessions_total')->nullable();
            $table->integer('package_sessions_used')->default(0);
            $table->date('package_expires_at')->nullable();
            
            // Add foreign key for parent appointment
            $table->foreign('parent_appointment_id')
                ->references('id')
                ->on('appointments')
                ->onDelete('cascade');
                
            // Add indexes for performance
            $table->index(['booking_type', 'starts_at']);
            $table->index(['customer_id', 'series_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['parent_appointment_id']);
            
            // Drop indexes
            $table->dropIndex(['booking_type', 'starts_at']);
            $table->dropIndex(['customer_id', 'series_id']);
            
            // Drop columns
            $table->dropColumn([
                'parent_appointment_id',
                'recurrence_rule',
                'series_id',
                'group_booking_id',
                'booking_type',
                'package_sessions_total',
                'package_sessions_used',
                'package_expires_at'
            ]);
        });
    }
};