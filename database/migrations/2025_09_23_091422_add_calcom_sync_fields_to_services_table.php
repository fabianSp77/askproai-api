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
        
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // Cal.com specific fields
            if (!Schema::hasColumn('services', 'slug')) {
                $table->string('slug')->nullable();
            }
            if (!Schema::hasColumn('services', 'schedule_id')) {
                $table->integer('schedule_id')->nullable();
            }
            if (!Schema::hasColumn('services', 'minimum_booking_notice')) {
                $table->integer('minimum_booking_notice')->default(120);
            }
            if (!Schema::hasColumn('services', 'before_event_buffer')) {
                $table->integer('before_event_buffer')->default(0);
            }
            if (!Schema::hasColumn('services', 'requires_confirmation')) {
                $table->boolean('requires_confirmation')->default(false);
            }
            if (!Schema::hasColumn('services', 'disable_guests')) {
                $table->boolean('disable_guests')->default(false);
            }
            if (!Schema::hasColumn('services', 'booking_link')) {
                $table->text('booking_link')->nullable();
            }

            // JSON fields for complex Cal.com data
            if (!Schema::hasColumn('services', 'locations_json')) {
                $table->json('locations_json')->nullable();
            }
            if (!Schema::hasColumn('services', 'metadata_json')) {
                $table->json('metadata_json')->nullable();
            }
            if (!Schema::hasColumn('services', 'booking_fields_json')) {
                $table->json('booking_fields_json')->nullable();
            }

            // Sync tracking fields
            if (!Schema::hasColumn('services', 'last_calcom_sync')) {
                $table->timestamp('last_calcom_sync')->nullable();
            }
            if (!Schema::hasColumn('services', 'sync_status')) {
                $table->enum('sync_status', ['synced', 'pending', 'error', 'never'])->default('never');
            }
            if (!Schema::hasColumn('services', 'sync_error')) {
                $table->text('sync_error')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_services_sync_status');
            $table->dropIndex('idx_services_calcom_event_type_id');

            // Drop columns
            $table->dropColumn([
                'slug',
                'schedule_id',
                'minimum_booking_notice',
                'before_event_buffer',
                'requires_confirmation',
                'disable_guests',
                'booking_link',
                'locations_json',
                'metadata_json',
                'booking_fields_json',
                'last_calcom_sync',
                'sync_status',
                'sync_error'
            ]);
        });
    }
};