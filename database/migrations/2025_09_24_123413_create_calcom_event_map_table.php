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
        if (!Schema::hasTable('calcom_event_map')) {
            Schema::create('calcom_event_map', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();

                // Foreign key for branches (UUID char(36) primary key)
                $table->char('branch_id', 36); // UUID to match branches table
                $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();

                $table->foreignId('service_id')->constrained()->cascadeOnDelete();
                $table->string('segment_key', 20)->nullable()
                    ->comment('A, B, C for composite segments');

                // Staff ID as UUID char(36) (matching staff table)
                $table->char('staff_id', 36)->nullable(); // UUID to match staff table
                $table->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();

                // Cal.com mapping
                $table->integer('event_type_id')->comment('Cal.com event type ID');
                $table->string('event_type_slug')->nullable();
                $table->boolean('hidden')->default(true)
                    ->comment('Hidden from public booking');
                $table->string('event_name_pattern')
                    ->comment('e.g. ACME-BER-FARBE-A-S123');

                // Drift tracking
                $table->string('external_changes', 20)->default('warn')
                    ->comment('warn|accept|reject - How to handle external changes');
                $table->json('drift_data')->nullable()
                    ->comment('Detected differences from Cal.com');
                $table->timestamp('drift_detected_at')->nullable();

                // Sync status
                $table->string('sync_status', 20)->default('pending')
                    ->comment('synced|pending|error');
                $table->timestamp('last_sync_at')->nullable();
                $table->text('sync_error')->nullable();

                $table->timestamps();

                // Indexes
                $table->unique(
                    ['company_id', 'branch_id', 'service_id', 'segment_key', 'staff_id'],
                    'unique_calcom_mapping'
                );
                $table->index('event_type_id');
                $table->index('sync_status');
                $table->index('drift_detected_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calcom_event_map');
    }
};