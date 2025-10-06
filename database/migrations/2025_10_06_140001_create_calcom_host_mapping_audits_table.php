<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 2: Staff Assignment - Audit Trail for Host Mappings
     * Tracks all changes to Cal.com host mappings for compliance and debugging
     */
    public function up(): void
    {
        Schema::create('calcom_host_mapping_audits', function (Blueprint $table) {
            $table->id();

            // Mapping relationship
            $table->unsignedBigInteger('mapping_id');
            $table->foreign('mapping_id')
                ->references('id')
                ->on('calcom_host_mappings')
                ->onDelete('cascade');

            // Audit details
            $table->enum('action', [
                'created',
                'updated',
                'deleted',
                'auto_matched',
                'manual_override'
            ])->comment('Type of change');

            $table->json('old_values')->nullable()->comment('Values before change');
            $table->json('new_values')->nullable()->comment('Values after change');

            // Change tracking
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->foreign('changed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->timestamp('changed_at')->useCurrent();
            $table->text('reason')->nullable();

            // Indexes
            $table->index('mapping_id', 'idx_mapping');
            $table->index('changed_at', 'idx_changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calcom_host_mapping_audits');
    }
};
