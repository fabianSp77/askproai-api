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
        Schema::table('customers', function (Blueprint $table) {
            // Customer preferences and insights
            $table->json('preference_data')->nullable()->after('location_data');
            $table->timestamp('last_seen_at')->nullable()->after('last_portal_login_at');
            $table->integer('loyalty_points')->default(0)->after('appointment_count');
            $table->json('custom_attributes')->nullable()->after('preference_data');
            
            // Enhanced tracking fields
            $table->decimal('total_spent', 10, 2)->default(0)->after('loyalty_points');
            $table->decimal('average_booking_value', 10, 2)->default(0)->after('total_spent');
            $table->integer('cancelled_count')->default(0)->after('no_show_count');
            $table->date('first_appointment_date')->nullable()->after('cancelled_count');
            $table->date('last_appointment_date')->nullable()->after('first_appointment_date');
            
            // Communication preferences
            $table->json('communication_preferences')->nullable()->after('custom_attributes');
            $table->string('preferred_contact_method')->default('phone')->after('preferred_language');
            $table->string('preferred_appointment_time')->nullable()->after('preferred_contact_method');
            
            // VIP and loyalty tracking
            $table->string('loyalty_tier')->default('standard')->after('is_vip');
            $table->timestamp('vip_since')->nullable()->after('loyalty_tier');
            $table->json('special_requirements')->nullable()->after('vip_since');
            
            // Indexes for performance
            $table->index(['loyalty_points', 'loyalty_tier']);
            $table->index(['last_seen_at']);
            $table->index(['is_vip', 'loyalty_tier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['loyalty_points', 'loyalty_tier']);
            $table->dropIndex(['last_seen_at']);
            $table->dropIndex(['is_vip', 'loyalty_tier']);
            
            // Drop columns
            $table->dropColumn([
                'preference_data',
                'last_seen_at',
                'loyalty_points',
                'custom_attributes',
                'total_spent',
                'average_booking_value',
                'cancelled_count',
                'first_appointment_date',
                'last_appointment_date',
                'communication_preferences',
                'preferred_contact_method',
                'preferred_appointment_time',
                'loyalty_tier',
                'vip_since',
                'special_requirements'
            ]);
        });
    }
};