<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 2: Staff Assignment - Cal.com Host Mapping Table
     * Maps Cal.com host IDs to internal staff records for automated staff assignment
     */
    public function up(): void
    {
        Schema::create('calcom_host_mappings', function (Blueprint $table) {
            $table->id();

            // Staff relationship (UUID foreign key)
            $table->char('staff_id', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci');
            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->onDelete('cascade');

            // Cal.com host identification
            $table->integer('calcom_host_id')->comment('Cal.com host ID from API');
            $table->string('calcom_name');
            $table->string('calcom_email');
            $table->string('calcom_username')->nullable();
            $table->string('calcom_timezone', 50)->nullable();

            // Mapping metadata
            $table->enum('mapping_source', ['auto_email', 'auto_name', 'manual', 'admin'])
                ->comment('How this mapping was created');
            $table->unsignedTinyInteger('confidence_score')
                ->default(100)
                ->comment('Match confidence: 0-100');
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true)->index();

            // Additional metadata
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['calcom_host_id', 'staff_id'], 'unique_host_staff');
            $table->index('staff_id', 'idx_staff');
            $table->index('calcom_email', 'idx_email');
            $table->index(['is_active', 'calcom_host_id'], 'idx_active_host');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calcom_host_mappings');
    }
};
