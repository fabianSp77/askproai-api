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
        // Erweitere appointments Tabelle
        Schema::table('appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_appointment_id')->nullable()->after('id');
            $table->json('recurrence_rule')->nullable()->after('metadata');
            $table->uuid('series_id')->nullable()->after('recurrence_rule');
            $table->uuid('group_booking_id')->nullable()->after('series_id');
            $table->enum('booking_type', ['single', 'recurring', 'group', 'package'])->default('single')->after('status');
            $table->integer('recurrence_count')->default(0)->after('booking_type');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_count');
            
            $table->index('parent_appointment_id');
            $table->index('series_id');
            $table->index('group_booking_id');
            $table->index('booking_type');
            
            $table->foreign('parent_appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });
        
        // Erweitere customers Tabelle
        Schema::table('customers', function (Blueprint $table) {
            $table->json('preference_data')->nullable()->after('metadata');
            $table->timestamp('last_seen_at')->nullable()->after('updated_at');
            $table->integer('loyalty_points')->default(0)->after('last_seen_at');
            $table->json('custom_attributes')->nullable()->after('loyalty_points');
            $table->enum('vip_status', ['none', 'bronze', 'silver', 'gold', 'platinum'])->default('none')->after('custom_attributes');
            $table->integer('total_appointments')->default(0)->after('vip_status');
            $table->integer('no_show_count')->default(0)->after('total_appointments');
            
            $table->index('vip_status');
            $table->index('loyalty_points');
            $table->index('last_seen_at');
        });
        
        // Neue Tabelle: appointment_series
        Schema::create('appointment_series', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->uuid('series_id')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('recurrence_pattern'); // { type: 'weekly', interval: 1, days: [1,3,5], count: 10 }
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->integer('total_appointments');
            $table->integer('completed_appointments')->default(0);
            $table->integer('cancelled_appointments')->default(0);
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('branch_id');
            $table->index('series_id');
            $table->index('status');
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('set null');
        });
        
        // Neue Tabelle: customer_preferences
        Schema::create('customer_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('company_id');
            $table->string('preference_type'); // time_preference, staff_preference, service_preference, etc.
            $table->string('preference_key');
            $table->json('preference_value');
            $table->integer('usage_count')->default(0);
            $table->decimal('confidence_score', 3, 2)->default(0.5); // 0.00 - 1.00
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->unique(['customer_id', 'preference_type', 'preference_key']);
            $table->index('customer_id');
            $table->index('company_id');
            $table->index('preference_type');
            $table->index('confidence_score');
            
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
        
        // Neue Tabelle: customer_interactions
        Schema::create('customer_interactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('company_id');
            $table->enum('interaction_type', ['call', 'booking', 'cancellation', 'no_show', 'feedback', 'complaint']);
            $table->json('interaction_data');
            $table->string('channel')->default('phone'); // phone, web, app, sms
            $table->decimal('sentiment_score', 3, 2)->nullable(); // -1.00 to 1.00
            $table->unsignedBigInteger('related_appointment_id')->nullable();
            $table->unsignedBigInteger('related_call_id')->nullable();
            $table->timestamps();
            
            $table->index(['customer_id', 'interaction_type']);
            $table->index('company_id');
            $table->index('created_at');
            $table->index('sentiment_score');
            
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('related_appointment_id')->references('id')->on('appointments')->onDelete('set null');
            $table->foreign('related_call_id')->references('id')->on('calls')->onDelete('set null');
        });
        
        // Neue Tabelle: group_bookings
        Schema::create('group_bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('group_booking_id')->unique();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('primary_customer_id');
            $table->string('group_name')->nullable();
            $table->integer('total_participants');
            $table->json('participant_details'); // Array of customer info
            $table->dateTime('booking_date');
            $table->string('booking_time');
            $table->integer('duration_minutes');
            $table->decimal('total_price', 10, 2)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('group_booking_id');
            $table->index('company_id');
            $table->index('branch_id');
            $table->index('booking_date');
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('primary_customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['parent_appointment_id']);
            $table->dropColumn([
                'parent_appointment_id',
                'recurrence_rule',
                'series_id',
                'group_booking_id',
                'booking_type',
                'recurrence_count',
                'recurrence_end_date'
            ]);
        });
        
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'preference_data',
                'last_seen_at',
                'loyalty_points',
                'custom_attributes',
                'vip_status',
                'total_appointments',
                'no_show_count'
            ]);
        });
        
        Schema::dropIfExists('group_bookings');
        Schema::dropIfExists('customer_interactions');
        Schema::dropIfExists('customer_preferences');
        Schema::dropIfExists('appointment_series');
    }
};